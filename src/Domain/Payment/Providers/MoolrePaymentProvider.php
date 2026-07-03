<?php

namespace Src\Domain\Payment\Providers;

use App\Enums\PaymentStatus;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Src\Domain\Payment\Contracts\PaymentProviderInterface;
use Src\Domain\Payment\DTOs\PaymentRequestDto;
use Src\Domain\Payment\DTOs\PaymentResult;

/**
 * MoolrePaymentProvider
 * ---------------------
 * Integration with the Moolre collection API (https://docs.moolre.com/#/initiate-payment).
 *
 * Auth:     X-API-USER + X-API-PUBKEY request headers (public key = collections).
 * Charge:   POST /open/transact/payment  { type:1, channel, currency, payer, amount,
 *                                          externalref, otpcode?, reference?, sessionid?,
 *                                          accountnumber }
 * Status:   POST /open/transact/status   { type:1, idtype:"externalref", id, accountnumber }
 *
 * A Moolre success is signalled by status === 1 in the JSON body (not merely a
 * 200 HTTP code). Response code TP14 means an OTP was required and sent to the
 * payer, so the caller must resubmit with the OTP.
 */
final class MoolrePaymentProvider implements PaymentProviderInterface
{
    private const PAYMENT_PATH = '/open/transact/payment';
    private const STATUS_PATH = '/open/transact/status';

    private const TYPE_PAYMENT = 1;
    private const TYPE_STATUS = 1;

    /** Response code returned when an OTP is required and has been sent. */
    private const CODE_OTP_REQUIRED = 'TP14';

    private readonly Client $http;

    private readonly string $accountNumber;

    /**
     * @param  array{base_uri?:string, api_user?:?string, pub_key?:?string, account_number?:?string, timeout?:int}  $config
     */
    public function __construct(array $config, ?Client $http = null)
    {
        $this->accountNumber = (string) ($config['account_number'] ?? '');

        $this->http = $http ?? new Client([
            'base_uri' => rtrim($config['base_uri'] ?? 'https://api.moolre.com', '/') . '/',
            'timeout' => $config['timeout'] ?? 30,
            'headers' => [
                'X-API-USER' => (string) ($config['api_user'] ?? ''),
                'X-API-PUBKEY' => (string) ($config['pub_key'] ?? ''),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ]);
    }

    public function name(): string
    {
        return 'moolre';
    }

    public function charge(PaymentRequestDto $request): PaymentResult
    {
        $payload = array_filter([
            'type' => self::TYPE_PAYMENT,
            'channel' => $request->channel->moolreCode(),
            'currency' => $request->currency,
            'payer' => ltrim($request->payer, '+'),
            'amount' => (string) $request->amount,
            'externalref' => $request->externalRef,
            'otpcode' => $request->otpCode,
            'reference' => $request->reference,
            'sessionid' => $request->sessionId,
            'accountnumber' => $request->accountNumber ?: $this->accountNumber,
        ], static fn ($v): bool => $v !== null && $v !== '');

        try {
            $body = $this->post(self::PAYMENT_PATH, $payload);
        } catch (RequestException $e) {
            return $this->exceptionResult($e);
        }

        if (((int) ($body['status'] ?? 0)) !== 1) {
            return PaymentResult::failed(
                (string) ($body['code'] ?? 'ERROR'),
                (string) ($body['message'] ?? 'Payment request was rejected by Moolre.'),
                $body,
            );
        }

        $code = (string) ($body['code'] ?? '');
        $data = is_array($body['data'] ?? null) ? $body['data'] : [];
        $providerRef = isset($data['transactionid']) ? (string) $data['transactionid'] : null;

        // TP14 → the payer must supply an OTP before the prompt can be triggered.
        if ($code === self::CODE_OTP_REQUIRED) {
            return PaymentResult::accepted(
                PaymentStatus::AwaitingOtp,
                $code,
                (string) ($body['message'] ?? 'An OTP has been sent to the customer.'),
                $providerRef,
                otpRequired: true,
                raw: $body,
            );
        }

        // Otherwise the USSD approval prompt has been dispatched to the handset.
        return PaymentResult::accepted(
            PaymentStatus::AwaitingApproval,
            $code ?: 'OK',
            (string) ($body['message'] ?? 'Payment prompt sent to the customer.'),
            $providerRef,
            raw: $body,
        );
    }

    public function status(string $externalRef): PaymentResult
    {
        $payload = array_filter([
            'type' => self::TYPE_STATUS,
            'idtype' => 'externalref',
            'id' => $externalRef,
            'accountnumber' => $this->accountNumber,
        ], static fn ($v): bool => $v !== null && $v !== '');

        try {
            $body = $this->post(self::STATUS_PATH, $payload);
        } catch (RequestException $e) {
            return $this->exceptionResult($e);
        }

        if (((int) ($body['status'] ?? 0)) !== 1) {
            return PaymentResult::failed(
                (string) ($body['code'] ?? 'ERROR'),
                (string) ($body['message'] ?? 'Could not read the transaction status.'),
                $body,
            );
        }

        $data = is_array($body['data'] ?? null) ? $body['data'] : [];
        $status = PaymentStatus::fromMoolreTxStatus((int) ($data['txstatus'] ?? 0));
        $providerRef = isset($data['transactionid']) ? (string) $data['transactionid'] : null;

        return PaymentResult::accepted(
            $status,
            (string) ($body['code'] ?? 'OK'),
            (string) ($body['message'] ?? $status->label()),
            $providerRef,
            raw: $body,
        );
    }

    /**
     * POST JSON and decode the response body.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function post(string $path, array $payload): array
    {
        $response = $this->http->post(ltrim($path, '/'), ['json' => $payload]);

        $decoded = json_decode((string) $response->getBody(), true);

        return is_array($decoded) ? $decoded : [];
    }

    private function exceptionResult(RequestException $e): PaymentResult
    {
        $body = [];

        if ($e->hasResponse()) {
            $decoded = json_decode((string) $e->getResponse()->getBody(), true);
            $body = is_array($decoded) ? $decoded : [];
        }

        logger()->channel(config('payment.log_channel', 'moolre_paymentapi'))
            ->error('Moolre payment request failed', ['error' => $e->getMessage(), 'body' => $body]);

        return PaymentResult::failed(
            (string) ($body['code'] ?? 'HTTP_ERROR'),
            (string) ($body['message'] ?? $e->getMessage()),
            $body,
        );
    }
}
