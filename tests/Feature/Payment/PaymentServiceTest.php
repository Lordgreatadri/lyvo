<?php

namespace Tests\Feature\Payment;

use App\Enums\PaymentChannel;
use App\Enums\PaymentStatus;
use App\Models\PaymentTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Src\Domain\Payment\Contracts\PaymentProviderInterface;
use Src\Domain\Payment\DTOs\PaymentRequestDto;
use Src\Domain\Payment\DTOs\PaymentResult;
use Src\Domain\Payment\PaymentService;
use Tests\TestCase;

/**
 * Exercises the PaymentService orchestration against the network-free "log"
 * provider (and a scripted fake for the OTP flow), verifying persistence and
 * status reconciliation without touching a real gateway.
 */
class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_charge_persists_a_transaction(): void
    {
        /** @var PaymentService $service */
        $service = app(PaymentService::class);

        $transaction = $service->charge(
            amount: 40.0,
            payer: '0201234567',
            channel: PaymentChannel::Mtn,
            context: 'order',
        );

        $this->assertDatabaseHas('payment_transactions', [
            'ref' => $transaction->ref,
            'payer' => '+233201234567', // normalised to E.164
            'amount' => 40.00,
            'context' => 'order',
            'provider' => 'log',
        ]);

        // Log provider accepts and moves straight to awaiting approval.
        $this->assertSame(PaymentStatus::AwaitingApproval, $transaction->fresh()->status);
        $this->assertNotNull($transaction->fresh()->provider_transaction_id);
    }

    public function test_otp_flow_records_awaiting_otp_then_completes(): void
    {
        $this->app->bind(PaymentProviderInterface::class, fn () => new class implements PaymentProviderInterface {
            public function name(): string
            {
                return 'fake';
            }

            public function charge(PaymentRequestDto $request): PaymentResult
            {
                // First call (no OTP) → awaiting OTP; second (with OTP) → approval.
                return $request->otpCode === null
                    ? PaymentResult::accepted(PaymentStatus::AwaitingOtp, 'TP14', 'OTP sent', 'TX1', otpRequired: true)
                    : PaymentResult::accepted(PaymentStatus::AwaitingApproval, 'OK', 'Prompt sent', 'TX1');
            }

            public function status(string $externalRef): PaymentResult
            {
                return PaymentResult::accepted(PaymentStatus::Successful, 'OK', 'Done', 'TX1', raw: ['data' => ['txstatus' => 1]]);
            }
        });

        /** @var PaymentService $service */
        $service = app(PaymentService::class);

        $transaction = $service->charge(50.0, '0201234567', PaymentChannel::Mtn);
        $this->assertSame(PaymentStatus::AwaitingOtp, $transaction->fresh()->status);
        $this->assertTrue($transaction->fresh()->otp_required);

        $transaction = $service->submitOtp($transaction, '123456');
        $this->assertSame(PaymentStatus::AwaitingApproval, $transaction->fresh()->status);
    }

    public function test_apply_status_settles_the_transaction(): void
    {
        $transaction = PaymentTransaction::create([
            'ref' => 'ref-apply',
            'provider' => 'log',
            'channel' => PaymentChannel::Mtn,
            'currency' => 'GHS',
            'amount' => 30.0,
            'payer' => '+233201234567',
            'status' => PaymentStatus::AwaitingApproval,
            'context' => 'order',
        ]);

        /** @var PaymentService $service */
        $service = app(PaymentService::class);

        $updated = $service->applyStatus('ref-apply', PaymentStatus::Successful, [
            'transactionid' => 'TX-XYZ',
            'value' => 29.5,
            'thirdpartyref' => 'MO-123',
        ]);

        $this->assertSame(1, $updated);

        $transaction->refresh();
        $this->assertSame(PaymentStatus::Successful, $transaction->status);
        $this->assertSame('TX-XYZ', $transaction->provider_transaction_id);
        $this->assertSame('MO-123', $transaction->third_party_ref);
        $this->assertSame('29.50', $transaction->value);
        $this->assertNotNull($transaction->completed_at);
    }

    public function test_sync_status_reconciles_from_the_gateway(): void
    {
        $transaction = PaymentTransaction::create([
            'ref' => 'ref-sync',
            'provider' => 'log',
            'channel' => PaymentChannel::Mtn,
            'currency' => 'GHS',
            'amount' => 12.0,
            'payer' => '+233201234567',
            'status' => PaymentStatus::AwaitingApproval,
            'context' => 'order',
        ]);

        /** @var PaymentService $service */
        $service = app(PaymentService::class);

        $service->syncStatus($transaction);

        // Log provider reports success.
        $this->assertSame(PaymentStatus::Successful, $transaction->fresh()->status);
    }
}
