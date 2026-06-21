<?php

namespace App\Http\Requests\Customer\Order;

use Illuminate\Foundation\Http\FormRequest;

class OrderListRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'status'    => ['sometimes', 'string', 'in:placed,confirmed,partially_shipped,shipped,partially_delivered,delivered,completed,cancelled,refunded,disputed'],
            'date_from' => ['sometimes', 'date_format:Y-m-d'],
            'date_to'   => ['sometimes', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'page'      => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
