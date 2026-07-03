<?php

namespace Src\Domain\Payment\Providers;

use App\Enums\PaymentStatus;
use Illuminate\Support\Str;
use Src\Domain\Payment\Contracts\PaymentProviderInterface;
use Src\Domain\Payment\DTOs\PaymentRequestDto;
use Src\Domain\Payment\DTOs\PaymentResult;

/**
 * LogPaymentProvider
 * ------------------
 * Network-free provider for local development and the test-suite. It performs no
 * HTTP, writes an entry to the payment log, and returns deterministic results so
 * the escrow flow can be exercised end-to-end without moving real money.
 *
 * A charge is "accepted" and moves straight to AwaitingApproval; a status check
 * reports the collection as Successful, mirroring a happy-path settlement.
 */
final class LogPaymentProvider implements PaymentProviderInterface
{
    public function name(): string
    {
        return 'log';
    }

    public function charge(PaymentRequestDto $request): PaymentResult
    {
        $providerRef = 'LOG-' . Str::upper(Str::random(10));

        logger()->channel(config('payment.log_channel', 'moolre_paymentapi'))->info('[LOG payment] charge', [
            'externalref' => $request->externalRef,
            'payer' => $request->payer,
            'amount' => $request->amount,
            'channel' => $request->channel->value,
        ]);

        return PaymentResult::accepted(
            PaymentStatus::AwaitingApproval,
            'LOG',
            'Payment logged (no gateway).',
            $providerRef,
            raw: ['status' => 1, 'code' => 'LOG', 'data' => ['transactionid' => $providerRef]],
        );
    }

    public function status(string $externalRef): PaymentResult
    {
        return PaymentResult::accepted(
            PaymentStatus::Successful,
            'LOG',
            'Payment logged as successful (no gateway).',
            'LOG-' . Str::upper(Str::random(10)),
            raw: ['status' => 1, 'code' => 'LOG', 'data' => ['txstatus' => 1]],
        );
    }
}
