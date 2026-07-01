<?php

namespace Src\Domain\Sms\Providers;

// use App\Models\BranchSmsConfig;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Src\Domain\Sms\Contracts\SmsProviderInterface;
use Src\Domain\Sms\DTOs\SmsMessageDto;
use Src\Domain\Sms\DTOs\SmsResult;

/**
 * Twilio SMS provider.
 *
 * API base: https://api.twilio.com/2010-04-01
 * Auth:     HTTP Basic — Account SID as username, Auth Token as password
 *
 * Send endpoint:    POST /Accounts/{SID}/Messages.json
 * Balance endpoint: GET  /Accounts/{SID}/Balance.json
 */
final class TwilioSmsProvider implements SmsProviderInterface
{
    private const BASE_URL = 'https://api.twilio.com/2010-04-01';

    /** Twilio statuses that mean the message was accepted by the platform. */
    private const ACCEPTED_STATUSES = ['queued', 'sending', 'sent'];

    private readonly Client $http;

    public function __construct(
        // private readonly BranchSmsConfig $config
        private readonly string $config = ''
    )
    {
        $this->http = new Client([
            'base_uri' => self::BASE_URL,
            'timeout'  => 15,
            'auth'     => [$config->twilio_account_sid, $config->twilio_auth_token],
            'headers'  => ['Accept' => 'application/json'],
        ]);
    }

    public function name(): string
    {
        return 'twilio';
    }

    public function send(SmsMessageDto $message): SmsResult
    {
        $sid  = $this->config->twilio_account_sid;
        $path = "/Accounts/{$sid}/Messages.json";

        $payload = [
            'From' => $this->config->twilio_from_number,
            'To'   => $message->recipient,
            'Body' => $message->message,
        ];

        try {
            $response = $this->http->post($path, ['form_params' => $payload]);
            $body     = json_decode((string) $response->getBody(), true) ?? [];
            $status   = strtolower($body['status'] ?? '');

            $accepted = in_array($status, self::ACCEPTED_STATUSES, true);

            return $accepted
                ? SmsResult::success(
                    $body['status'],
                    $body['message'] ?? 'Message accepted by Twilio.',
                    $body['sid'] ?? null,
                    $body
                )
                : SmsResult::failure(
                    $body['status'] ?? 'ERROR',
                    $body['message'] ?? 'Unknown error from Twilio.',
                    $body
                );
        } catch (RequestException $e) {
            logger()->channel('twilioapi')->error('Twilio SMS request failed', [
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
        $sid  = $this->config->twilio_account_sid;
        $path = "/Accounts/{$sid}/Balance.json";

        try {
            $response = $this->http->get($path);

            return json_decode((string) $response->getBody(), true) ?? [];
        } catch (RequestException $e) {
            logger()->channel('twilioapi')->error('Twilio SMS balance check failed', [
                'error' => $e->getMessage(),
                'response' => $e->hasResponse() ? (string) $e->getResponse()->getBody() : null,
                'exception' => $e,
            ]);

            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
