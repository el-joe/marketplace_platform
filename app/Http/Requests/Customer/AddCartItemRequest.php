<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class AddCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vendor_listing_id'   => ['required', 'uuid', 'exists:vendor_listings,id'],
            'quantity'            => ['required', 'integer', 'min:1', 'max:999'],
            'shipping_method_id'  => ['nullable', 'uuid', 'exists:shipping_methods,id'],
        ];
    }
}
