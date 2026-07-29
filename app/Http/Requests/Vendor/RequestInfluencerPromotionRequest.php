<?php

namespace App\Http\Requests\Vendor;

use Illuminate\Foundation\Http\FormRequest;

class RequestInfluencerPromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'marketer_ids' => ['required', 'array', 'min:1'],
            'marketer_ids.*' => ['required', 'uuid', 'distinct', 'exists:marketers,id'],
            'vendor_note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
