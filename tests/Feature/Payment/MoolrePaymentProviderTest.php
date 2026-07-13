<?php

namespace Tests\Feature\Payment;

use App\Enums\PaymentChannel;
use App\Enums\PaymentStatus;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Src\Domain\Payment\DTOs\PaymentRequestDto;
use Src\Domain\Payment\Providers\MoolrePaymentProvider;
use Tests\TestCase;

/**
 * Exercises the Moolre payment provider against a mocked HTTP client so the
 * request shape and response parsing are verified without touching the network.
 */
class MoolrePaymentProviderTest extends TestCase
{
    /** @var array<int, array> */
    private array $history = [];

    private function provider(array $responses): MoolrePaymentProvider
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($this->history));

        $client = new Client([
            'handler' => $stack,
            'base_uri' => 'https://api.moolre.com/',
            'headers' => [
                'X-API-USER' => 'test-user',
                'X-API-PUBKEY' => 'test-pubkey',
            ],
        ]);

        return new MoolrePaymentProvider(
            ['base_uri' => 'https://api.moolre.com', 'api_user' => 'test-user', 'pub_key' => 'test-pubkey', 'account_number' => '10000123', 'timeout' => 5],
            $client,
        );
    }

    private function chargeDto(): PaymentRequestDto
    {
        return new PaymentRequestDto(
            amount: 25.0,
            payer: '+233201234567',
            channel: PaymentChannel::Mtn,
            externalRef: 'pay-ref-1',
            currency: 'GHS',
            accountNumber: '10000123',
        );
    }

    public function test_charge_otp_required_is_parsed_and_request_is_well_formed(): void
    {
        $provider = $this->provider([
            new Response(200, [], json_encode([
                'status' => 1,
                'code' => 'TP14',
                'message' => 'An OTP has been sent to the customer.',
                'data' => ['transactionid' => 'TX123'],
            ])),
        ]);

        $result = $provider->charge($this->chargeDto());

        $this->assertTrue($result->success);
        $this->assertTrue($result->otpRequired);
        $this->assertSame(PaymentStatus::AwaitingOtp, $result->status);
        $this->assertSame('TX123', $result->providerTransactionId);

        // Verify the outbound request matches the Moolre contract.
        $request = $this->history[0]['request'];
        $this->assertSame('POST', $request->getMethod());
        $this->assertStringEndsWith('/open/transact/payment', $request->getUri()->getPath());
        $this->assertSame('test-user', $request->getHeaderLine('X-API-USER'));
        $this->assertSame('test-pubkey', $request->getHeaderLine('X-API-PUBKEY'));

        $body = json_decode((string) $request->getBody(), true);
        $this->assertSame(1, $body['type']);
        $this->assertSame(PaymentChannel::Mtn->moolreCode(), $body['channel']);
        $this->assertSame('0201234567', $body['payer']); // Ghana local format — no +233
        $this->assertSame('pay-ref-1', $body['externalref']);
        $this->assertSame('10000123', $body['accountnumber']);
    }

    public function test_otp_verified_response_auto_initiates_payment(): void
    {
        // Step 2 (TP17 — number verified) is followed automatically by a second
        // POST that reaches step 3 (TR099 — payment initiated). The provider
        // drives both from a single charge() call carrying the OTP.
        $provider = $this->provider([
            new Response(200, [], json_encode([
                'status' => 1,
                'code' => 'TP17',
                'message' => 'The customer number has been verified.',
            ])),
            new Response(200, [], json_encode([
                'status' => 1,
                'code' => 'TR099',
                'message' => 'Payment initiated.',
                'data' => 'TX-INIT-1',
            ])),
        ]);

        $result = $provider->charge(new PaymentRequestDto(
            amount: 25.0,
            payer: '+233201234567',
            channel: PaymentChannel::Mtn,
            externalRef: 'pay-ref-2',
            currency: 'GHS',
            accountNumber: '10000123',
            otpCode: '123456',
        ));

        $this->assertTrue($result->success);
        $this->assertFalse($result->otpRequired);
        $this->assertSame(PaymentStatus::AwaitingApproval, $result->status);
        $this->assertSame('TR099', $result->code);
        $this->assertSame('TX-INIT-1', $result->providerTransactionId);

        // Two requests were made (verify → initiate) with the same OTP payload.
        $this->assertCount(2, $this->history);
        $second = json_decode((string) $this->history[1]['request']->getBody(), true);
        $this->assertSame('123456', $second['otpcode']);
    }

    public function test_charge_accepted_moves_to_awaiting_approval(): void
    {
        $provider = $this->provider([
            new Response(200, [], json_encode([
                'status' => 1,
                'code' => 'TP15',
                'message' => 'Prompt sent.',
                'data' => ['transactionid' => 'TX999'],
            ])),
        ]);

        $result = $provider->charge($this->chargeDto());

        $this->assertTrue($result->success);
        $this->assertFalse($result->otpRequired);
        $this->assertSame(PaymentStatus::AwaitingApproval, $result->status);
    }

    public function test_charge_failure_is_captured(): void
    {
        $provider = $this->provider([
            new Response(200, [], json_encode(['status' => 0, 'code' => 'TP01', 'message' => 'Insufficient funds'])),
        ]);

        $result = $provider->charge($this->chargeDto());

        $this->assertFalse($result->success);
        $this->assertSame(PaymentStatus::Failed, $result->status);
        $this->assertSame('TP01', $result->code);
        $this->assertSame('Insufficient funds', $result->message);
    }

    public function test_status_maps_txstatus_to_canonical_state(): void
    {
        $provider = $this->provider([
            new Response(200, [], json_encode([
                'status' => 1,
                'code' => 'OK',
                'data' => ['txstatus' => 1, 'transactionid' => 'TX500'],
            ])),
        ]);

        $result = $provider->status('pay-ref-1');

        $this->assertTrue($result->success);
        $this->assertSame(PaymentStatus::Successful, $result->status);
        $this->assertSame('TX500', $result->providerTransactionId);

        $request = $this->history[0]['request'];
        $this->assertStringEndsWith('/open/transact/status', $request->getUri()->getPath());
        $body = json_decode((string) $request->getBody(), true);
        $this->assertSame('externalref', $body['idtype']);
        $this->assertSame('pay-ref-1', $body['id']);
    }
}
