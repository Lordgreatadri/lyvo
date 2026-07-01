<?php

namespace App\Http\Controllers;

use App\Enums\OtpChannel;
use App\Enums\OtpPurpose;
use App\Http\Requests\ProfileUpdateRequest;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(private readonly OtpService $otp)
    {
    }

    /**
     * Display the user's profile settings (rendered inside their role dashboard).
     */
    public function edit(Request $request): View
    {
        return view('settings.profile', [
            'user' => $request->user(),
            'role' => $request->user()->account_type->value,
        ]);
    }

    /**
     * Update the user's profile information. Changing a contact detail resets its
     * verification and re-issues an OTP so the account stays trustworthy.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->validated());

        $emailChanged = $user->isDirty('email');
        $phoneChanged = $user->isDirty('phone');

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        if ($phoneChanged) {
            $user->phone_verified_at = null;
        }

        $user->save();

        if ($emailChanged) {
            $this->otp->send($user, OtpChannel::Email, OtpPurpose::EmailVerification);
        }

        if ($phoneChanged) {
            $this->otp->send($user, OtpChannel::Sms, OtpPurpose::PhoneVerification);
        }

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current-password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
