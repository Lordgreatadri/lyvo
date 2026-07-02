<?php

namespace Tests\Feature\Sms;

use App\Enums\SmsStatus;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Src\Domain\Sms\DTOs\SmsMessageDto;
use Src\Domain\Sms\Providers\MoolreSmsProvider;
use Tests\TestCase;

/**
 * Exercises the Moolre provider against a mocked HTTP client so the request
 * shape and response parsing are verified without touching the network.
 */
class MoolreSmsProviderTest extends TestCase
{
    /** @var array<int, array> */
    private array $history = [];

    private function provider(array $responses): MoolreSmsProvider
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($this->history));

        $client = new Client([
            'handler' => $stack,
            'base_uri' => 'https://api.moolre.com/',
        ]);

        return new MoolreSmsProvider(
            ['base_uri' => 'https://api.moolre.com', 'vas_key' => 'test-key', 'sender_id' => 'LYVO', 'timeout' => 5],
            $client,
        );
    }

    public function test_send_success_is_parsed_and_request_is_well_formed(): void
    {
        $provider = $this->provider([
            new Response(200, [], json_encode(['status' => 1, 'code' => 'SMS01', 'message' => 'Success'])),
        ]);

        $result = $provider->send(SmsMessageDto::make('+233201234567', 'Hi there', 'LYVO', 'ref-1'));

        $this->assertTrue($result->success);
        $this->assertSame('SMS01', $result->status);

        // Verify the outbound request matches the Moolre contract.
        $request = $this->history[0]['request'];
        $this->assertSame('POST', $request->getMethod());
        $this->assertStringEndsWith('/open/sms/send', $request->getUri()->getPath());
        $this->assertSame('test-key', $request->getHeaderLine('X-API-VASKEY'));

        $body = json_decode((string) $request->getBody(), true);
        $this->assertSame(1, $body['type']);
        $this->assertSame('LYVO', $body['senderid']);
        $this->assertSame('233201234567', $body['messages'][0]['recipient']); // '+' stripped
        $this->assertSame('ref-1', $body['messages'][0]['ref']);
    }

    public function test_send_failure_is_captured(): void
    {
        $provider = $this->provider([
            new Response(200, [], json_encode(['status' => 0, 'code' => 'ASMS07', 'message' => 'Invalid sender', 'data' => 'senderid'])),
        ]);

        $result = $provider->send(SmsMessageDto::make('+233201234567', 'Hi', 'LYVO', 'ref-2'));

        $this->assertFalse($result->success);
        $this->assertSame('ASMS07', $result->status);
        $this->assertSame('Invalid sender', $result->message);
    }

    public function test_balance_is_extracted(): void
    {
        $provider = $this->provider([
            new Response(200, [], json_encode(['status' => 1, 'code' => 'ASMQ03', 'data' => ['balance' => 1500]])),
        ]);

        $this->assertSame(1500.0, $provider->balance()['balance']);
    }

    public function test_statuses_map_to_canonical_states(): void
    {
        $provider = $this->provider([
            new Response(200, [], json_encode([
                'status' => 1,
                'data' => [
                    ['ref' => 'a', 'status' => 2],
                    ['ref' => 'b', 'status' => 3],
                    ['ref' => 'c', 'status' => 1],
                ],
            ])),
        ]);

        $statuses = $provider->statuses(['a', 'b', 'c']);

        $this->assertSame(SmsStatus::Delivered, $statuses['a']);
        $this->assertSame(SmsStatus::Failed, $statuses['b']);
        $this->assertSame(SmsStatus::Sent, $statuses['c']);
    }

    public function test_sender_ids_are_normalised(): void
    {
        $provider = $this->provider([
            new Response(200, [], json_encode([
                'status' => 1,
                'data' => [
                    ['id' => 1, 'senderid' => 'LYVO', 'approval' => 'Approved', 'whitelisted' => true],
                ],
            ])),
        ]);

        $senders = $provider->senderIds();

        $this->assertSame('LYVO', $senders[0]['senderid']);
        $this->assertSame('Approved', $senders[0]['approval']);
        $this->assertTrue($senders[0]['whitelisted']);
    }
}
