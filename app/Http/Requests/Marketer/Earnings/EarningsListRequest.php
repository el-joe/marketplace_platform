<?php

namespace App\Http\Requests\Marketer\Earnings;

use App\Enums\MarketerTrackingStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EarningsListRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'status'    => ['nullable', Rule::enum(MarketerTrackingStatus::class)],
            'date_from' => ['nullable', 'date'],
            'date_to'   => ['nullable', 'date', 'after_or_equal:date_from'],
            'page'      => ['nullable', 'integer', 'min:1'],
        ];
    }
}
