{{-- Notifications Settings Partial --}}
<x-card class="mb-6">
    <div class="mb-4">
        <h2 class="text-base font-semibold text-gray-900">Admin Notifications</h2>
        <p class="text-sm text-gray-500">Control which events trigger email alerts to the ops team.</p>
    </div>
    <div class="divide-y divide-gray-100">

        @foreach($settings->whereIn('key', [
            'admin_notification_email',
            'notify_admin_new_order',
            'notify_admin_new_vendor_application',
            'notify_admin_new_dispute',
            'notify_admin_sla_breach',
            'notify_admin_low_stock',
            'notify_admin_payout_failure',
        ]) as $setting)
            @include('admin.settings.partials._field', ['setting' => $setting])
        @endforeach

    </div>
</x-card>

<x-card>
    <div class="mb-4">
        <h2 class="text-base font-semibold text-gray-900">Customer &amp; Vendor Notifications</h2>
        <p class="text-sm text-gray-500">Outbound messaging channels to vendors and customers.</p>
    </div>
    <div class="divide-y divide-gray-100">

        @foreach($settings->whereIn('key', [
            'vendor_welcome_email_enabled',
            'customer_order_email_enabled',
            'sms_notifications_enabled',
            'push_notifications_enabled',
        ]) as $setting)
            @include('admin.settings.partials._field', ['setting' => $setting])
        @endforeach

    </div>
</x-card>
