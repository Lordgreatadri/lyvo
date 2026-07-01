<?php

return [

    /*
    |--------------------------------------------------------------------------
    | One-Time Password (OTP) Verification
    |--------------------------------------------------------------------------
    |
    | LYVO verifies every email address and phone number with a short numeric
    | code. During local development codes are written to the application log
    | (channel = "log") so they can be copied without a real SMS/email provider.
    | When the SMS gateway is integrated later, only the delivery driver changes
    | — every call site (OtpService) stays the same.
    |
    */

    'otp' => [
        // Number of digits in a generated code.
        'length' => env('LYVO_OTP_LENGTH', 6),

        // Minutes a code remains valid after it is issued.
        'expiry_minutes' => env('LYVO_OTP_EXPIRY', 10),

        // Maximum verification attempts before a code is invalidated.
        'max_attempts' => env('LYVO_OTP_MAX_ATTEMPTS', 5),

        // Seconds a user must wait before requesting a new code (resend throttle).
        'resend_throttle_seconds' => env('LYVO_OTP_RESEND_THROTTLE', 60),

        // When true, the generated code is logged (local/dev). Disable in prod.
        'log_codes' => env('LYVO_OTP_LOGGING_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Customer Limits
    |--------------------------------------------------------------------------
    */

    'customer' => [
        // A customer may save at most this many delivery addresses.
        'max_delivery_addresses' => 3,
    ],

];
