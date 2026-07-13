<?php

namespace Src\Domain\Payout\Providers;

use App\Enums\PayoutChannel;
use App\Enums\PayoutStatus;
use Illuminate\Support\Str;
use Src\Domain\Payout\Contracts\PayoutProviderInterface;
use Src\Domain\Payout\DTOs\PayoutRequestDto;
use Src\Domain\Payout\DTOs\PayoutResult;

/**
 * LogPayoutProvider
 * -----------------
 * Network-free provider for local development and the test-suite. It performs no
 * HTTP, writes to the payment log, and returns deterministic results so the
 * escrow → payout flow can be exercised end-to-end without moving real money.
 *
 * Name validation returns a placeholder name; a transfer is "accepted" and moves
 * straight to Successful, mirroring a happy-path disbursement.
 */
final class LogPayoutProvider implements PayoutProviderInterface
{
    public function name(): string
    {
        return 'log';
    }

    public function validateName(string $receiver, PayoutChannel $channel, ?string $sublistId = null): PayoutResult
    {
        return PayoutResult::validated('LYVO TEST RECIPIENT', 'LOG', [
            'status' => 1, 'code' => 'LOG', 'data' => 'LYVO TEST RECIPIENT',
        ]);
    }

    public function transfer(PayoutRequestDto $request): PayoutResult
    {
        $providerRef = 'LOGPO-' . Str::upper(Str::random(10));

        logger()->channel(config('payment.log_channel', 'moolre_paymentapi'))->info('[LOG payout] transfer', [
            'externalref' => $request->externalRef,
            'receiver' => $request->receiver,
            'amount' => $request->amount,
            'channel' => $request->channel->value,
        ]);

        return PayoutResult::accepted(
            PayoutStatus::Successful,
            'LOG',
            'Payout logged as successful (no gateway).',
            $providerRef,
            'LYVO TEST RECIPIENT',
            0.0,
            ['status' => 1, 'code' => 'LOG', 'data' => ['txstatus' => 1, 'transactionid' => $providerRef]],
        );
    }

    public function status(string $id, string $idType = '1'): PayoutResult
    {
        return PayoutResult::accepted(
            PayoutStatus::Successful,
            'LOG',
            'Payout logged as successful (no gateway).',
            'LOGPO-' . Str::upper(Str::random(10)),
            null,
            null,
            ['status' => 1, 'code' => 'LOG', 'data' => ['txstatus' => 1]],
        );
    }
}
