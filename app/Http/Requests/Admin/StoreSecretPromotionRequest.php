<?php

namespace App\Http\Requests\Admin;

use App\Models\VendorListing;
use Illuminate\Foundation\Http\FormRequest;

class StoreSecretPromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->guard('admin')->check();
    }

    public function rules(): array
    {
        return [
            'listing_type' => ['nullable', 'in:vendor,admin'],
            'vendor_id' => ['required_if:listing_type,vendor', 'nullable', 'exists:vendors,id'],
            'vendor_listing_id' => ['required_if:listing_type,vendor', 'nullable', 'exists:vendor_listings,id'],
            'admin_listing_id' => ['required_if:listing_type,admin', 'nullable', 'exists:admin_listings,id'],
            'marketer_id' => ['nullable', 'exists:marketers,id'],
            'product_value' => ['required', 'numeric', 'min:0.01'],
            'total_commission_pct' => ['required', 'numeric', 'min:0.01', 'max:99'],
            'marketer_share_pct' => ['required', 'numeric', 'min:0.01', 'max:99'],
            'valid_until' => ['nullable', 'date', 'after:today'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $total = (float) $this->total_commission_pct;
            $marketer = (float) $this->marketer_share_pct;
            $adminShare = round($total - $marketer, 2);
            $minFloor = (float) setting('min_marketer_commission', 5.0);

            if ($adminShare < 0) {
                $v->errors()->add(
                    'marketer_share_pct',
                    'Marketer share cannot exceed total commission.'
                );
            }

            if ($total < $minFloor) {
                $v->errors()->add(
                    'total_commission_pct',
                    "Total commission cannot be below the platform minimum of {$minFloor}%."
                );
            }

            $isAdminListing = ($this->listing_type ?? 'vendor') === 'admin';

            if (!$isAdminListing && $this->vendor_listing_id) {
                $listing = VendorListing::find($this->vendor_listing_id);
                if ($listing && $listing->vendor_id !== $this->vendor_id) {
                    $v->errors()->add(
                        'vendor_listing_id',
                        'This listing does not belong to the selected vendor.'
                    );
                }
            }

            if ($isAdminListing && !$this->admin_listing_id) {
                $v->errors()->add(
                    'admin_listing_id',
                    'Select an admin product listing.'
                );
            }
        });
    }
}
