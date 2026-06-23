<?php

namespace App\Http\Requests\Partner;

use Illuminate\Foundation\Http\FormRequest;

class StoreVendorWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'               => ['required', 'string', 'max:150'],
            'country_id'         => ['required', 'exists:countries,id'],
            'total_capacity_m3'  => ['nullable', 'numeric', 'min:0'],
            'city_id'            => ['nullable', 'exists:cities,id'],
            'area'               => ['nullable', 'string', 'max:255'],
            'street_address'     => ['required', 'string', 'max:255'],
            'building'           => ['nullable', 'string', 'max:100'],
        ];
    }
}
