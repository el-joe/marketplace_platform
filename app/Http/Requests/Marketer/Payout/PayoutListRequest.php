<?php

namespace App\Http\Requests\Marketer\Payout;

use Illuminate\Foundation\Http\FormRequest;

class PayoutListRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', 'in:pending,approved,paid,failed'],
            'page'   => ['nullable', 'integer', 'min:1'],
        ];
    }
}
