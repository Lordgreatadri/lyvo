<?php

namespace Tests\Feature\Payout;

use App\Enums\PayoutChannel;
use App\Enums\PayoutStatus;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Src\Domain\Payout\DTOs\PayoutRequestDto;
use Src\Domain\Payout\Providers\MoolrePayoutProvider;
use Tests\TestCase;

/**
 * Exercises the Moolre payout provider against a mocked HTTP client so the
 * request shape and response parsing are verified without touching the network.
 */
class MoolrePayoutProviderTest extends TestCase
{
    /** @var array<int, array> */
    private array $history = [];

    private function provider(array $responses): MoolrePayoutProvider
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($this->history));

        $client = new Client([
            'handler' => $stack,
            'base_uri' => 'https://api.moolre.com/',
            'headers' => [
                'X-API-USER' => 'test-user',
                'X-API-KEY' => 'test-privkey',
            ],
        ]);

        return new MoolrePayoutProvider(
            ['base_uri' => 'https://api.moolre.com', 'api_user' => 'test-user', 'priv_key' => 'test-privkey', 'account_number' => '10000123', 'timeout' => 5],
            $client,
        );
    }

    public function test_validate_name_parses_the_recipient(): void
    {
        $provider = $this->provider([
            new Response(200, [], json_encode([
                'status' => 1,
                'code' => 'AVD01',
                'data' => 'AMA MENSAH',
            ])),
        ]);

        $result = $provider->validateName('0543645688', PayoutChannel::Mtn);

        $this->assertTrue($result->success);
        $this->assertSame('AMA MENSAH', $result->recipientName);

        $request = $this->history[0]['request'];
        $this->assertStringEndsWith('/open/transact/validate', $request->getUri()->getPath());
        $body = json_decode((string) $request->getBody(), true);
        $this->assertSame(PayoutChannel::Mtn->moolreCode(), $body['channel']);
        $this->assertSame('0543645688', $body['receiver']); // Ghana local format
    }

    public function test_transfer_is_well_formed_and_maps_status(): void
    {
        $provider = $this->provider([
            new Response(200, [], json_encode([
                'status' => 1,
                'code' => 'OBGH01',
                'message' => 'Transfer accepted.',
                'data' => ['txstatus' => 0, 'transactionid' => 'PO-1', 'receivername' => 'AMA MENSAH', 'fee' => 1.5],
            ])),
        ]);

        $result = $provider->transfer(PayoutRequestDto::make(
            amount: 100.0,
            receiver: '+233543645688',
            channel: PayoutChannel::Mtn,
            externalRef: 'payout-ref-1',
            accountNumber: '10000123',
        ));

        $this->assertTrue($result->success);
        $this->assertSame(PayoutStatus::Processing, $result->status);
        $this->assertSame('PO-1', $result->providerTransactionId);
        $this->assertSame('AMA MENSAH', $result->recipientName);
        $this->assertSame(1.5, $result->fee);

        $request = $this->history[0]['request'];
        $this->assertSame('POST', $request->getMethod());
        $this->assertStringEndsWith('/open/transact/transfer', $request->getUri()->getPath());
        $this->assertSame('test-user', $request->getHeaderLine('X-API-USER'));
        $this->assertSame('test-privkey', $request->getHeaderLine('X-API-KEY'));

        $body = json_decode((string) $request->getBody(), true);
        $this->assertSame(PayoutChannel::Mtn->moolreCode(), $body['channel']);
        $this->assertSame('0543645688', $body['receiver']); // +233 stripped to local
        $this->assertSame('payout-ref-1', $body['externalref']);
        $this->assertSame('10000123', $body['accountnumber']);
    }

    public function test_transfer_failure_is_captured(): void
    {
        $provider = $this->provider([
            new Response(200, [], json_encode([
                'status' => 0,
                'code' => 'OBGH09',
                'message' => ['Insufficient wallet balance'],
            ])),
        ]);

        $result = $provider->transfer(PayoutRequestDto::make(
            amount: 100.0,
            receiver: '0543645688',
            channel: PayoutChannel::Mtn,
            externalRef: 'payout-ref-2',
        ));

        $this->assertFalse($result->success);
        $this->assertSame(PayoutStatus::Failed, $result->status);
        $this->assertSame('OBGH09', $result->code);
        $this->assertSame('Insufficient wallet balance', $result->message);
    }

    public function test_status_maps_txstatus_to_canonical_state(): void
    {
        $provider = $this->provider([
            new Response(200, [], json_encode([
                'status' => 1,
                'code' => 'OK',
                'data' => ['txstatus' => 1, 'transactionid' => 'PO-9'],
            ])),
        ]);

        $result = $provider->status('payout-ref-1');

        $this->assertTrue($result->success);
        $this->assertSame(PayoutStatus::Successful, $result->status);
        $this->assertSame('PO-9', $result->providerTransactionId);

        $request = $this->history[0]['request'];
        $this->assertStringEndsWith('/open/transact/status', $request->getUri()->getPath());
    }
}
