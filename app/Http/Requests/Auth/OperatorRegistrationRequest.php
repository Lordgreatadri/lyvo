<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * OperatorRegistrationRequest
 * ---------------------------
 * Validates the full operator onboarding submission: business information,
 * login credentials, Ghana Card identity, and the verification video. All
 * file assets are stored via the Spatie media library on the OperatorProfile.
 */
class OperatorRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Step 1 — Business information & login credentials
            'business_name' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'], // owner full name
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'business_category' => ['required', 'exists:business_categories,slug'],
            'business_location' => ['required', 'string', 'max:255'],
            'business_description' => ['required', 'string', 'max:2000'],

            // Step 2 — Ghana Card identity verification
            'ghana_card_number' => ['required', 'string', 'max:50'],
            'ghana_card_front' => ['required', 'image', 'max:5120'], // 5MB
            'ghana_card_back' => ['required', 'image', 'max:5120'],

            // Step 3 — Video verification
            'verification_video' => ['required', 'file', 'mimetypes:video/mp4,video/quicktime,video/webm', 'max:51200'], // 50MB
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.unique' => 'An account already exists with this phone number.',
            'email.unique' => 'An account already exists with this email address.',
            'business_category.exists' => 'Please choose a valid business category.',
        ];
    }
}
