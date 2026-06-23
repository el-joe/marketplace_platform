<?php

namespace App\Http\Requests\Marketer\QrCode;

use Illuminate\Foundation\Http\FormRequest;

class StoreQrCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type'               => 'required|in:profile,product,store,campaign',
            'target_campaign_id' => 'nullable|uuid|exists:marketer_campaigns,id',
            'target_listing_id'  => 'nullable|uuid|exists:vendor_listings,id',
            'label'              => 'nullable|string|max:150',
        ];
    }
}
