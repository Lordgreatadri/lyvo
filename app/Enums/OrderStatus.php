<?php

namespace App\Enums;

/**
 * OrderStatus
 * -----------
 * The escrow-protected order lifecycle:
 *
 *   PendingPayment → FundsHeld → Processing → Delivered → Released
 *
 * with two off-ramps once funds are held: a buyer can raise a dispute
 * (Disputed → admin resolves to Refunded or Released) and an unpaid order can be
 * Cancelled. Money only moves to the operator on Released; a Refunded order
 * returns it to the buyer.
 */
enum OrderStatus: string
{
    case PendingPayment = 'pending_payment';
    case FundsHeld = 'funds_held';
    case Processing = 'processing';
    case Delivered = 'delivered';
    case Released = 'released';
    case Disputed = 'disputed';
    case Refunded = 'refunded';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PendingPayment => 'Awaiting payment',
            self::FundsHeld => 'Funds held securely',
            self::Processing => 'Seller processing',
            self::Delivered => 'Delivered',
            self::Released => 'Funds released',
            self::Disputed => 'Under dispute',
            self::Refunded => 'Refunded',
            self::Cancelled => 'Cancelled',
        };
    }

    /** Tailwind colour token used by status badges. */
    public function color(): string
    {
        return match ($this) {
            self::PendingPayment => 'slate',
            self::FundsHeld => 'sky',
            self::Processing => 'amber',
            self::Delivered => 'indigo',
            self::Released => 'emerald',
            self::Disputed => 'rose',
            self::Refunded => 'slate',
            self::Cancelled => 'slate',
        };
    }

    /** Key into the visual escrow pipeline (initiated → released). */
    public function pipelineKey(): string
    {
        return match ($this) {
            self::PendingPayment => 'initiated',
            self::FundsHeld => 'held',
            self::Processing => 'processing',
            self::Delivered => 'delivered',
            self::Released, self::Refunded => 'released',
            self::Disputed => 'processing',
            self::Cancelled => 'initiated',
        };
    }

    /** Funds are currently held in escrow (money is on the platform). */
    public function isEscrowHeld(): bool
    {
        return in_array($this, [self::FundsHeld, self::Processing, self::Delivered, self::Disputed], true);
    }

    /** The order has reached a final state — no further transitions. */
    public function isTerminal(): bool
    {
        return in_array($this, [self::Released, self::Refunded, self::Cancelled], true);
    }
}
