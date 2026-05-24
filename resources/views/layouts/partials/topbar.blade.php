@php
    /** @var array $breadcrumbs */
    $user = auth()->user();
@endphp

<header id="topbar" class="bg-white border-b border-gray-200 px-4 lg:px-6 flex items-center gap-4">
    {{-- Mobile hamburger --}}
    <button id="mobile-menu-btn" type="button" class="md:hidden p-2 rounded-lg text-gray-600 hover:bg-gray-100"
        aria-label="Open menu">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
    </button>

    {{-- Breadcrumb --}}
    <nav aria-label="Breadcrumb" class="hidden sm:flex items-center text-sm min-w-0">
        <ol class="flex items-center gap-1 text-gray-500 truncate">
            <li>
                <a href="{{ \Illuminate\Support\Facades\Route::has('admin.dashboard') ? route('admin.dashboard') : '/' }}"
                    class="hover:text-gray-700 inline-flex items-center">
                    <x-heroicon name="home" class="w-4 h-4" />
                </a>
            </li>
            @foreach($breadcrumbs as $i => $crumb)
                <li class="inline-flex items-center">
                    <svg class="w-4 h-4 mx-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                    @if($i === count($breadcrumbs) - 1 || empty($crumb['url']))
                        <span class="text-gray-900 font-medium">{{ $crumb['label'] }}</span>
                    @else
                        <a href="{{ $crumb['url'] }}" class="hover:text-gray-700">{{ $crumb['label'] }}</a>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>

    <div class="ml-auto flex items-center gap-2">
        {{-- Global search trigger --}}
        <button type="button" id="global-search-btn" class="hidden sm:inline-flex items-center gap-2 px-3 py-1.5 rounded-lg
                       border border-gray-200 text-sm text-gray-500 hover:bg-gray-50">
            <x-heroicon name="magnifying-glass" class="w-4 h-4" />
            <span>Search…</span>
            <kbd class="ml-2 px-1.5 py-0.5 text-[10px] bg-gray-100 rounded border border-gray-200">⌘K</kbd>
        </button>

        {{-- Notifications --}}
        <div class="relative" x-data="{ open: false }">
            <button type="button" @click="open = !open" class="relative p-2 rounded-lg text-gray-600 hover:bg-gray-100">
                <x-heroicon name="bell" class="w-5 h-5" />
                <span id="notif-badge" class="hidden absolute -top-0.5 -right-0.5 inline-flex items-center justify-center
                             min-w-[18px] h-[18px] px-1 rounded-full bg-danger-600 text-white text-[10px] font-bold">
                    0
                </span>
            </button>
            <div x-show="open" @click.outside="open = false" x-cloak
                class="absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-lg border border-gray-200 z-50">
                <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                    <h4 class="text-sm font-semibold">Notifications</h4>
                    <button type="button" id="notif-mark-all-read" class="text-xs text-primary-600 hover:underline">Mark
                        all read</button>
                </div>
                <div id="notif-list" class="max-h-80 overflow-y-auto">
                    <div class="p-6 text-center text-sm text-gray-500">Loading…</div>
                </div>
                <div class="px-4 py-2 border-t border-gray-100 text-center">
                    <a href="{{ \Illuminate\Support\Facades\Route::has('admin.notifications.index') ? route('admin.notifications.index') : '#' }}"
                        class="text-xs text-primary-600 hover:underline">View all</a>
                </div>
            </div>
        </div>

        {{-- Country selector --}}
        <div class="relative" x-data="{ open: false }">
            <button type="button" @click="open = !open" class="hidden md:inline-flex items-center gap-2 px-3 py-1.5 rounded-lg
                           text-sm text-gray-700 hover:bg-gray-100">
                <x-heroicon name="globe-alt" class="w-4 h-4" />
                <span>{{ session('admin_country', 'EG') }}</span>
                <x-heroicon name="chevron-down" class="w-4 h-4" />
            </button>
            <div x-show="open" @click.outside="open = false" x-cloak
                class="absolute right-0 mt-2 w-44 bg-white rounded-lg shadow-lg border border-gray-200 z-50 py-1">
                @foreach(['EG' => 'Egypt', 'SA' => 'Saudi Arabia', 'AE' => 'UAE'] as $code => $name)
                    <button type="button" data-country="{{ $code }}"
                        class="country-switch w-full text-left px-3 py-1.5 text-sm hover:bg-gray-50">
                        {{ $name }} <span class="text-gray-400 text-xs">({{ $code }})</span>
                    </button>
                @endforeach
            </div>
        </div>

        {{-- User dropdown --}}
        <div class="relative" x-data="{ open: false }">
            <button type="button" @click="open = !open"
                class="flex items-center gap-2 px-2 py-1 rounded-lg hover:bg-gray-100">
                <div
                    class="w-8 h-8 rounded-full bg-primary-600 text-white inline-flex items-center justify-center text-sm font-semibold">
                    {{ strtoupper(mb_substr($user?->name ?? 'A', 0, 1)) }}
                </div>
                <span
                    class="hidden lg:inline text-sm text-gray-700 truncate max-w-[140px]">{{ $user?->name ?? 'Admin' }}</span>
                <x-heroicon name="chevron-down" class="w-4 h-4 text-gray-400" />
            </button>
            <div x-show="open" @click.outside="open = false" x-cloak
                class="absolute right-0 mt-2 w-52 bg-white rounded-lg shadow-lg border border-gray-200 z-50 py-1">
                <div class="px-3 py-2 border-b border-gray-100">
                    <div class="text-sm font-medium text-gray-900 truncate">{{ $user?->name ?? 'Admin' }}</div>
                    <div class="text-xs text-gray-500 truncate">{{ $user?->email ?? '' }}</div>
                </div>
                <a href="{{ \Illuminate\Support\Facades\Route::has('admin.profile.edit') ? route('admin.profile.edit') : '#' }}"
                    class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">Profile</a>
                <a href="{{ \Illuminate\Support\Facades\Route::has('vendor.dashboard') ? route('vendor.dashboard') : '#' }}"
                    class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">Switch to Vendor</a>
                <div class="border-t border-gray-100 my-1"></div>
                <form method="POST"
                    action="{{ \Illuminate\Support\Facades\Route::has('admin.logout') ? route('admin.logout') : '#' }}">
                    @csrf
                    <button type="submit" class="w-full text-left px-3 py-2 text-sm text-danger-600 hover:bg-danger-50">
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>