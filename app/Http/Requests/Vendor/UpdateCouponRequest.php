<?php

namespace App\Http\Requests\Vendor;

use App\Enums\CouponType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['terms_ar', 'terms_en'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([
                    $field => array_values(array_filter(array_map('trim', explode("\n", $this->input($field))))),
                ]);
            }
        }
    }

    public function rules(): array
    {
        $couponId = $this->route('id');

        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('coupons', 'code')->ignore($couponId)],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'title_ar' => ['nullable', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'terms_ar' => ['nullable', 'array'],
            'terms_ar.*' => ['string', 'max:500'],
            'terms_en' => ['nullable', 'array'],
            'terms_en.*' => ['string', 'max:500'],
            'max_orders_per_customer_per_month' => ['nullable', 'integer', 'min:1'],
            'type' => ['required', Rule::enum(CouponType::class)],
            'value' => [
                'required',
                'numeric',
                'min:0',
                Rule::when(fn () => $this->input('type') === 'percentage', ['max:100']),
                Rule::when(fn () => $this->input('type') === 'fixed_amount', ['gt:0']),
            ],
            'currency' => [
                Rule::when(fn () => in_array($this->input('type'), ['fixed_amount', 'bogo'], true), ['required'], ['nullable']),
                'string', 'size:3',
            ],
            'scope' => ['required', Rule::in(['vendor', 'product'])],
            'product_ids' => [
                Rule::when(fn () => $this->input('scope') === 'product', ['required', 'array', 'min:1'], ['nullable']),
            ],
            'product_ids.*' => ['uuid', 'exists:products,id'],
            'min_order_amount' => ['nullable', 'integer', 'min:0'],
            'max_discount' => ['nullable', 'integer', 'min:0'],
            'usage_limit_total' => ['nullable', 'integer', 'min:1'],
            'usage_limit_per_customer' => ['required', 'integer', 'min:1'],
            'valid_from' => ['required', 'date'],
            'valid_until' => ['required', 'date', 'after:valid_from'],
            'is_active' => ['boolean'],
            'is_stackable' => ['boolean'],
        ];
    }
}
