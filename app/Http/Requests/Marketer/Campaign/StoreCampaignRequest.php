<?php

namespace App\Http\Requests\Marketer\Campaign;

use Illuminate\Foundation\Http\FormRequest;

class StoreCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vendor_id'              => ['required', 'uuid', 'exists:vendors,id'],
            'name'                   => ['required', 'string', 'max:200'],
            'product_listing_ids'    => ['required', 'array', 'min:1'],
            'product_listing_ids.*'  => ['uuid', 'exists:vendor_listings,id'],
            'requested_message'      => ['nullable', 'string', 'max:1000'],
        ];
    }
}
