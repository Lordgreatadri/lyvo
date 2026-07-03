<?php

namespace App\Enums;

/**
 * PaymentStatus
 * -------------
 * Canonical lifecycle of a single Moolre collection as tracked in the
 * `payment_transactions` table. Provider-specific codes are mapped onto these
 * states so the rest of the application (escrow, dashboards) never depends on
 * any one gateway's vocabulary.
 *
 * Moolre payment status integers (txstatus, status query & webhook):
 *   0 = Pending (in flight), 1 = Successful, 2 = Failed
 */
enum PaymentStatus: string
{
    case Pending = 'pending';                     // persisted, not yet sent to gateway
    case AwaitingOtp = 'awaiting_otp';            // gateway asked the payer for an OTP (code TP14)
    case AwaitingApproval = 'awaiting_approval';  // USSD prompt sent to the payer's handset
    case Processing = 'processing';               // accepted / collecting (txstatus 0)
    case Successful = 'successful';               // funds settled (txstatus 1)
    case Failed = 'failed';                       // rejected or undeliverable (txstatus 2)

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::AwaitingOtp => 'Awaiting OTP',
            self::AwaitingApproval => 'Awaiting approval',
            self::Processing => 'Processing',
            self::Successful => 'Successful',
            self::Failed => 'Failed',
        };
    }

    /** Tailwind colour token used by the admin/operator transaction badges. */
    public function color(): string
    {
        return match ($this) {
            self::Pending, self::AwaitingOtp, self::AwaitingApproval => 'amber',
            self::Processing => 'sky',
            self::Successful => 'emerald',
            self::Failed => 'rose',
        };
    }

    /** True once the transaction can no longer change state. */
    public function isTerminal(): bool
    {
        return in_array($this, [self::Successful, self::Failed], true);
    }

    /** True while the collection is still open with the gateway. */
    public function isOpen(): bool
    {
        return ! $this->isTerminal();
    }

    /** Map a Moolre `txstatus` integer onto a canonical status. */
    public static function fromMoolreTxStatus(int $txStatus): self
    {
        return match ($txStatus) {
            1 => self::Successful,
            2 => self::Failed,
            default => self::Processing, // 0 = pending / still collecting
        };
    }
}
