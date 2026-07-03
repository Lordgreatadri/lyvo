<?php

namespace App\Support\Webhooks;

use Illuminate\Http\Request;
use Spatie\WebhookClient\SignatureValidator\SignatureValidator;
use Spatie\WebhookClient\WebhookConfig;

/**
 * MoolrePaymentSignatureValidator
 * -------------------------------
 * Validates that an incoming Moolre payment callback is authentic. Unlike the
 * SMS callback (which carries the secret in a header), the Moolre payment
 * webhook embeds the shared secret in the request BODY at `data.secret`, so the
 * check reads it from there. When no secret is configured (local development)
 * validation is skipped so callbacks can be simulated without ceremony. The
 * comparison is constant-time to avoid timing attacks.
 */
class MoolrePaymentSignatureValidator implements SignatureValidator
{
    public function isValid(Request $request, WebhookConfig $config): bool
    {
        $secret = (string) config('payment.webhook.secret', '');

        // No secret configured → accept (dev/sandbox). Enforce in production.
        if ($secret === '') {
            return true;
        }

        $provided = (string) $request->input('data.secret', '');

        return $provided !== '' && hash_equals($secret, $provided);
    }
}
