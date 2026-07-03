<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Payment Provider
    |--------------------------------------------------------------------------
    |
    | The gateway used to collect every platform payment. Each provider is fully
    | isolated behind Src\Domain\Payment\Contracts\PaymentProviderInterface, so
    | switching gateways (or adding a second one) never touches a call site.
    | Locally we default to the "log" driver (writes to the payment log, performs
    | no HTTP) which keeps development and the test-suite free of network calls
    | and real money movement.
    |
    | Supported: "moolre", "log"
    |
    */
    'default' => env('PAYMENT_PROVIDER', 'log'),

    /*
    |--------------------------------------------------------------------------
    | Global Defaults
    |--------------------------------------------------------------------------
    */

    // Settlement currency for the merchant wallet.
    'currency' => env('PAYMENT_CURRENCY', 'GHS'),

    // Country dialling code used to normalise local payer numbers (Ghana = 233).
    'country_code' => env('SMS_COUNTRY_CODE', '233'),

    // Minutes a queried transaction status is cached before re-checking.
    'status_cache_minutes' => (int) env('PAYMENT_STATUS_CACHE_MINUTES', 1),

    // Seconds the admin dashboard payments overview aggregates are cached.
    'overview_cache_seconds' => (int) env('PAYMENT_OVERVIEW_CACHE_SECONDS', 60),

    // Dedicated log channel for detailed payment-gateway debugging.
    'log_channel' => env('PAYMENT_LOG_CHANNEL', 'moolre_paymentapi'),

    /*
    |--------------------------------------------------------------------------
    | Provider Credentials
    |--------------------------------------------------------------------------
    |
    | Secrets live in the environment only — never in the database — so they are
    | never editable from the admin UI and never leak into a config cache dump.
    |
    | Moolre uses a Public key (X-API-PUBKEY) for collections and a Private key
    | (X-API-KEY) for payouts / sensitive account management. Collections use the
    | public key as the security best practice.
    |
    */
    'providers' => [

        'moolre' => [
            'base_uri'       => env('MOOLRE_PAY_BASE_URI', 'https://api.moolre.com'),
            'api_user'       => env('MOOLRE_PAY_API_USER'),
            'pub_key'        => env('MOOLRE_PAY_PUBKEY'),
            'priv_key'       => env('MOOLRE_PAY_PRIVKEY'),
            'account_number' => env('MOOLRE_PAY_ACCOUNT_NUMBER'),
            'email'          => env('MOOLRE_PAY_EMAIL'),
            'timeout'        => (int) env('MOOLRE_PAY_TIMEOUT', 30),
        ],

        'log' => [
            //
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Collection Webhook
    |--------------------------------------------------------------------------
    |
    | Moolre posts a payment callback to /api/webhooks/moolre/payment. Unlike the
    | SMS callback, the shared secret arrives in the JSON body (data.secret) — it
    | is compared by MoolrePaymentSignatureValidator. When no secret is set
    | (local dev) validation is skipped so callbacks can be simulated.
    |
    */
    'webhook' => [
        'secret' => env('MOOLRE_WEBHOOK_SECRET'),
    ],
];
