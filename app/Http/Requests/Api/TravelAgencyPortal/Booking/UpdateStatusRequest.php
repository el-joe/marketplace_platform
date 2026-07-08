<?php

namespace App\Http\Requests\Api\TravelAgencyPortal\Booking;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:confirmed,cancelled'],
        ];
    }
}
