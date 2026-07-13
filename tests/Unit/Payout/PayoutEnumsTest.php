<?php

namespace Tests\Unit\Payout;

use App\Enums\PayoutChannel;
use App\Enums\PayoutStatus;
use PHPUnit\Framework\TestCase;

/**
 * Verifies the Moolre transfer code mappings on the payout enums. These differ
 * from the collection (PaymentChannel) codes, so they are pinned here.
 */
class PayoutEnumsTest extends TestCase
{
    public function test_channel_moolre_codes(): void
    {
        // Transfer codes differ from collection codes (MTN is 1 here, 13 there).
        $this->assertSame('1', PayoutChannel::Mtn->moolreCode());
        $this->assertSame('6', PayoutChannel::Telecel->moolreCode());
        $this->assertSame('7', PayoutChannel::AirtelTigo->moolreCode());
        $this->assertSame('2', PayoutChannel::Bank->moolreCode());

        $this->assertSame(PayoutChannel::Mtn, PayoutChannel::fromMoolreCode('1'));
        $this->assertSame(PayoutChannel::Bank, PayoutChannel::fromMoolreCode(2));
    }

    public function test_only_bank_is_not_mobile_money(): void
    {
        $this->assertTrue(PayoutChannel::Mtn->isMobileMoney());
        $this->assertFalse(PayoutChannel::Bank->isMobileMoney());
        $this->assertSame(
            [PayoutChannel::Mtn, PayoutChannel::Telecel, PayoutChannel::AirtelTigo],
            PayoutChannel::mobileMoneyCases(),
        );
    }

    public function test_txstatus_maps_to_canonical_states(): void
    {
        $this->assertSame(PayoutStatus::Successful, PayoutStatus::fromMoolreTxStatus(1));
        $this->assertSame(PayoutStatus::Failed, PayoutStatus::fromMoolreTxStatus(2));
        $this->assertSame(PayoutStatus::Unknown, PayoutStatus::fromMoolreTxStatus(3));
        $this->assertSame(PayoutStatus::Processing, PayoutStatus::fromMoolreTxStatus(0));
    }

    public function test_terminal_and_open_states(): void
    {
        $this->assertTrue(PayoutStatus::Successful->isTerminal());
        $this->assertTrue(PayoutStatus::Failed->isTerminal());
        $this->assertFalse(PayoutStatus::Unknown->isTerminal());
        $this->assertTrue(PayoutStatus::Processing->isOpen());
        $this->assertTrue(PayoutStatus::Unknown->isOpen());
    }
}
