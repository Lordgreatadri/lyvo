<?php

namespace Src\Domain\Sms\Providers;

use App\Enums\SmsStatus;
use Src\Domain\Sms\Contracts\SmsProviderInterface;
use Src\Domain\Sms\DTOs\SmsMessageDto;
use Src\Domain\Sms\DTOs\SmsResult;

/**
 * LogSmsProvider
 * --------------
 * Zero-cost, network-free provider used locally and in the test-suite. It writes
 * each "sent" message to the log and always reports success, so registration,
 * OTP and admin flows can be exercised end-to-end without a real gateway or
 * SMS charges. Delivery status is reported as Sent.
 */
final class LogSmsProvider implements SmsProviderInterface
{
    public function name(): string
    {
        return 'log';
    }

    public function send(SmsMessageDto $message): SmsResult
    {
        logger()->channel(config('sms.log_channel'))->info('[SMS:log] outbound message', [
            'recipient' => $message->recipient,
            'sender_id' => $message->senderId,
            'ref' => $message->ref,
            'segments' => $message->segments(),
            'encoding' => $message->encoding(),
            'message' => $message->message,
        ]);

        return SmsResult::success('LOG', 'Message logged (log driver).', $message->ref, [
            'driver' => 'log',
            'ref' => $message->ref,
        ]);
    }

    public function sendBatch(array $messages): array
    {
        $results = [];

        foreach ($messages as $message) {
            $results[$message->ref] = $this->send($message);
        }

        return $results;
    }

    public function statuses(array $refs): array
    {
        $out = [];

        foreach ($refs as $ref) {
            $out[(string) $ref] = SmsStatus::Sent;
        }

        return $out;
    }

    public function balance(): array
    {
        return ['balance' => (float) config('sms.log_balance', 100000), 'raw' => ['driver' => 'log']];
    }

    public function senderIds(): array
    {
        return [[
            'id' => 1,
            'senderid' => config('sms.sender_id', 'LYVO'),
            'approval' => 'Approved',
            'whitelisted' => true,
        ]];
    }
}
