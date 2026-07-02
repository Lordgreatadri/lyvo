<?php

namespace App\Console\Commands;

use App\Enums\AccountType;
use App\Models\SmsSetting;
use App\Models\User;
use App\Notifications\LowSmsCreditNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;
use Src\Domain\Sms\SmsService;

/**
 * CheckSmsBalance
 * ---------------
 * Polls the gateway for the live SMS credit balance and, when it falls below the
 * admin-configured threshold, alerts every administrator. Alerts are throttled
 * to once every few hours so a sustained low balance does not spam inboxes.
 *
 * Scheduled hourly (see App\Console\Kernel).
 */
class CheckSmsBalance extends Command
{
    protected $signature = 'sms:check-balance {--force : Ignore the alert throttle}';

    protected $description = 'Check the SMS credit balance and alert admins when it is low';

    /** Minimum hours between repeat low-credit alerts. */
    private const ALERT_THROTTLE_HOURS = 6;

    public function handle(SmsService $sms): int
    {
        $settings = SmsSetting::current();

        $balance = $sms->balance(force: true)['balance'];
        $threshold = $settings->low_credit_threshold;

        $this->info("SMS balance: {$balance} (threshold: {$threshold}).");

        if ($balance >= $threshold) {
            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->shouldAlert($settings)) {
            $this->comment('Below threshold, but an alert was sent recently — skipping.');

            return self::SUCCESS;
        }

        $admins = User::query()
            ->where('account_type', AccountType::Admin->value)
            ->get();

        Notification::send($admins, new LowSmsCreditNotification((float) $balance, (int) $threshold));

        $settings->forceFill(['low_credit_alerted_at' => now()])->save();

        $this->warn("Low-credit alert sent to {$admins->count()} admin(s).");

        return self::SUCCESS;
    }

    private function shouldAlert(SmsSetting $settings): bool
    {
        return $settings->low_credit_alerted_at === null
            || $settings->low_credit_alerted_at->lt(now()->subHours(self::ALERT_THROTTLE_HOURS));
    }
}
