<?php

namespace Src\Domain\Payout\DTOs;

use App\Enums\PayoutChannel;
use Illuminate\Support\Str;

/**
 * PayoutRequestDto
 * ----------------
 * Immutable description of one disbursement to hand to a payout provider. The
 * `externalRef` is our idempotency key — generated once when a Payout row is
 * created so the gateway treats retries as the same transfer.
 */
readonly class PayoutRequestDto
{
    public function __construct(
        public float         $amount,
        public string        $receiver,
        public PayoutChannel $channel,
        public string        $externalRef,
        public string        $currency = 'GHS',
        public ?string       $reference = null,
        public ?string       $sublistId = null,
        public ?string       $accountNumber = null,
    ) {}

    public static function make(
        float $amount,
        string $receiver,
        PayoutChannel $channel,
        string $currency = 'GHS',
        ?string $reference = null,
        ?string $externalRef = null,
        ?string $sublistId = null,
        ?string $accountNumber = null,
    ): self {
        return new self(
            amount: $amount,
            receiver: $receiver,
            channel: $channel,
            externalRef: $externalRef ?: (string) Str::uuid(),
            currency: $currency,
            reference: $reference,
            sublistId: $sublistId,
            accountNumber: $accountNumber,
        );
    }
}
