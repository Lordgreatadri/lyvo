<?php

namespace Src\Domain\Sms\DTOs;

use Illuminate\Support\Str;
use Src\Domain\Sms\Support\SmsEncoding;

/**
 * Immutable value object representing a single outbound SMS handed to a provider.
 */
readonly class SmsMessageDto
{
    public function __construct(
        /** Recipient phone number in international format, e.g. +233201234567 */
        public string $recipient,
        public string $message,
        /** Approved sender ID (e.g. "LYVO"), max 11 chars. */
        public string $senderId,
        /** Caller-supplied reference used to correlate delivery receipts. */
        public string $ref,
    ) {}

    /**
     * Build a DTO, generating a unique reference when one is not supplied.
     */
    public static function make(
        string $recipient,
        string $message,
        string $senderId,
        ?string $ref = null,
    ): self {
        return new self(
            recipient: $recipient,
            message: $message,
            senderId: $senderId,
            ref: $ref ?? (string) Str::uuid(),
        );
    }

    public function encoding(): string
    {
        return SmsEncoding::detect($this->message);
    }

    public function segments(): int
    {
        return SmsEncoding::segments($this->message);
    }
}
