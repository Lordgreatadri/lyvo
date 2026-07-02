<?php

namespace Tests\Feature\Sms;

use App\Enums\SmsStatus;
use App\Models\SmsMessage;
use App\Models\SmsSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Src\Domain\Sms\SmsService;
use Tests\TestCase;

/**
 * Covers the SmsService orchestration on the default (log) driver: message
 * persistence with encoding/segment cost, phone normalisation, status
 * reconciliation and balance caching.
 */
class SmsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_persists_a_message_with_cost_metadata(): void
    {
        $result = send_sms('0201234567', 'Hello from LYVO', 'marketing');

        $this->assertTrue($result->success);

        $this->assertDatabaseHas('sms_messages', [
            'recipient' => '+233201234567', // normalised to E.164
            'context' => 'marketing',
            'provider' => 'log',
            'status' => SmsStatus::Queued->value,
            'encoding' => 'gsm-7',
            'segments' => 1,
        ]);
    }

    public function test_long_message_records_multiple_segments(): void
    {
        send_sms('0201234567', str_repeat('a', 200), 'marketing');

        $this->assertSame(2, SmsMessage::query()->latest('id')->first()->segments);
    }

    public function test_apply_status_updates_the_row_and_timestamps(): void
    {
        send_sms('0201234567', 'Hi', 'otp');
        $message = SmsMessage::query()->latest('id')->first();

        app(SmsService::class)->applyStatus($message->ref, SmsStatus::Delivered);

        $message->refresh();
        $this->assertSame(SmsStatus::Delivered, $message->status);
        $this->assertNotNull($message->delivered_at);
    }

    public function test_balance_is_cached_after_the_first_lookup(): void
    {
        $first = app(SmsService::class)->balance();
        $this->assertFalse($first['cached']);

        $settings = SmsSetting::current();
        $this->assertNotNull($settings->balance_checked_at);

        $second = app(SmsService::class)->balance();
        $this->assertTrue($second['cached']);
    }
}
