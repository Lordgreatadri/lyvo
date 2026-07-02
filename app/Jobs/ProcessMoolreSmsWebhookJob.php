<?php

namespace App\Jobs;

use App\Enums\SmsStatus;
use Spatie\WebhookClient\Jobs\ProcessWebhookJob;
use Src\Domain\Sms\SmsService;

/**
 * ProcessMoolreSmsWebhookJob
 * --------------------------
 * Handles a stored Moolre delivery-receipt webhook and reconciles the delivery
 * status of the referenced messages. Accepts either a single {ref,status} pair
 * or a {data:[{ref,status}, …]} batch so it is resilient to the exact callback
 * shape. Status updates are applied by reference in a single UPDATE per ref.
 *
 * Extends Spatie's ProcessWebhookJob (which provides the WebhookCall
 * constructor and queue traits) so it satisfies the webhook-client config
 * contract.
 */
class ProcessMoolreSmsWebhookJob extends ProcessWebhookJob
{
    public function handle(SmsService $sms): void
    {
        $payload = $this->webhookCall->payload ?? [];

        foreach ($this->entries($payload) as $entry) {
            $ref = $entry['ref'] ?? null;

            if (! $ref || ! array_key_exists('status', $entry)) {
                continue;
            }

            $sms->applyStatus((string) $ref, SmsStatus::fromMoolre((int) $entry['status']));
        }
    }

    /**
     * Normalise the payload into a flat list of {ref,status} entries.
     *
     * @return array<int, array{ref?:string, status?:int}>
     */
    private function entries(array $payload): array
    {
        if (isset($payload['data']) && is_array($payload['data'])) {
            return array_values(array_filter($payload['data'], 'is_array'));
        }

        if (isset($payload['ref'])) {
            return [$payload];
        }

        return [];
    }
}
