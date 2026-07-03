<?php

namespace App\Jobs;

use App\Enums\PaymentStatus;
use Spatie\WebhookClient\Jobs\ProcessWebhookJob;
use Src\Domain\Payment\PaymentService;

/**
 * ProcessMoolrePaymentWebhookJob
 * ------------------------------
 * Handles a stored Moolre payment callback and reconciles the settlement status
 * of the referenced transaction. The Moolre payload nests the settlement facts
 * under `data` ({externalref, txstatus, transactionid, value, thirdpartyref}).
 * The transaction is matched by our `externalref` and updated in a single
 * indexed UPDATE.
 *
 * Extends Spatie's ProcessWebhookJob (which provides the WebhookCall
 * constructor and queue traits) so it satisfies the webhook-client config
 * contract.
 */
class ProcessMoolrePaymentWebhookJob extends ProcessWebhookJob
{
    public function handle(PaymentService $payments): void
    {
        $payload = $this->webhookCall->payload ?? [];
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : $payload;

        $externalRef = $data['externalref'] ?? null;

        if (! $externalRef || ! array_key_exists('txstatus', $data)) {
            return;
        }

        $payments->applyStatus(
            (string) $externalRef,
            PaymentStatus::fromMoolreTxStatus((int) $data['txstatus']),
            $data,
        );
    }
}
