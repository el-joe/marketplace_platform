<?php

namespace App\Http\Requests\Customer\Review;

use Illuminate\Foundation\Http\FormRequest;

class ReviewStoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'product_id'    => ['required', 'uuid', 'exists:products,id'],
            'order_item_id' => ['required', 'uuid', 'exists:order_items,id'],
            'rating'        => ['required', 'integer', 'min:1', 'max:5'],
            'comment'       => ['nullable', 'string', 'max:5000'],
            'images'        => ['nullable', 'array', 'max:5'],
            'images.*'      => ['image', 'max:5120'],
        ];
    }
}
