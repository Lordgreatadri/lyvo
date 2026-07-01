<?php

namespace Src\Domain\Sms\Contracts;

use Src\Domain\Sms\DTOs\SmsMessageDto;
use Src\Domain\Sms\DTOs\SmsResult;

interface SmsProviderInterface
{
    /** Unique provider slug: frog | twilio */
    public function name(): string;

    /** Send a single SMS message. */
    public function send(SmsMessageDto $message): SmsResult;

    /**
     * Retrieve account balance from the provider.
     *
     * Returns a raw associative array whose shape is provider-specific.
     * SmsService::normalizeBalanceResponse() standardises it before returning
     * to callers. Providers are NOT required to include 'status' or 'message'
     * keys; those are part of the HTTP response layer, not the domain contract.
     *
     * Frog shape:   cashbalance, paidcashbalance, bundles, activebundleinvoices
     * Twilio shape: balance, currency
     */
    public function balance(): array;
}
