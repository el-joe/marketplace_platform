<?php

namespace App\Http\Requests\Customer\PaymentMethod;

use App\Enums\PaymentMethodType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(PaymentMethodType::class)],
            'gateway' => ['required', 'string', 'max:50'],
            'gateway_token' => ['required', 'string', 'max:255'],
            'card_brand' => ['nullable', 'string', 'max:20'],
            'card_last4' => ['nullable', 'digits:4'],
            'card_exp_month' => ['nullable', 'integer', 'between:1,12'],
            'card_exp_year' => ['nullable', 'integer', 'min:' . date('Y')],
            'billing_address_id' => ['nullable', 'integer', 'exists:addresses,id'],
            'is_default' => ['boolean'],
        ];
    }
}
