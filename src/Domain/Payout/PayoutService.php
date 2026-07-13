<?php

namespace Src\Domain\Payout;

use App\Enums\PayoutChannel;
use App\Enums\PayoutStatus;
use App\Models\Payout;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Src\Domain\Payout\Contracts\PayoutProviderInterface;
use Src\Domain\Payout\DTOs\PayoutRequestDto;
use Src\Domain\Payout\DTOs\PayoutResult;

/**
 * PayoutService
 * -------------
 * The single orchestrator the application uses to disburse money (e.g. paying an
 * operator once escrow funds are released). It is provider-agnostic: the gateway
 * is injected as a PayoutProviderInterface, so call sites never change when the
 * gateway does.
 *
 * Responsibilities:
 *   • validate a recipient name before a transfer,
 *   • persist a durable Payout row and drive the transfer,
 *   • reconcile settlement from the transfer webhook and status polls,
 *   • notify the operator when funds land, without coupling to other domains.
 */
class PayoutService
{
    public function __construct(
        private readonly PayoutProviderInterface $provider,
    ) {}

    /** Confirm the registered name on a momo wallet / bank account. */
    public function validateName(string $receiver, PayoutChannel $channel, ?string $sublistId = null): PayoutResult
    {
        return $this->provider->validateName($receiver, $channel, $sublistId);
    }

    /**
     * Initiate a disbursement. Persists the Payout, calls the gateway, and folds
     * the result onto the row. Never throws on a gateway failure — the failure is
     * captured on the returned Payout.
     *
     * @param  Model|null  $payable  the record this payout settles (e.g. an Order)
     */
    public function pay(
        float $amount,
        string $receiver,
        PayoutChannel $channel,
        string $context = 'escrow-release',
        ?int $recipientUserId = null,
        ?int $initiatedBy = null,
        ?string $reference = null,
        ?Model $payable = null,
        ?string $sublistId = null,
        ?string $recipientName = null,
    ): Payout {
        $currency = (string) config('payment.currency', 'GHS');
        $accountNumber = (string) config('payment.providers.moolre.account_number', '');

        $payout = new Payout([
            'ref' => (string) Str::uuid(),
            'provider' => $this->provider->name(),
            'channel' => $channel,
            'currency' => $currency,
            'amount' => $amount,
            'recipient' => $receiver,
            'recipient_name' => $recipientName,
            'account_number' => $accountNumber ?: null,
            'sublist_id' => $sublistId,
            'context' => $context,
            'user_id' => $recipientUserId,
            'initiated_by' => $initiatedBy,
            'reference' => $reference,
            'status' => PayoutStatus::Pending,
        ]);

        if ($payable !== null) {
            $payout->payable()->associate($payable);
        }

        $payout->save();

        $dto = PayoutRequestDto::make(
            amount: $amount,
            receiver: $receiver,
            channel: $channel,
            currency: $currency,
            reference: $reference,
            externalRef: $payout->ref,
            sublistId: $sublistId,
            accountNumber: $accountNumber ?: null,
        );

        try {
            $result = $this->provider->transfer($dto);
        } catch (\Throwable $e) {
            $payout->forceFill([
                'status' => PayoutStatus::Failed->value,
                'failed_at' => now(),
                'failure_reason' => $e->getMessage(),
                'meta' => ['exception' => class_basename($e)],
            ])->save();

            return $payout->refresh();
        }

        $payout->forceFill([
            'status' => $result->status->value,
            'provider_transaction_id' => $result->providerTransactionId ?? $payout->provider_transaction_id,
            'recipient_name' => $result->recipientName ?: $payout->recipient_name,
            'fee' => $result->fee ?? $payout->fee,
            'failure_reason' => $result->success ? null : $result->message,
            'completed_at' => $result->status === PayoutStatus::Successful ? now() : $payout->completed_at,
            'failed_at' => $result->status === PayoutStatus::Failed ? now() : $payout->failed_at,
            'meta' => ['last_response' => $result->raw],
        ])->save();

        $payout->refresh();

        if ($payout->status === PayoutStatus::Successful) {
            $this->notifyRecipient($payout);
        }

        return $payout;
    }

    /** Poll the gateway for the settled status of a payout and apply it. */
    public function syncStatus(Payout $payout): Payout
    {
        $result = $this->provider->status($payout->ref, '1');

        if ($result->success) {
            $this->applyStatus($payout->ref, $result->status, $result->raw['data'] ?? []);
        }

        return $payout->refresh();
    }

    /**
     * Apply a settlement status to a payout row (used by the webhook and status
     * poller). Targets one indexed row by external ref; returns rows updated.
     *
     * @param  array<string, mixed>  $data
     */
    public function applyStatus(string $externalRef, PayoutStatus $status, array $data = []): int
    {
        $columns = ['status' => $status->value];

        if (isset($data['transactionid'])) {
            $columns['provider_transaction_id'] = (string) $data['transactionid'];
        }
        if (isset($data['thirdpartyref'])) {
            $columns['third_party_ref'] = (string) $data['thirdpartyref'];
        }
        if (isset($data['receivername'])) {
            $columns['recipient_name'] = (string) $data['receivername'];
        }

        if ($status === PayoutStatus::Successful) {
            $columns['completed_at'] = now();
        } elseif ($status === PayoutStatus::Failed) {
            $columns['failed_at'] = now();
        }

        $updated = Payout::query()->where('ref', $externalRef)->update($columns);

        if ($updated > 0 && $status === PayoutStatus::Successful) {
            $payout = Payout::where('ref', $externalRef)->first();

            if ($payout !== null) {
                $this->notifyRecipient($payout);
            }
        }

        return $updated;
    }

    public function providerName(): string
    {
        return $this->provider->name();
    }

    /** Let the operator know their money is on the way (best-effort). */
    private function notifyRecipient(Payout $payout): void
    {
        try {
            $user = $payout->user_id ? User::find($payout->user_id) : null;

            if ($user && $user->phone) {
                $amount = number_format((float) $payout->amount, 2);
                send_sms(
                    $user->phone,
                    "LYVO: A payout of {$payout->currency} {$amount} has been sent to your {$payout->channel->label()} ({$payout->recipient}).",
                    'payout',
                    $user->id,
                );
            }
        } catch (\Throwable $e) {
            // Notifications must never break a settlement.
            Log::warning('Payout notification failed', ['ref' => $payout->ref, 'error' => $e->getMessage()]);
        }
    }
}
