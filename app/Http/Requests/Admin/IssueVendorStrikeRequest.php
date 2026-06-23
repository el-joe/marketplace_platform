<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IssueVendorStrikeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:200'],
            'severity' => ['required', Rule::in(['warning', 'minor', 'major', 'critical'])],
            'description' => ['nullable', 'string', 'max:2000'],
            'expires_at' => ['nullable', 'date', 'after:today'],
        ];
    }
}
