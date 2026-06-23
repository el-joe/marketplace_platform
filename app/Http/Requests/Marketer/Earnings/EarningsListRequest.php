<?php

namespace App\Http\Requests\Marketer\Earnings;

use Illuminate\Foundation\Http\FormRequest;

class EarningsListRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'status'    => ['nullable', 'string', 'in:pending,approved,paid,reversed'],
            'date_from' => ['nullable', 'date'],
            'date_to'   => ['nullable', 'date', 'after_or_equal:date_from'],
            'page'      => ['nullable', 'integer', 'min:1'],
        ];
    }
}
