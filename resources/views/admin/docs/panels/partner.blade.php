@extends('layouts.admin')

@section('title', 'Partner Panel')

@section('content')
    <div class="mb-6">
        <a href="{{ route('admin.docs.index') }}" class="text-sm text-primary-600 hover:underline">&larr; {{ __('admin.nav.documentation') }}</a>
        <div class="flex items-center gap-3 mt-2">
            <span class="text-3xl">🤝</span>
            <h1 class="text-2xl font-bold text-gray-900">Partner (Vendor) Panel</h1>
        </div>
        <p class="text-sm text-gray-500 mt-2">
            URL: <code>partner.{domain}</code> &middot; Access: <code>vendor_admins</code> table &middot; Guard: <code>vendor</code>
        </p>
    </div>

    <div class="space-y-8">

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Authentication</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li>Login at <code>partner.domain/login</code></li>
                <li>Vendor account must be approved and active — suspended vendors see <code>/suspended</code> page</li>
                <li>Password reset via email</li>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Dashboard</h2>
            <p class="text-sm text-gray-700">Sales summary, orders chart, pending actions, low stock alerts, recent orders</p>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Orders</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/orders</code> — Sub-orders assigned to this vendor</li>
                <li>Actions: confirm &rarr; ship &rarr; out-for-delivery &rarr; deliver | cancel</li>
                <li>Vendor sees ONLY their sub_orders — never other vendors' data</li>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Listings</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/listings</code> — Vendor's product listings on the marketplace</li>
                <li>Create: search existing product catalog &rarr; set price, fulfillment model, warehouse</li>
                <li>Edit: update price, shipping, dimensions, adjust stock, toggle status</li>
                <li>Shipping preview: see how shipping fee is calculated for a specific listing</li>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Inventory</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/inventory</code> — Stock levels across all listings</li>
                <li>Low stock and out-of-stock filtered views</li>
                <li>Movement history per listing (inbound, outbound, adjustments)</li>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Payouts</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/payouts</code> — Payout batches from platform settlements</li>
                <li>Earnings summary: total earned, pending, commission deducted</li>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Coupons</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/coupons</code> — Vendor-specific coupons applied only to their sub_orders</li>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Flash Sales</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/flash-sales</code> — Platform flash sale events the vendor is invited to</li>
                <li>Submit listing prices for flash sale inclusion</li>
                <li>Live stats during active sales</li>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Bank Accounts</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/bank-accounts</code> — Vendor's bank accounts for payout transfer</li>
                <li>Set primary, delete (not editable — change request required for locked section)</li>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">FBN (Fulfilled by Platform)</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/fbn/inbound</code> — Ship inventory to platform warehouses</li>
                <li><code>/fbn/storage-fees</code> — View storage fee charges per listing</li>
                <li><code>/fbn/warehouses</code> — View assigned platform warehouses</li>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Performance</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/performance</code> — Vendor scorecard: order completion rate, cancellation rate, reviews, SLA</li>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Exceptional Shipping Zones</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/exceptional-zones</code> — Opt into serving remote zones with delivery subsidy applied</li>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Team Management</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/team</code> — Manage staff with role-based access</li>
                <li><code>/roles</code> — Create custom permission roles scoped to this vendor</li>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Profile &amp; Settings</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/profile</code> — Agency info, logo, contact</li>
                <li><code>/bank-accounts</code> — Bank account management</li>
                <li><code>/change-requests</code> — View pending change requests for locked sections</li>
                <li><code>/warehouse</code> — Register/manage vendor-owned warehouses</li>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Packaging Supplies</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/packaging/catalog</code> — Browse platform packaging items</li>
                <li><code>/packaging/order</code> — Place packaging order (vendor bears delivery fee)</li>
                <li><code>/packaging/requests</code> — View own packaging order history</li>
            </ul>
        </section>

    </div>
@endsection
