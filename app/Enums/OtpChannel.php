<?php

namespace App\Enums;

/**
 * OtpChannel
 * ----------
 * Delivery channel for a one-time verification code. During local development
 * every code is written to the application log regardless of channel; the SMS
 * provider integration is added later without touching call sites.
 */
enum OtpChannel: string
{
    case Email = 'email';
    case Sms = 'sms';

    public function label(): string
    {
        return match ($this) {
            self::Email => 'Email',
            self::Sms => 'SMS',
        };
    }
}
