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
            'address_id'         => ['required', 'uuid', 'exists:addresses,id'],
            'shipping_method_id' => ['required', 'uuid', 'exists:shipping_methods,id'],
            'payment_method'     => ['required', Rule::in(['card', 'wallet', 'cod', 'bnpl', 'bank_transfer'])],
            'payment_token'      => ['nullable', 'string', 'max:500'],
            'idempotency_key'    => ['required', 'uuid'],
        ];
    }
}
