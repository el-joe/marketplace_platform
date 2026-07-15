<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutPrepareRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'address_id'         => ['required', 'integer', 'exists:addresses,id'],
            'shipping_method_id' => ['required', 'uuid', 'exists:shipping_methods,id'],
            'payment_method'     => ['required', Rule::in(['card', 'wallet', 'cod', 'bnpl', 'bank_transfer'])],
            'coupon_code'        => ['nullable', 'string', 'max:50'],
            'gift_card_code'     => ['nullable', 'string', 'max:20'],
            'warranty_selections' => ['nullable', 'array'],
            'warranty_selections.*.listing_id' => ['required_with:warranty_selections', 'uuid'],
            'warranty_selections.*.warranty_plan_id' => ['required_with:warranty_selections', 'uuid'],
        ];
    }
}
