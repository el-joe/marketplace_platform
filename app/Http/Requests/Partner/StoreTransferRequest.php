<?php

namespace App\Http\Requests\Partner;

use App\Models\VendorListing;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'source_warehouse_id'      => ['required', 'exists:warehouses,id'],
            'destination_warehouse_id' => ['required', 'exists:warehouses,id', 'different:source_warehouse_id'],
            'expected_arrival_date'    => ['nullable', 'date', 'after:today'],
            'notes'                    => ['nullable', 'string'],
            'items'                    => ['required', 'array', 'min:1'],
            'items.*.vendor_listing_id' => ['required', 'exists:vendor_listings,id'],
            'items.*.quantity'          => ['required', 'integer', 'min:1'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $vendorId = auth('vendor')->user()->vendor_id;

            foreach ((array) $this->input('items', []) as $item) {
                $listing = VendorListing::find($item['vendor_listing_id'] ?? null);
                if ($listing && $listing->vendor_id !== $vendorId) {
                    $v->errors()->add('items', 'One or more products do not belong to your account.');
                    break;
                }
            }
        });
    }
}
