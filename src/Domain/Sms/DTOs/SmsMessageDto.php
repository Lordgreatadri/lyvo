<?php

namespace Src\Domain\Sms\DTOs;

/**
 * Immutable value object representing a single outbound SMS.
 */
readonly class SmsMessageDto
{
    public function __construct(
        /** Recipient phone number in international format, e.g. +233201234567 */
        public string $recipient,
        public string $message,
        /** Approved sender ID (e.g. "LVYO") */
        public string $senderId,
        /** Caller-supplied message ID for correlation */
        public string $msgId,
        public string $smsType = 'text',
    ) {}
}
