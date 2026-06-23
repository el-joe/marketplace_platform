<?php

namespace App\Http\Requests\Customer\Dispute;

use Illuminate\Foundation\Http\FormRequest;

class DisputeStoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'reason'      => ['required', 'string', 'in:item_not_received,item_damaged,item_not_as_described,counterfeit,wrong_item,quality_issue,seller_unresponsive,refund_not_received,other'],
            'description' => ['required', 'string', 'max:5000'],
        ];
    }
}
