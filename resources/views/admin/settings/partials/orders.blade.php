{{-- Orders Settings Partial --}}
<x-card class="mb-6">
    <div class="mb-4">
        <h2 class="text-base font-semibold text-gray-900">Orders &amp; Cart</h2>
        <p class="text-sm text-gray-500">Checkout flow, SLA thresholds, and return policies.</p>
    </div>
    <div class="divide-y divide-gray-100">

        @foreach($settings->whereIn('key', [
                'max_cart_items',
                'cart_expiry_days_guest',
                'cart_expiry_days_customer',
                'checkout_lock_minutes',
                'min_order_amount_cents',
                'cod_fee_flat',
                'max_order_quantity_per_item',
                'order_cancellation_window_hours',
                'auto_complete_order_days',
                'return_window_days',
                'sla_ship_hours',
                'sla_warning_hours_before',
            ]) as $setting)
                @include('admin.settings.partials._field', ['setting' => $setting])
        @endforeach

    </div>
</x-card>
