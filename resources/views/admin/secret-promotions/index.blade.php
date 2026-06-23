@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/column-renderers.js'])
@endpush

@section('title', 'Secret Promotions')

@section('content')

    {{-- ─── Page Header ─────────────────────────────────────────────────────────── --}}
    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Secret Promotions <span class="text-gray-400 font-normal text-lg">—
                    الترويج السري</span></h1>
            <p class="text-sm text-gray-500 mt-0.5">Hidden commission splits between vendors, marketers and the platform.
                All financial details shown only to admin.</p>
        </div>
        <button type="button" id="create-promo-btn" class="btn btn-primary btn-sm">
            + New Secret Promotion
        </button>
    </div>

    {{-- ─── Security notice ─────────────────────────────────────────────────────── --}}
    <div class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 flex items-start gap-3 mb-6">
        <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
        </svg>
        <div>
            <p class="text-sm font-semibold text-amber-800">Admin-only visibility</p>
            <p class="text-xs text-amber-700 mt-0.5">Commission splits, product values, and admin share percentages shown
                here are <strong>NEVER</strong> visible to vendors or marketers. Marketers only see their own share. Vendors
                only see the total commission they agreed to.</p>
        </div>
    </div>

    {{-- ─── Stats Row ───────────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Active</p>
            <p class="mt-1 text-2xl font-bold text-success-600">{{ $stats['total_active'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Paused</p>
            <p class="mt-1 text-2xl font-bold text-warning-600">{{ $stats['total_paused'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Expired</p>
            <p class="mt-1 text-2xl font-bold text-gray-500">{{ $stats['total_expired'] }}</p>
        </div>
        <div class="bg-gradient-to-br from-primary-600 to-primary-800 rounded-xl p-4 text-white">
            <p class="text-xs font-medium opacity-70 uppercase tracking-wide">🔒 Admin Revenue (MTD)</p>
            <p class="mt-1 text-2xl font-bold">{{ number_format($stats['admin_revenue_month'] / 100, 2) }}</p>
            <p class="text-xs opacity-60 mt-0.5">Hidden platform earnings</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Avg Admin Share</p>
            <p class="mt-1 text-2xl font-bold text-amber-700">{{ number_format($stats['avg_admin_share'], 1) }}%</p>
            <p class="text-xs text-gray-400 mt-0.5">Active promotions</p>
        </div>
    </div>

    {{-- ─── Filters ─────────────────────────────────────────────────────────────── --}}
    <x-card class="mb-5">
        <div class="flex flex-wrap gap-3 items-end">
            <div class="w-36">
                <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                <select id="filter-status" class="form-input w-full text-sm">
                    <option value="">All statuses</option>
                    <option value="active">Active</option>
                    <option value="paused">Paused</option>
                    <option value="expired">Expired</option>
                </select>
            </div>
            <div class="w-48">
                <label class="block text-xs font-medium text-gray-600 mb-1">Vendor</label>
                <select id="filter-vendor" class="form-input w-full text-sm">
                    <option value="">All vendors</option>
                    @foreach($vendors as $v)
                        <option value="{{ $v->id }}">{{ $v->store_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-48">
                <label class="block text-xs font-medium text-gray-600 mb-1">Marketer</label>
                <select id="filter-marketer" class="form-input w-full text-sm">
                    <option value="">All marketers</option>
                    <option value="open_to_all">Open to all</option>
                    @foreach($marketers as $m)
                        <option value="{{ $m->id }}">{{ $m->name }} ({{ $m->type }})</option>
                    @endforeach
                </select>
            </div>
            <div class="w-44">
                <label class="block text-xs font-medium text-gray-600 mb-1">Expiry</label>
                <select id="filter-expiry" class="form-input w-full text-sm">
                    <option value="">Any expiry</option>
                    <option value="expiring_soon">Expiring within 7 days</option>
                    <option value="no_expiry">No expiry set</option>
                </select>
            </div>
            <button type="button" id="clear-filters-btn" class="btn btn-ghost btn-sm self-end">Reset</button>
        </div>
    </x-card>

    {{-- ─── DataTable ───────────────────────────────────────────────────────────── --}}
    <x-card>
        <table id="secret-promos-table" class="w-full text-sm" style="width:100%">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Vendor</th>
                    <th>Marketer</th>
                    <th>Listing Price</th>
                    <th class="bg-amber-50 text-amber-700">🔒 Product Value</th>
                    <th class="bg-amber-50 text-amber-700">🔒 Margin %</th>
                    <th>Total Comm. %</th>
                    <th>Marketer %</th>
                    <th class="bg-amber-50 text-amber-700">🔒 Admin %</th>
                    <th>Conv.</th>
                    <th>Valid Until</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </x-card>

    {{-- ─── Create / Edit Modal ────────────────────────────────────────────────── --}}
    <div id="promo-modal" class="modal-backdrop hidden">
        <div class="modal-box max-w-3xl">

            {{-- Header --}}
            <div class="flex items-center justify-between mb-4" data-modal-close>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900" id="promo-modal-title">New Secret Promotion</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Commission splits are visible only to admin.</p>
                </div>
                <button type="button" data-modal-close
                    class="text-gray-400 hover:text-gray-600 text-2xl leading-none p-1">&times;</button>
            </div>

            {{-- Security notice --}}
            <div class="bg-amber-50 border border-amber-200 rounded-xl px-3 py-2 flex items-center gap-2 mb-5">
                <svg class="w-4 h-4 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                </svg>
                <p class="text-xs text-amber-700">Split values are only visible to admin. Marketer sees only their %. Vendor
                    sees only total %.</p>
            </div>

            <form id="promo-form">
                <input type="hidden" id="promo-id">

                <div class="grid grid-cols-2 gap-6">

                    {{-- LEFT — Product & Parties --}}
                    <div class="space-y-4">
                        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Product & Parties</h4>

                        <div>
                            <label class="form-label">Vendor <span class="text-red-500">*</span></label>
                            <select id="vendor-select" class="form-input w-full text-sm">
                                <option value="">Select vendor…</option>
                                @foreach($vendors as $v)
                                    <option value="{{ $v->id }}">{{ $v->store_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="form-label">Product listing <span class="text-red-500">*</span></label>
                            <select id="listing-select" class="form-input w-full text-sm" disabled>
                                <option value="">Select vendor first…</option>
                            </select>
                            <div id="listing-preview" class="hidden mt-2 p-3 bg-gray-50 rounded-xl flex items-center gap-3">
                                <img id="listing-preview-img"
                                    class="w-12 h-12 rounded-lg object-contain bg-white flex-shrink-0" src="" alt="">
                                <div>
                                    <p id="listing-preview-name" class="text-sm font-medium text-gray-900"></p>
                                    <p id="listing-preview-price" class="text-base font-bold text-primary-700"></p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="form-label">Marketer <span class="text-xs text-gray-400 font-normal">(leave empty
                                    = open to all)</span></label>
                            <select id="marketer-select" class="form-input w-full text-sm">
                                <option value="">Open to all eligible marketers</option>
                                @foreach($marketers as $m)
                                    <option value="{{ $m->id }}">{{ $m->name }} ({{ $m->type }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="form-label">Valid until <span
                                    class="text-xs text-gray-400 font-normal">(optional)</span></label>
                            <input type="date" id="valid-until-input" class="form-input w-full text-sm"
                                min="{{ now()->addDay()->format('Y-m-d') }}">
                            <p class="text-xs text-gray-400 mt-1">Leave empty for no expiry</p>
                        </div>
                    </div>

                    {{-- RIGHT — Commission Calculator --}}
                    <div class="space-y-4">
                        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Commission Split <span
                                class="text-amber-600 normal-case font-normal">🔒 Admin eyes only</span></h4>

                        {{-- True product value --}}
                        <div>
                            <label class="form-label">🔒 True product value (hidden from all) <span
                                    class="text-red-500">*</span></label>
                            <div class="flex">
                                <input type="number" id="product-value-input" step="0.01" min="0.01"
                                    class="form-input rounded-r-none w-full text-sm" placeholder="e.g. 100.00">
                                <span id="product-value-currency"
                                    class="inline-flex items-center px-3 bg-amber-50 border border-l-0 border-gray-300 rounded-r-lg text-sm text-amber-700 whitespace-nowrap">EGP</span>
                            </div>
                            <p class="text-xs text-amber-600 mt-1">Actual cost — used for margin calculation only. Never
                                shown outside admin.</p>
                        </div>

                        {{-- Split calculator --}}
                        <div class="bg-gray-50 rounded-xl p-4 space-y-4">
                            <p class="text-xs font-semibold text-gray-600 uppercase tracking-wider">Commission split
                                calculator</p>

                            {{-- Total % --}}
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label class="form-label mb-0 text-sm">Total — Vendor pays % <span
                                            class="text-red-500">*</span></label>
                                    <span class="text-xs text-blue-600 bg-blue-50 px-2 py-0.5 rounded">Vendor knows
                                        this</span>
                                </div>
                                <div class="flex">
                                    <input type="number" id="total-pct-input" step="0.01" min="0.01" max="99"
                                        class="form-input rounded-r-none w-full text-sm border-blue-300"
                                        placeholder="e.g. 15.00">
                                    <span
                                        class="inline-flex items-center px-3 bg-blue-50 border border-l-0 border-blue-300 rounded-r-lg text-sm text-blue-700">%</span>
                                </div>
                                <p id="total-per-sale-preview" class="text-xs text-blue-600 mt-1">= per sale</p>
                            </div>

                            {{-- Marketer % --}}
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label class="form-label mb-0 text-sm">Marketer gets % <span
                                            class="text-red-500">*</span></label>
                                    <span class="text-xs text-green-600 bg-green-50 px-2 py-0.5 rounded">Marketer sees
                                        this</span>
                                </div>
                                <div class="flex">
                                    <input type="number" id="marketer-pct-input" step="0.01" min="0.01"
                                        class="form-input rounded-r-none w-full text-sm border-green-300"
                                        placeholder="e.g. 10.00">
                                    <span
                                        class="inline-flex items-center px-3 bg-green-50 border border-l-0 border-green-300 rounded-r-lg text-sm text-green-700">%</span>
                                </div>
                                <p id="marketer-per-sale-preview" class="text-xs text-green-600 mt-1">= per sale</p>
                            </div>

                            {{-- Admin % (read-only, auto-calculated) --}}
                            <div class="bg-amber-50 border border-amber-200 rounded-xl p-3">
                                <div class="flex items-center justify-between mb-2">
                                    <label class="text-xs font-semibold text-amber-700">🔒 Admin gets %
                                        (auto-calculated)</label>
                                    <span class="text-xs text-amber-600 bg-amber-100 px-2 py-0.5 rounded">Platform silent
                                        cut</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="flex-1">
                                        <input type="text" id="admin-pct-display"
                                            class="form-input bg-amber-50 border-amber-300 text-amber-800 font-bold text-lg cursor-not-allowed w-full"
                                            readonly value="0.00 %">
                                        <input type="hidden" id="admin-pct-hidden" value="0">
                                    </div>
                                    <div class="text-right flex-shrink-0">
                                        <p id="admin-per-sale-preview" class="text-sm font-bold text-amber-700">= 0.00</p>
                                        <p class="text-xs text-amber-600">per sale</p>
                                    </div>
                                </div>
                            </div>

                            {{-- Visual split bar --}}
                            <div>
                                <p class="text-xs text-gray-500 mb-1.5">Commission split visual:</p>
                                <div class="h-5 rounded-lg overflow-hidden flex" id="split-bar">
                                    <div id="split-bar-marketer"
                                        class="bg-green-500 transition-all flex items-center justify-center text-white text-xs font-bold"
                                        style="width:0%"></div>
                                    <div id="split-bar-admin"
                                        class="bg-amber-500 transition-all flex items-center justify-center text-white text-xs font-bold"
                                        style="width:0%"></div>
                                    <div class="bg-gray-200 flex-1 flex items-center justify-center text-gray-400 text-xs">
                                        vendor margin</div>
                                </div>
                                <div class="flex text-xs mt-1 gap-4">
                                    <span class="flex items-center gap-1"><span
                                            class="w-2 h-2 bg-green-500 rounded-full inline-block"></span>Marketer</span>
                                    <span class="flex items-center gap-1"><span
                                            class="w-2 h-2 bg-amber-500 rounded-full inline-block"></span>Admin</span>
                                    <span class="flex items-center gap-1"><span
                                            class="w-2 h-2 bg-gray-200 rounded-full inline-block"></span>Vendor
                                        margin</span>
                                </div>
                            </div>

                            {{-- Margin analysis --}}
                            <div id="margin-analysis" class="hidden border-t border-gray-200 pt-3 space-y-1.5">
                                <p class="text-xs font-semibold text-gray-600 uppercase">Margin analysis:</p>
                                <div class="flex justify-between text-sm"><span class="text-gray-500">Listing
                                        price:</span><span id="ma-price" class="font-medium">—</span></div>
                                <div class="flex justify-between text-sm"><span class="text-amber-600">🔒 Product
                                        cost:</span><span id="ma-cost" class="font-medium text-amber-700">—</span></div>
                                <div class="flex justify-between text-sm border-t pt-1.5"><span class="text-gray-500">Gross
                                        margin:</span><span id="ma-margin" class="font-bold text-primary-700">—</span></div>
                                <div class="flex justify-between text-sm"><span class="text-green-600">Marketer
                                        earns:</span><span id="ma-marketer" class="font-medium text-green-700">—</span>
                                </div>
                                <div class="flex justify-between text-sm"><span class="text-amber-600">🔒 Admin
                                        earns:</span><span id="ma-admin" class="font-medium text-amber-700">—</span></div>
                                <div class="flex justify-between text-sm border-t pt-1.5"><span
                                        class="font-semibold text-gray-700">Net to vendor:</span><span id="ma-vendor-net"
                                        class="font-bold text-primary-900">—</span></div>
                            </div>

                            {{-- Validation errors --}}
                            <div id="split-errors" class="hidden space-y-1"></div>
                        </div>
                    </div>

                </div>

                {{-- Footer --}}
                <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-100">
                    <button type="button" data-modal-close class="btn btn-ghost btn-sm">Cancel</button>
                    <button type="submit" id="promo-save-btn" class="btn btn-primary btn-sm">Save secret promotion</button>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
    <script type="module">
        window.MIN_COMMISSION_FLOOR = {{ (float) setting('min_marketer_commission', 5.0) }};
    </script>
    @vite('resources/js/admin/secret-promotions.js')
@endpush