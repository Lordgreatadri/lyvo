<?php

namespace Src\Domain\Sms\Providers;

// use App\Models\BranchSmsConfig;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Src\Domain\Sms\Contracts\SmsProviderInterface;
use Src\Domain\Sms\DTOs\SmsMessageDto;
use Src\Domain\Sms\DTOs\SmsResult;

/**
 * Frog SMS (Wigal) provider.
 *
 * API base: https://frogapi.wigal.com.gh/api/v3
 * Auth:     API-KEY and USERNAME request headers
 *
 * Send endpoint:    POST /sms/send
 * Balance endpoint: GET  /balance
 */
final class FrogSmsProvider implements SmsProviderInterface
{
    private const BASE_URL     = 'https://frogapi.wigal.com.gh/api/v3/';
    private const SEND_PATH    = 'sms/send';
    private const BALANCE_PATH = 'balance';

    /** Frog returns this status string when a message is accepted. */
    private const ACCEPTED_STATUS = 'ACCEPTD';

    private readonly Client $http;

    public function __construct(
        // private readonly BranchSmsConfig $config
        private readonly string $config = ''
    )
    {
        $this->http = new Client([
            'base_uri' => self::BASE_URL,
            'timeout'  => 15,
            'headers'  => [
                'API-KEY'      => $config->frog_api_key,
                'USERNAME'     => $config->frog_username,
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ],
        ]);
    }

    public function name(): string
    {
        return 'frog';
    }

    public function send(SmsMessageDto $message): SmsResult
    {
        // Frog requires phone numbers WITHOUT the '+' prefix (e.g. "233201234567")
        $destination = ltrim($message->recipient, '+');

        $payload = [
            'senderid'     => $message->senderId,
            'destinations' => [
                [
                    'destination' => $destination,
                    'msgid'       => $message->msgId,
                ],
            ],
            'message' => $message->message,
            'smstype' => $message->smsType,
        ];

        try {
            $response = $this->http->post(self::SEND_PATH, ['json' => $payload]);
            $body     = json_decode((string) $response->getBody(), true) ?? [];

            $accepted = isset($body['status'])
                     && strtoupper((string) $body['status']) === self::ACCEPTED_STATUS;

            return $accepted
                ? SmsResult::success(
                    $body['status'],
                    $body['message'] ?? 'Message accepted for processing.',
                    $message->msgId,
                    $body
                )
                : SmsResult::failure(
                    $body['status'] ?? 'ERROR',
                    $body['message'] ?? 'Unknown error from Frog SMS.',
                    $body
                );
        } catch (RequestException $e) {
            logger()->channel('frogapi')->error('Frog SMS request failed', [
                'error' => $e->getMessage(),
                'payload' => $payload,
                'response' => $e->hasResponse() ? (string) $e->getResponse()->getBody() : null,
                'exception' => $e,
            ]);
            $raw = [];
            if ($e->hasResponse()) {
                $raw = json_decode((string) $e->getResponse()->getBody(), true) ?? [];
            }

            return SmsResult::failure('REQUEST_FAILED', $e->getMessage(), $raw);
        }
    }

    public function balance(): array
    {
        try {
            $response = $this->http->get(self::BALANCE_PATH);
            $body     = json_decode((string) $response->getBody(), true) ?? [];

            // Frog response: { status, message, data: { cashbalance, bundles, ... } }
            $data = $body['data'] ?? [];

            logger()->channel('frogapi')->info('Frog SMS balance check successful', [
                'cashbalance' => $data['cashbalance'] ?? null,
                'bundles'     => $data['bundles'] ?? null,
            ]);

            return [
                'raw_status'           => $body['status']   ?? null,
                'cashbalance'          => (float) ($data['cashbalance'] ?? 0),
                'paidcashbalance'      => (float) ($data['paidcashbalance'] ?? 0),
                'bundles'              => $data['bundles'] ?? [],
                'activebundleinvoices' => $data['activebundleinvoices'] ?? [],
            ];
        } catch (RequestException $e) {
            logger()->channel('frogapi')->error('Frog SMS balance check failed', [
                'error' => $e->getMessage(),
                'response' => $e->hasResponse() ? (string) $e->getResponse()->getBody() : null,
                'exception' => $e,
            ]);
            $raw = [];
            if ($e->hasResponse()) {
                $raw = json_decode((string) $e->getResponse()->getBody(), true) ?? [];
            }

            return array_merge(['status' => 'ERROR', 'message' => $e->getMessage()], $raw);
        }
    }
}
