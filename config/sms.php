<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default SMS Provider
    |--------------------------------------------------------------------------
    |
    | The provider used to deliver every outbound SMS. Each provider is fully
    | isolated behind Src\Domain\Sms\Contracts\SmsProviderInterface, so swapping
    | gateways never touches a single call site. Locally we default to the "log"
    | driver (writes to the log, performs no HTTP) which keeps development and
    | the automated test-suite free of network calls and real SMS charges.
    |
    | Supported: "moolre", "log"
    |
    */
    'default' => env('SMS_PROVIDER', 'log'),

    /*
    |--------------------------------------------------------------------------
    | Global Defaults
    |--------------------------------------------------------------------------
    */

    // Fallback sender ID when the admin has not stored one in sms_settings.
    'sender_id' => env('MOOLRE_SMS_SENDER_ID', 'LYVO'),

    // Country dialling code used to normalise local numbers to E.164 (Ghana=233).
    'country_code' => env('SMS_COUNTRY_CODE', '233'),

    // Default low-credit threshold (in SMS units) used to seed sms_settings.
    'low_credit_threshold' => (int) env('SMS_LOW_CREDIT_THRESHOLD', 100),

    // Minutes an account balance is cached before a fresh provider call is made.
    'balance_cache_minutes' => (int) env('SMS_BALANCE_CACHE_MINUTES', 15),

    // Minutes the approved sender-ID list is cached.
    'sender_ids_cache_minutes' => (int) env('SMS_SENDER_IDS_CACHE_MINUTES', 60),

    // Dedicated log channel for SMS diagnostics.
    'log_channel' => env('SMS_LOG_CHANNEL', config('logging.default', 'stack')),

    /*
    |--------------------------------------------------------------------------
    | Provider Credentials
    |--------------------------------------------------------------------------
    |
    | Secrets live in the environment only — never in the database — so they are
    | never editable from the admin UI and never leak into a config cache dump.
    |
    */
    'providers' => [

        'moolre' => [
            'base_uri'  => env('MOOLRE_SMS_BASE_URI', 'https://api.moolre.com'),
            'vas_key'   => env('MOOLRE_SMS_VASKEY'),
            'sender_id' => env('MOOLRE_SMS_SENDER_ID', env('SMS_SENDER_ID', 'LYVO')),
            'timeout'   => (int) env('MOOLRE_SMS_TIMEOUT', 25),
        ],

        'log' => [
            //
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Delivery Webhook
    |--------------------------------------------------------------------------
    |
    | Moolre posts delivery receipts back to us. The shared secret is compared
    | against the configured header by MoolreSignatureValidator. When no secret
    | is set (local dev) validation is skipped so callbacks can be simulated.
    |
    */
    'webhook' => [
        'secret'        => env('MOOLRE_WEBHOOK_SECRET'),
        'header'        => env('MOOLRE_WEBHOOK_HEADER', 'X-Moolre-Signature'),
    ],
];
