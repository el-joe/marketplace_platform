<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $warehouseId = $this->route('warehouse')?->id;

        return [
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:20', Rule::unique('warehouses', 'code')->ignore($warehouseId)],
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
