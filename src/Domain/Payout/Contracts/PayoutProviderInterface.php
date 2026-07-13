<?php

namespace Src\Domain\Payout\Contracts;

use App\Enums\PayoutChannel;
use Src\Domain\Payout\DTOs\PayoutRequestDto;
use Src\Domain\Payout\DTOs\PayoutResult;

/**
 * PayoutProviderInterface
 * -----------------------
 * Every disbursement gateway lives behind this contract so the rest of the
 * application depends only on the abstraction. Adding a provider means writing
 * one class — no call site changes.
 */
interface PayoutProviderInterface
{
    /** Unique provider slug, e.g. "moolre" | "log". */
    public function name(): string;

    /**
     * Confirm the registered name on a momo wallet or bank account before a
     * transfer. Accepts both public and private keys.
     */
    public function validateName(string $receiver, PayoutChannel $channel, ?string $sublistId = null): PayoutResult;

    /**
     * Send money to the recipient. Strictly requires the private API key.
     */
    public function transfer(PayoutRequestDto $request): PayoutResult;

    /**
     * Query the settled status of a transfer.
     *
     * @param  string  $idType  "1" = our externalref, "2" = Moolre generated id
     */
    public function status(string $id, string $idType = '1'): PayoutResult;
}
