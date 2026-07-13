<?php

namespace App\Enums;

/**
 * PayoutStatus
 * ------------
 * Canonical lifecycle of a single Moolre disbursement (transfer) as tracked in
 * the `payouts` table. Provider-specific `txstatus` integers are mapped onto
 * these states so the rest of the application never depends on the gateway's
 * vocabulary.
 *
 * Moolre transfer txstatus integers (transfer response, status query & webhook):
 *   1 = Successful, 0 = Pending, 2 = Failed, 3 = Unknown.
 *
 * Per Moolre guidance, a payout must NEVER be treated as failed unless txstatus
 * is explicitly 2 — an Unknown (3) result is held pending and re-checked.
 */
enum PayoutStatus: string
{
    case Pending = 'pending';         // persisted, not yet sent to the gateway
    case Processing = 'processing';   // accepted / in flight (txstatus 0)
    case Successful = 'successful';   // funds delivered to the recipient (txstatus 1)
    case Failed = 'failed';           // rejected / undeliverable (txstatus 2)
    case Unknown = 'unknown';         // gateway could not confirm (txstatus 3) — re-check

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Processing => 'Processing',
            self::Successful => 'Successful',
            self::Failed => 'Failed',
            self::Unknown => 'Unknown',
        };
    }

    /** Tailwind colour token used by the payout status badges. */
    public function color(): string
    {
        return match ($this) {
            self::Pending, self::Processing => 'amber',
            self::Successful => 'emerald',
            self::Failed => 'rose',
            self::Unknown => 'slate',
        };
    }

    /** True once the payout can no longer change state. */
    public function isTerminal(): bool
    {
        return in_array($this, [self::Successful, self::Failed], true);
    }

    /** True while the payout is still open with the gateway (incl. Unknown). */
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
            3 => self::Unknown,
            default => self::Processing, // 0 = pending / still transferring
        };
    }
}
