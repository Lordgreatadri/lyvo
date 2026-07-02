<?php

namespace App\Services;

use App\Enums\OtpChannel;
use App\Enums\OtpPurpose;
use App\Models\User;
use App\Models\VerificationCode;
use App\Notifications\OtpNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * OtpService
 * ----------
 * Single entry point for issuing and verifying one-time codes used to verify a
 * user's email address and phone number.
 *
 * Delivery is intentionally abstracted: during local development every code is
 * written to the application log (config lyvo.otp.log_codes). Email codes also
 * go out via the mail channel. When the SMS provider is integrated later, only
 * the `deliver()` method changes — registration/verification flows stay intact.
 */
class OtpService
{
    /**
     * Issue a fresh code for the given user/channel/purpose and deliver it.
     * Any previous unconsumed codes for the same channel+purpose are invalidated.
     */
    public function send(User $user, OtpChannel $channel, OtpPurpose $purpose): VerificationCode
    {
        $destination = $channel === OtpChannel::Email ? $user->email : $user->phone;

        abort_if(blank($destination), 422, 'No '.$channel->label().' on file to send a code to.');

        // Invalidate outstanding codes so only the newest one is valid.
        VerificationCode::query()
            ->where('user_id', $user->id)
            ->where('channel', $channel->value)
            ->where('purpose', $purpose->value)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        $plainCode = $this->generateCode();

        $verification = VerificationCode::create([
            'user_id' => $user->id,
            'channel' => $channel,
            'purpose' => $purpose,
            'destination' => $destination,
            'code_hash' => Hash::make($plainCode),
            'attempts' => 0,
            'expires_at' => now()->addMinutes((int) config('lyvo.otp.expiry_minutes', 10)),
        ]);

        $this->deliver($user, $channel, $destination, $plainCode);

        return $verification;
    }

    /**
     * Verify a submitted code. Returns true and consumes the code on success.
     */
    public function verify(User $user, OtpChannel $channel, OtpPurpose $purpose, string $code): bool
    {
        $verification = VerificationCode::query()
            ->where('user_id', $user->id)
            ->where('channel', $channel->value)
            ->where('purpose', $purpose->value)
            ->whereNull('consumed_at')
            ->latest()
            ->first();

        if (! $verification || ! $verification->isUsable()) {
            return false;
        }

        if ($verification->attempts >= (int) config('lyvo.otp.max_attempts', 5)) {
            $verification->update(['consumed_at' => now()]);

            return false;
        }

        $verification->increment('attempts');

        if (! Hash::check($code, $verification->code_hash)) {
            return false;
        }

        $verification->update(['consumed_at' => now()]);

        return true;
    }

    /**
     * Whether the user must wait before another code can be requested.
     */
    public function isThrottled(User $user, OtpChannel $channel, OtpPurpose $purpose): bool
    {
        $throttle = (int) config('lyvo.otp.resend_throttle_seconds', 60);

        $latest = VerificationCode::query()
            ->where('user_id', $user->id)
            ->where('channel', $channel->value)
            ->where('purpose', $purpose->value)
            ->latest()
            ->first();

        return $latest && $latest->created_at->gt(now()->subSeconds($throttle));
    }

    protected function generateCode(): string
    {
        $length = (int) config('lyvo.otp.length', 6);
        $max = (10 ** $length) - 1;

        return str_pad((string) random_int(0, $max), $length, '0', STR_PAD_LEFT);
    }

    /**
     * Deliver the plaintext code. Email goes via the mail channel; SMS via the
     * central SmsService (send_sms) so the gateway is swappable in one place.
     * When code logging is enabled (local/dev) the code is also written to the
     * log for convenience.
     */
    protected function deliver(User $user, OtpChannel $channel, string $destination, string $code): void
    {
        if (config('lyvo.otp.log_codes', true)) {
            Log::channel(config('logging.default'))->info('[LYVO OTP] Verification code issued', [
                'reference' => Str::upper(Str::random(8)),
                'channel' => $channel->value,
                'destination' => $destination,
                'code' => $code,
            ]);
        }

        if ($channel === OtpChannel::Email) {
            $user->notify(new OtpNotification($code, $channel));

            return;
        }

        if ($channel === OtpChannel::Sms) {
            send_sms($destination, $this->smsBody($code), 'otp', $user->id);
        }
    }

    /**
     * Build the SMS body for an OTP. Kept plain GSM-7 (no emoji) so it always
     * fits in a single billable segment.
     */
    protected function smsBody(string $code): string
    {
        $minutes = (int) config('lyvo.otp.expiry_minutes', 10);

        return "Your LYVO verification code is {$code}. It expires in {$minutes} minutes. Do not share it.";
    }
}
