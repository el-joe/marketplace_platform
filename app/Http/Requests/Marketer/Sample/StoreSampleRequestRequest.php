<?php

namespace App\Http\Requests\Marketer\Sample;

use Illuminate\Foundation\Http\FormRequest;

class StoreSampleRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vendor_listing_ids'    => ['required', 'array', 'min:1'],
            'vendor_listing_ids.*'  => ['uuid', 'exists:vendor_listings,id'],
            'notes'                 => ['nullable', 'string', 'max:1000'],
        ];
    }
}
