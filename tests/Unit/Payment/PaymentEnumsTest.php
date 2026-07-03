<?php

namespace Tests\Unit\Payment;

use App\Enums\PaymentChannel;
use App\Enums\PaymentStatus;
use PHPUnit\Framework\TestCase;

/**
 * Verifies the Moolre code mappings on the payment enums, since the rest of the
 * application depends on these being stable regardless of the active gateway.
 */
class PaymentEnumsTest extends TestCase
{
    public function test_txstatus_maps_to_canonical_states(): void
    {
        $this->assertSame(PaymentStatus::Successful, PaymentStatus::fromMoolreTxStatus(1));
        $this->assertSame(PaymentStatus::Failed, PaymentStatus::fromMoolreTxStatus(2));
        $this->assertSame(PaymentStatus::Processing, PaymentStatus::fromMoolreTxStatus(0));
    }

    public function test_terminal_states(): void
    {
        $this->assertTrue(PaymentStatus::Successful->isTerminal());
        $this->assertTrue(PaymentStatus::Failed->isTerminal());
        $this->assertFalse(PaymentStatus::AwaitingOtp->isTerminal());
        $this->assertTrue(PaymentStatus::AwaitingApproval->isOpen());
    }

    public function test_channel_moolre_codes_round_trip(): void
    {
        $this->assertSame('13', PaymentChannel::Mtn->moolreCode());
        $this->assertSame('6', PaymentChannel::Telecel->moolreCode());
        $this->assertSame('7', PaymentChannel::AirtelTigo->moolreCode());

        $this->assertSame(PaymentChannel::Mtn, PaymentChannel::fromMoolreCode('13'));
        $this->assertSame(PaymentChannel::Telecel, PaymentChannel::fromMoolreCode(6));
        $this->assertSame(PaymentChannel::AirtelTigo, PaymentChannel::fromMoolreCode('7'));
    }
}
