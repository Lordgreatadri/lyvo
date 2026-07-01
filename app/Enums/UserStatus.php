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
}
