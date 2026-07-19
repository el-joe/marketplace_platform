@php
    $vendorAdmin = auth()->guard('vendor')->user();
    $vendor = $vendorAdmin?->vendor;
    $statusColor = match ($vendor?->global_status?->value) {
        'active' => 'bg-green-400',
        'under_review' => 'bg-yellow-400',
        default => 'bg-gray-500',
    };
    $statusLabel = match ($vendor?->global_status?->value) {
        'active' => __('partner.nav.status_active'),
        'under_review' => __('partner.nav.status_under_review'),
        default => __('partner.nav.status_suspended'),
    };
@endphp

{{-- Sidebar: 240px, dark --}}
<aside class="w-60 shrink-0 bg-gray-900 border-e border-gray-800 flex flex-col h-full overflow-y-auto">

    {{-- Logo --}}
    <div class="flex items-center gap-2 px-5 h-16 border-b border-gray-800 shrink-0">
        <span class="bg-yellow-400 text-gray-950 font-black text-lg px-2 py-0.5 rounded">noon</span>
        <span class="text-white text-xs font-semibold">{{ __('partner.nav.for_sellers') }}</span>
    </div>

    {{-- Store info --}}
    <div class="px-5 py-4 border-b border-gray-800 shrink-0">
        <div class="flex items-center gap-3">
            <div
                class="w-9 h-9 rounded-full bg-yellow-400/20 flex items-center justify-center text-yellow-400 font-bold text-sm shrink-0">
                {{ mb_substr($vendor?->store_name ?? 'S', 0, 1) }}
            </div>
            <div class="min-w-0">
                <div class="text-white text-xs font-bold truncate">{{ $vendor?->store_name ?? __('partner.nav.my_store') }}</div>
                <div class="flex items-center gap-1 mt-0.5">
                    <span class="w-1.5 h-1.5 rounded-full {{ $statusColor }}"></span>
                    <span class="text-gray-400 text-xs">{{ $statusLabel }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Nav --}}
    <nav class="flex-1 px-3 py-4 space-y-0.5">

        <x-partner-nav-item route="partner.dashboard" icon="squares-2x2" label="{{ __('partner.nav.home') }}" />
        <x-partner-nav-item route="partner.notifications.index" icon="bell" label="{{ __('partner.nav.notifications') }}" />

        <x-partner-nav-group label="{{ __('partner.nav.orders') }}">
            <x-partner-nav-item route="partner.orders.index" icon="shopping-bag" label="{{ __('partner.nav.my_orders') }}" />
            <x-partner-nav-item route="partner.returns.index" icon="arrow-uturn-left" label="{{ __('partner.nav.returns') }}" />
            <x-partner-nav-item route="partner.warranty-claims.index" icon="shield-check" label="{{ __('partner.nav.warranty_claims') }}" />
            <x-partner-nav-item route="partner.claims.index" icon="shield-exclamation" label="{{ __('partner.nav.claims') }}" />
            <x-partner-nav-item route="partner.disputes.index" icon="exclamation-triangle" label="{{ __('partner.nav.disputes') }}" />
        </x-partner-nav-group>

        <x-partner-nav-group label="{{ __('partner.nav.catalog') }}">
            <x-partner-nav-item route="partner.listings.index" icon="tag" label="{{ __('partner.nav.listings') }}" />
            <x-partner-nav-item route="partner.inventory.index" icon="cube" label="{{ __('partner.nav.inventory') }}" />
            <x-partner-nav-item route="partner.change-requests.index" icon="pencil-square" label="{{ __('partner.nav.change_requests') }}" />
            <x-partner-nav-item route="partner.ai.credits" icon="sparkles" label="{{ __('partner.nav.ai_credits') }}" />
            <x-partner-nav-item route="partner.tools.weight-calculator" icon="scale" label="حاسبة الوزن / Weight Calculator" />
        </x-partner-nav-group>

        <x-partner-nav-group label="{{ __('partner.nav.fulfillment_storage') }}">
            <x-partner-nav-item route="partner.fulfillment.index" icon="truck" label="{{ __('partner.nav.fulfillment') }}" />
            <x-partner-nav-item route="partner.warehouses.index" icon="building-storefront" label="{{ __('partner.nav.warehouses') }}" />
            <x-partner-nav-item route="partner.warehouses.transfers.index" icon="arrows-right-left" label="{{ __('partner.nav.transfers') }}" />
            <x-partner-nav-item route="partner.city-surcharges.index" icon="map-pin" label="City Shipping Surcharges" />
            <x-partner-nav-item route="partner.packaging-supplies.index" icon="archive-box" label="{{ __('partner.nav.packaging_supplies') }}" />
        </x-partner-nav-group>

        <x-partner-nav-group label="{{ __('partner.nav.finance') }}">
            <x-partner-nav-item route="partner.payouts.index" icon="banknotes" label="{{ __('partner.nav.payouts') }}" />
            <x-partner-nav-item route="partner.finance.transactions" icon="credit-card" label="{{ __('partner.nav.transactions') }}" />
            <x-partner-nav-item route="partner.finance.sales-report" icon="document-chart-bar" label="{{ __('partner.finance.sales_report') }}" />
            <x-partner-nav-item route="partner.wallet.index" icon="wallet" label="{{ __('partner.nav.wallet') }}" />
            <x-partner-nav-item route="partner.subscription.index" icon="credit-card" label="{{ __('partner.nav.subscription') }}" />
        </x-partner-nav-group>

        <x-partner-nav-group label="{{ __('partner.nav.marketing') }}">
            <x-partner-nav-item route="partner.flash-sales.index" icon="bolt" label="{{ __('partner.nav.flash_sales') }}" />
            <x-partner-nav-item route="partner.coupons.index" icon="ticket" label="{{ __('partner.nav.coupons') }}" />
            <x-partner-nav-item route="partner.ads.index" icon="megaphone" label="{{ __('partner.nav.ads') }}" />
            <x-partner-nav-item route="partner.marketer-campaigns.index" icon="user-group" label="{{ __('partner.nav.marketer_campaigns') }}" />
            <x-partner-nav-item route="partner.marketer-samples.index" icon="inbox-stack" label="{{ __('partner.nav.sample_requests') }}" />
            <x-partner-nav-item route="partner.campaign-offers.index" icon="gift" label="{{ __('partner.nav.campaign_offers') }}" />
        </x-partner-nav-group>

        <x-partner-nav-group label="{{ __('partner.nav.open_market') }}">
            <x-partner-nav-item route="partner.classifieds.index" icon="squares-plus" label="{{ __('partner.nav.my_classifieds') }}" />
        </x-partner-nav-group>

        <x-partner-nav-item route="partner.performance.index" icon="chart-bar" label="{{ __('partner.nav.performance') }}" />
        <x-partner-nav-item route="partner.support.tickets.index" icon="chat-bubble-left" label="{{ __('partner.nav.support') }}" />

        <x-partner-nav-group label="{{ __('partner.nav.settings') }}">
            <x-partner-nav-item route="partner.profile.index" icon="building-storefront" label="{{ __('partner.nav.my_profile') }}" />
            @if ($vendorAdmin?->can('team.view'))
                <x-partner-nav-item route="partner.team.index" icon="users" label="{{ __('partner.nav.team') }}" />
            @endif
            <x-partner-nav-item route="partner.bank-accounts.index" icon="building-library" label="{{ __('partner.nav.bank_accounts') }}" />
            <x-partner-nav-item route="partner.documents.index" icon="document-check" label="{{ __('partner.nav.documents') }}" />
        </x-partner-nav-group>

    </nav>

    {{-- Bottom: logout --}}
    <div class="px-3 py-4 border-t border-gray-800 shrink-0">
        <form method="POST" action="{{ route('partner.logout') }}">
            @csrf
            <button type="submit" class="w-full flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium
                           text-gray-400 hover:text-red-400 hover:bg-red-400/10 transition-colors">
                <x-heroicon name="arrow-left-on-rectangle" class="w-4 h-4 shrink-0" />
                <span>{{ __('partner.nav.logout') }}</span>
            </button>
        </form>
    </div>

</aside>
