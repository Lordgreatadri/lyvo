<?php

namespace Src\Domain\Payout\Providers;

use App\Enums\PayoutChannel;
use App\Enums\PayoutStatus;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Src\Domain\Payout\Contracts\PayoutProviderInterface;
use Src\Domain\Payout\DTOs\PayoutRequestDto;
use Src\Domain\Payout\DTOs\PayoutResult;

/**
 * MoolrePayoutProvider
 * --------------------
 * Integration with the Moolre transfer API (disbursements).
 *
 * Auth:      X-API-USER + X-API-KEY (the *private* key — required to initiate a
 *            transfer; name validation and status also accept the public key).
 * Validate:  POST /open/transact/validate { type:1, receiver, channel,
 *                                           sublistid?, currency, accountnumber }
 * Transfer:  POST /open/transact/transfer { type:1, channel, currency, amount,
 *                                           receiver, sublistid?, externalref,
 *                                           reference?, accountnumber }
 * Status:    POST /open/transact/status   { type:1, idtype, id, accountnumber }
 *
 * Momo receivers are sent in Ghana local format (e.g. 0543645688). A Moolre
 * success is status === 1 in the JSON body. Per Moolre guidance a transfer is
 * only failed when the response/status txstatus is explicitly 2.
 */
final class MoolrePayoutProvider implements PayoutProviderInterface
{
    private const VALIDATE_PATH = '/open/transact/validate';
    private const TRANSFER_PATH = '/open/transact/transfer';
    private const STATUS_PATH = '/open/transact/status';

    private const TYPE = 1;

    private readonly Client $http;

    private readonly string $accountNumber;

    private readonly string $countryCode;

    /**
     * @param  array{base_uri?:string, api_user?:?string, priv_key?:?string, account_number?:?string, country_code?:?string, timeout?:int}  $config
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
                // Transfers require the private key.
                'X-API-KEY' => (string) ($config['priv_key'] ?? ''),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ]);
    }

    public function name(): string
    {
        return 'moolre';
    }

    public function validateName(string $receiver, PayoutChannel $channel, ?string $sublistId = null): PayoutResult
    {
        $payload = array_filter([
            'type' => self::TYPE,
            'receiver' => $this->formatReceiver($receiver, $channel),
            'channel' => $channel->moolreCode(),
            'sublistid' => $sublistId,
            'currency' => 'GHS',
            'accountnumber' => $this->accountNumber,
        ], static fn ($v): bool => $v !== null && $v !== '');

        try {
            $body = $this->post(self::VALIDATE_PATH, $payload);
        } catch (RequestException $e) {
            return $this->exceptionResult($e);
        }

        if (((int) ($body['status'] ?? 0)) !== 1) {
            return PayoutResult::failed(
                (string) ($body['code'] ?? 'ERROR'),
                $this->flatten($body['message'] ?? 'The recipient name could not be validated.'),
                $body,
            );
        }

        return PayoutResult::validated(
            (string) ($body['data'] ?? ''),
            (string) ($body['code'] ?? 'AVD01'),
            $body,
        );
    }

    public function transfer(PayoutRequestDto $request): PayoutResult
    {
        $payload = array_filter([
            'type' => self::TYPE,
            'channel' => $request->channel->moolreCode(),
            'currency' => $request->currency,
            'amount' => (string) $request->amount,
            'receiver' => $this->formatReceiver($request->receiver, $request->channel),
            'sublistid' => $request->sublistId,
            'externalref' => $request->externalRef,
            'reference' => $request->reference,
            'accountnumber' => $request->accountNumber ?: $this->accountNumber,
        ], static fn ($v): bool => $v !== null && $v !== '');

        try {
            $body = $this->post(self::TRANSFER_PATH, $payload);
        } catch (RequestException $e) {
            return $this->exceptionResult($e);
        }

        if (((int) ($body['status'] ?? 0)) !== 1) {
            return PayoutResult::failed(
                (string) ($body['code'] ?? 'ERROR'),
                $this->flatten($body['message'] ?? 'The transfer was rejected by Moolre.'),
                $body,
            );
        }

        $data = is_array($body['data'] ?? null) ? $body['data'] : [];

        return PayoutResult::accepted(
            PayoutStatus::fromMoolreTxStatus((int) ($data['txstatus'] ?? 0)),
            (string) ($body['code'] ?? 'OK'),
            $this->flatten($body['message'] ?? 'Transfer accepted.'),
            isset($data['transactionid']) ? (string) $data['transactionid'] : null,
            isset($data['receivername']) ? (string) $data['receivername'] : null,
            isset($data['fee']) ? (float) $data['fee'] : null,
            $body,
        );
    }

    public function status(string $id, string $idType = '1'): PayoutResult
    {
        $payload = array_filter([
            'type' => self::TYPE,
            'idtype' => $idType,
            'id' => $id,
            'accountnumber' => $this->accountNumber,
        ], static fn ($v): bool => $v !== null && $v !== '');

        try {
            $body = $this->post(self::STATUS_PATH, $payload);
        } catch (RequestException $e) {
            return $this->exceptionResult($e);
        }

        if (((int) ($body['status'] ?? 0)) !== 1) {
            return PayoutResult::failed(
                (string) ($body['code'] ?? 'ERROR'),
                $this->flatten($body['message'] ?? 'Could not read the transfer status.'),
                $body,
            );
        }

        $data = is_array($body['data'] ?? null) ? $body['data'] : [];

        return PayoutResult::accepted(
            PayoutStatus::fromMoolreTxStatus((int) ($data['txstatus'] ?? 0)),
            (string) ($body['code'] ?? 'OK'),
            $this->flatten($body['message'] ?? 'Transfer status.'),
            isset($data['transactionid']) ? (string) $data['transactionid'] : null,
            null,
            null,
            $body,
        );
    }

    /** Momo receivers are sent in Ghana local format; bank accounts are left as-is. */
    private function formatReceiver(string $receiver, PayoutChannel $channel): string
    {
        return $channel->isMobileMoney()
            ? format_msisdn_local($receiver, $this->countryCode)
            : preg_replace('/\s+/', '', $receiver);
    }

    /** Moolre returns `message` as either a string or an array of lines. */
    private function flatten(mixed $message): string
    {
        return is_array($message) ? implode(' ', array_map('strval', $message)) : (string) $message;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function post(string $path, array $payload): array
    {
        $response = $this->http->post(ltrim($path, '/'), ['json' => $payload]);

        $decoded = json_decode((string) $response->getBody(), true);

        return is_array($decoded) ? $decoded : [];
    }

    private function exceptionResult(RequestException $e): PayoutResult
    {
        $body = [];

        if ($e->hasResponse()) {
            $decoded = json_decode((string) $e->getResponse()->getBody(), true);
            $body = is_array($decoded) ? $decoded : [];
        }

        logger()->channel(config('payment.log_channel', 'moolre_paymentapi'))
            ->error('Moolre payout request failed', ['error' => $e->getMessage(), 'body' => $body]);

        return PayoutResult::failed(
            (string) ($body['code'] ?? 'HTTP_ERROR'),
            $this->flatten($body['message'] ?? $e->getMessage()),
            $body,
        );
    }
}
