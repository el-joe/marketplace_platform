<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class AddCartItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items'                        => ['required', 'array', 'min:1', 'max:50'],
            'items.*.vendor_listing_id'    => ['required', 'uuid', 'exists:vendor_listings,id'],
            'items.*.quantity'             => ['required', 'integer', 'min:1', 'max:999'],
        ];
    }
}
