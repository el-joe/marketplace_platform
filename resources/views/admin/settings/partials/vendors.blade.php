{{-- Vendors Settings Partial --}}
<x-card class="mb-6">
    <div class="mb-4">
        <h2 class="text-base font-semibold text-gray-900">Vendor Management</h2>
        <p class="text-sm text-gray-500">Commission, auto-approval, strikes, and payouts.</p>
    </div>
    <div class="divide-y divide-gray-100">

        @foreach($settings->whereIn('key', [
                'vendor_commission_default',
                'new_vendor_listing_review',
                'new_vendor_listing_count_threshold',
                'vendor_auto_approve_rating_threshold',
                'vendor_auto_approve_listings_threshold',
                'vendor_strikes_auto_suspend',
                'vendor_strike_expiry_days',
                'vendor_document_expiry_warning_days',
                'vendor_review_sla_days',
                'vendor_min_payout_amount',
                'vendor_payout_processing_days',
            ]) as $setting)
                @include('admin.settings.partials._field', ['setting' => $setting])
        @endforeach

    </div>
</x-card>
