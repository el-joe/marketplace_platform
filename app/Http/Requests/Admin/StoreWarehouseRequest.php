<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:20', 'unique:warehouses,code'],
            'type' => ['required', 'in:platform_fbn,seller_owned,third_party'],
            'country_id' => ['required', 'uuid', 'exists:countries,id'],
            'owner_vendor_id' => ['nullable', 'uuid', 'exists:vendors,id'],
            'manager_admin_id' => ['nullable', 'uuid', 'exists:admins,id'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'total_capacity_m3' => ['nullable', 'numeric', 'min:0'],
            'used_capacity_m3' => ['nullable', 'numeric', 'min:0'],
            'storage_rate_per_m3_price' => ['nullable', 'numeric', 'min:0'],
            'storage_currency' => ['nullable', 'string', 'size:3'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * Convert storage_rate_per_m3_price from decimal to integer cents before validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->filled('storage_rate_per_m3_price')) {
            $this->merge([
                'storage_rate_per_m3_price' => (int) round((float) $this->storage_rate_per_m3_price * 100),
            ]);
        }

        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
