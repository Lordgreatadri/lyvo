<?php

namespace App\Enums;

/**
 * PaymentMethodType
 * -----------------
 * Supported saved payment instruments for customers. Stored decoupled from any
 * single provider so additional gateways (Moolre, cards, bank) plug in later.
 */
enum PaymentMethodType: string
{
    case MobileMoney = 'mobile_money';
    case Card = 'card';
    case Bank = 'bank';

    public function label(): string
    {
        return match ($this) {
            self::MobileMoney => 'Mobile Money',
            self::Card => 'Card',
            self::Bank => 'Bank Account',
        };
    }
}
