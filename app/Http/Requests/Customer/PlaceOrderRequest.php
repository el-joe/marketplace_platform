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
            'shipping_method_id' => ['required', 'uuid', 'exists:shipping_methods,id'],
            'payment_method'     => ['required', Rule::in(['card', 'wallet', 'cod', 'bnpl', 'bank_transfer'])],
            'coupon_code'        => ['nullable', 'string', 'max:50'],
            'gift_card_code'     => ['nullable', 'string', 'max:20'],
            'customer_notes'     => ['nullable', 'string', 'max:500'],
            'idempotency_key'    => ['required', 'string', 'max:100'],
            'gateway_token'      => ['required_if:payment_method,card', 'nullable', 'string'],
            'gateway'            => ['required_if:payment_method,card', 'nullable', 'string', Rule::in(['thawani', 'stripe', 'tap'])],
        ];
    }
}
