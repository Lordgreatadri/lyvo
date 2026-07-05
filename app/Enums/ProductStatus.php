<?php

namespace App\Enums;

/**
 * ProductStatus
 * -------------
 * Lifecycle of an operator's catalogue item. Only `Active` items with a
 * `published_at` in the past are shown publicly; the rest are operator-private.
 */
enum ProductStatus: string
{
    case Draft = 'draft';         // being prepared, not visible publicly
    case Active = 'active';       // published and buyable
    case SoldOut = 'sold_out';    // published but out of stock
    case Archived = 'archived';   // retired, hidden from the store

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Active => 'Active',
            self::SoldOut => 'Sold out',
            self::Archived => 'Archived',
        };
    }

    /** Tailwind colour token used by status badges. */
    public function color(): string
    {
        return match ($this) {
            self::Draft => 'slate',
            self::Active => 'emerald',
            self::SoldOut => 'amber',
            self::Archived => 'rose',
        };
    }

    /** True when an item in this status may appear in the public store. */
    public function isPublicallyVisible(): bool
    {
        return $this === self::Active || $this === self::SoldOut;
    }

    /** True when a customer can place an order for an item in this status. */
    public function isBuyable(): bool
    {
        return $this === self::Active;
    }
}
