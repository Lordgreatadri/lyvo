<?php

/**
 * ChurchBridge global helpers.
 *
 * Loaded automatically via composer.json autoload.files.
 * Keep this file lean — only add functions used in multiple modules.
 */

// use App\Models\Branch;
// use Src\Domain\Sms\SmsService;

if (! function_exists('sms_service_for_branch')) {
    /**
     * Resolve a configured SmsService for the given branch.
     * Returns null when no active provider is configured — callers should
     * fall back to the system-default (e.g. Laravel notification channel).
     *
     * Example:
     *   $sms = sms_service_for_branch($branch);
     *   $sms?->send('+233201234567', 'Hello from ChurchBridge!', 'birthday');
     */
    // function sms_service_for_branch(Branch $branch): ?SmsService
    // {
    //     return SmsService::forBranch($branch);
    // }
}

if (! function_exists('format_phone_for_sms')) {
    /**
     * Normalise a raw phone number to international E.164-ish format.
     *
     * Strips all non-digit characters, removes a leading zero, and prepends
     * the given country dialling code (default: 233 for Ghana).
     *
     * Examples:
     *   format_phone_for_sms('0201234567')        → '+233201234567'
     *   format_phone_for_sms('+233201234567')      → '+233201234567'
     *   format_phone_for_sms('233201234567')        → '+233201234567'
     */
    function format_phone_for_sms(string $phone, string $countryCode = '233'): string
    {
        // Strip all non-digit characters (spaces, dashes, +, parentheses, etc.)
        $digits = preg_replace('/\D/', '', $phone);

        // Remove international dialling prefix '00' (e.g. 0023320... → 23320...)
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
