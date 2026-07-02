<?php

namespace App\Support\Webhooks;

use Illuminate\Http\Request;
use Spatie\WebhookClient\SignatureValidator\SignatureValidator;
use Spatie\WebhookClient\WebhookConfig;

/**
 * MoolreSignatureValidator
 * ------------------------
 * Validates that an incoming Moolre delivery callback carries our shared secret
 * in the configured header. When no secret is configured (local development)
 * validation is skipped so callbacks can be simulated without ceremony. The
 * comparison is constant-time to avoid timing attacks.
 */
class MoolreSignatureValidator implements SignatureValidator
{
    public function isValid(Request $request, WebhookConfig $config): bool
    {
        $secret = (string) config('sms.webhook.secret', '');

        // No secret configured → accept (dev/sandbox). Enforce in production.
        if ($secret === '') {
            return true;
        }

        $header = (string) config('sms.webhook.header', 'X-Moolre-Signature');
        $provided = (string) $request->header($header, '');

        return $provided !== '' && hash_equals($secret, $provided);
    }
}
