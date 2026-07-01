<?php

namespace App\Enums;

/**
 * AccountType
 * -----------
 * The single discriminator on the `users` table that determines which area of
 * LYVO a user belongs to. One users table powers authentication for everyone;
 * this enum decides which dashboard/pages they are routed to and (together with
 * Spatie roles) what they are allowed to do.
 */
enum AccountType: string
{
    case Admin = 'admin';
    case Customer = 'customer';
    case Operator = 'operator';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Customer => 'Customer',
            self::Operator => 'Operator',
        };
    }

    /**
     * The route a user of this type should land on after login.
     */
    public function homeRoute(): string
    {
        return match ($this) {
            self::Admin => 'admin.dashboard',
            self::Customer => 'customer.dashboard',
            self::Operator => 'operator.dashboard',
        };
    }

    /**
     * The Spatie role name that maps 1:1 to this account type.
     */
    public function defaultRole(): string
    {
        return $this->value;
    }

    /**
     * Account types a visitor is allowed to self-register as.
     *
     * @return array<int, self>
     */
    public static function selfRegisterable(): array
    {
        return [self::Customer, self::Operator];
    }
}
