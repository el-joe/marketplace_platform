<?php

namespace App\Http\Requests\Carrier\Agent;

use App\Enums\DeliveryAgentVehicleType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAgentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'vehicle_type'  => ['sometimes', Rule::enum(DeliveryAgentVehicleType::class)],
            'license_plate' => ['sometimes', 'string', 'max:20'],
            'zone_id'       => ['sometimes', 'nullable', 'string', 'exists:delivery_zones,id'],
            'is_available'  => ['sometimes', 'boolean'],
            // status toggling: supervisors can activate/deactivate but not suspend
            'status'        => ['sometimes', Rule::in(['active', 'inactive'])],
        ];
    }
}
