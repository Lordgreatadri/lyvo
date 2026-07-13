<?php

namespace Tests\Feature\Payout;

use App\Enums\PayoutChannel;
use App\Enums\PayoutStatus;
use App\Models\Payout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Src\Domain\Payout\PayoutService;
use Tests\TestCase;

/**
 * Exercises the PayoutService orchestration against the network-free "log"
 * provider, verifying persistence and settlement reconciliation without
 * touching a real gateway.
 */
class PayoutServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_pay_persists_a_payout_and_settles_via_log_provider(): void
    {
        /** @var PayoutService $service */
        $service = app(PayoutService::class);

        $payout = $service->pay(
            amount: 120.0,
            receiver: '0543645688',
            channel: PayoutChannel::Mtn,
            context: 'escrow-release',
        );

        $this->assertDatabaseHas('payouts', [
            'ref' => $payout->ref,
            'recipient' => '0543645688',
            'amount' => 120.00,
            'context' => 'escrow-release',
            'provider' => 'log',
        ]);

        // The log provider accepts and settles immediately.
        $payout->refresh();
        $this->assertSame(PayoutStatus::Successful, $payout->status);
        $this->assertNotNull($payout->provider_transaction_id);
        $this->assertNotNull($payout->completed_at);
    }

    public function test_validate_name_returns_the_resolved_recipient(): void
    {
        /** @var PayoutService $service */
        $service = app(PayoutService::class);

        $result = $service->validateName('0543645688', PayoutChannel::Mtn);

        $this->assertTrue($result->success);
        $this->assertSame('LYVO TEST RECIPIENT', $result->recipientName);
    }

    public function test_apply_status_settles_the_payout(): void
    {
        $payout = Payout::create([
            'ref' => 'payout-apply',
            'provider' => 'log',
            'channel' => PayoutChannel::Mtn,
            'currency' => 'GHS',
            'amount' => 80.0,
            'recipient' => '0543645688',
            'status' => PayoutStatus::Processing,
            'context' => 'escrow-release',
        ]);

        /** @var PayoutService $service */
        $service = app(PayoutService::class);

        $updated = $service->applyStatus('payout-apply', PayoutStatus::Successful, [
            'transactionid' => 'PO-XYZ',
            'receivername' => 'AMA MENSAH',
        ]);

        $this->assertSame(1, $updated);

        $payout->refresh();
        $this->assertSame(PayoutStatus::Successful, $payout->status);
        $this->assertSame('PO-XYZ', $payout->provider_transaction_id);
        $this->assertSame('AMA MENSAH', $payout->recipient_name);
        $this->assertNotNull($payout->completed_at);
    }

    public function test_apply_status_is_idempotent_for_repeated_callbacks(): void
    {
        $payout = Payout::create([
            'ref' => 'payout-dupe',
            'provider' => 'log',
            'channel' => PayoutChannel::Mtn,
            'currency' => 'GHS',
            'amount' => 60.0,
            'recipient' => '0543645688',
            'status' => PayoutStatus::Processing,
            'context' => 'escrow-release',
        ]);

        /** @var PayoutService $service */
        $service = app(PayoutService::class);

        // First callback settles the payout.
        $this->assertSame(1, $service->applyStatus('payout-dupe', PayoutStatus::Successful, ['transactionid' => 'PO-1']));
        $settledAt = $payout->fresh()->completed_at;
        $this->assertNotNull($settledAt);

        // A re-sent callback must be a no-op — no row updated, timestamp unchanged.
        $this->assertSame(0, $service->applyStatus('payout-dupe', PayoutStatus::Successful, ['transactionid' => 'PO-1']));
        $this->assertEquals($settledAt, $payout->fresh()->completed_at);
    }
}
