<?php

return [
    'configs' => [
        [
            /*
             * Moolre SMS delivery receipts. Callbacks are validated against a
             * shared secret (MOOLRE_WEBHOOK_SECRET) and dispatched to a job that
             * reconciles each message's delivery status by reference.
             */
            'name' => 'moolre',
            'signing_secret' => env('MOOLRE_WEBHOOK_SECRET'),
            'signature_header_name' => env('MOOLRE_WEBHOOK_HEADER', 'X-Moolre-Signature'),
            'signature_validator' => \App\Support\Webhooks\MoolreSignatureValidator::class,
            'webhook_profile' => \Spatie\WebhookClient\WebhookProfile\ProcessEverythingWebhookProfile::class,
            'webhook_response' => \Spatie\WebhookClient\WebhookResponse\DefaultRespondsTo::class,
            'webhook_model' => \Spatie\WebhookClient\Models\WebhookCall::class,
            'store_headers' => [],
            'store_attachments' => false,
            'process_webhook_job' => \App\Jobs\ProcessMoolreSmsWebhookJob::class,
        ],
        [
            /*
             * Moolre payment settlement callbacks. Unlike the SMS callback, the
             * shared secret is carried in the request body (data.secret), so the
             * validator reads it from there rather than a header. The job
             * reconciles the referenced transaction's settlement status.
             */
            'name' => 'moolre-payment',
            'signing_secret' => env('MOOLRE_WEBHOOK_SECRET'),
            'signature_header_name' => 'X-Moolre-Signature',
            'signature_validator' => \App\Support\Webhooks\MoolrePaymentSignatureValidator::class,
            'webhook_profile' => \Spatie\WebhookClient\WebhookProfile\ProcessEverythingWebhookProfile::class,
            'webhook_response' => \Spatie\WebhookClient\WebhookResponse\DefaultRespondsTo::class,
            'webhook_model' => \Spatie\WebhookClient\Models\WebhookCall::class,
            'store_headers' => [],
            'store_attachments' => false,
            'process_webhook_job' => \App\Jobs\ProcessMoolrePaymentWebhookJob::class,
        ],
    ],

    /*
     * The integer amount of days after which models should be deleted.
     *
     * It deletes all records after 30 days. Set to null if no models should be deleted.
     */
    'delete_after_days' => 30,

    /*
     * Should a unique token be added to the route name
     */
    'add_unique_token_to_route_name' => false,
];
