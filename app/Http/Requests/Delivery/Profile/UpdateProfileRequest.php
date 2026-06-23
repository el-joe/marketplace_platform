<?php

namespace App\Http\Requests\Delivery\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vehicle_type'  => ['sometimes', 'string', Rule::in(['motorcycle', 'car', 'van', 'bicycle'])],
            'vehicle_plate' => ['sometimes', 'string', 'max:30'],
        ];
    }
}
