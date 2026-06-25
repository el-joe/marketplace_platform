<?php

namespace App\Http\Requests\Vendor\Classified;

use Illuminate\Foundation\Http\FormRequest;

class IndexClassifiedListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status'      => 'nullable|in:draft,pending_contract,pending_review,active,paused,sold,expired,rejected',
            'category_id' => 'nullable|exists:classified_categories,id',
            'page'        => 'nullable|integer|min:1',
        ];
    }
}
