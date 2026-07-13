<?php

namespace Tests\Feature\Payout;

use App\Enums\PayoutChannel;
use App\Enums\PayoutStatus;
use App\Jobs\ProcessMoolrePayoutWebhookJob;
use App\Models\Payout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\WebhookClient\Models\WebhookCall;
use Src\Domain\Payout\PayoutService;
use Tests\TestCase;

/**
 * Verifies inbound Moolre transfer callbacks are accepted and reconcile the
 * settlement status of the referenced payout.
 */
class MoolrePayoutWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_endpoint_accepts_and_stores_the_callback(): void
    {
        $response = $this->postJson('/api/webhooks/moolre/payout', [
            'data' => ['externalref' => 'payout-123', 'txstatus' => 1, 'transactionid' => 'PO1'],
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('webhook_calls', ['name' => 'moolre-payout']);
    }

    public function test_process_job_reconciles_successful_settlement(): void
    {
        $payout = Payout::create([
            'ref' => 'payout-success',
            'provider' => 'log',
            'channel' => PayoutChannel::Mtn,
            'currency' => 'GHS',
            'amount' => 90.0,
            'recipient' => '0543645688',
            'status' => PayoutStatus::Processing,
            'context' => 'escrow-release',
        ]);

        $call = new WebhookCall();
        $call->payload = ['data' => [
            'externalref' => 'payout-success',
            'txstatus' => 1,
            'transactionid' => 'PO-OK',
        ]];

        (new ProcessMoolrePayoutWebhookJob($call))->handle(app(PayoutService::class));

        $payout->refresh();
        $this->assertSame(PayoutStatus::Successful, $payout->status);
        $this->assertSame('PO-OK', $payout->provider_transaction_id);
        $this->assertNotNull($payout->completed_at);
    }

    public function test_process_job_marks_failed_settlement(): void
    {
        $payout = Payout::create([
            'ref' => 'payout-fail',
            'provider' => 'log',
            'channel' => PayoutChannel::Mtn,
            'currency' => 'GHS',
            'amount' => 90.0,
            'recipient' => '0543645688',
            'status' => PayoutStatus::Processing,
            'context' => 'escrow-release',
        ]);

        $call = new WebhookCall();
        $call->payload = ['data' => ['externalref' => 'payout-fail', 'txstatus' => 2]];

        (new ProcessMoolrePayoutWebhookJob($call))->handle(app(PayoutService::class));

        $payout->refresh();
        $this->assertSame(PayoutStatus::Failed, $payout->status);
        $this->assertNotNull($payout->failed_at);
    }
}
