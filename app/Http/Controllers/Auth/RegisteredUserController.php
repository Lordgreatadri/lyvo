<?php

namespace App\Http\Controllers\Auth;

use App\Enums\AccountType;
use App\Enums\OtpChannel;
use App\Enums\OtpPurpose;
use App\Enums\UserStatus;
use App\Http\Controllers\Concerns\RedirectsUsers;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CustomerRegistrationRequest;
use App\Models\CustomerProfile;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    use RedirectsUsers;

    public function __construct(private readonly OtpService $otp)
    {
    }

    /**
     * Display the customer registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle a customer registration request.
     *
     * Creates the user (account_type = customer), its customer profile, assigns
     * the customer role, then issues email + phone OTP codes for verification.
     */
    public function store(CustomerRegistrationRequest $request): RedirectResponse
    {
        $user = DB::transaction(function () use ($request): User {
            $user = User::create([
                'account_type' => AccountType::Customer,
                'status' => UserStatus::Active,
                'name' => $request->validated('name'),
                'email' => $request->validated('email'),
                'phone' => $request->validated('phone'),
                'password' => $request->validated('password'),
            ]);

            CustomerProfile::create(['user_id' => $user->id]);

            $user->assignRole(AccountType::Customer->defaultRole());

            return $user;
        });

        event(new Registered($user));

        // Issue verification codes (logged locally; SMS later).
        $this->otp->send($user, OtpChannel::Email, OtpPurpose::EmailVerification);
        $this->otp->send($user, OtpChannel::Sms, OtpPurpose::PhoneVerification);

        Auth::login($user);

        return $this->redirectAfterAuth($user);
    }
}
