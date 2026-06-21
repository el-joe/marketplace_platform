<?php

namespace App\Http\Requests\Delivery\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email_or_phone' => ['required', 'string'],
            'password'       => ['required', 'string'],
        ];
    }
}
