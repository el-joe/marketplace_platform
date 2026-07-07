<?php

namespace App\Http\Requests\Marketer\Sample;

use Illuminate\Foundation\Http\FormRequest;

class SampleListRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', 'in:requested,approved,dispatched,received,rejected'],
            'page'   => ['nullable', 'integer', 'min:1'],
        ];
    }
}
