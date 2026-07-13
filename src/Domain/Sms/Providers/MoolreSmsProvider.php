<?php

namespace Src\Domain\Sms\Providers;

use App\Enums\SmsStatus;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Src\Domain\Sms\Contracts\SmsProviderInterface;
use Src\Domain\Sms\DTOs\SmsMessageDto;
use Src\Domain\Sms\DTOs\SmsResult;

/**
 * MoolreSmsProvider
 * -----------------
 * Integration with the Moolre SMS API (https://docs.moolre.com/#/sms-getting-started).
 *
 * Auth:     X-API-VASKEY request header.
 * Send:     POST /open/sms/send      { type:1, senderid, messages:[{recipient,message,ref}] }
 * Status:   POST /open/sms/status    { type:5, ref:[...] }
 * Balance:  POST /open/sms/status    { type:2 }
 * Senders:  POST /open/sms/status    { type:7 }
 *
 * A Moolre success is signalled by status === 1 in the JSON body (not merely a
 * 200 HTTP code), so every response is inspected on that field.
 */
final class MoolreSmsProvider implements SmsProviderInterface
{
    private const SEND_PATH = '/open/sms/send';
    private const STATUS_PATH = '/open/sms/status';

    private const TYPE_SEND = 1;
    private const TYPE_BALANCE = 2;
    private const TYPE_STATUS = 5;
    private const TYPE_SENDER_IDS = 7;

    private readonly Client $http;

    private readonly string $defaultSenderId;

    private readonly string $vasKey;

    /**
     * @param  array{base_uri:string, vas_key:?string, sender_id:string, timeout:int}  $config
     */
    public function __construct(array $config, ?Client $http = null)
    {
        $this->defaultSenderId = $config['sender_id'] ?? '';
        $this->vasKey = $config['vas_key'] ?? '';

        $this->http = $http ?? new Client([
            'base_uri' => rtrim($config['base_uri'] ?? 'https://api.moolre.com', '/') . '/',
            'timeout' => $config['timeout'] ?? 15,
            'headers' => [
                'X-API-VASKEY' => $this->vasKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ]);
    }

    public function name(): string
    {
        return 'moolre';
    }

    public function send(SmsMessageDto $message): SmsResult
    {
        return $this->sendBatch([$message])[$message->ref]
            ?? SmsResult::failure('ERROR', 'No result returned for message.', []);
    }

    public function sendBatch(array $messages): array
    {
        if ($messages === []) {
            return [];
        }

        // A batch shares one sender ID; Moolre accepts up to one senderid per call.
        $senderId = $messages[0]->senderId ?: $this->defaultSenderId;

        $payload = [
            'type' => self::TYPE_SEND,
            'senderid' => $senderId,
            'messages' => array_map(static fn (SmsMessageDto $m): array => [
                'recipient' => ltrim($m->recipient, '+'),
                'message' => $m->message,
                'ref' => $m->ref,
            ], $messages),
        ];

        try {
            $body = $this->post(self::SEND_PATH, $payload);
        } catch (RequestException $e) {
            return $this->failAll($messages, $this->exceptionResult($e));
        }

        $success = ((int) ($body['status'] ?? 0)) === 1;

        $result = $success
            ? SmsResult::success(
                (string) ($body['code'] ?? 'SMS01'),
                (string) ($body['message'] ?? 'Success'),
                null,
                $body,
            )
            : SmsResult::failure(
                (string) ($body['code'] ?? 'ERROR'),
                (string) ($body['message'] ?? 'Unknown error from Moolre.'),
                $body,
            );

            logger()->channel(config('sms.log_channel'))->info('Moolre send batch', [
                'success' => $success,
                'sender_id' => $senderId,
                'messages' => array_map(static fn (SmsMessageDto $m): array => [
                    'ref' => $m->ref,
                    'recipient' => $m->recipient,
                    'message' => $m->message,
                ], $messages),
                'result' => [
                    'code' => $result->status,
                    'message' => $result->message,
                    'provider_id' => $result->providerId,
                    'raw_response' => $result->rawResponse,
                ],
            ]);

        // Moolre returns a single status for the whole batch; fan it out per ref.
        return $this->mapAll($messages, $result);
    }

    public function statuses(array $refs): array
    {
        if ($refs === []) {
            return [];
        }

        try {
            $body = $this->post(self::STATUS_PATH, [
                'type' => self::TYPE_STATUS,
                'ref' => array_values($refs),
            ]);
        } catch (RequestException $e) {
            logger()->channel(config('sms.log_channel'))->error('Moolre status query failed', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }

        $statuses = [];

        foreach ($body['data'] ?? [] as $entry) {
            if (isset($entry['ref'])) {
                $statuses[(string) $entry['ref']] = SmsStatus::fromMoolre((int) ($entry['status'] ?? 0));
            }
        }

        return $statuses;
    }

    public function balance(): array
    {
        try {
            $body = $this->post(self::STATUS_PATH, ['type' => self::TYPE_BALANCE]);
        } catch (RequestException $e) {
            logger()->channel(config('sms.log_channel'))->error('Moolre balance query failed', [
                'error' => $e->getMessage(),
            ]);

            return ['balance' => 0.0, 'raw' => []];
        }

        logger()->channel(config('sms.log_channel'))->info('Moolre balance query', [
            'balance' => (float) ($body['data']['balance'] ?? 0),
            'raw' => $body,
        ]);

        return [
            'balance' => (float) ($body['data']['balance'] ?? 0),
            'raw' => $body,
        ];
    }

    public function senderIds(): array
    {
        try {
            $body = $this->post(self::STATUS_PATH, ['type' => self::TYPE_SENDER_IDS]);
        } catch (RequestException $e) {
            logger()->channel(config('sms.log_channel'))->error('Moolre sender-id query failed', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }

        logger()->channel(config('sms.log_channel'))->info('Moolre sender-id query', [
            'count' => count($body['data'] ?? []),
            'raw' => $body,
        ]);

        return array_map(static fn (array $entry): array => [
            'id' => isset($entry['id']) ? (int) $entry['id'] : null,
            'senderid' => (string) ($entry['senderid'] ?? ''),
            'approval' => (string) ($entry['approval'] ?? 'Unknown'),
            'whitelisted' => (bool) ($entry['whitelisted'] ?? false),
        ], $body['data'] ?? []);
    }

    /**
     * Perform a POST and decode the JSON body.
     *
     * @throws RequestException
     */
    private function post(string $path, array $payload): array
    {
        $response = $this->http->post(ltrim($path, '/'), [
            'json' => $payload,
            'headers' => [
                'X-API-VASKEY' => $this->vasKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ]);

        logger()->channel(config('sms.log_channel'))->info('Moolre POST request', [
            'path' => $path,
            'payload' => $payload,
            'status_code' => $response->getStatusCode(),
            'response_body' => (string) $response->getBody(),
        ]);

        return json_decode((string) $response->getBody(), true) ?? [];
    }

    private function exceptionResult(RequestException $e): SmsResult
    {
        $raw = [];

        if ($e->hasResponse()) {
            $raw = json_decode((string) $e->getResponse()->getBody(), true) ?? [];
        }

        logger()->channel(config('sms.log_channel'))->error('Moolre SMS request failed', [
            'error' => $e->getMessage(),
            'response' => $raw,
        ]);

        return SmsResult::failure(
            (string) ($raw['code'] ?? 'REQUEST_FAILED'),
            (string) ($raw['message'] ?? $e->getMessage()),
            $raw,
        );
    }

    /**
     * @param  array<int, SmsMessageDto>  $messages
     * @return array<string, SmsResult>
     */
    private function mapAll(array $messages, SmsResult $result): array
    {
        $out = [];

        foreach ($messages as $message) {
            $out[$message->ref] = $result;
        }

        return $out;
    }

    /**
     * @param  array<int, SmsMessageDto>  $messages
     * @return array<string, SmsResult>
     */
    private function failAll(array $messages, SmsResult $result): array
    {
        return $this->mapAll($messages, $result);
    }
}
