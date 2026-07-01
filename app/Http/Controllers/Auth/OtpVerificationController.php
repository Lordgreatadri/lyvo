<?php

namespace App\Http\Controllers\Auth;

use App\Enums\OtpChannel;
use App\Enums\OtpPurpose;
use App\Http\Controllers\Concerns\RedirectsUsers;
use App\Http\Controllers\Controller;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * OtpVerificationController
 * -------------------------
 * Unified email + phone verification using one-time codes. Replaces Breeze's
 * email-link flow so both contacts follow the same OTP experience. Codes are
 * logged locally during development (see App\Services\OtpService).
 *
 * The {channel} route segment is "email" or "phone".
 */
class OtpVerificationController extends Controller
{
    use RedirectsUsers;

    public function __construct(private readonly OtpService $otp)
    {
    }

    /**
     * Show the verification screen, or move the user on once fully verified.
     */
    public function show(Request $request): RedirectResponse|View
    {
        $user = $request->user();

        if ($user->isFullyVerified()) {
            return redirect()->route($user->homeRoute());
        }

        return view('auth.verify-otp', ['user' => $user]);
    }

    /**
     * (Re)send a code for the given channel.
     */
    public function send(Request $request, string $channel): RedirectResponse
    {
        $user = $request->user();
        [$otpChannel, $purpose] = $this->resolve($channel);

        if ($this->otp->isThrottled($user, $otpChannel, $purpose)) {
            return back()->with('status', 'Please wait a moment before requesting another code.');
        }

        $this->otp->send($user, $otpChannel, $purpose);

        return back()->with('status', 'A new '.$otpChannel->label().' code has been sent.');
    }

    /**
     * Verify a submitted code for the given channel.
     */
    public function verify(Request $request, string $channel): RedirectResponse
    {
        [$otpChannel, $purpose] = $this->resolve($channel);

        // Scope validation/verification errors to a per-channel bag so a failure on
        // one channel never surfaces under the other channel's form.
        $request->validateWithBag($channel, ['code' => ['required', 'string']]);

        $user = $request->user();

        if (! $this->otp->verify($user, $otpChannel, $purpose, $request->input('code'))) {
            return back()->withErrors([
                'code' => 'That code is invalid or has expired. Please try again.',
            ], $channel);
        }

        if ($otpChannel === OtpChannel::Email) {
            $user->markEmailAsVerified();
        } else {
            $user->markPhoneAsVerified();
        }

        if ($user->fresh()->isFullyVerified()) {
            return redirect()->route($user->homeRoute())
                ->with('status', 'Your account is fully verified.');
        }

        return back()->with('status', ucfirst($channel).' verified successfully.');
    }

    /**
     * Map a {channel} segment to its OtpChannel + OtpPurpose pair.
     *
     * @return array{0: OtpChannel, 1: OtpPurpose}
     */
    private function resolve(string $channel): array
    {
        return match ($channel) {
            'email' => [OtpChannel::Email, OtpPurpose::EmailVerification],
            'phone' => [OtpChannel::Sms, OtpPurpose::PhoneVerification],
            default => abort(404),
        };
    }
}
