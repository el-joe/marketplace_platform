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
        <div class="modal-box max-w-4xl">

            {{-- Header --}}
            <div class="flex items-start justify-between p-6 border-b border-gray-100">
                <div>
                    <h3 class="text-xl font-bold text-gray-900" id="promo-modal-title">New Secret Promotion</h3>
                    <p class="text-sm text-gray-500 mt-0.5">الترويج السري — Commission splits are visible only to admin.</p>
                </div>
                <button type="button" data-modal-close
                    class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg p-1.5 transition-colors text-xl leading-none">&times;</button>
            </div>

            {{-- Security banner --}}
            <div class="mx-6 mt-4 bg-amber-50 border border-amber-200 rounded-xl px-4 py-2.5 flex items-center gap-2.5">
                <svg class="w-4 h-4 text-amber-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                </svg>
                <p class="text-xs text-amber-700">Split values are only visible to admin. Marketer sees only their %. Vendor sees only total %.</p>
            </div>

            <form id="promo-form">
                <input type="hidden" id="promo-id">

                <div class="p-6 grid grid-cols-1 lg:grid-cols-2 gap-8">

                    {{-- LEFT — Product & Parties --}}
                    <div class="space-y-5">
                        <div class="flex items-center gap-2 mb-1">
                            <div class="w-1 h-4 bg-primary-500 rounded-full"></div>
                            <h4 class="text-xs font-bold text-gray-500 uppercase tracking-widest">Product &amp; Parties</h4>
                        </div>

                        <div>
                            <label class="form-label">Vendor <span class="text-red-500">*</span></label>
                            <select id="vendor-select" class="form-input w-full">
                                <option value="">Select vendor…</option>
                                @foreach($vendors as $v)
                                    <option value="{{ $v->id }}">{{ $v->store_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="form-label">Product listing <span class="text-red-500">*</span></label>
                            <select id="listing-select" class="form-input w-full" disabled>
                                <option value="">Select vendor first…</option>
                            </select>
                            <div id="listing-preview" class="mt-2 p-3 bg-gray-50 border border-gray-200 rounded-xl gap-3" style="display:none">
                                <img id="listing-preview-img" class="w-12 h-12 rounded-lg object-contain bg-white shrink-0 border border-gray-100" src="" alt="">
                                <div class="min-w-0">
                                    <p id="listing-preview-name" class="text-sm font-semibold text-gray-900 truncate"></p>
                                    <p id="listing-preview-price" class="text-base font-bold text-primary-700 mt-0.5"></p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="form-label">
                                Marketer
                                <span class="text-xs text-gray-400 font-normal ml-1">(leave empty = open to all)</span>
                            </label>
                            <select id="marketer-select" class="form-input w-full">
                                <option value="">Open to all eligible marketers</option>
                                @foreach($marketers as $m)
                                    <option value="{{ $m->id }}">{{ $m->name }} ({{ $m->type }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="form-label">
                                Valid until
                                <span class="text-xs text-gray-400 font-normal ml-1">(optional)</span>
                            </label>
                            <input type="date" id="valid-until-input" class="form-input w-full"
                                min="{{ now()->addDay()->format('Y-m-d') }}">
                            <p class="text-xs text-gray-400 mt-1">Leave empty for no expiry</p>
                        </div>

                        {{-- True product value (moved here for logical flow) --}}
                        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                            <label class="block text-xs font-bold text-amber-800 mb-2 uppercase tracking-wide">
                                🔒 True product cost <span class="text-red-500">*</span>
                            </label>
                            <div class="flex">
                                <input type="number" id="product-value-input" step="0.01" min="0.01"
                                    class="form-input rounded-r-none w-full border-amber-300 bg-white" placeholder="e.g. 100.00">
                                <span id="product-value-currency"
                                    class="inline-flex items-center px-3 bg-amber-100 border border-l-0 border-amber-300 rounded-r-lg text-sm font-semibold text-amber-700">—</span>
                            </div>
                            <p class="text-xs text-amber-600 mt-1.5">Actual supplier cost — used for margin analysis only. Never shown to anyone outside admin.</p>
                        </div>
                    </div>

                    {{-- RIGHT — Commission Calculator --}}
                    <div class="space-y-5">
                        <div class="flex items-center gap-2 mb-1">
                            <div class="w-1 h-4 bg-amber-500 rounded-full"></div>
                            <h4 class="text-xs font-bold text-gray-500 uppercase tracking-widest">Commission Split <span class="text-amber-600 normal-case font-normal">🔒 Admin only</span></h4>
                        </div>

                        {{-- Total % --}}
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="form-label mb-0">Total — Vendor pays % <span class="text-red-500">*</span></label>
                                <span class="text-xs text-blue-600 bg-blue-50 border border-blue-200 px-2 py-0.5 rounded-full font-medium">Vendor knows this</span>
                            </div>
                            <div class="flex">
                                <input type="number" id="total-pct-input" step="0.01" min="0.01" max="99"
                                    class="form-input rounded-r-none w-full border-blue-300 focus:border-blue-500"
                                    placeholder="e.g. 15.00">
                                <span class="inline-flex items-center px-3 bg-blue-50 border border-l-0 border-blue-300 rounded-r-lg text-sm font-bold text-blue-700">%</span>
                            </div>
                            <p id="total-per-sale-preview" class="text-xs text-blue-500 mt-1 h-4"></p>
                        </div>

                        {{-- Marketer % --}}
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="form-label mb-0">Marketer gets % <span class="text-red-500">*</span></label>
                                <span class="text-xs text-green-600 bg-green-50 border border-green-200 px-2 py-0.5 rounded-full font-medium">Marketer sees this</span>
                            </div>
                            <div class="flex">
                                <input type="number" id="marketer-pct-input" step="0.01" min="0.01"
                                    class="form-input rounded-r-none w-full border-green-300 focus:border-green-500"
                                    placeholder="e.g. 10.00">
                                <span class="inline-flex items-center px-3 bg-green-50 border border-l-0 border-green-300 rounded-r-lg text-sm font-bold text-green-700">%</span>
                            </div>
                            <p id="marketer-per-sale-preview" class="text-xs text-green-500 mt-1 h-4"></p>
                        </div>

                        {{-- Admin % (auto-calculated) --}}
                        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                            <div class="flex items-center justify-between mb-3">
                                <label class="text-xs font-bold text-amber-800 uppercase tracking-wide">🔒 Admin cut (auto)</label>
                                <span class="text-xs text-amber-600 bg-amber-100 border border-amber-200 px-2 py-0.5 rounded-full font-medium">Platform silent cut</span>
                            </div>
                            <div class="flex items-center gap-4">
                                <div class="flex-1">
                                    <input type="text" id="admin-pct-display"
                                        class="form-input bg-white border-amber-300 text-amber-800 font-bold text-2xl cursor-not-allowed text-center w-full"
                                        readonly value="0.00 %">
                                    <input type="hidden" id="admin-pct-hidden" value="0">
                                </div>
                                <div class="text-right shrink-0">
                                    <p id="admin-per-sale-preview" class="text-lg font-bold text-amber-700">—</p>
                                    <p class="text-xs text-amber-500">per sale</p>
                                </div>
                            </div>
                        </div>

                        {{-- Visual split bar --}}
                        <div>
                            <p class="text-xs font-medium text-gray-500 mb-2">Commission split visual</p>
                            <div class="h-6 rounded-lg overflow-hidden flex shadow-inner bg-gray-100" id="split-bar">
                                <div id="split-bar-marketer"
                                    class="bg-green-500 transition-all duration-300 flex items-center justify-center text-white text-xs font-bold"
                                    style="width:0%"></div>
                                <div id="split-bar-admin"
                                    class="bg-amber-500 transition-all duration-300 flex items-center justify-center text-white text-xs font-bold"
                                    style="width:0%"></div>
                                <div class="bg-gray-200 flex-1 flex items-center justify-center text-gray-400 text-xs font-medium">
                                    vendor margin</div>
                            </div>
                            <div class="flex text-xs mt-2 gap-4 text-gray-500">
                                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 bg-green-500 rounded-full inline-block"></span>Marketer</span>
                                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 bg-amber-500 rounded-full inline-block"></span>Admin</span>
                                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 bg-gray-200 rounded-full inline-block border border-gray-300"></span>Vendor margin</span>
                            </div>
                        </div>

                        {{-- Margin analysis --}}
                        <div id="margin-analysis" class="hidden bg-gray-50 border border-gray-200 rounded-xl p-4 space-y-2">
                            <p class="text-xs font-bold text-gray-600 uppercase tracking-wide mb-3">Margin analysis</p>
                            <div class="flex justify-between text-sm py-1"><span class="text-gray-500">Listing price</span><span id="ma-price" class="font-semibold text-gray-800">—</span></div>
                            <div class="flex justify-between text-sm py-1"><span class="text-amber-600">🔒 Product cost</span><span id="ma-cost" class="font-semibold text-amber-700">—</span></div>
                            <div class="flex justify-between text-sm py-1 border-t border-gray-200 mt-1"><span class="text-gray-500">Gross margin</span><span id="ma-margin" class="font-bold text-primary-700">—</span></div>
                            <div class="flex justify-between text-sm py-1"><span class="text-green-600">Marketer earns</span><span id="ma-marketer" class="font-semibold text-green-700">—</span></div>
                            <div class="flex justify-between text-sm py-1"><span class="text-amber-600">🔒 Admin earns</span><span id="ma-admin" class="font-semibold text-amber-700">—</span></div>
                            <div class="flex justify-between text-sm py-1 border-t border-gray-200 mt-1"><span class="font-semibold text-gray-700">Net to vendor</span><span id="ma-vendor-net" class="font-bold text-gray-900">—</span></div>
                        </div>

                        {{-- Validation errors --}}
                        <div id="split-errors" class="hidden space-y-1"></div>
                    </div>

                </div>

                {{-- Footer --}}
                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-100 bg-gray-50 rounded-b-2xl">
                    <button type="button" data-modal-close class="btn btn-ghost btn-sm">Cancel</button>
                    <button type="submit" id="promo-save-btn" class="btn btn-primary">Save secret promotion</button>
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