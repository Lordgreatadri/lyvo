<?php

namespace App\Http\Controllers\Auth;

use App\Enums\AccountType;
use App\Enums\OperatorVerificationStatus;
use App\Enums\OtpChannel;
use App\Enums\OtpPurpose;
use App\Enums\UserStatus;
use App\Http\Controllers\Concerns\RedirectsUsers;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\OperatorRegistrationRequest;
use App\Models\BusinessCategory;
use App\Models\OperatorProfile;
use App\Models\OperatorVerificationEvent;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * OperatorRegistrationController
 * ------------------------------
 * Drives the operator onboarding wizard: business info + credentials, Ghana Card
 * identity, and the verification video. The account is created immediately but
 * the operator dashboard stays locked (verification_status = pending) until an
 * admin approves it. All file uploads are stored via the Spatie media library.
 */
class OperatorRegistrationController extends Controller
{
    use RedirectsUsers;

    public function __construct(private readonly OtpService $otp)
    {
    }

    /**
     * Show the operator onboarding wizard.
     */
    public function create(): View
    {
        return view('operator.register', [
            'categories' => BusinessCategory::active()->orderBy('sort_order')->get(),
        ]);
    }

    /**
     * Handle the operator onboarding submission.
     */
    public function store(OperatorRegistrationRequest $request): RedirectResponse
    {
        $category = BusinessCategory::where('slug', $request->validated('business_category'))->first();

        $user = DB::transaction(function () use ($request, $category): User {
            $user = User::create([
                'account_type' => AccountType::Operator,
                'status' => UserStatus::Active,
                'name' => $request->validated('name'),
                'email' => $request->validated('email'),
                'phone' => $request->validated('phone'),
                'password' => $request->validated('password'),
            ]);

            $profile = OperatorProfile::create([
                'user_id' => $user->id,
                'business_category_id' => $category?->id,
                'business_name' => $request->validated('business_name'),
                'owner_full_name' => $request->validated('name'),
                'business_location' => $request->validated('business_location'),
                'business_description' => $request->validated('business_description'),
                'ghana_card_number' => $request->validated('ghana_card_number'),
                'verification_status' => OperatorVerificationStatus::Pending,
                'ghana_card_submitted_at' => now(),
                'video_submitted_at' => now(),
                'submitted_at' => now(),
            ]);

            // Store identity & verification assets in the media library.
            $profile->addMediaFromRequest('ghana_card_front')->toMediaCollection('ghana_card_front');
            $profile->addMediaFromRequest('ghana_card_back')->toMediaCollection('ghana_card_back');
            $profile->addMediaFromRequest('verification_video')->toMediaCollection('verification_video');

            // Seed the verification audit trail.
            OperatorVerificationEvent::create([
                'operator_profile_id' => $profile->id,
                'actor_id' => $user->id,
                'from_status' => null,
                'to_status' => OperatorVerificationStatus::Pending->value,
                'notes' => 'Operator submitted application for review.',
            ]);

            $user->assignRole(AccountType::Operator->defaultRole());

            return $user;
        });

        event(new Registered($user));

        $this->otp->send($user, OtpChannel::Email, OtpPurpose::EmailVerification);
        $this->otp->send($user, OtpChannel::Sms, OtpPurpose::PhoneVerification);

        Auth::login($user);

        return $this->redirectAfterAuth($user);
    }
}
