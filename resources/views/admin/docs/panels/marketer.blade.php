@extends('layouts.admin')

@section('title', __('docs/panels/marketer.title'))

@section('content')
    <div class="mb-6">
        <a href="{{ route('admin.docs.index') }}" class="text-sm text-primary-600 hover:underline">&larr; {{ __('admin.nav.documentation') }}</a>
        <div class="flex items-center gap-3 mt-2">
            <span class="text-3xl">📣</span>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('docs/panels/marketer.title') }}</h1>
        </div>
        <p class="text-sm text-gray-500 mt-2">{!! __('docs/panels/marketer.meta_url') !!}</p>
        <p class="text-sm text-gray-500 mt-1">
            {!! __('docs/panels/marketer.meta_types') !!}
        </p>
    </div>

    <div class="space-y-8">

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">{{ __('docs/panels/marketer.dashboard.title') }}</h2>
            <p class="text-sm text-gray-700">{{ __('docs/panels/marketer.dashboard.summary') }}</p>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">{{ __('docs/panels/marketer.analytics.title') }}</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/analytics</code> — {{ __('docs/panels/marketer.analytics.analytics') }}</li>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">{{ __('docs/panels/marketer.store.title') }}</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/store</code> — {{ __('docs/panels/marketer.store.store') }}</li>
                <li><code>/store/preview</code> — {{ __('docs/panels/marketer.store.preview') }}</li>
                <li>{{ __('docs/panels/marketer.store.public_url') }} <code>marketer.domain/p/{slug}</code></li>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">{{ __('docs/panels/marketer.campaigns.title') }}</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/campaigns</code> — {{ __('docs/panels/marketer.campaigns.campaigns') }}</li>
                <li>{!! __('docs/panels/marketer.campaigns.create') !!}</li>
                <li>{{ __('docs/panels/marketer.campaigns.track') }}</li>
                <li>{{ __('docs/panels/marketer.campaigns.actions') }}</li>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">{{ __('docs/panels/marketer.invitations.title') }}</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/invitations</code> — {{ __('docs/panels/marketer.invitations.invitations') }}</li>
                <li>{{ __('docs/panels/marketer.invitations.respond') }}</li>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">{{ __('docs/panels/marketer.admin_offers.title') }}</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/admin-offers</code> — {{ __('docs/panels/marketer.admin_offers.offers') }}</li>
                <li>{{ __('docs/panels/marketer.admin_offers.respond') }}</li>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">{{ __('docs/panels/marketer.qr_codes.title') }}</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/qr-codes</code> — {{ __('docs/panels/marketer.qr_codes.qr_codes') }}</li>
                <li>{{ __('docs/panels/marketer.qr_codes.download') }}</li>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">{{ __('docs/panels/marketer.promo_codes.title') }} <span class="text-xs font-normal text-gray-400">{{ __('docs/panels/marketer.promo_codes.affiliate_only') }}</span></h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/promo-codes</code> — {{ __('docs/panels/marketer.promo_codes.request') }}</li>
                <li>{{ __('docs/panels/marketer.promo_codes.usage') }}</li>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">{{ __('docs/panels/marketer.deals.title') }} <span class="text-xs font-normal text-gray-400">{{ __('docs/panels/marketer.deals.influencer_only') }}</span></h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/deals</code> — {{ __('docs/panels/marketer.deals.view') }}</li>
                <li>{{ __('docs/panels/marketer.deals.submit') }}</li>
                <li><code>/media-kit</code> — {{ __('docs/panels/marketer.deals.media_kit') }}</li>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">{{ __('docs/panels/marketer.samples.title') }}</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/samples</code> — {{ __('docs/panels/marketer.samples.samples') }}</li>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">{{ __('docs/panels/marketer.earnings.title') }}</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/earnings</code> — {{ __('docs/panels/marketer.earnings.history') }}</li>
                <li><code>/earnings/summary</code> — {{ __('docs/panels/marketer.earnings.summary') }}</li>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">{{ __('docs/panels/marketer.wallet.title') }}</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/wallet</code> — {{ __('docs/panels/marketer.wallet.balance') }}</li>
                <li>{{ __('docs/panels/marketer.wallet.withdraw') }}</li>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">{{ __('docs/panels/marketer.secret_promotions.title') }}</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/secret-promotions</code> — {{ __('docs/panels/marketer.secret_promotions.promotions') }}</li>
            </ul>
        </section>

    </div>
@endsection
