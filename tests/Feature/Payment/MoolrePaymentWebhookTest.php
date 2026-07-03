<?php

namespace Tests\Feature\Payment;

use App\Enums\PaymentChannel;
use App\Enums\PaymentStatus;
use App\Jobs\ProcessMoolrePaymentWebhookJob;
use App\Models\PaymentTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\WebhookClient\Models\WebhookCall;
use Src\Domain\Payment\PaymentService;
use Tests\TestCase;

/**
 * Verifies inbound Moolre payment callbacks are accepted and reconcile the
 * settlement status of the referenced transaction.
 */
class MoolrePaymentWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_endpoint_accepts_and_stores_the_callback(): void
    {
        $response = $this->postJson('/api/webhooks/moolre/payment', [
            'data' => ['externalref' => 'pay-123', 'txstatus' => 1, 'transactionid' => 'TX1'],
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('webhook_calls', ['name' => 'moolre-payment']);
    }

    public function test_process_job_reconciles_successful_settlement(): void
    {
        $transaction = PaymentTransaction::create([
            'ref' => 'pay-success',
            'provider' => 'log',
            'channel' => PaymentChannel::Mtn,
            'currency' => 'GHS',
            'amount' => 25.0,
            'payer' => '+233201234567',
            'status' => PaymentStatus::AwaitingApproval,
            'context' => 'order',
        ]);

        $call = new WebhookCall();
        $call->payload = ['data' => [
            'externalref' => 'pay-success',
            'txstatus' => 1,
            'transactionid' => 'TX-OK',
            'value' => 24.5,
        ]];

        (new ProcessMoolrePaymentWebhookJob($call))->handle(app(PaymentService::class));

        $transaction->refresh();
        $this->assertSame(PaymentStatus::Successful, $transaction->status);
        $this->assertSame('TX-OK', $transaction->provider_transaction_id);
        $this->assertNotNull($transaction->completed_at);
    }

    public function test_process_job_marks_failed_settlement(): void
    {
        $transaction = PaymentTransaction::create([
            'ref' => 'pay-fail',
            'provider' => 'log',
            'channel' => PaymentChannel::Mtn,
            'currency' => 'GHS',
            'amount' => 25.0,
            'payer' => '+233201234567',
            'status' => PaymentStatus::AwaitingApproval,
            'context' => 'order',
        ]);

        $call = new WebhookCall();
        $call->payload = ['data' => ['externalref' => 'pay-fail', 'txstatus' => 2]];

        (new ProcessMoolrePaymentWebhookJob($call))->handle(app(PaymentService::class));

        $transaction->refresh();
        $this->assertSame(PaymentStatus::Failed, $transaction->status);
        $this->assertNotNull($transaction->failed_at);
    }
}
