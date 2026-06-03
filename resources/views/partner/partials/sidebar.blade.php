@php
    $vendorAdmin = auth()->guard('vendor')->user();
    $vendor = $vendorAdmin?->vendor;
    $statusColor = match ($vendor?->global_status) {
        'active' => 'bg-green-400',
        'under_review' => 'bg-yellow-400',
        default => 'bg-gray-500',
    };
    $statusLabel = match ($vendor?->global_status) {
        'active' => 'نشط',
        'under_review' => 'قيد المراجعة',
        default => 'موقوف',
    };
@endphp

{{-- Sidebar: 240px, dark --}}
<aside class="w-60 shrink-0 bg-gray-900 border-e border-gray-800 flex flex-col h-full overflow-y-auto">

    {{-- Logo --}}
    <div class="flex items-center gap-2 px-5 h-16 border-b border-gray-800 shrink-0">
        <span class="bg-yellow-400 text-gray-950 font-black text-lg px-2 py-0.5 rounded">noon</span>
        <span class="text-white text-xs font-semibold">للبائعين</span>
    </div>

    {{-- Store info --}}
    <div class="px-5 py-4 border-b border-gray-800 shrink-0">
        <div class="flex items-center gap-3">
            <div
                class="w-9 h-9 rounded-full bg-yellow-400/20 flex items-center justify-center text-yellow-400 font-bold text-sm shrink-0">
                {{ mb_substr($vendor?->store_name ?? 'S', 0, 1) }}
            </div>
            <div class="min-w-0">
                <div class="text-white text-xs font-bold truncate">{{ $vendor?->store_name ?? 'متجري' }}</div>
                <div class="flex items-center gap-1 mt-0.5">
                    <span class="w-1.5 h-1.5 rounded-full {{ $statusColor }}"></span>
                    <span class="text-gray-400 text-xs">{{ $statusLabel }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Nav --}}
    <nav class="flex-1 px-3 py-4 space-y-0.5">

        <x-partner-nav-item route="partner.dashboard" icon="squares-2x2" label="الرئيسية" />

        <x-partner-nav-group label="الطلبات">
            <x-partner-nav-item route="partner.orders.index" icon="shopping-bag" label="طلباتي" />
            <x-partner-nav-item route="partner.returns.index" icon="arrow-uturn-left" label="المرتجعات" />
        </x-partner-nav-group>

        <x-partner-nav-group label="الكتالوج">
            <x-partner-nav-item route="partner.listings.index" icon="tag" label="قوائم المنتجات" />
            <x-partner-nav-item route="partner.inventory.index" icon="cube" label="المخزون" />
        </x-partner-nav-group>

        <x-partner-nav-group label="التوصيل والتخزين">
            <x-partner-nav-item route="partner.fulfillment.index" icon="truck" label="نظام التوصيل" />
        </x-partner-nav-group>

        <x-partner-nav-group label="المالية">
            <x-partner-nav-item route="partner.payouts.index" icon="banknotes" label="المدفوعات" />
            <x-partner-nav-item route="partner.finance.transactions" icon="credit-card" label="المعاملات" />
            <x-partner-nav-item route="partner.subscription.index" icon="credit-card" label="اشتراكاتي" />
        </x-partner-nav-group>

        <x-partner-nav-group label="التسويق">
            <x-partner-nav-item route="partner.flash-sales.index" icon="bolt" label="التخفيضات السريعة" />
            <x-partner-nav-item route="partner.ads.index" icon="megaphone" label="الإعلانات" />
            <x-partner-nav-item route="partner.marketer-campaigns.index" icon="user-group" label="حملات المسوّقين" />
            <x-partner-nav-item route="partner.marketer-samples.index" icon="inbox-stack" label="طلبات العينات" />
        </x-partner-nav-group>

        <x-partner-nav-item route="partner.performance.index" icon="chart-bar" label="الأداء" />
        <x-partner-nav-item route="partner.support.tickets.index" icon="chat-bubble-left" label="الدعم الفني" />

        <x-partner-nav-group label="الإعدادات">
            <x-partner-nav-item route="partner.profile.index" icon="building-storefront" label="الملف الشخصي" />
            <x-partner-nav-item route="partner.team.index" icon="users" label="الفريق" />
            <x-partner-nav-item route="partner.bank-accounts.index" icon="building-library" label="الحسابات البنكية" />
            <x-partner-nav-item route="partner.documents.index" icon="document-check" label="المستندات" />
        </x-partner-nav-group>

    </nav>

    {{-- Bottom: logout --}}
    <div class="px-3 py-4 border-t border-gray-800 shrink-0">
        <form method="POST" action="{{ route('partner.logout') }}">
            @csrf
            <button type="submit" class="w-full flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium
                           text-gray-400 hover:text-red-400 hover:bg-red-400/10 transition-colors">
                <x-heroicon name="arrow-left-on-rectangle" class="w-4 h-4 shrink-0" />
                <span>تسجيل الخروج</span>
            </button>
        </form>
    </div>

</aside>