<?php

namespace App\Http\Requests\Carrier\Agent;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAgentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:255'],
            'email'         => ['required', 'email', 'max:255', 'unique:delivery_agents,email'],
            'phone'         => ['required', 'string', 'max:30', 'unique:delivery_agents,phone'],
            'password'      => ['required', 'string', 'min:8'],
            'vehicle_type'  => ['required', Rule::in(['motorcycle', 'car', 'van', 'bicycle'])],
            'license_plate' => ['required', 'string', 'max:20'],
            'zone_id'       => ['nullable', 'string', 'exists:delivery_zones,id'],
            'country_id'    => ['required', 'string', 'exists:countries,id'],
        ];
    }
}
