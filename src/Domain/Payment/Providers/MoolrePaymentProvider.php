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
 * 200 HTTP code). The collection uses a three-step, OTP-gated flow:
 *   1. charge (empty otpcode)  → code TP14: an OTP is sent to the payer.
 *   2. charge (with otpcode)   → code TP17: the payer's number is verified.
 *   3. charge (same payload)   → code TR099: the payment is initiated and a
 *      provider transaction id is returned in `data`. Settlement then arrives
 *      asynchronously on the payment webhook.
 *
 * The payer number is sent in Ghana local format (e.g. 0543645688) — never the
 * +233 international form — which is what the Moolre transaction API expects.
 */
final class MoolrePaymentProvider implements PaymentProviderInterface
{
    private const PAYMENT_PATH = '/open/transact/payment';
    private const STATUS_PATH = '/open/transact/status';

    private const TYPE_PAYMENT = 1;
    private const TYPE_STATUS = 1;

    /** Response code returned when an OTP is required and has been sent. */
    private const CODE_OTP_REQUIRED = 'TP14';

    /** Response code returned once the payer's number has been verified. */
    private const CODE_OTP_VERIFIED = 'TP17';

    /** Response code returned once the payment has been initiated. */
    private const CODE_PAYMENT_INITIATED = 'TR099';

    private readonly Client $http;

    private readonly string $accountNumber;

    private readonly string $countryCode;

    /**
     * @param  array{base_uri?:string, api_user?:?string, pub_key?:?string, account_number?:?string, country_code?:?string, timeout?:int}  $config
     */
    public function __construct(array $config, ?Client $http = null)
    {
        $this->accountNumber = (string) ($config['account_number'] ?? '');
        $this->countryCode = (string) ($config['country_code'] ?? config('payment.country_code', '233'));

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
            'payer' => format_msisdn_local($request->payer, $this->countryCode),
            'amount' => (string) $request->amount,
            'externalref' => $request->externalRef,
            'otpcode' => $request->otpCode,
            'reference' => $request->reference,
            'sessionid' => $request->sessionId,
            'accountnumber' => $request->accountNumber ?: $this->accountNumber,
        ], static fn ($v): bool => $v !== null && $v !== '');

        return $this->send($payload);
    }

    /**
     * POST a charge payload and interpret Moolre's OTP-gated response. When the
     * payer's number has just been verified (TP17) the same payload is re-sent
     * once to initiate the payment (step 3), so the caller drives the whole flow
     * with a single submitOtp() call.
     *
     * @param  array<string, mixed>  $payload
     */
    private function send(array $payload, bool $allowVerifyRetry = true): PaymentResult
    {
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
        $providerRef = $this->resolveProviderRef($body);

        // TP14 → the payer must supply the OTP before the payment can proceed.
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

        // TP17 → the number is now verified; re-send the same payload once to
        // initiate the actual payment (Moolre step 3 → TR099).
        if ($code === self::CODE_OTP_VERIFIED && $allowVerifyRetry) {
            return $this->send($payload, allowVerifyRetry: false);
        }

        // TR099 (or any other accepted code) → the payment has been dispatched
        // and will settle asynchronously on the webhook.
        return PaymentResult::accepted(
            PaymentStatus::AwaitingApproval,
            $code ?: 'OK',
            (string) ($body['message'] ?? 'Payment prompt sent to the customer.'),
            $providerRef,
            raw: $body,
        );
    }

    /**
     * Extract a provider transaction id from a charge response. On TR099 the id
     * is the plain string in `data`; on other responses it is nested under
     * `data.transactionid`.
     *
     * @param  array<string, mixed>  $body
     */
    private function resolveProviderRef(array $body): ?string
    {
        $data = $body['data'] ?? null;

        if (is_array($data) && isset($data['transactionid'])) {
            return (string) $data['transactionid'];
        }

        if (is_string($data) && (string) ($body['code'] ?? '') === self::CODE_PAYMENT_INITIATED) {
            return $data;
        }

        return null;
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
