<?php

namespace Src\Domain\Payment\DTOs;

use App\Enums\PaymentChannel;
use Illuminate\Support\Str;

/**
 * PaymentRequestDto
 * -----------------
 * Immutable description of one collection to hand to a payment provider. The
 * `externalRef` is our idempotency key — it is generated once when a
 * PaymentTransaction is created and reused across every OTP / retry submission
 * so the gateway treats them as the same order.
 */
readonly class PaymentRequestDto
{
    public function __construct(
        public float          $amount,
        public string         $payer,
        public PaymentChannel $channel,
        public string         $externalRef,
        public string         $currency = 'GHS',
        public ?string        $reference = null,
        public ?string        $otpCode = null,
        public ?string        $sessionId = null,
        public ?string        $accountNumber = null,
    ) {}

    /**
     * Build a request, generating an idempotency reference when none is given.
     */
    public static function make(
        float $amount,
        string $payer,
        PaymentChannel $channel,
        string $currency = 'GHS',
        ?string $reference = null,
        ?string $externalRef = null,
        ?string $accountNumber = null,
    ): self {
        return new self(
            amount: $amount,
            payer: $payer,
            channel: $channel,
            externalRef: $externalRef ?: (string) Str::uuid(),
            currency: $currency,
            reference: $reference,
            accountNumber: $accountNumber,
        );
    }

    /** Return a copy carrying an OTP code (for the verification submission). */
    public function withOtp(string $otpCode): self
    {
        return new self(
            amount: $this->amount,
            payer: $this->payer,
            channel: $this->channel,
            externalRef: $this->externalRef,
            currency: $this->currency,
            reference: $this->reference,
            otpCode: $otpCode,
            sessionId: $this->sessionId,
            accountNumber: $this->accountNumber,
        );
    }
}
