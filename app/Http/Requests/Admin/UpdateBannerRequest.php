<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'placement_code' => ['required', 'string', 'max:100', 'exists:banner_placement_definitions,code'],
            'country_id' => ['nullable', 'uuid', 'exists:countries,id'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'title_ar' => ['nullable', 'string', 'max:255'],
            'subtitle_en' => ['nullable', 'string', 'max:500'],
            'subtitle_ar' => ['nullable', 'string', 'max:500'],
            'cta_label_en' => ['nullable', 'string', 'max:100'],
            'cta_label_ar' => ['nullable', 'string', 'max:100'],
            'cta_url' => ['nullable', 'string', 'max:500'],
            'link_type' => ['nullable', Rule::in(['url', 'product', 'category', 'brand', 'flash_sale', 'page', 'none'])],
            'link_reference_id' => ['nullable', 'uuid'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'status' => ['required', Rule::in(['active', 'inactive', 'scheduled'])],
            'device_target' => ['required', Rule::in(['all', 'desktop', 'mobile', 'app'])],
            'audience' => ['required', Rule::in(['all', 'guest', 'logged_in', 'vip', 'new_user'])],
            'priority' => ['nullable', 'integer', 'min:0'],
            'desktop_image' => ['nullable', 'image', 'max:5120'],
            'mobile_image' => ['nullable', 'image', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'ends_at.after' => 'End date must be after start date.',
            'placement_code.exists' => 'Please select a valid placement.',
        ];
    }
}
