<?php

namespace App\Enums;

/**
 * OtpPurpose
 * ----------
 * Why a one-time code was issued. Keeping purpose explicit lets a single
 * `verification_codes` table safely serve registration, login step-up,
 * contact changes and password resets without codes leaking across flows.
 */
enum OtpPurpose: string
{
    case EmailVerification = 'email_verification';
    case PhoneVerification = 'phone_verification';

    public function label(): string
    {
        return match ($this) {
            self::EmailVerification => 'Email Verification',
            self::PhoneVerification => 'Phone Verification',
        };
    }
}
