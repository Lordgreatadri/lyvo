<?php

namespace App\Enums;

/**
 * SmsStatus
 * ---------
 * Lifecycle of a single outbound SMS as tracked in the `sms_messages` table.
 * Provider-specific delivery codes are mapped onto these canonical states so
 * the rest of the application never depends on any one gateway's vocabulary.
 *
 * Moolre delivery-status integers (status query type 5):
 *   0 = Unknown, 1 = Sent, 2 = Delivered, 3 = Failed
 */
enum SmsStatus: string
{
    case Pending = 'pending';     // persisted, not yet handed to the provider
    case Queued = 'queued';       // accepted by the provider for delivery
    case Sent = 'sent';           // left the provider toward the carrier
    case Delivered = 'delivered'; // confirmed delivered to the handset
    case Failed = 'failed';       // rejected or undeliverable

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Queued => 'Queued',
            self::Sent => 'Sent',
            self::Delivered => 'Delivered',
            self::Failed => 'Failed',
        };
    }

    /** Tailwind colour token used by the admin message log badges. */
    public function color(): string
    {
        return match ($this) {
            self::Pending, self::Queued => 'amber',
            self::Sent => 'sky',
            self::Delivered => 'emerald',
            self::Failed => 'rose',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Delivered, self::Failed], true);
    }

    /** Map a Moolre delivery-status integer onto a canonical status. */
    public static function fromMoolre(int $status): self
    {
        return match ($status) {
            1 => self::Sent,
            2 => self::Delivered,
            3 => self::Failed,
            default => self::Queued, // 0 = unknown → still in flight
        };
    }
}
