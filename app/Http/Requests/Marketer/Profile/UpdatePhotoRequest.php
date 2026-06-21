<?php

namespace App\Http\Requests\Marketer\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePhotoRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            // 'type' param distinguishes profile photo from banner
            'type' => ['required', 'in:profile_photo,banner'],
            'file' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
