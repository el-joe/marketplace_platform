@php
    $isAr = session('locale', 'ar') === 'ar';
    $vendorAdmin = auth()->guard('vendor')->user();
    $vendor = $vendorAdmin?->vendor;
@endphp

{{-- Sidebar: 240px, dark --}}
<aside class="w-60 shrink-0 bg-gray-900 border-e border-gray-800 flex flex-col h-full overflow-y-auto">

    {{-- Logo --}}
    <div class="flex items-center gap-2 px-5 h-16 border-b border-gray-800 shrink-0">
        <span class="bg-yellow-400 text-gray-950 font-black text-lg px-2 py-0.5 rounded">noon</span>
        <span class="text-white text-xs font-semibold">{{ $isAr ? 'للبائعين' : 'Sellers' }}</span>
    </div>

    {{-- Store info --}}
    <div class="px-5 py-4 border-b border-gray-800 shrink-0">
        <div class="flex items-center gap-3">
            <div
                class="w-9 h-9 rounded-full bg-yellow-400/20 flex items-center justify-center text-yellow-400 font-bold text-sm shrink-0">
                {{ mb_substr($vendor?->store_name ?? 'S', 0, 1) }}
            </div>
            <div class="min-w-0">
                <div class="text-white text-xs font-bold truncate">{{ $vendor?->store_name ?? 'My Store' }}</div>
                <div class="flex items-center gap-1 mt-0.5">
                    <span
                        class="w-1.5 h-1.5 rounded-full {{ $vendor?->global_status === 'active' ? 'bg-green-400' : 'bg-yellow-400' }}"></span>
                    <span class="text-gray-400 text-xs">
                        {{ $vendor?->global_status === 'active' ? ($isAr ? 'نشط' : 'Active') : ($isAr ? 'قيد المراجعة' : 'Under Review') }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Nav --}}
    <nav class="flex-1 px-3 py-4 space-y-0.5">
        @php
            $navItems = [
                [
                    'route' => 'partner.dashboard',
                    'icon' => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9,22 9,12 15,12 15,22"/>',
                    'label_ar' => 'الرئيسية',
                    'label_en' => 'Dashboard'
                ],
                [
                    'route' => '#',
                    'icon' => '<path d="M20 7H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>',
                    'label_ar' => 'المنتجات',
                    'label_en' => 'Products'
                ],
                [
                    'route' => '#',
                    'icon' => '<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/>',
                    'label_ar' => 'الطلبات',
                    'label_en' => 'Orders'
                ],
                [
                    'route' => '#',
                    'icon' => '<rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/>',
                    'label_ar' => 'الإعلانات',
                    'label_en' => 'Advertising'
                ],
                [
                    'route' => '#',
                    'icon' => '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>',
                    'label_ar' => 'التحليلات',
                    'label_en' => 'Analytics'
                ],
                [
                    'route' => '#',
                    'icon' => '<rect x="2" y="2" width="20" height="8" rx="2" ry="2"/><rect x="2" y="14" width="20" height="8" rx="2" ry="2"/><line x1="6" y1="6" x2="6.01" y2="6"/><line x1="6" y1="18" x2="6.01" y2="18"/>',
                    'label_ar' => 'المخزون',
                    'label_en' => 'Inventory'
                ],
                [
                    'route' => '#',
                    'icon' => '<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>',
                    'label_ar' => 'المدفوعات',
                    'label_en' => 'Payouts'
                ],
                [
                    'route' => '#',
                    'icon' => '<circle cx="12" cy="12" r="3"/><path d="M19.07 4.93l-1.41 1.41M12 2v2m6.36 13.64-1.41-1.41M22 12h-2M4.93 19.07l1.41-1.41M12 22v-2M5.64 5.64l1.41 1.41M2 12h2"/>',
                    'label_ar' => 'الإعدادات',
                    'label_en' => 'Settings'
                ],
            ];
        @endphp

        @foreach($navItems as $item)
            @php
                $isActive = $item['route'] !== '#' && request()->routeIs($item['route']);
            @endphp
            <a href="{{ $item['route'] !== '#' ? route($item['route']) : '#' }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all
                      {{ $isActive
            ? 'bg-yellow-400/15 text-yellow-400 border border-yellow-400/20'
            : 'text-gray-400 hover:text-white hover:bg-gray-800/60' }}
                      {{ $isAr ? 'flex-row-reverse' : '' }}">
                <svg class="w-4.5 h-4.5 shrink-0" width="18" height="18" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    {!! $item['icon'] !!}
                </svg>
                <span>{{ $isAr ? $item['label_ar'] : $item['label_en'] }}</span>
            </a>
        @endforeach
    </nav>

    {{-- Bottom: logout --}}
    <div class="px-3 py-4 border-t border-gray-800 shrink-0">
        <form method="POST" action="{{ route('partner.logout') }}">
            @csrf
            <button type="submit" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium
                           text-gray-400 hover:text-red-400 hover:bg-red-400/10 transition-all
                           {{ $isAr ? 'flex-row-reverse' : '' }}">
                <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                    <polyline points="16,17 21,12 16,7" />
                    <line x1="21" y1="12" x2="9" y2="12" />
                </svg>
                <span>{{ $isAr ? 'تسجيل الخروج' : 'Sign Out' }}</span>
            </button>
        </form>
    </div>

</aside>