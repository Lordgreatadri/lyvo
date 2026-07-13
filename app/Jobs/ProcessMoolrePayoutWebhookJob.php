<?php

namespace App\Jobs;

use App\Enums\PayoutStatus;
use Spatie\WebhookClient\Jobs\ProcessWebhookJob;
use Src\Domain\Payout\PayoutService;

/**
 * ProcessMoolrePayoutWebhookJob
 * -----------------------------
 * Handles a stored Moolre transfer callback and reconciles the settlement status
 * of the referenced payout. The payload nests the settlement facts under `data`
 * ({externalref, txstatus, transactionid, thirdpartyref, receivername}). The
 * payout is matched by our `externalref` and updated in a single indexed UPDATE.
 *
 * Extends Spatie's ProcessWebhookJob so it satisfies the webhook-client config
 * contract.
 */
class ProcessMoolrePayoutWebhookJob extends ProcessWebhookJob
{
    public function handle(PayoutService $payouts): void
    {
        $payload = $this->webhookCall->payload ?? [];
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : $payload;

        $externalRef = $data['externalref'] ?? null;

        if (! $externalRef || ! array_key_exists('txstatus', $data)) {
            return;
        }

        $payouts->applyStatus(
            (string) $externalRef,
            PayoutStatus::fromMoolreTxStatus((int) $data['txstatus']),
            $data,
        );
    }
}
