<?php

namespace App\Http\Requests\Operator;

use App\Enums\ProductStatus;
use App\Models\BusinessCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * ProductRequest
 * --------------
 * Validates operator catalogue create/update. Ownership and the "approved
 * operator" gate are enforced by ProductPolicy on the controller, so this class
 * only concerns itself with field shape. On update, `status` and images are
 * optional (partial edits); on create, `name`/`price` are required.
 */
class ProductRequest extends FormRequest
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
        $creating = $this->isMethod('post');

        return [
            'name' => [$creating ? 'required' : 'sometimes', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => [$creating ? 'required' : 'sometimes', 'numeric', 'min:0', 'max:9999999.99'],
            'quantity' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'business_category_id' => [
                'nullable',
                Rule::exists(BusinessCategory::class, 'id'),
            ],
            'status' => ['sometimes', new Enum(ProductStatus::class)],
            'images' => ['sometimes', 'array', 'max:6'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
