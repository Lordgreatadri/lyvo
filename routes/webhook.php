<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| External webhook routes
|--------------------------------------------------------------------------
|
| Central registry for every inbound callback from an external gateway
| (SMS, payments, etc.). Loaded by RouteServiceProvider with the "api"
| middleware group and the "api/webhooks" prefix, so routes are stateless
| (no CSRF/session) and every URL below resolves under /api/webhooks/*.
|
| Each provider is wired through the Spatie webhook-client:
|   1. Add a named config in config/webhook-client.php (signature validator,
|      profile, model and the ProcessWebhookJob that reconciles the payload).
|   2. Register the endpoint here with Route::webhooks($path, $configName).
|
| To onboard a new provider, add one line below — nothing else in this file.
|
*/

// Moolre SMS delivery receipts -> POST /api/webhooks/moolre/sms
Route::webhooks('moolre/sms', 'moolre');
