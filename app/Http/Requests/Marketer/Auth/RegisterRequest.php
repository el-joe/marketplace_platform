<?php

namespace App\Http\Requests\Marketer\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'                  => ['required', 'string', 'max:255'],
            'email'                 => ['required', 'email', 'max:255', 'unique:marketers,email'],
            'password'              => ['required', 'string', 'confirmed', Password::min(8)],
            'password_confirmation' => ['required', 'string'],
            'phone'                 => ['nullable', 'string', 'max:30'],
            'type'                  => ['required', 'in:influencer,celebrity,affiliate,brand_ambassador'],
            'country_id'            => ['required', 'uuid', 'exists:countries,id'],
            'followers_count'       => ['nullable', 'integer', 'min:0'],
            'niche'                 => ['nullable', 'string', 'max:100'],
            'bio'                   => ['nullable', 'string', 'max:2000'],
            // social_links accepted as structured object; each field optional URL
            'social_links'                    => ['nullable', 'array'],
            'social_links.instagram'          => ['nullable', 'url', 'max:255'],
            'social_links.tiktok'             => ['nullable', 'url', 'max:255'],
            'social_links.youtube'            => ['nullable', 'url', 'max:255'],
            'social_links.twitter'            => ['nullable', 'url', 'max:255'],
            'social_links.facebook'           => ['nullable', 'url', 'max:255'],
        ];
    }
}
