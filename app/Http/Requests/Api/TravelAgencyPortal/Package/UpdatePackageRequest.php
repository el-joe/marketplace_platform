<?php

namespace App\Http\Requests\Api\TravelAgencyPortal\Package;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title_en' => ['required', 'string', 'max:255'],
            'title_ar' => ['required', 'string', 'max:255'],
            'description_en' => ['nullable', 'string'],
            'description_ar' => ['nullable', 'string'],
            'destination_travel_country_id' => ['required', 'uuid', 'exists:travel_countries,id'],
            'destination_travel_city_id' => ['nullable', 'uuid', 'exists:travel_cities,id'],
            'price_cents' => ['required', 'integer', 'min:1'],
            'currency' => ['required', 'string', 'size:3', 'exists:currencies,code'],
            'duration_days' => ['required', 'integer', 'min:1'],
            'duration_nights' => ['required', 'integer', 'min:0'],
            'departure_date' => ['required', 'date'],
            'return_date' => ['required', 'date', 'after:departure_date'],
            'available_seats' => ['nullable', 'integer', 'min:1'],
            'inclusions' => ['nullable', 'array'],
            'inclusions.*' => ['string'],
            'media' => ['nullable', 'array', 'max:10'],
            'media.*' => ['file', 'mimes:jpg,jpeg,png,webp,mp4,mov', 'max:51200'],
            'contract_file' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ];
    }
}
