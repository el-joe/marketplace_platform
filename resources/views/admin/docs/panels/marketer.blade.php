@extends('layouts.admin')

@section('title', 'Marketer Panel')

@section('content')
    <div class="mb-6">
        <a href="{{ route('admin.docs.index') }}" class="text-sm text-primary-600 hover:underline">&larr; {{ __('admin.nav.documentation') }}</a>
        <div class="flex items-center gap-3 mt-2">
            <span class="text-3xl">📣</span>
            <h1 class="text-2xl font-bold text-gray-900">Marketer Panel</h1>
        </div>
        <p class="text-sm text-gray-500 mt-2">URL: <code>marketer.{domain}</code></p>
        <p class="text-sm text-gray-500 mt-1">
            Two marketer types: <strong>Affiliate</strong> — commission-based, uses promo codes and tracking links &middot;
            <strong>Influencer</strong> — flat-fee deals with content deliverables
        </p>
    </div>

    <div class="space-y-8">

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Dashboard</h2>
            <p class="text-sm text-gray-700">Earnings summary, active campaigns, recent conversions, pending invitations</p>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Analytics</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/analytics</code> — Cross-campaign performance: clicks, conversions, earnings by date range</li>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">My Store</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/store</code> — Public profile management: bio, social links, audience stats, cover image</li>
                <li><code>/store/preview</code> — How vendors and customers see the profile</li>
                <li>Public URL: <code>marketer.domain/p/{slug}</code></li>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Campaigns</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/campaigns</code> — Self-initiated campaigns to promote products/classifieds/travel packages</li>
                <li>Create: choose campaign type, add products/travel packages, set attribution model</li>
                <li>Track: clicks, conversions, earnings per campaign</li>
                <li>Actions: pause, resume, cancel, resubmit after rejection</li>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Vendor Invitations</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/invitations</code> — Vendors invite marketers to promote their campaigns</li>
                <li>Accept or decline with optional note</li>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Admin Offers</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/admin-offers</code> — Admin sends direct campaign offers to specific marketers</li>
                <li>Accept or decline with reason note</li>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">QR Codes</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/qr-codes</code> — Generate trackable QR codes linking to campaigns</li>
                <li>Download for offline marketing use</li>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Promo Codes <span class="text-xs font-normal text-gray-400">(Affiliate only)</span></h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/promo-codes</code> — Request platform-assigned affiliate codes</li>
                <li>Used at customer checkout; attribution tied to this marketer</li>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Deals + Deliverables <span class="text-xs font-normal text-gray-400">(Influencer only)</span></h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/deals</code> — View flat-fee content deals proposed by admin</li>
                <li>Accept/reject deals; submit content deliverables for review</li>
                <li><code>/media-kit</code> — Manage media kit shown to potential partners</li>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Sample Requests</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/samples</code> — Request product samples for review content creation</li>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Earnings</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/earnings</code> — Commission earnings history and breakdown</li>
                <li><code>/earnings/summary</code> — Aggregated view by period and campaign</li>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Wallet</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/wallet</code> — Platform wallet balance</li>
                <li>Request withdrawal to bank account</li>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Secret Promotions</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/secret-promotions</code> — Exclusive promotions only visible to this marketer's audience</li>
            </ul>
        </section>

    </div>
@endsection
