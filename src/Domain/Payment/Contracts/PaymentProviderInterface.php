<?php

namespace Src\Domain\Payment\Contracts;

use Src\Domain\Payment\DTOs\PaymentRequestDto;
use Src\Domain\Payment\DTOs\PaymentResult;

/**
 * PaymentProviderInterface
 * ------------------------
 * Every payment gateway integration lives behind this contract so the rest of
 * the application depends only on the abstraction, never on a concrete gateway.
 * Adding a new provider means writing one class — no call site changes.
 */
interface PaymentProviderInterface
{
    /** Unique provider slug, e.g. "moolre" | "log". */
    public function name(): string;

    /**
     * Initiate (or continue) a collection. The same request may be submitted
     * more than once for one order: first to request an OTP, again with the OTP
     * to verify, and finally without an OTP to trigger the USSD prompt. The
     * gateway keys everything on the request's externalRef.
     */
    public function charge(PaymentRequestDto $request): PaymentResult;

    /**
     * Query the final/settled status of a collection by its externalRef.
     */
    public function status(string $externalRef): PaymentResult;
}
