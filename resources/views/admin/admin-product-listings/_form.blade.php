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
        <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">{{ __('admin.admin_product_listings.listing_details') }}</h2>

        <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.admin_product_listings.product_variant_required') }} <span class="text-red-500">*</span></label>
                <select name="product_variant_id" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <option value="">{{ __('admin.admin_product_listings.select_variant') }}</option>
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
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.admin_product_listings.country_required') }} <span class="text-red-500">*</span></label>
                <select name="country_id" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <option value="">{{ __('admin.admin_product_listings.select_country') }}</option>
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
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.admin_product_listings.status_required') }} <span class="text-red-500">*</span></label>
                <select name="status" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                    @foreach(['active' => __('admin.admin_product_listings.active'), 'paused' => __('admin.admin_product_listings.paused'), 'archived' => __('admin.admin_product_listings.archived')] as $v => $l)
                        <option value="{{ $v }}" {{ $val('status', 'active') === $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
                @error('status')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.admin_product_listings.price_cents_required') }} <span class="text-red-500">*</span></label>
                <input type="number" name="price_cents" min="0" required
                       value="{{ $val('price_cents', 0) }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                @error('price_cents')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.admin_product_listings.currency_required') }} <span class="text-red-500">*</span></label>
                <input type="text" name="currency" maxlength="3" required dir="ltr"
                       value="{{ $val('currency', $isEdit ? ($listing->country?->currency_code ?? '') : '') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono uppercase focus:outline-none focus:ring-2 focus:ring-primary-500">
                @error('currency')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.admin_product_listings.shipping_cost_cents') }}</label>
                <input type="number" name="shipping_cost_cents" min="0"
                       value="{{ $val('shipping_cost_cents', 0) }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.admin_product_listings.commission_type_required') }} <span class="text-red-500">*</span></label>
                <select name="commission_type" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                    @foreach(['percentage' => __('admin.admin_product_listings.percentage'), 'fixed' => __('admin.admin_product_listings.fixed'), 'mixed' => __('admin.admin_product_listings.mixed')] as $v => $l)
                        <option value="{{ $v }}" {{ $val('commission_type', 'percentage') === $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.admin_product_listings.commission_value_required') }} <span class="text-red-500">*</span></label>
                <input type="number" name="commission_value" min="0" step="0.01" required
                       value="{{ $val('commission_value', 0) }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
            </div>
        </div>

        {{-- Toggles --}}
        <div class="flex flex-wrap gap-6 pt-2">
            @foreach([
                ['name' => 'is_exclusive',         'label' => __('admin.admin_product_listings.exclusive_admin_only')],
                ['name' => 'available_for_vendors',  'label' => __('admin.admin_product_listings.available_for_vendors')],
                ['name' => 'available_for_marketers','label' => __('admin.admin_product_listings.available_for_marketers')],
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
            <h2 class="text-sm font-semibold text-primary-700 uppercase tracking-wide">{{ __('admin.admin_product_listings.now_nawy_settings') }}</h2>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.admin_product_listings.fulfillment_type_required') }} <span class="text-red-500">*</span></label>
                <select name="fulfillment_type" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                    @foreach(['express' => __('admin.admin_product_listings.express'), 'global' => __('admin.admin_product_listings.global'), 'mixed' => __('admin.admin_product_listings.mixed')] as $v => $l)
                        <option value="{{ $v }}" {{ $val('fulfillment_type', 'mixed') === $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
                @error('fulfillment_type')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.admin_product_listings.payment_options_required') }} <span class="text-red-500">*</span></label>
                <select name="payment_options" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                    @foreach(['both' => __('admin.admin_product_listings.both_cod_electronic'), 'cod_only' => __('admin.admin_product_listings.cod_only'), 'electronic_only' => __('admin.admin_product_listings.electronic_only')] as $v => $l)
                        <option value="{{ $v }}" {{ $val('payment_options', 'both') === $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
                @error('payment_options')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.admin_product_listings.nawy_category') }}</label>
                <select name="nawy_category_id"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <option value="">{{ __('admin.admin_product_listings.no_category') }}</option>
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
            <span class="text-sm font-medium text-primary-700">{{ __('admin.admin_product_listings.feature_in_nawy_feed') }}</span>
        </label>

        @if($isEdit)
        <div class="pt-1">
            <a href="{{ route('admin.admin-product-listings.nawy-preview', $listing) }}"
               target="_blank"
               class="inline-flex items-center gap-2 px-4 py-2 border border-primary-300 text-primary-700 text-sm font-medium rounded-lg hover:bg-primary-50 transition-colors">
                <x-heroicon name="device-phone-mobile" class="w-4 h-4" />
                {{ __('admin.admin_product_listings.preview_in_now_nawy') }}
            </a>
        </div>
        @endif
    </div>

    {{-- Submit --}}
    <div class="flex items-center gap-3">
        <button type="submit"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary-600 text-white text-sm font-semibold rounded-lg hover:bg-primary-700 transition-colors">
            <x-heroicon name="check" class="w-4 h-4" />
            {{ $isEdit ? __('admin.admin_product_listings.save_changes') : __('admin.admin_product_listings.create_listing') }}
        </button>
        <a href="{{ route('admin.admin-product-listings.index') }}"
           class="text-sm text-gray-500 hover:text-gray-700">{{ __('admin.admin_product_listings.cancel') }}</a>
    </div>

</div>
