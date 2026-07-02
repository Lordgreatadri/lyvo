<?php

namespace Tests\Feature\Sms;

use App\Enums\AccountType;
use App\Enums\UserStatus;
use App\Models\SmsSetting;
use App\Models\User;
use App\Notifications\LowSmsCreditNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * The scheduled balance check must alert admins only when credit is below the
 * configured threshold, and must throttle repeat alerts.
 */
class CheckSmsBalanceCommandTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'account_type' => AccountType::Admin->value,
            'status' => UserStatus::Active->value,
        ]);
    }

    public function test_admins_are_alerted_when_the_balance_is_below_threshold(): void
    {
        Notification::fake();
        $admin = $this->admin();

        // Log driver reports 100,000 credits; set the threshold above that.
        SmsSetting::current()->update(['low_credit_threshold' => 999999]);
        SmsSetting::flushCache();

        $this->artisan('sms:check-balance')->assertSuccessful();

        Notification::assertSentTo($admin, LowSmsCreditNotification::class);
        $this->assertNotNull(SmsSetting::current()->low_credit_alerted_at);
    }

    public function test_no_alert_when_balance_is_healthy(): void
    {
        Notification::fake();
        $this->admin();

        SmsSetting::current()->update(['low_credit_threshold' => 10]);
        SmsSetting::flushCache();

        $this->artisan('sms:check-balance')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_repeat_alerts_are_throttled(): void
    {
        Notification::fake();
        $this->admin();

        SmsSetting::current()->update([
            'low_credit_threshold' => 999999,
            'low_credit_alerted_at' => now()->subHour(), // alerted recently
        ]);
        SmsSetting::flushCache();

        $this->artisan('sms:check-balance')->assertSuccessful();

        Notification::assertNothingSent();
    }
}
