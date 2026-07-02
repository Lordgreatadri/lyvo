<?php

namespace Tests\Feature\Sms;

use App\Enums\OtpChannel;
use App\Enums\OtpPurpose;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Confirms the OTP flow now routes phone codes through the central SmsService
 * (the single send_sms entry point), producing a durable sms_messages record.
 */
class OtpSmsDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_phone_otp_is_delivered_through_the_sms_service(): void
    {
        $user = User::factory()->create(['phone' => '0201234567']);

        app(OtpService::class)->send($user, OtpChannel::Sms, OtpPurpose::PhoneVerification);

        $this->assertDatabaseHas('sms_messages', [
            'recipient' => '+233201234567',
            'context' => 'otp',
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseCount('sms_messages', 1);
    }
}
