<?php

namespace App\Http\Requests\Customer;

use App\Enums\PaymentMethodType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * StorePaymentMethodRequest
 * -------------------------
 * Validates saving a non-sensitive payment instrument.
 */
class StorePaymentMethodRequest extends FormRequest
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
            'type' => ['required', new Enum(PaymentMethodType::class)],
            'provider' => ['required', 'string', 'max:100'],
            'account_name' => ['required', 'string', 'max:255'],
            'account_reference' => ['required', 'string', 'max:50'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }
}
