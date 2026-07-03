<?php

namespace Src\Domain\Payment\DTOs;

use App\Enums\PaymentStatus;

/**
 * PaymentResult
 * -------------
 * Immutable value object returned by every PaymentProviderInterface call. It
 * captures both whether the gateway *accepted* the request and the interpreted
 * lifecycle `status` (which may still be non-terminal, e.g. awaiting approval).
 */
readonly class PaymentResult
{
    public function __construct(
        public bool          $success,        // request accepted by the gateway (not necessarily settled)
        public PaymentStatus $status,         // interpreted lifecycle state
        public string        $code,           // provider response code (e.g. TP14, SS01)
        public string        $message,
        public ?string       $providerTransactionId,
        public bool          $otpRequired,    // gateway is waiting for an OTP from the payer
        public array         $raw,            // full decoded response for auditing
    ) {}

    /** The gateway accepted the request and moved it to $status. */
    public static function accepted(
        PaymentStatus $status,
        string $code,
        string $message,
        ?string $providerTransactionId = null,
        bool $otpRequired = false,
        array $raw = [],
    ): self {
        return new self(
            success: true,
            status: $status,
            code: $code,
            message: $message,
            providerTransactionId: $providerTransactionId,
            otpRequired: $otpRequired,
            raw: $raw,
        );
    }

    /** The gateway rejected the request. */
    public static function failed(string $code, string $message, array $raw = []): self
    {
        return new self(
            success: false,
            status: PaymentStatus::Failed,
            code: $code,
            message: $message,
            providerTransactionId: null,
            otpRequired: false,
            raw: $raw,
        );
    }
}
