<?php

namespace App\Http\Requests\Admin;

use App\Enums\ProductStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = $this->route('product');

        return [
            'name_en' => ['required', 'string', 'max:255'],
            'name_ar' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'gtin' => ['nullable', 'string', 'regex:/^\d{8}$|^\d{12}$|^\d{13}$|^\d{14}$/', Rule::unique("products", "gtin")->ignore($productId)],
            'model_number' => ['nullable', 'string', 'max:100'],
            'description_en' => ['nullable', 'string'],
            'description_ar' => ['nullable', 'string'],
            'short_desc_en' => ['nullable', 'string', 'max:500'],
            'short_desc_ar' => ['nullable', 'string', 'max:500'],
            'status' => ['required', Rule::enum(ProductStatus::class)],
            'has_variants' => ['boolean'],
            'is_featured' => ['boolean'],
            'requires_brand_auth' => ['boolean'],
            'is_age_restricted' => ['boolean'],
            'min_age' => ['nullable', 'integer', 'min:1', 'max:99', 'required_if:is_age_restricted,1'],
            'is_hazardous' => ['boolean'],
            'seo_title' => ['nullable', 'string', 'max:70'],
            'seo_description' => ['nullable', 'string', 'max:160'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('products', 'slug')->ignore($productId)],

            'variants' => ['nullable', 'array'],
            'variants.*.id' => ['nullable', 'exists:product_variants,id'],
            'variants.*.sku' => ['nullable', 'string', 'max:100'],
            'variants.*.barcode' => ['nullable', 'string', 'max:50'],
            'variants.*.weight_grams' => ['nullable', 'integer', 'min:0'],
            'variants.*.is_default' => ['nullable', 'boolean'],
            'variants.*.is_active' => ['nullable', 'boolean'],

            'countries' => ['nullable', 'array'],
            'countries.*.is_available' => ['nullable', 'boolean'],
            'countries.*.name_override' => ['nullable', 'string', 'max:255'],
            'countries.*.requires_cert' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name_en' => 'English name',
            'name_ar' => 'Arabic name',
            'category_id' => 'category',
            'brand_id' => 'brand',
            'gtin' => 'barcode (GTIN)',
        ];
    }
}
