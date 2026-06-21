<?php

namespace App\Http\Requests\Marketer\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        // name/email/phone are freely editable (no admin re-review required for marketers,
        // unlike vendor legal fields which trigger re-review).
        return [
            'name'                   => ['sometimes', 'string', 'max:255'],
            'phone'                  => ['sometimes', 'nullable', 'string', 'max:30'],
            'bio'                    => ['sometimes', 'nullable', 'string', 'max:2000'],
            'niche'                  => ['sometimes', 'nullable', 'string', 'max:100'],
            'profile_video_url'      => ['sometimes', 'nullable', 'url', 'max:500'],
            'social_links'           => ['sometimes', 'nullable', 'array'],
            'social_links.instagram' => ['nullable', 'url', 'max:255'],
            'social_links.tiktok'    => ['nullable', 'url', 'max:255'],
            'social_links.youtube'   => ['nullable', 'url', 'max:255'],
            'social_links.twitter'   => ['nullable', 'url', 'max:255'],
            'social_links.facebook'  => ['nullable', 'url', 'max:255'],
            'whatsapp_number'        => ['sometimes', 'nullable', 'string', 'max:30'],
            'followers_count'        => ['sometimes', 'nullable', 'integer', 'min:0'],
        ];
    }
}
