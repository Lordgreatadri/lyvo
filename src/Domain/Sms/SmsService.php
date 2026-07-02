<?php

namespace Src\Domain\Sms;

use App\Enums\SmsStatus;
use App\Models\SmsMessage;
use App\Models\SmsSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Src\Domain\Sms\Contracts\SmsProviderInterface;
use Src\Domain\Sms\DTOs\SmsMessageDto;
use Src\Domain\Sms\DTOs\SmsResult;
use Src\Domain\Sms\Support\SmsEncoding;

/**
 * SmsService
 * ----------
 * The single orchestrator every part of the application uses to send SMS. It is
 * provider-agnostic: the concrete gateway is injected as an SmsProviderInterface
 * (resolved from settings in SmsServiceProvider), so call sites never change
 * when the gateway does.
 *
 * Responsibilities:
 *   • normalise the recipient number,
 *   • persist a durable SmsMessage row (with encoding + segment cost),
 *   • hand the message to the provider and record the outcome,
 *   • expose balance / status / sender-ID look-ups with sensible caching.
 *
 * Every read is written with performance in mind — balances and sender-ID lists
 * are cached, and status reconciliation batches references into a single call.
 */
class SmsService
{
    public function __construct(
        private readonly SmsProviderInterface $provider,
        private readonly SmsSetting $settings,
    ) {}

    /**
     * Send a single SMS and persist its record. Never throws on provider
     * failure — the failure is captured on the returned result and the row.
     */
    public function send(
        string $recipient,
        string $message,
        string $context = 'manual',
        ?int $userId = null,
    ): SmsResult {
        $recipient = format_phone_for_sms($recipient, (string) config('sms.country_code', '233'));
        $senderId = $this->settings->effectiveSenderId();
        $ref = (string) Str::uuid();
        $meta = SmsEncoding::analyse($message);

        $record = SmsMessage::create([
            'ref' => $ref,
            'provider' => $this->provider->name(),
            'sender_id' => $senderId,
            'recipient' => $recipient,
            'message' => $message,
            'context' => $context,
            'user_id' => $userId,
            'status' => SmsStatus::Pending,
            'encoding' => $meta['encoding'],
            'segments' => $meta['segments'],
        ]);

        $dto = new SmsMessageDto(
            recipient: $recipient,
            message: $message,
            senderId: $senderId,
            ref: $ref,
        );

        try {
            $result = $this->provider->send($dto);
        } catch (\Throwable $e) {
            $record->update([
                'status' => SmsStatus::Failed,
                'failed_at' => now(),
                'failure_reason' => $e->getMessage(),
                'meta' => ['exception' => class_basename($e)],
            ]);

            return SmsResult::failure('EXCEPTION', $e->getMessage());
        }

        $record->update([
            'status' => $result->success ? SmsStatus::Queued : SmsStatus::Failed,
            'provider_message_id' => $result->providerId,
            'sent_at' => $result->success ? now() : null,
            'failed_at' => $result->success ? null : now(),
            'failure_reason' => $result->success ? null : $result->message,
            'meta' => ['response' => $result->rawResponse],
        ]);

        return $result;
    }

    /**
     * Reconcile the delivery status of the given references with the provider
     * and update their rows. Batches every ref into one provider call.
     *
     * @param  array<int, string>  $refs
     * @return int  number of rows updated
     */
    public function reconcileStatuses(array $refs): int
    {
        $refs = array_values(array_unique(array_filter($refs)));

        if ($refs === []) {
            return 0;
        }

        $statuses = $this->provider->statuses($refs);
        $updated = 0;

        foreach ($statuses as $ref => $status) {
            $updated += $this->applyStatus((string) $ref, $status);
        }

        return $updated;
    }

    /**
     * Apply a single delivery status to its message row (used by the webhook and
     * the status poller). Returns 1 when a row was updated, 0 otherwise.
     */
    public function applyStatus(string $ref, SmsStatus $status): int
    {
        $columns = ['status' => $status->value];

        if ($status === SmsStatus::Delivered) {
            $columns['delivered_at'] = now();
        } elseif ($status === SmsStatus::Failed) {
            $columns['failed_at'] = now();
        }

        return SmsMessage::query()->where('ref', $ref)->update($columns);
    }

    /**
     * Return the account credit balance, cached to avoid hammering the gateway.
     * Pass $force = true to bypass the cache and refresh the stored snapshot.
     *
     * @return array{balance: float, cached: bool, checked_at: ?string, raw: array}
     */
    public function balance(bool $force = false): array
    {
        if (! $force && ! $this->settings->isBalanceStale()) {
            return [
                'balance' => (float) $this->settings->cached_balance,
                'cached' => true,
                'checked_at' => $this->settings->balance_checked_at?->toISOString(),
                'raw' => $this->settings->cached_balance_snapshot ?? [],
            ];
        }

        $result = $this->provider->balance();

        $this->settings->forceFill([
            'cached_balance' => $result['balance'],
            'cached_balance_snapshot' => $result['raw'],
            'balance_checked_at' => now(),
        ])->save();

        return [
            'balance' => (float) $result['balance'],
            'cached' => false,
            'checked_at' => now()->toISOString(),
            'raw' => $result['raw'],
        ];
    }

    /**
     * Registered sender IDs, cached for the configured window.
     *
     * @return array<int, array{id:int|null, senderid:string, approval:string, whitelisted:bool}>
     */
    public function senderIds(bool $force = false): array
    {
        $key = 'sms:sender-ids:' . $this->provider->name();
        $ttl = now()->addMinutes((int) config('sms.sender_ids_cache_minutes', 60));

        if ($force) {
            Cache::forget($key);
        }

        return Cache::remember($key, $ttl, fn () => $this->provider->senderIds());
    }

    public function providerName(): string
    {
        return $this->provider->name();
    }
}
