<?php

namespace App\Http\Requests\Customer\Return;

use Illuminate\Foundation\Http\FormRequest;

class ReturnStoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'order_item_ids'   => ['required', 'array', 'min:1'],
            'order_item_ids.*' => ['required', 'uuid', 'exists:order_items,id'],
            'reason'           => ['required', 'string', 'in:changed_mind,wrong_item,defective,damaged,not_as_described,size_issue,quality_issue,arrived_late,other'],
            'return_type'      => ['required', 'string', 'in:refund,exchange,store_credit'],
            'comments'         => ['nullable', 'string', 'max:2000'],
        ];
    }
}
