<?php

namespace App\Enums;

/**
 * UserStatus
 * ----------
 * Account-level lifecycle status, independent of operator verification.
 * Admins use this to suspend or ban abusive accounts.
 */
enum UserStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Banned = 'banned';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function canLogin(): bool
    {
        return $this === self::Active;
    }

    /**
     * Tailwind colour stem used by status badges in the admin UI.
     */
    public function badgeColor(): string
    {
        return match ($this) {
            self::Active => 'emerald',
            self::Suspended => 'amber',
            self::Banned => 'rose',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Active => 'Full access to the platform.',
            self::Suspended => 'Temporarily frozen — cannot sign in until reactivated.',
            self::Banned => 'Permanently blocked from the platform.',
        };
    }
}
