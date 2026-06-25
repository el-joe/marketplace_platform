<?php

namespace App\Http\Requests\Customer\Travel;

use Illuminate\Foundation\Http\FormRequest;

class CreateBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'travelers_count'    => ['required', 'integer', 'min:1', 'max:50'],
            'passport_file_path' => ['nullable', 'string', 'max:500'],
        ];
    }
}
