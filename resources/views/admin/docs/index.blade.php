@extends('layouts.admin')

@section('title', __('admin.nav.documentation'))

@section('content')
    <div class="mb-8 bg-gradient-to-br from-primary-600 to-primary-800 rounded-xl px-6 py-8 text-white">
        <h1 class="text-2xl font-bold">Platform Documentation</h1>
        <p class="text-primary-100 mt-1">Your complete reference for every panel, feature, and workflow.</p>
    </div>

    {{-- Panels --}}
    <div class="mb-10">
        <h2 class="text-sm font-semibold text-gray-900 mb-3">Panels</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <a href="{{ route('admin.docs.panels.admin') }}" class="block bg-white rounded-xl border border-gray-200 p-5 hover:border-primary-300 hover:shadow-sm transition">
                <div class="text-2xl mb-2">🏛</div>
                <div class="font-semibold text-gray-900">Admin Panel</div>
                <p class="text-sm text-gray-500 mt-1">Full control center for the entire platform</p>
            </a>
            <a href="{{ route('admin.docs.panels.partner') }}" class="block bg-white rounded-xl border border-gray-200 p-5 hover:border-primary-300 hover:shadow-sm transition">
                <div class="text-2xl mb-2">🤝</div>
                <div class="font-semibold text-gray-900">Partner Panel</div>
                <p class="text-sm text-gray-500 mt-1">Vendor / seller management and operations</p>
            </a>
            <a href="{{ route('admin.docs.panels.marketer') }}" class="block bg-white rounded-xl border border-gray-200 p-5 hover:border-primary-300 hover:shadow-sm transition">
                <div class="text-2xl mb-2">📣</div>
                <div class="font-semibold text-gray-900">Marketer Panel</div>
                <p class="text-sm text-gray-500 mt-1">Affiliate and influencer tools</p>
            </a>
            <a href="{{ route('admin.docs.panels.travel') }}" class="block bg-white rounded-xl border border-gray-200 p-5 hover:border-primary-300 hover:shadow-sm transition">
                <div class="text-2xl mb-2">✈️</div>
                <div class="font-semibold text-gray-900">Travel Agency Panel</div>
                <p class="text-sm text-gray-500 mt-1">Travel packages and bookings management</p>
            </a>
            <a href="{{ route('admin.docs.panels.delivery') }}" class="block bg-white rounded-xl border border-gray-200 p-5 hover:border-primary-300 hover:shadow-sm transition">
                <div class="text-2xl mb-2">🚴</div>
                <div class="font-semibold text-gray-900">Delivery Agent Panel</div>
                <p class="text-sm text-gray-500 mt-1">Last-mile delivery operations</p>
            </a>
            <a href="{{ route('admin.docs.panels.carrier') }}" class="block bg-white rounded-xl border border-gray-200 p-5 hover:border-primary-300 hover:shadow-sm transition">
                <div class="text-2xl mb-2">🚚</div>
                <div class="font-semibold text-gray-900">Carrier Panel</div>
                <p class="text-sm text-gray-500 mt-1">Shipping company supervisor tools</p>
            </a>
        </div>
    </div>

    {{-- Features --}}
    <div>
        <h2 class="text-sm font-semibold text-gray-900 mb-3">Features</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Orders &amp; Fulfillment</h3>
                <ul class="space-y-1.5 text-sm">
                    <li><a class="text-primary-600 hover:underline" href="{{ route('admin.docs.features.order-lifecycle') }}">{{ __('admin.nav.docs_order_lifecycle') }}</a></li>
                    <li><a class="text-primary-600 hover:underline" href="{{ route('admin.docs.features.shipping') }}">{{ __('admin.nav.docs_shipping') }}</a></li>
                    <li><a class="text-primary-600 hover:underline" href="{{ route('admin.docs.features.warehouses') }}">{{ __('admin.nav.docs_warehouses') }}</a></li>
                    <li><a class="text-primary-600 hover:underline" href="{{ route('admin.docs.features.packaging') }}">{{ __('admin.nav.docs_packaging') }}</a></li>
                </ul>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Marketing</h3>
                <ul class="space-y-1.5 text-sm">
                    <li><a class="text-primary-600 hover:underline" href="{{ route('admin.docs.features.banners') }}">{{ __('admin.nav.docs_banners') }}</a></li>
                    <li><a class="text-primary-600 hover:underline" href="{{ route('admin.docs.features.page-builder') }}">{{ __('admin.nav.docs_page_builder') }}</a></li>
                    <li><a class="text-primary-600 hover:underline" href="{{ route('admin.docs.features.ad-campaigns') }}">{{ __('admin.nav.docs_ad_campaigns') }}</a></li>
                    <li><a class="text-primary-600 hover:underline" href="{{ route('admin.docs.features.vendor-campaigns') }}">{{ __('admin.nav.docs_vendor_campaigns') }}</a></li>
                    <li><a class="text-primary-600 hover:underline" href="{{ route('admin.docs.features.marketer-campaigns') }}">{{ __('admin.nav.docs_marketer_campaigns') }}</a></li>
                    <li><a class="text-primary-600 hover:underline" href="{{ route('admin.docs.features.influencer-deals') }}">{{ __('admin.nav.docs_influencer_deals') }}</a></li>
                    <li><a class="text-primary-600 hover:underline" href="{{ route('admin.docs.features.secret-promotions') }}">{{ __('admin.nav.docs_secret_promotions') }}</a></li>
                    <li><a class="text-primary-600 hover:underline" href="{{ route('admin.docs.features.affiliate-codes') }}">{{ __('admin.nav.docs_affiliate_codes') }}</a></li>
                    <li><a class="text-primary-600 hover:underline" href="{{ route('admin.docs.features.flash-sales') }}">{{ __('admin.nav.docs_flash_sales') }}</a></li>
                </ul>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Finance</h3>
                <ul class="space-y-1.5 text-sm">
                    <li><a class="text-primary-600 hover:underline" href="{{ route('admin.docs.features.payments') }}">{{ __('admin.nav.docs_payments') }}</a></li>
                    <li><a class="text-primary-600 hover:underline" href="{{ route('admin.docs.features.finance') }}">{{ __('admin.nav.docs_finance') }}</a></li>
                    <li><a class="text-primary-600 hover:underline" href="{{ route('admin.docs.features.subsidy') }}">{{ __('admin.nav.docs_subsidy') }}</a></li>
                </ul>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Content</h3>
                <ul class="space-y-1.5 text-sm">
                    <li><a class="text-primary-600 hover:underline" href="{{ route('admin.docs.features.content-pages') }}">{{ __('admin.nav.docs_content_pages') }}</a></li>
                    <li><a class="text-primary-600 hover:underline" href="{{ route('admin.docs.features.system-pages') }}">{{ __('admin.nav.docs_system_pages') }}</a></li>
                    <li><a class="text-primary-600 hover:underline" href="{{ route('admin.docs.features.radio') }}">{{ __('admin.nav.docs_radio') }}</a></li>
                    <li><a class="text-primary-600 hover:underline" href="{{ route('admin.docs.features.classifieds') }}">{{ __('admin.nav.docs_classifieds') }}</a></li>
                    <li><a class="text-primary-600 hover:underline" href="{{ route('admin.docs.features.travel') }}">{{ __('admin.nav.docs_travel') }}</a></li>
                </ul>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Platform</h3>
                <ul class="space-y-1.5 text-sm">
                    <li><a class="text-primary-600 hover:underline" href="{{ route('admin.docs.features.roles') }}">{{ __('admin.nav.docs_roles') }}</a></li>
                    <li><a class="text-primary-600 hover:underline" href="{{ route('admin.docs.features.warranties') }}">{{ __('admin.nav.docs_warranties') }}</a></li>
                </ul>
            </div>
        </div>
    </div>
@endsection
