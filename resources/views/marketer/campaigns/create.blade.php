@extends('layouts.marketer')

@section('title', 'New Campaign')
@section('page-title', 'Request a Campaign')

@section('content')

<div class="max-w-2xl">

    <div class="flex items-center gap-2 mb-6">
        <a href="{{ route('marketer.campaigns.index') }}" class="text-sm text-gray-400 hover:text-gray-600">← Campaigns</a>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 p-6">
        <p class="text-sm text-gray-500 mb-5">
            Your campaign request will be reviewed by our team. Once approved, you can start sharing your tracking link.
        </p>

        <form action="{{ route('marketer.campaigns.store') }}" method="POST" id="campaign-form">
            @csrf

            <div class="space-y-5">

                {{-- Name --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Campaign Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}"
                           class="form-input w-full text-sm @error('name') border-red-400 @enderror"
                           placeholder="e.g. Ramadan 2025 Collection">
                    @error('name')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Description --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Description</label>
                    <textarea name="description" rows="3"
                              class="form-input w-full text-sm @error('description') border-red-400 @enderror"
                              placeholder="Briefly describe what you're promoting…">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Campaign Type --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Campaign Type <span class="text-red-500">*</span></label>
                    <select name="campaign_type" id="campaign-type-select" class="form-input w-full text-sm @error('campaign_type') border-red-400 @enderror">
                        <option value="">Select type…</option>
                        @foreach([
                            'referral_link'         => 'Referral Link',
                            'discount_code'         => 'Discount Code',
                            'product_specific'      => 'Product Specific',
                            'brand_deal'            => 'Brand Deal',
                            'classified_promotion'  => 'Promote a Classified Listing',
                            'travel_promotion'      => 'Promote a Travel Package',
                        ] as $value => $label)
                            <option value="{{ $value }}" {{ old('campaign_type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('campaign_type')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Vendor (hidden for classified/travel) --}}
                <div id="vendor-section">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Vendor <span class="text-red-500">*</span></label>
                    <select name="vendor_id" id="vendor-select"
                            class="form-input w-full text-sm @error('vendor_id') border-red-400 @enderror">
                        <option value="">Select a vendor…</option>
                        @foreach($vendors as $vendor)
                            <option value="{{ $vendor->id }}" {{ old('vendor_id') == $vendor->id ? 'selected' : '' }}>
                                {{ $vendor->display_name ?? $vendor->name ?? $vendor->id }}
                            </option>
                        @endforeach
                    </select>
                    @error('vendor_id')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Classified Listing search (single-select) --}}
                <div id="classified-section" class="hidden">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Classified Listing <span class="text-red-500">*</span></label>
                    <div class="flex gap-2 mb-2">
                        <input type="text" id="classified-search-input" class="form-input flex-1 text-sm"
                               placeholder="Search active listings…">
                        <button type="button" id="classified-search-btn" class="btn btn-ghost btn-sm">Search</button>
                    </div>
                    <div id="classified-results" class="border border-gray-200 rounded-xl overflow-hidden hidden">
                        <ul id="classified-list" class="divide-y divide-gray-100 max-h-48 overflow-y-auto text-sm"></ul>
                    </div>
                    <div id="classified-selected" class="mt-2 text-sm text-gray-700 font-medium"></div>
                    <input type="hidden" name="classified_listing_id" id="classified-listing-id" value="{{ old('classified_listing_id') }}">
                    @error('classified_listing_id')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Travel Package search (single-select) --}}
                <div id="travel-section" class="hidden">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Travel Package <span class="text-red-500">*</span></label>
                    <div class="flex gap-2 mb-2">
                        <input type="text" id="travel-search-input" class="form-input flex-1 text-sm"
                               placeholder="Search active travel packages…">
                        <button type="button" id="travel-search-btn" class="btn btn-ghost btn-sm">Search</button>
                    </div>
                    <div id="travel-results" class="border border-gray-200 rounded-xl overflow-hidden hidden">
                        <ul id="travel-list" class="divide-y divide-gray-100 max-h-48 overflow-y-auto text-sm"></ul>
                    </div>
                    <div id="travel-selected" class="mt-2 text-sm text-gray-700 font-medium"></div>
                    <input type="hidden" name="travel_package_id" id="travel-package-id" value="{{ old('travel_package_id') }}">
                    @error('travel_package_id')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Dates --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Start Date</label>
                        <input type="date" name="starts_at" value="{{ old('starts_at') }}"
                               class="form-input w-full text-sm @error('starts_at') border-red-400 @enderror">
                        @error('starts_at')
                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">End Date</label>
                        <input type="date" name="ends_at" value="{{ old('ends_at') }}"
                               class="form-input w-full text-sm @error('ends_at') border-red-400 @enderror">
                        @error('ends_at')
                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Product Search --}}
                <div id="product-search-section">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Products (optional)</label>
                    <div class="flex gap-2 mb-2">
                        <input type="text" id="product-search-input" class="form-input flex-1 text-sm"
                               placeholder="Search products from selected vendor…" disabled>
                        <button type="button" id="search-products-btn"
                                class="btn btn-ghost btn-sm" disabled>Search</button>
                    </div>

                    {{-- Results --}}
                    <div id="product-results" class="border border-gray-200 rounded-xl overflow-hidden hidden">
                        <ul id="product-list" class="divide-y divide-gray-100 max-h-48 overflow-y-auto text-sm"></ul>
                    </div>

                    {{-- Selected products --}}
                    <div id="selected-products" class="mt-2 flex flex-wrap gap-2"></div>
                </div>

            </div>

            <div class="flex gap-3 justify-end mt-8 pt-5 border-t border-gray-100">
                <a href="{{ route('marketer.campaigns.index') }}" class="btn btn-ghost btn-sm">Cancel</a>
                <button type="submit" class="btn btn-primary btn-sm px-8">Submit for Review</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    const campaignTypeSelect  = document.getElementById('campaign-type-select');
    const vendorSection       = document.getElementById('vendor-section');
    const productSearchSection = document.getElementById('product-search-section');
    const classifiedSection   = document.getElementById('classified-section');
    const travelSection       = document.getElementById('travel-section');

    const VENDOR_TYPES = ['referral_link', 'discount_code', 'product_specific', 'brand_deal'];

    function onCampaignTypeChange() {
        const type = campaignTypeSelect.value;
        const isVendor = VENDOR_TYPES.includes(type);
        const isClassified = type === 'classified_promotion';
        const isTravel = type === 'travel_promotion';

        vendorSection.classList.toggle('hidden', !isVendor);
        productSearchSection.classList.toggle('hidden', !isVendor);
        classifiedSection.classList.toggle('hidden', !isClassified);
        travelSection.classList.toggle('hidden', !isTravel);
    }

    campaignTypeSelect.addEventListener('change', onCampaignTypeChange);
    onCampaignTypeChange(); // init on page load

    // ── Vendor / Product search ───────────────────────────────────────────────
    const vendorSelect   = document.getElementById('vendor-select');
    const searchInput    = document.getElementById('product-search-input');
    const searchBtn      = document.getElementById('search-products-btn');
    const productList    = document.getElementById('product-list');
    const productResults = document.getElementById('product-results');
    const selectedBox    = document.getElementById('selected-products');
    const selected       = {};   // id → { id, name }

    vendorSelect.addEventListener('change', function () {
        const hasVendor = !!this.value;
        searchInput.disabled = !hasVendor;
        searchBtn.disabled   = !hasVendor;
        if (!hasVendor) {
            productResults.classList.add('hidden');
            productList.innerHTML = '';
        }
    });

    searchBtn.addEventListener('click', searchProducts);
    searchInput.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); searchProducts(); }});

    function searchProducts() {
        const q        = searchInput.value.trim();
        const vendorId = vendorSelect.value;
        if (!vendorId) return;

        fetch(`{{ route('marketer.campaigns.products.search') }}?vendor_id=${encodeURIComponent(vendorId)}&q=${encodeURIComponent(q)}`)
            .then(r => r.json())
            .then(products => {
                productList.innerHTML = '';
                if (!products.length) {
                    productList.innerHTML = '<li class="px-4 py-2 text-gray-400">No products found.</li>';
                } else {
                    products.forEach(p => {
                        const li = document.createElement('li');
                        li.className = 'px-4 py-2 hover:bg-gray-50 cursor-pointer flex items-center justify-between';
                        li.innerHTML = `
                            <span>${p.name_en ?? p.id}</span>
                            <button type="button" data-id="${p.listing_id}" data-name="${p.name_en ?? p.id}"
                                    class="add-product-btn text-xs bg-yellow-400 hover:bg-yellow-300 text-slate-900 font-semibold rounded-lg px-2 py-0.5">
                                + Add
                            </button>`;
                        productList.appendChild(li);
                    });
                }
                productResults.classList.remove('hidden');
            });
    }

    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('add-product-btn')) {
            const id   = e.target.dataset.id;
            const name = e.target.dataset.name;
            if (!selected[id]) {
                selected[id] = { id, name };
                renderSelected();
            }
        }
        if (e.target.classList.contains('remove-product-btn')) {
            delete selected[e.target.dataset.id];
            renderSelected();
        }
    });

    function renderSelected() {
        selectedBox.innerHTML = Object.values(selected).map(p => `
            <span class="inline-flex items-center gap-1 bg-blue-50 text-blue-700 text-xs rounded-lg px-2 py-1 font-medium">
                ${p.name}
                <button type="button" data-id="${p.id}" class="remove-product-btn ml-1 text-blue-400 hover:text-red-500">✕</button>
                <input type="hidden" name="product_listing_ids[]" value="${p.id}">
            </span>
        `).join('');
    }

    // ── Classified Listing single-select search ───────────────────────────────
    function makeSingleSelectSearch({ inputId, btnId, listId, resultsId, selectedId, hiddenId, searchUrl, labelKey }) {
        const input   = document.getElementById(inputId);
        const btn     = document.getElementById(btnId);
        const list    = document.getElementById(listId);
        const results = document.getElementById(resultsId);
        const display = document.getElementById(selectedId);
        const hidden  = document.getElementById(hiddenId);

        function doSearch() {
            const q = input.value.trim();
            fetch(`${searchUrl}?q=${encodeURIComponent(q)}`)
                .then(r => r.json())
                .then(items => {
                    list.innerHTML = '';
                    if (!items.length) {
                        list.innerHTML = '<li class="px-4 py-2 text-gray-400">No results found.</li>';
                    } else {
                        items.forEach(item => {
                            const li = document.createElement('li');
                            li.className = 'px-4 py-2 hover:bg-gray-50 cursor-pointer flex items-center justify-between';
                            const label = item[labelKey] ?? item.title ?? item.id;
                            const price = item.price ? ` — ${item.price} SAR` : '';
                            li.innerHTML = `
                                <span>${label}${price}</span>
                                <button type="button" data-id="${item.id}" data-label="${label}"
                                        class="pick-btn text-xs bg-yellow-400 hover:bg-yellow-300 text-slate-900 font-semibold rounded-lg px-2 py-0.5">
                                    Select
                                </button>`;
                            list.appendChild(li);
                        });
                    }
                    results.classList.remove('hidden');
                });
        }

        btn.addEventListener('click', doSearch);
        input.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); doSearch(); } });

        list.addEventListener('click', function (e) {
            if (e.target.classList.contains('pick-btn')) {
                hidden.value = e.target.dataset.id;
                display.textContent = '✓ Selected: ' + e.target.dataset.label;
                results.classList.add('hidden');
            }
        });
    }

    makeSingleSelectSearch({
        inputId: 'classified-search-input', btnId: 'classified-search-btn',
        listId: 'classified-list', resultsId: 'classified-results',
        selectedId: 'classified-selected', hiddenId: 'classified-listing-id',
        searchUrl: '{{ route('marketer.campaigns.classifieds.search') }}',
        labelKey: 'title',
    });

    makeSingleSelectSearch({
        inputId: 'travel-search-input', btnId: 'travel-search-btn',
        listId: 'travel-list', resultsId: 'travel-results',
        selectedId: 'travel-selected', hiddenId: 'travel-package-id',
        searchUrl: '{{ route('marketer.campaigns.travel-packages.search') }}',
        labelKey: 'title',
    });
})();
</script>
@endpush
