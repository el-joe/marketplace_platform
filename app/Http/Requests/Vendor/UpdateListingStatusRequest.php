<?php

namespace App\Http\Requests\Vendor;

use Illuminate\Foundation\Http\FormRequest;

class UpdateListingStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Vendors may only self-serve active/paused; admin-gated statuses are excluded
        return [
            'status' => ['required', 'string', 'in:active,paused'],
        ];
    }
}
