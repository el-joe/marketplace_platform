<?php

namespace App\Http\Requests\Vendor\Classified;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInquiryStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'required|in:contacted,closed',
        ];
    }
}
