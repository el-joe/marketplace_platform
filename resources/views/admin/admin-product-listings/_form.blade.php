{{--
    Shared form partial for create/edit AdminProductListing.
    Requires: $countries, $nawyCategories, $productVariants
    Optional: $listing (edit mode)
--}}
@php
    $listing = $listing ?? null;
    $isEdit  = $listing !== null;
    $val = fn(string $f, $d = '') => old($f, $isEdit ? ($listing->{$f} ?? $d) : $d);
    $bool = fn(string $f, bool $d = false): bool => (bool) old($f, $isEdit ? ($listing->{$f} ?? $d) : $d);
@endphp

<div class="space-y-6 max-w-3xl">

    {{-- ── Core listing fields ─────────────────────────────────────────── --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm space-y-4">
        <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Listing Details</h2>

        <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Product Variant <span class="text-red-500">*</span></label>
                <select name="product_variant_id" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <option value="">Select variant…</option>
                    @foreach($productVariants as $variant)
                        <option value="{{ $variant->id }}"
                                {{ $val('product_variant_id') === $variant->id ? 'selected' : '' }}>
                            {{ $variant->product?->name_en }} — {{ $variant->sku }}
                        </option>
                    @endforeach
                </select>
                @error('product_variant_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Country <span class="text-red-500">*</span></label>
                <select name="country_id" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <option value="">Select country…</option>
                    @foreach($countries as $country)
                        <option value="{{ $country->id }}"
                                {{ $val('country_id') === $country->id ? 'selected' : '' }}>
                            {{ $country->name_en }}
                        </option>
                    @endforeach
                </select>
                @error('country_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status <span class="text-red-500">*</span></label>
                <select name="status" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                    @foreach(['active' => 'Active', 'paused' => 'Paused', 'archived' => 'Archived'] as $v => $l)
                        <option value="{{ $v }}" {{ $val('status', 'active') === $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
                @error('status')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Price (cents) <span class="text-red-500">*</span></label>
                <input type="number" name="price_cents" min="0" required
                       value="{{ $val('price_cents', 0) }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                @error('price_cents')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Currency <span class="text-red-500">*</span></label>
                <input type="text" name="currency" maxlength="3" required
                       value="{{ $val('currency', 'SAR') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono uppercase focus:outline-none focus:ring-2 focus:ring-primary-500">
                @error('currency')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Shipping Cost (cents)</label>
                <input type="number" name="shipping_cost_cents" min="0"
                       value="{{ $val('shipping_cost_cents', 0) }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Commission Type <span class="text-red-500">*</span></label>
                <select name="commission_type" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                    @foreach(['percentage' => 'Percentage', 'fixed' => 'Fixed', 'mixed' => 'Mixed'] as $v => $l)
                        <option value="{{ $v }}" {{ $val('commission_type', 'percentage') === $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Commission Value <span class="text-red-500">*</span></label>
                <input type="number" name="commission_value" min="0" step="0.01" required
                       value="{{ $val('commission_value', 0) }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
            </div>
        </div>

        {{-- Toggles --}}
        <div class="flex flex-wrap gap-6 pt-2">
            @foreach([
                ['name' => 'is_exclusive',         'label' => 'Exclusive (admin-only)'],
                ['name' => 'available_for_vendors',  'label' => 'Available for Vendors'],
                ['name' => 'available_for_marketers','label' => 'Available for Marketers'],
            ] as $toggle)
            <label class="flex items-center gap-2 cursor-pointer select-none">
                <input type="hidden" name="{{ $toggle['name'] }}" value="0">
                <input type="checkbox" name="{{ $toggle['name'] }}" value="1"
                       {{ $bool($toggle['name'], true) ? 'checked' : '' }}
                       class="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                <span class="text-sm text-gray-700">{{ $toggle['label'] }}</span>
            </label>
            @endforeach
        </div>
    </div>

    {{-- ── Now Nawy settings ───────────────────────────────────────────── --}}
    <div class="bg-white rounded-xl border border-primary-200 p-6 shadow-sm space-y-4">
        <div class="flex items-center gap-2">
            <x-heroicon name="sparkles" class="w-5 h-5 text-primary-600" />
            <h2 class="text-sm font-semibold text-primary-700 uppercase tracking-wide">Now Nawy Settings</h2>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fulfillment Type <span class="text-red-500">*</span></label>
                <select name="fulfillment_type" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                    @foreach(['express' => '🚀 Express', 'global' => '🌍 Global', 'mixed' => 'Mixed'] as $v => $l)
                        <option value="{{ $v }}" {{ $val('fulfillment_type', 'mixed') === $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
                @error('fulfillment_type')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Payment Options <span class="text-red-500">*</span></label>
                <select name="payment_options" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                    @foreach(['both' => 'Both (COD + Electronic)', 'cod_only' => 'COD Only', 'electronic_only' => 'Electronic Only'] as $v => $l)
                        <option value="{{ $v }}" {{ $val('payment_options', 'both') === $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
                @error('payment_options')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Nawy Category</label>
                <select name="nawy_category_id"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <option value="">— No category —</option>
                    @foreach($nawyCategories as $cat)
                        <option value="{{ $cat->id }}"
                                {{ $val('nawy_category_id') === $cat->id ? 'selected' : '' }}>
                            {{ $cat->name_en }} / {{ $cat->name_ar }}
                        </option>
                    @endforeach
                </select>
                @error('nawy_category_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <label class="flex items-center gap-2 cursor-pointer select-none">
            <input type="hidden" name="featured_in_nawy" value="0">
            <input type="checkbox" name="featured_in_nawy" value="1"
                   {{ $bool('featured_in_nawy', true) ? 'checked' : '' }}
                   class="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
            <span class="text-sm font-medium text-primary-700">Feature in Now Nawy feed</span>
        </label>

        @if($isEdit)
        <div class="pt-1">
            <a href="{{ route('admin.admin-product-listings.nawy-preview', $listing) }}"
               target="_blank"
               class="inline-flex items-center gap-2 px-4 py-2 border border-primary-300 text-primary-700 text-sm font-medium rounded-lg hover:bg-primary-50 transition-colors">
                <x-heroicon name="device-phone-mobile" class="w-4 h-4" />
                Preview in Now Nawy
            </a>
        </div>
        @endif
    </div>

    {{-- Submit --}}
    <div class="flex items-center gap-3">
        <button type="submit"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary-600 text-white text-sm font-semibold rounded-lg hover:bg-primary-700 transition-colors">
            <x-heroicon name="check" class="w-4 h-4" />
            {{ $isEdit ? 'Save Changes' : 'Create Listing' }}
        </button>
        <a href="{{ route('admin.admin-product-listings.index') }}"
           class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
    </div>

</div>
