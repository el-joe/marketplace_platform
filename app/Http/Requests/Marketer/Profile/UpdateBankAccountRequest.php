<?php

namespace App\Http\Requests\Marketer\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBankAccountRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'account_holder_name' => ['required', 'string', 'max:255'],
            'bank_name'           => ['required', 'string', 'max:150'],
            'iban'                => ['required', 'string', 'max:50'],
            // currency accepted but not persisted to a column yet — stored for future use
            'currency'            => ['sometimes', 'nullable', 'string', 'size:3'],
        ];
    }
}
