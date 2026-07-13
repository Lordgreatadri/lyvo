<?php

/**
 * LYVO global helpers.
 *
 * Loaded automatically via composer.json autoload.files. Keep this file lean —
 * only add functions used across multiple modules.
 */

use Src\Domain\Sms\DTOs\SmsResult;
use Src\Domain\Sms\SmsService;

if (! function_exists('sms')) {
    /**
     * Resolve the singleton SmsService from the container.
     *
     * Example:
     *   sms()->send('0201234567', 'Hello from LYVO!', 'marketing');
     */
    function sms(): SmsService
    {
        return app(SmsService::class);
    }
}

if (! function_exists('send_sms')) {
    /**
     * Reusable one-liner for sending an SMS anywhere in the application. This is
     * the single entry point every feature should use so the gateway can be
     * swapped centrally.
     *
     *   send_sms('0201234567', 'Your code is 123456', 'otp', $user->id);
     */
    function send_sms(string $recipient, string $message, string $context = 'manual', ?int $userId = null): SmsResult
    {
        return sms()->send($recipient, $message, $context, $userId);
    }
}

if (! function_exists('format_phone_for_sms')) {
    /**
     * Normalise a raw phone number to international E.164-ish format.
     *
     * Strips all non-digit characters, removes a leading zero, and prepends the
     * given country dialling code (default: 233 for Ghana).
     *
     *   format_phone_for_sms('0201234567')    → '+233201234567'
     *   format_phone_for_sms('+233201234567') → '+233201234567'
     *   format_phone_for_sms('233201234567')  → '+233201234567'
     */
    function format_phone_for_sms(string $phone, string $countryCode = '233'): string
    {
        // Strip all non-digit characters (spaces, dashes, +, parentheses, etc.)
        $digits = preg_replace('/\D/', '', $phone);

        // Remove international dialling prefix '00' (e.g. 0023320… → 23320…)
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        // Already in full international format (without leading +)
        if (str_starts_with($digits, $countryCode)) {
            return '+' . $digits;
        }

        // Local format with leading zero (e.g. 0201234567 → 233201234567)
        return '+' . $countryCode . ltrim($digits, '0');
    }
}

if (! function_exists('format_msisdn_local')) {
    /**
     * Normalise a raw mobile number to Ghana local format (leading zero, no
     * country code) — the shape the Moolre transaction API expects for the
     * `payer` / `receiver` fields.
     *
     *   format_msisdn_local('+233543645688') → '0543645688'
     *   format_msisdn_local('233543645688')  → '0543645688'
     *   format_msisdn_local('0543645688')    → '0543645688'
     *   format_msisdn_local('543645688')     → '0543645688'
     *   format_msisdn_local('')              → ''  (no digits → let callers reject)
     */
    function format_msisdn_local(string $phone, string $countryCode = '233'): string
    {
        // Strip everything except digits (spaces, dashes, +, parentheses, …).
        $digits = preg_replace('/\D/', '', $phone);

        // No usable digits — return empty so callers can fail validation cleanly
        // rather than emitting a bogus '0' MSISDN.
        if ($digits === '') {
            return '';
        }

        // Drop an international '00' dialling prefix (e.g. 00233… → 233…).
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        // Strip the country code if present, then re-apply the local leading zero.
        if ($countryCode !== '' && str_starts_with($digits, $countryCode)) {
            $digits = substr($digits, strlen($countryCode));
        }

        $local = ltrim($digits, '0');

        return $local === '' ? '' : '0' . $local;
    }
}
