<?php

namespace App\Enums;

/**
 * PayoutChannel
 * -------------
 * The destination network a disbursement (transfer) is sent to. Each case
 * carries the numeric channel code Moolre expects on /open/transact/transfer
 * and /open/transact/validate — note these differ from the *collection* codes
 * (PaymentChannel), e.g. MTN is 1 for payouts but 13 for collections.
 *
 * Moolre transfer channel codes:
 *   1 = MTN, 6 = Telecel, 7 = AT (AirtelTigo), 2 = Instant Bank Transfer.
 */
enum PayoutChannel: string
{
    case Mtn = 'mtn';
    case Telecel = 'telecel';
    case AirtelTigo = 'airteltigo';
    case Bank = 'bank';

    public function label(): string
    {
        return match ($this) {
            self::Mtn => 'MTN Mobile Money',
            self::Telecel => 'Telecel Cash',
            self::AirtelTigo => 'AirtelTigo Money',
            self::Bank => 'Bank Transfer',
        };
    }

    /** Numeric code Moolre expects in the transfer/validate `channel` field. */
    public function moolreCode(): string
    {
        return match ($this) {
            self::Mtn => '1',
            self::Telecel => '6',
            self::AirtelTigo => '7',
            self::Bank => '2',
        };
    }

    /** True when the destination is a mobile-money wallet (vs a bank account). */
    public function isMobileMoney(): bool
    {
        return $this !== self::Bank;
    }

    /** Resolve a payout channel from a Moolre numeric code. */
    public static function fromMoolreCode(string|int $code): self
    {
        return match ((string) $code) {
            '1' => self::Mtn,
            '6' => self::Telecel,
            '7' => self::AirtelTigo,
            '2' => self::Bank,
            default => self::Mtn,
        };
    }

    /** Only the mobile-money channels (used for the operator momo payout UI). */
    public static function mobileMoneyCases(): array
    {
        return [self::Mtn, self::Telecel, self::AirtelTigo];
    }
}
