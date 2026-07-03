<?php

namespace Src\Domain\Payment;

use App\Enums\PaymentChannel;
use App\Enums\PaymentStatus;
use App\Models\PaymentSetting;
use App\Models\PaymentTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Src\Domain\Payment\Contracts\PaymentProviderInterface;
use Src\Domain\Payment\DTOs\PaymentRequestDto;

/**
 * PaymentService
 * --------------
 * The single orchestrator every part of the application uses to collect money.
 * It is provider-agnostic: the concrete gateway is injected as a
 * PaymentProviderInterface (resolved from settings in PaymentServiceProvider),
 * so call sites never change when the gateway does.
 *
 * Responsibilities:
 *   • persist a durable PaymentTransaction row (linked to an escrow order/etc.),
 *   • drive the Moolre OTP → approval flow via charge() / submitOtp(),
 *   • reconcile settlement from webhooks and status polls,
 *   • keep every gateway exchange on the dedicated payment log channel.
 *
 * Reads/writes are written with performance in mind — reconciliation targets a
 * single indexed row by external_ref, never a table scan.
 */
class PaymentService
{
    public function __construct(
        private readonly PaymentProviderInterface $provider,
        private readonly PaymentSetting $settings,
    ) {}

    /**
     * Begin a collection. Persists the transaction, asks the gateway to charge
     * the payer, and records the resulting lifecycle state. Never throws on a
     * gateway failure — the failure is captured on the returned transaction.
     *
     * @param  Model|null  $payable  the thing being paid for (e.g. an escrow order)
     */
    public function charge(
        float $amount,
        string $payer,
        PaymentChannel $channel,
        string $context = 'order',
        ?int $userId = null,
        ?string $reference = null,
        ?Model $payable = null,
    ): PaymentTransaction {
        $payer = format_phone_for_sms($payer, (string) config('payment.country_code', '233'));
        $currency = (string) ($this->settings->currency ?: config('payment.currency', 'GHS'));
        $accountNumber = (string) config('payment.providers.moolre.account_number', '');

        $transaction = new PaymentTransaction([
            'ref' => (string) Str::uuid(),
            'provider' => $this->provider->name(),
            'channel' => $channel,
            'currency' => $currency,
            'amount' => $amount,
            'payer' => $payer,
            'account_number' => $accountNumber ?: null,
            'context' => $context,
            'user_id' => $userId,
            'reference' => $reference,
            'status' => PaymentStatus::Pending,
        ]);

        if ($payable !== null) {
            $transaction->payable()->associate($payable);
        }

        $transaction->save();

        $dto = new PaymentRequestDto(
            amount: $amount,
            payer: $payer,
            channel: $channel,
            externalRef: $transaction->ref,
            currency: $currency,
            reference: $reference,
            accountNumber: $accountNumber ?: null,
        );

        return $this->dispatch($transaction, $dto);
    }

    /**
     * Submit the OTP the payer received (Moolre step 3) and continue the flow.
     */
    public function submitOtp(PaymentTransaction $transaction, string $otpCode): PaymentTransaction
    {
        $dto = (new PaymentRequestDto(
            amount: (float) $transaction->amount,
            payer: $transaction->payer,
            channel: $transaction->channel,
            externalRef: $transaction->ref,
            currency: $transaction->currency,
            reference: $transaction->reference,
            sessionId: $transaction->session_id,
            accountNumber: $transaction->account_number,
        ))->withOtp($otpCode);

        return $this->dispatch($transaction, $dto);
    }

    /**
     * Hand a request to the gateway and fold the result onto the transaction.
     */
    private function dispatch(PaymentTransaction $transaction, PaymentRequestDto $dto): PaymentTransaction
    {
        try {
            $result = $this->provider->charge($dto);
        } catch (\Throwable $e) {
            $transaction->forceFill([
                'status' => PaymentStatus::Failed->value,
                'failed_at' => now(),
                'failure_reason' => $e->getMessage(),
                'meta' => ['exception' => class_basename($e)],
            ])->save();

            return $transaction->refresh();
        }

        $transaction->forceFill([
            'status' => $result->status->value,
            'otp_required' => $result->otpRequired,
            'provider_transaction_id' => $result->providerTransactionId ?? $transaction->provider_transaction_id,
            'failure_reason' => $result->success ? null : $result->message,
            'failed_at' => $result->status === PaymentStatus::Failed ? now() : $transaction->failed_at,
            'meta' => ['last_response' => $result->raw],
        ])->save();

        return $transaction->refresh();
    }

    /**
     * Poll the gateway for the settled status of a transaction and apply it.
     */
    public function syncStatus(PaymentTransaction $transaction): PaymentTransaction
    {
        $result = $this->provider->status($transaction->ref);

        if ($result->success) {
            $this->applyStatus($transaction->ref, $result->status, $result->raw['data'] ?? []);
        }

        return $transaction->refresh();
    }

    /**
     * Apply a settlement status to a transaction row (used by the webhook and
     * the status poller). Targets one indexed row by external ref; returns the
     * number of rows updated.
     *
     * @param  array<string, mixed>  $data  provider data block (value, transactionid, …)
     */
    public function applyStatus(string $externalRef, PaymentStatus $status, array $data = []): int
    {
        $columns = ['status' => $status->value];

        if (isset($data['transactionid'])) {
            $columns['provider_transaction_id'] = (string) $data['transactionid'];
        }
        if (isset($data['thirdpartyref'])) {
            $columns['third_party_ref'] = (string) $data['thirdpartyref'];
        }
        if (isset($data['value'])) {
            $columns['value'] = (float) $data['value'];
        }

        if ($status === PaymentStatus::Successful) {
            $columns['completed_at'] = now();
        } elseif ($status === PaymentStatus::Failed) {
            $columns['failed_at'] = now();
        }

        return PaymentTransaction::query()->where('ref', $externalRef)->update($columns);
    }

    public function providerName(): string
    {
        return $this->provider->name();
    }
}
