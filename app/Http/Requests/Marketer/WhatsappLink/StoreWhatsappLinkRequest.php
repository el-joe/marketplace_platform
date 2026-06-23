<?php

namespace App\Http\Requests\Marketer\WhatsappLink;

use Illuminate\Foundation\Http\FormRequest;

class StoreWhatsappLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'discount_pct'  => ['nullable', 'numeric', 'min:1', 'max:100'],
            'free_shipping' => ['required', 'boolean'],
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function ($v): void {
            if (empty($this->input('discount_pct')) && ! $this->boolean('free_shipping')) {
                $v->errors()->add('discount_pct', 'At least one of discount_pct or free_shipping must be provided.');
            }
        });
    }
}
