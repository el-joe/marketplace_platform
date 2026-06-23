<?php

namespace App\Http\Requests\Vendor;

use Illuminate\Foundation\Http\FormRequest;

class CreateListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_variant_id'  => ['required', 'uuid', 'exists:product_variants,id'],
            'country_id'          => ['required', 'uuid', 'exists:countries,id'],
            'price'               => ['required', 'integer', 'min:1'],
            'compare_at_price'    => ['nullable', 'integer', 'min:1', 'gt:price'],
            'condition'           => ['required', 'string', 'in:new,like_new,good,acceptable,refurbished'],
            'fulfillment_model'   => ['required', 'string', 'in:fbm,fbn,cross_dock'],
        ];
    }
}
