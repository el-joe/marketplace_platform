<?php

namespace App\Http\Requests\Api\Customer;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseGiftCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'gift_card_batch_id' => ['required', 'uuid', 'exists:gift_card_batches,id'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'payment_method' => ['required', 'string'],
            'recipient_email' => ['nullable', 'email'],
            'recipient_name' => ['nullable', 'string', 'max:255'],
            'gift_message' => ['nullable', 'string', 'max:500'],
        ];
    }
}
