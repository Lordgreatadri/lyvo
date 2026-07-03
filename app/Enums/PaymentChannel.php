<?php

namespace App\Enums;

/**
 * PaymentChannel
 * --------------
 * The mobile-money network a collection is debited from. Each case carries the
 * numeric channel code Moolre expects on /open/transact/payment, keeping that
 * gateway detail out of the rest of the application.
 *
 * Moolre channel codes: 13 = MTN, 6 = Telecel, 7 = AT (AirtelTigo).
 */
enum PaymentChannel: string
{
    case Mtn = 'mtn';
    case Telecel = 'telecel';
    case AirtelTigo = 'airteltigo';

    public function label(): string
    {
        return match ($this) {
            self::Mtn => 'MTN Mobile Money',
            self::Telecel => 'Telecel Cash',
            self::AirtelTigo => 'AirtelTigo Money',
        };
    }

    /** Numeric code Moolre expects in the `channel` field. */
    public function moolreCode(): string
    {
        return match ($this) {
            self::Mtn => '13',
            self::Telecel => '6',
            self::AirtelTigo => '7',
        };
    }

    /** Resolve a channel from a Moolre numeric code. */
    public static function fromMoolreCode(string|int $code): self
    {
        return match ((string) $code) {
            '13' => self::Mtn,
            '6' => self::Telecel,
            '7' => self::AirtelTigo,
            default => self::Mtn,
        };
    }
}
