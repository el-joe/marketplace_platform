@extends('layouts.admin')

@section('title', 'Admin Panel')

@section('content')
    <div class="mb-6">
        <a href="{{ route('admin.docs.index') }}" class="text-sm text-primary-600 hover:underline">&larr; {{ __('admin.nav.documentation') }}</a>
        <div class="flex items-center gap-3 mt-2">
            <span class="text-3xl">🏛</span>
            <h1 class="text-2xl font-bold text-gray-900">Admin Panel</h1>
        </div>
    </div>

    <div class="space-y-8">

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-1">Dashboard</h2>
            <p class="text-sm text-gray-500 mb-3">Purpose: Real-time overview of platform health.</p>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/admin</code> — Main dashboard with revenue chart, order counts, top sellers, pending items</li>
                <li><code>/admin/analytics</code> — Detailed analytics: revenue by period, top products/vendors/categories, customer stats, SLA metrics, flash sale analytics, return rates, support metrics</li>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Catalog Management</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/admin/products</code> — Create/edit products with variants, images, GTIN, country settings</li>
                <li><code>/admin/categories</code> — Nested category tree with commissions, attributes, shipping methods</li>
                <li><code>/admin/brands</code> — Brand CRUD with logo management</li>
                <li><code>/admin/attributes</code> — Product attribute and value management (used for variant filtering)</li>
                <li><code>/admin/product-highlights</code> — Pin products to featured spots per category</li>
                <li><code>/admin/bestsellers</code> — Auto-computed from total_sold on vendor_listings (read-only)</li>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Vendor Management</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/admin/vendor-applications</code> — Review queue for new vendor applications (start-review, assign-me, approve/reject)</li>
                <li><code>/admin/vendors</code> — All active vendors: suspend, blacklist, assign account manager, lock/unlock sections, issue strikes, manage hold</li>
                <li><code>/admin/vendor-change-requests</code> — Pending changes to locked vendor sections (bank accounts etc.)</li>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Orders</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/admin/orders</code> — All platform orders with filters; view/update status; force-cancel; refund; flag fraud</li>
            </ul>
            <p class="text-sm text-gray-500 mt-3 mb-1">Order actions available per status:</p>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li>confirmed &rarr; processing: assign shipping</li>
                <li>processing &rarr; shipped: mark shipped</li>
                <li>shipped &rarr; delivered: mark delivered</li>
                <li>any &rarr; cancelled: force-cancel (admin only)</li>
                <li>any &rarr; refund: process refund (auth required)</li>
            </ul>
            <p class="text-sm text-gray-500 mt-3">Sub-order status transitions available via <code>/sub-orders/{id}/next-statuses</code></p>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Finance</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/admin/payouts</code> — Vendor payout batches: approve/process/hold/recalculate</li>
                <li><code>/admin/transactions</code> — All payment transactions; refund management sub-section</li>
                <li><code>/admin/ledger</code> — Double-entry ledger with transaction groups</li>
                <li><code>/admin/delivery/cod-settlements</code> — COD cash collection settlements from delivery agents</li>
                <li><code>/admin/reports/financial</code> — Revenue reports with export</li>
                <li><code>/admin/warranty-purchases</code> — Extended warranty sales report</li>
                <li><code>/admin/wallets</code> — Customer wallets: adjust, freeze, withdrawal approvals</li>
                <li><code>/admin/subscriptions</code> — Vendor subscription plans + active subscriptions + invoices</li>
                <li><code>/admin/subscriptions/plans</code> — Plan CRUD (monthly/annual pricing)</li>
                <li><code>/admin/fbn/storage-fees</code> — FBN monthly storage fee invoices per vendor</li>
                <li><code>/admin/fbn/inbound</code> — Incoming inventory from vendors to platform warehouses</li>
                <li><code>/admin/fbn/marketplace</code> — FBN marketplace shipping rules</li>
            </ul>
            <p class="text-sm text-gray-500 mt-3">
                COD Settlement lifecycle: Agent collects cash &rarr; Agent remits to platform &rarr; Admin generates settlement &rarr; Admin marks settled &rarr; vendor_payout unlocked for that sub_order
            </p>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Customers</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/admin/customers</code> — Customer list: suspend/ban/reactivate, adjust loyalty points, export data, send notifications, regenerate QR, revoke devices</li>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Marketing (Ad System)</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/admin/banners</code> — Promotional banners with placement slots and scheduling</li>
                <li><code>/admin/ad-campaigns</code> — Self-serve ad campaigns by vendors: approve/reject/pause/resume + fraud alerts</li>
                <li><code>/admin/ad-slots</code> — Named placement slots where ads appear (home hero, category top, etc.)</li>
                <li><code>/admin/paid-ad-bookings</code> — Reserved ad placements booked by vendors for fixed dates</li>
                <li><code>/admin/flash-sales</code> — Time-limited sale events: create, invite vendors, review submissions, live monitor</li>
                <li><code>/admin/vendor-campaign-offers</code> — Vendor-created marketer campaigns: approve/reject</li>
                <li><code>/admin/marketers-secret-promotions</code> — Platform-exclusive discount promotions visible only to specific marketers</li>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Marketers</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/admin/marketers</code> — All marketers: approve, reject, suspend, view campaigns/conversions/payouts</li>
                <li><code>/admin/marketer-campaigns</code> — Marketer-initiated campaigns: approve/reject/pause</li>
                <li><code>/admin/marketer-conversions</code> — Sales attributed to marketers; bulk approve</li>
                <li><code>/admin/marketer-payouts</code> — Generate + approve + process marketer commission payouts</li>
                <li><code>/admin/marketer-samples</code> — Product sample requests by marketers: approve/dispatch/reject</li>
                <li><code>/admin/affiliate-promo-codes</code> — Promo codes assigned to affiliate marketers</li>
                <li><code>/admin/influencer-deals</code> — Flat-fee content deals: propose, approve, manage deliverables, initiate payment</li>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Delivery</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/admin/delivery/agents</code> — Create/manage delivery agents, verify documents, assign zones</li>
                <li><code>/admin/delivery/zones</code> — Define delivery zones; assign agents; view live agent map</li>
                <li><code>/admin/delivery/assignments</code> — View/manage deliveries; auto-assign or manual-assign; live map</li>
                <li><code>/admin/delivery/payouts</code> — Generate/approve/process delivery agent payouts</li>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Fulfillment / Warehouses</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/admin/warehouses</code> — Platform FBN + vendor-owned warehouses; inventory; adjustments; overage fees; vendor storage limits; inventory transfers</li>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Shipping System</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/admin/shipping-zones</code> — Zone CRUD; assign cities; manage per-zone shipping rates</li>
                <li><code>/admin/shipping-methods</code> — Method + carrier + rate management; country settings</li>
                <li><code>/admin/shipping/weight-slabs</code> — Extra fee tiers by weight range</li>
                <li><code>/admin/shipping-subsidies</code> — Platform subsidy rules for exceptional zones</li>
                <li><code>/admin/warehouses/shipping-surcharges</code> — FBN warehouse surcharges on outbound shipments</li>
                <li><code>/admin/shipping-companies</code> — Carrier company management; supervisor notifications; fallback rules</li>
                <li><code>/admin/carrier-claims</code> — Claims against carriers for lost/damaged shipments</li>
                <li><code>/admin/carrier-scorecard</code> — Performance scores per carrier; trend data</li>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Support</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/admin/support-tickets</code> — Customer support tickets: reply, assign, update status/priority</li>
                <li><code>/admin/disputes</code> — Order disputes: reply, assign, resolve</li>
                <li><code>/admin/warranty-claims</code> — Product warranty claims: review, resolve, message thread</li>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Travel</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/admin/travel/agencies</code> — Agency approval/suspension/reactivation</li>
                <li><code>/admin/travel/packages</code> — Package review queue: approve/reject/expire; contract download</li>
                <li><code>/admin/travel/bookings</code> — All travel bookings overview</li>
                <li><code>/admin/travel/countries</code> — Travel destination countries</li>
                <li><code>/admin/travel/cities</code> — Travel destination cities</li>
                <li><code>/admin/travel/inclusions</code> — Standard inclusions (meals, transfers, etc.)</li>
                <li><code>/admin/travel/inquiries</code> — Public inquiries from storefront</li>
                <li><code>/admin/travel/change-requests</code> — Agency change request approval</li>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Classifieds</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/admin/classifieds/categories</code> — Classified ad categories</li>
                <li><code>/admin/classifieds/contract-templates</code> — Legal contract templates for classified listings</li>
                <li><code>/admin/classifieds/listings</code> — Review queue: approve/reject; verify attachments</li>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Content</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/admin/page-builder</code> — Visual homepage/page builder (see Page Builder docs)</li>
                <li><code>/admin/app-contexts</code> — App navigation contexts (Main, Super Mall, Food, 15min, etc.)</li>
                <li><code>/admin/reviews</code> — Product reviews moderation</li>
                <li><code>/admin/blog/categories</code> — Blog category CRUD</li>
                <li><code>/admin/blog/posts</code> — Blog post management with attachments</li>
                <li><code>/admin/adsupport/collections</code> — Knowledge hub collection groups</li>
                <li><code>/admin/adsupport/articles</code> — Knowledge hub articles for vendor help center</li>
                <li><code>/admin/helpcenter/categories</code> — Customer-facing help center categories</li>
                <li><code>/admin/helpcenter/articles</code> — Customer-facing help center articles</li>
                <li><code>/admin/faqs</code> — Platform FAQs</li>
                <li><code>/admin/portal-content</code> — Static page content editor (About, Terms, Privacy, etc.)</li>
                <li><code>/admin/content-settings</code> — All portal media/text settings (logo, banners, colors, etc.)</li>
                <li><code>/admin/radio</code> — Radio channel + slot scheduling</li>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">System</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/admin/countries</code> — Platform countries (launch, deactivate, payment methods, shipping settings)</li>
                <li><code>/admin/cities</code> — City management with bulk import</li>
                <li><code>/admin/currencies</code> — Currency rates and display settings</li>
                <li><code>/admin/shipping-zones</code> — (Also under Shipping section)</li>
                <li><code>/admin/shipping-methods</code> — (Also under Shipping section)</li>
                <li><code>/admin/vendor-document-types</code> — Required document types for vendor onboarding</li>
                <li><code>/admin/payment-methods</code> — Enabled payment methods per country</li>
                <li><code>/admin/payment-gateways</code> — Gateway credential configuration</li>
                <li><code>/admin/shipping/weight-slabs</code> — Weight surcharge slabs</li>
                <li><code>/admin/shipping-subsidies</code> — Exceptional zone subsidy rules</li>
                <li><code>/admin/warehouses</code> — (Also under Warehouses section)</li>
                <li><code>/admin/settings</code> — Platform-wide settings grouped by category</li>
                <li><code>/admin/activity-log</code> — Audit trail of all admin actions</li>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Administration</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/admin/admins</code> — Admin user CRUD; roles; impersonation; session management; reset password</li>
                <li><code>/admin/roles</code> — Admin role and permission management</li>
            </ul>
        </section>

    </div>
@endsection
