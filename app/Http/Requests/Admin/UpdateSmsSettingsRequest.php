<?php

namespace App\Http\Requests\Admin;

use App\Support\Permissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * UpdateSmsSettingsRequest
 * ------------------------
 * Validates the admin SMS configuration form: active gateway, default sender ID
 * and the low-credit alert threshold.
 */
class UpdateSmsSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(Permissions::SMS_MANAGE) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'provider' => ['required', 'string', Rule::in(array_keys(config('sms.providers', [])))],
            'sender_id' => ['nullable', 'string', 'max:11'],
            'low_credit_threshold' => ['required', 'integer', 'min:0', 'max:1000000'],
        ];
    }
}
