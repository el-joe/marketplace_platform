<?php

namespace App\Http\Requests\Marketer\Payout;

use App\Enums\MarketerPayoutStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PayoutListRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::enum(MarketerPayoutStatus::class)],
            'page'   => ['nullable', 'integer', 'min:1'],
        ];
    }
}
