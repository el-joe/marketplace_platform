<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'address_id'         => ['required', 'integer', 'exists:addresses,id'],
            'payment_method'     => ['required', Rule::in(['card', 'wallet', 'cod', 'bnpl', 'bank_transfer'])],
            'coupon_code'        => ['nullable', 'string', 'max:50'],
            'wallet_amount_to_use' => ['nullable', 'integer', 'min:1'],
            'wallet_amount_used' => ['nullable', 'integer', 'min:0'],
            'customer_notes'     => ['nullable', 'string', 'max:500'],
            'idempotency_key'    => ['required', 'string', 'max:100'],
            'gateway_token'      => ['required_if:payment_method,card', 'nullable', 'string'],
            'gateway'            => ['required_if:payment_method,card', 'nullable', 'string', Rule::in(['thawani', 'stripe', 'tap'])],
            'gateway_code'       => ['nullable', 'string', 'max:50'],
            'warranty_selections' => ['nullable', 'array'],
            'warranty_selections.*.listing_id' => ['required_with:warranty_selections', 'uuid'],
            'warranty_selections.*.warranty_plan_id' => ['required_with:warranty_selections', 'uuid'],
            'loyalty_points_to_use' => ['nullable', 'numeric', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'wallet_amount_used.integer' => 'Wallet amount must be a whole number.',
            'wallet_amount_used.min'     => 'Wallet amount cannot be negative.',
            'loyalty_points_to_use.numeric' => 'Loyalty points must be a number.',
            'loyalty_points_to_use.min'     => 'Loyalty points to use must be at least 1.',
        ];
    }
}
