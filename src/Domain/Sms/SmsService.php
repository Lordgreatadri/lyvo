<?php

namespace Src\Domain\Sms;

// use App\Models\Branch;
// use App\Models\BranchSmsConfig;
// use App\Models\SmsMessage;
use Illuminate\Support\Str;
use Src\Domain\Sms\Contracts\SmsProviderInterface;
use Src\Domain\Sms\DTOs\SmsMessageDto;
use Src\Domain\Sms\DTOs\SmsResult;
use Src\Domain\Sms\Providers\FrogSmsProvider;
use Src\Domain\Sms\Providers\TwilioSmsProvider;

/**
 * SmsService — central orchestrator for sending SMS messages.
 *
 * Usage:
 *   $service = SmsService::forBranch($branch);
 *   if ($service) {
 *       $result = $service->send('+233201234567', 'Hello!', 'birthday');
 *   }
 *
 * When no configured provider exists for a branch, forBranch() returns null
 * and the caller should fall back to its existing notification flow.
 */
final class SmsService
{
    // private readonly SmsProviderInterface $provider;
    private string  $provider = ''; //just to hold space for now to avoid errors

    public function __construct(private string $config)
    {
        $this->provider = match ($config->provider) {
            'twilio' => new TwilioSmsProvider($config),
            default  => new FrogSmsProvider($config),
        };
    }

    // ── Factory ──────────────────────────────────────────────────────────────

    /**
     * Build a SmsService for the given branch.
     * Returns null when the branch has no active, fully-configured provider —
     * the caller is expected to fall back to its existing SMS mechanism.
     */
    // public static function forBranch(Branch $branch): ?self
    // {
    //     $config = BranchSmsConfig::where('branch_id', $branch->id)
    //                              ->where('is_active', true)
    //                              ->first();

    //     return ($config && $config->isConfigured()) ? new self($config) : null;
    // }

    // ── Public API ───────────────────────────────────────────────────────────

    /**
     * Send a single SMS and persist the message record.
     *
     * @param  string   $recipient  International phone number, e.g. +233201234567
     * @param  string   $message    Plain-text message body
     * @param  string   $context    Module that triggered the send (see SmsMessage::CONTEXTS)
     * @param  int|null $userId     Authenticated user who initiated the send
     */
    public function send(
        string $recipient,
        string $message,
        string $context    = 'manual',
        ?int   $userId     = null,
        ?int   $campaignId = null,
    ): SmsResult {
        $msgId = (string) Str::uuid();

        $dto = new SmsMessageDto(
            recipient: $recipient,
            message:   $message,
            senderId:  $this->senderIdForProvider(),
            msgId:     $msgId,
        );

        // Pre-create the record so we have an ID even if the provider throws.
        // $record = SmsMessage::create([
        //     'branch_id'   => $this->config->branch_id,
        //     'campaign_id' => $campaignId,
        //     'user_id'     => $userId,
        //     'provider'    => $this->provider->name(),
        //     'sender_id'   => $dto->senderId,
        //     'recipient'   => $recipient,
        //     'message'     => $message,
        //     'context'     => $context,
        //     'status'      => 'pending',
        // ]);

        try {
            $result = $this->provider->send($dto);
        } catch (\Throwable $e) {
            $record->update([
                'status'         => 'failed',
                'failed_at'      => now(),
                'failure_reason' => $e->getMessage(),
                'meta'           => [
                    'request'   => [
                        'recipient' => $recipient,
                        'sender_id' => $dto->senderId,
                        'msg_id'    => $msgId,
                        'context'   => $context,
                    ],
                    'exception' => class_basename($e),
                ],
            ]);

            throw $e;
        }

        $record->update([
            'status'              => $result->success ? 'queued' : 'failed',
            'provider_message_id' => $result->providerId,
            'sent_at'             => $result->success ? now() : null,
            'failed_at'           => $result->success ? null : now(),
            'failure_reason'      => $result->success ? null : $result->message,
            'meta'                => [
                'request'  => [
                    'recipient' => $recipient,
                    'sender_id' => $dto->senderId,
                    'msg_id'    => $msgId,
                    'context'   => $context,
                ],
                'response' => $result->rawResponse,
            ],
        ]);

        return $result;
    }

    /**
     * Return account balance, using a 15-minute cache to reduce API calls.
     * Always returns a normalized shape regardless of provider:
     *   { cached, provider, cashBalance, ...provider_extras }
     * Pass $forceRefresh = true to bypass the cache.
     */
    public function balance(bool $forceRefresh = false): array
    {
        if (! $forceRefresh && ! $this->config->isBalanceStale()) {
            // Use the full JSON snapshot when available (preserves bundles etc.)
            $snapshot = $this->config->cached_balance_snapshot;
            if ($snapshot) {
                return array_merge($snapshot, [
                    'cached'     => true,
                    'checked_at' => $this->config->balance_checked_at?->toISOString(),
                ]);
            }

            // Legacy fallback: only scalar balance was stored
            return [
                'cached'      => true,
                'provider'    => $this->provider->name(),
                'cashBalance' => (float) $this->config->cached_balance,
                'checked_at'  => $this->config->balance_checked_at?->toISOString(),
            ];
        }

        $raw      = $this->provider->balance();
        $snapshot = $this->normalizeBalanceResponse($raw);

        $this->config->update([
            'cached_balance'          => $this->extractBalanceValue($raw),
            'cached_balance_snapshot' => $snapshot,
            'balance_checked_at'      => now(),
        ]);

        return array_merge($snapshot, [
            'checked_at' => now()->toISOString(),
        ]);
    }

    /** Expose the active provider name for callers. */
    public function providerName(): string
    {
        return $this->provider->name();
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function senderIdForProvider(): string
    {
        return match ($this->provider->name()) {
            'twilio' => $this->config->twilio_from_number ?? '',
            default  => $this->config->frog_sender_id ?? '',
        };
    }

    private function extractBalanceValue(array $raw): ?float
    {
        // Frog (already normalized by FrogSmsProvider::balance()): cashbalance key
        if (isset($raw['cashbalance'])) {
            return (float) $raw['cashbalance'];
        }

        // Twilio: balance key
        if (isset($raw['balance'])) {
            return (float) $raw['balance'];
        }

        return null;
    }

    /**
     * Return a consistent balance shape for all providers:
     *   { cached, provider, cashBalance, ...provider-specific extras }
     */
    private function normalizeBalanceResponse(array $raw): array
    {
        return match ($this->provider->name()) {
            'frog'   => [
                'cached'               => false,
                'provider'             => 'frog',
                'cashBalance'          => $raw['cashbalance'] ?? 0,
                'paidBalance'          => $raw['paidcashbalance'] ?? 0,
                'bundles'              => $raw['bundles'] ?? [],
                'activeBundleInvoices' => $raw['activebundleinvoices'] ?? [],
                'raw_status'           => $raw['raw_status'] ?? null,
            ],
            'twilio' => [
                'cached'      => false,
                'provider'    => 'twilio',
                'cashBalance' => (float) ($raw['balance'] ?? 0),
                'currency'    => $raw['currency'] ?? 'USD',
                'bundles'     => [],
            ],
            default  => array_merge(['cached' => false, 'provider' => $this->provider->name()], $raw),
        };
    }
}
