<?php

namespace Tests\Feature\Sms;

use App\Enums\SmsStatus;
use App\Jobs\ProcessMoolreSmsWebhookJob;
use App\Models\SmsMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\WebhookClient\Models\WebhookCall;
use Src\Domain\Sms\SmsService;
use Tests\TestCase;

/**
 * Verifies inbound Moolre delivery callbacks are accepted and reconcile the
 * delivery status of the referenced messages.
 */
class MoolreWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_endpoint_accepts_and_stores_the_callback(): void
    {
        $response = $this->postJson('/api/webhooks/moolre/sms', [
            'data' => [['ref' => 'ref-123', 'status' => 2]],
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('webhook_calls', ['name' => 'moolre']);
    }

    public function test_process_job_reconciles_delivery_status(): void
    {
        $message = SmsMessage::create([
            'ref' => 'ref-xyz',
            'provider' => 'log',
            'recipient' => '+233201234567',
            'message' => 'Hi',
            'context' => 'otp',
            'status' => SmsStatus::Queued,
            'segments' => 1,
        ]);

        $call = new WebhookCall();
        $call->payload = ['data' => [['ref' => 'ref-xyz', 'status' => 2]]];

        (new ProcessMoolreSmsWebhookJob($call))->handle(app(SmsService::class));

        $message->refresh();
        $this->assertSame(SmsStatus::Delivered, $message->status);
        $this->assertNotNull($message->delivered_at);
    }

    public function test_process_job_marks_failed_deliveries(): void
    {
        $message = SmsMessage::create([
            'ref' => 'ref-fail',
            'provider' => 'log',
            'recipient' => '+233201234567',
            'message' => 'Hi',
            'context' => 'otp',
            'status' => SmsStatus::Queued,
            'segments' => 1,
        ]);

        $call = new WebhookCall();
        $call->payload = ['ref' => 'ref-fail', 'status' => 3];

        (new ProcessMoolreSmsWebhookJob($call))->handle(app(SmsService::class));

        $message->refresh();
        $this->assertSame(SmsStatus::Failed, $message->status);
        $this->assertNotNull($message->failed_at);
    }
}
