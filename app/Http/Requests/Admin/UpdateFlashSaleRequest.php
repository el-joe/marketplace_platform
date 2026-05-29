<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateFlashSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name_en' => ['sometimes', 'required', 'string', 'max:200'],
            'name_ar' => ['sometimes', 'required', 'string', 'max:200'],
            'description_en' => ['sometimes', 'nullable', 'string'],
            'description_ar' => ['sometimes', 'nullable', 'string'],
            'country_id' => ['sometimes', 'nullable', 'exists:countries,id'],
            'submission_opens_at' => ['sometimes', 'required', 'date'],
            'submission_closes_at' => ['sometimes', 'required', 'date', 'after:submission_opens_at'],
            'review_deadline_at' => ['sometimes', 'required', 'date', 'after:submission_closes_at'],
            'sale_starts_at' => ['sometimes', 'required', 'date', 'after:review_deadline_at'],
            'sale_ends_at' => ['sometimes', 'required', 'date', 'after:sale_starts_at'],
            'min_discount_pct' => ['sometimes', 'required', 'numeric', 'min:0', 'max:100'],
            'max_products_per_seller' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'eligible_categories' => ['sometimes', 'nullable', 'array'],
            'eligible_categories.*' => ['exists:categories,id'],
            'eligible_seller_tiers' => ['sometimes', 'nullable', 'array'],
            'eligible_seller_tiers.*' => ['in:bronze,silver,gold,platinum'],
            'min_seller_rating' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:5'],
            'commission_override_pct' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:100'],
            'is_featured' => ['sometimes', 'boolean'],
            'is_exclusive' => ['sometimes', 'boolean'],
            'price_drop_required' => ['sometimes', 'boolean'],
            'max_total_slots' => ['sometimes', 'nullable', 'integer', 'min:1'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $flashSale = $this->route('flashSale');
        if ($flashSale && in_array($flashSale->status, ['live', 'ended', 'cancelled'], true)) {
            $validator->errors()->add(
                'status',
                'Cannot edit a ' . $flashSale->status . ' flash sale.'
            );
        }
    }
}
