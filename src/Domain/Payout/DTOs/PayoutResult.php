<?php

namespace Src\Domain\Payout\DTOs;

use App\Enums\PayoutStatus;

/**
 * PayoutResult
 * ------------
 * Immutable value object returned by every PayoutProviderInterface call. It
 * captures whether the gateway accepted the request, the interpreted lifecycle
 * `status`, and — for a name validation — the resolved recipient name.
 */
readonly class PayoutResult
{
    public function __construct(
        public bool         $success,      // request accepted by the gateway
        public PayoutStatus $status,       // interpreted lifecycle state
        public string       $code,         // provider response code (e.g. OBGH01, AVD01)
        public string       $message,
        public ?string      $providerTransactionId = null,
        public ?string      $recipientName = null,   // resolved on validate / transfer
        public ?float       $fee = null,
        public array        $raw = [],
    ) {}

    /** The gateway accepted the request and moved it to $status. */
    public static function accepted(
        PayoutStatus $status,
        string $code,
        string $message,
        ?string $providerTransactionId = null,
        ?string $recipientName = null,
        ?float $fee = null,
        array $raw = [],
    ): self {
        return new self(
            success: true,
            status: $status,
            code: $code,
            message: $message,
            providerTransactionId: $providerTransactionId,
            recipientName: $recipientName,
            fee: $fee,
            raw: $raw,
        );
    }

    /** A successful name validation carrying the resolved recipient name. */
    public static function validated(string $recipientName, string $code = 'AVD01', array $raw = []): self
    {
        return new self(
            success: true,
            status: PayoutStatus::Pending,
            code: $code,
            message: 'Name validated.',
            recipientName: $recipientName,
            raw: $raw,
        );
    }

    /** The gateway rejected the request. */
    public static function failed(string $code, string $message, array $raw = []): self
    {
        return new self(
            success: false,
            status: PayoutStatus::Failed,
            code: $code,
            message: $message,
            raw: $raw,
        );
    }
}
