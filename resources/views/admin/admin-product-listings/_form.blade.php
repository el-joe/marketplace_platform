{{--
    Shared form partial for create/edit AdminProductListing.
    Requires: $countries, $warehouses, $shippingMethods, $nawyCategories
    Optional: $listing (edit mode), $selectedVariant
--}}
@php
    $listing = $listing ?? null;
    $isEdit  = $listing !== null;
    $selectedVariant = $selectedVariant ?? null;
    $val = fn(string $f, $d = '') => old($f, $isEdit ? (($listing->{$f} ?? null) instanceof \BackedEnum ? $listing->{$f}->value : ($listing->{$f} ?? $d)) : $d);
    $bool = fn(string $f, bool $d = false): bool => (bool) old($f, $isEdit ? ($listing->{$f} ?? $d) : $d);
    $canViewCost = auth('admin')->user()?->can('admin_listings.view');
@endphp

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6" x-data="{ physicalOpen: {{ $isEdit && ($listing->declared_weight_grams || $listing->weight_class) ? 'true' : 'false' }}, featuredInNawy: {{ $bool('featured_in_nawy', true) ? 'true' : 'false' }} }">

    {{-- ══════════════════════ LEFT COLUMN ══════════════════════ --}}
    <div class="space-y-6">

        {{-- Section 1 — Product --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm space-y-4">
            <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">{{ __('admin.admin_product_listings.listing_details') }}</h2>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.admin_product_listings.product_variant_required') }} <span class="text-red-500">*</span></label>
                <select name="product_variant_id" required data-async-select
                        data-config='{{ json_encode(["url" => route("admin.admin-product-listings.search-variants"), "param" => "q", "minLength" => 2, "delay" => 300]) }}'
                        data-url-info-url-template="{{ route("admin.products.variants.url-info", ["variant" => "__VARIANT__"]) }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                    @if($selectedVariant)
                        <option value="{{ $selectedVariant->id }}" data-product-id="{{ $selectedVariant->product_id }}" selected>
                            {{ $selectedVariant->product?->name_en }} ({{ $selectedVariant->variant_name }}) [{{ $selectedVariant->slug }}] — {{ $selectedVariant->sku }}
                        </option>
                    @endif
                </select>
                @error('product_variant_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div id="variant-url-preview-card" class="{{ $selectedVariant ? '' : 'hidden' }} bg-gray-50 border border-gray-200 rounded-lg p-3 space-y-1">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ __('admin.admin_product_listings.url_preview') ?? 'Customer URL' }}</p>
                <p class="text-xs text-gray-500">
                    {{ __('admin.admin_product_listings.attribute_summary') ?? 'Variant' }}:
                    <span id="variant-attribute-summary" class="font-medium text-gray-700">—</span>
                </p>
                <input type="text" id="variant-url-preview" readonly
                       class="w-full border border-gray-200 bg-white rounded-lg px-3 py-2 text-xs font-mono text-gray-600 focus:outline-none">
            </div>

            @if($isEdit)
            <div id="listing-final-url-card" class="bg-green-50 border border-green-200 rounded-lg p-3 space-y-1">
                <p class="text-xs font-semibold text-green-700 uppercase tracking-wide">{{ __('admin.admin_product_listings.customer_url') ?? 'Customer URL' }}</p>
                <div class="flex items-center gap-2">
                    <input type="text" readonly value="/products/{{ $listing->productVariant?->id }}/{{ $listing->id }}"
                           class="flex-1 border border-green-200 bg-white rounded-lg px-3 py-2 text-xs font-mono text-gray-700 focus:outline-none">
                    <button type="button" class="js-copy inline-flex items-center gap-1 px-3 py-2 text-xs font-medium text-green-700 border border-green-300 rounded-lg hover:bg-green-100"
                            data-value="/products/{{ $listing->productVariant?->id }}/{{ $listing->id }}">
                        {{ __('admin.admin_product_listings.copy_url') ?? 'Copy URL' }}
                    </button>
                </div>
            </div>
            @endif

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.admin_product_listings.country_required') }} <span class="text-red-500">*</span></label>
                    <select name="country_id" required data-select2-init
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="">{{ __('admin.admin_product_listings.select_country') }}</option>
                        @foreach($countries as $country)
                            <option value="{{ $country->id }}"
                                    data-currency-code="{{ $country->currency_code }}"
                                    {{ $val('country_id') === $country->id ? 'selected' : '' }}>
                                {{ $country->name_en }}
                            </option>
                        @endforeach
                    </select>
                    @error('country_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.admin_product_listings.currency_required') }}</label>
                    <input type="text" id="currency-display" readonly disabled dir="ltr"
                           value="{{ $isEdit ? ($listing->country?->currency_code ?? '') : '' }}"
                           placeholder="{{ __('admin.admin_product_listings.select_country') }}"
                           class="w-full border border-gray-200 bg-gray-50 rounded-lg px-3 py-2 text-sm font-mono uppercase text-gray-500 focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.admin_product_listings.warehouse') }}</label>
                <select name="warehouse_id" data-select2-init
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <option value="">{{ __('admin.admin_product_listings.no_warehouse_option') }}</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" {{ $val('warehouse_id') === $warehouse->id ? 'selected' : '' }}>
                            {{ $warehouse->name }}
                        </option>
                    @endforeach
                </select>
                @error('warehouse_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        {{-- Section 2 — Pricing --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm space-y-4">
            <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">{{ __('admin.admin_product_listings.price_required') }}</h2>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.admin_product_listings.price_required') }} <span class="text-red-500">*</span></label>
                    <input type="number" name="price" min="0" step="1" required
                           value="{{ $val('price', 0) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                    @error('price')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.admin_product_listings.compare_at_price') }}</label>
                    <input type="number" name="compare_at_price" min="0" step="1"
                           value="{{ $val('compare_at_price') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <p class="mt-1 text-xs text-gray-400">{{ __('admin.admin_product_listings.compare_at_price_hint') }}</p>
                    @error('compare_at_price')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                @if($canViewCost)
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.admin_product_listings.cost_price') }}</label>
                    <input type="number" name="cost_price" min="0" step="1"
                           value="{{ $val('cost_price') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                    @error('cost_price')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                @endif

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.admin_product_listings.shipping_cost') }}</label>
                    <input type="number" name="shipping_cost" min="0" step="1" required
                           value="{{ $val('shipping_cost', 0) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                    @error('shipping_cost')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        {{-- Section 3 — Commission --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm space-y-4" x-data="{ commissionType: '{{ $val('commission_type', 'percentage') }}' }">
            <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">{{ __('admin.admin_product_listings.commission_type_required') }}</h2>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('admin.admin_product_listings.commission_type_required') }} <span class="text-red-500">*</span></label>
                <div class="flex flex-wrap gap-4">
                    @foreach(['fixed' => __('admin.admin_product_listings.fixed'), 'percentage' => __('admin.admin_product_listings.percentage'), 'mixed' => __('admin.admin_product_listings.mixed')] as $v => $l)
                        <label class="flex items-center gap-1.5 cursor-pointer select-none text-sm text-gray-700">
                            <input type="radio" name="commission_type" value="{{ $v }}" x-model="commissionType"
                                   {{ $val('commission_type', 'percentage') === $v ? 'checked' : '' }}
                                   class="text-primary-600 focus:ring-primary-500">
                            {{ $l }}
                        </label>
                    @endforeach
                </div>
                @error('commission_type')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    {{ __('admin.admin_product_listings.commission_value_required') }}
                    <span x-show="commissionType === 'percentage'">(%)</span>
                    <span x-show="commissionType !== 'percentage'">({{ $val('currency', '') ?: __('admin.admin_product_listings.currency_required') }})</span>
                    <span class="text-red-500">*</span>
                </label>
                <input type="number" name="commission_value" min="0" step="0.01" required
                       value="{{ $val('commission_value', 0) }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                @error('commission_value')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        {{-- Section 4 — Fulfillment --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm space-y-4">
            <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">{{ __('admin.admin_product_listings.fulfillment_type_required') }}</h2>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('admin.admin_product_listings.fulfillment_type_required') }} <span class="text-red-500">*</span></label>
                <div class="flex flex-wrap gap-4">
                    @foreach(['express' => __('admin.admin_product_listings.express'), 'global' => __('admin.admin_product_listings.global'), 'mixed' => __('admin.admin_product_listings.mixed')] as $v => $l)
                        <label class="flex items-center gap-1.5 cursor-pointer select-none text-sm text-gray-700">
                            <input type="radio" name="fulfillment_type" value="{{ $v }}"
                                   {{ $val('fulfillment_type', 'mixed') === $v ? 'checked' : '' }}
                                   class="text-primary-600 focus:ring-primary-500">
                            {{ $l }}
                        </label>
                    @endforeach
                </div>
                @error('fulfillment_type')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.admin_product_listings.primary_shipping_method') }}</label>
                <select name="primary_shipping_method_id" data-select2-init
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <option value="">{{ __('admin.admin_product_listings.no_shipping_method') }}</option>
                    @foreach($shippingMethods as $method)
                        <option value="{{ $method->id }}" {{ $val('primary_shipping_method_id') === $method->id ? 'selected' : '' }}>
                            {{ $method->name }}
                        </option>
                    @endforeach
                </select>
                @error('primary_shipping_method_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <label class="flex items-center gap-2 cursor-pointer select-none">
                <input type="hidden" name="is_global_shipping" value="0">
                <input type="checkbox" name="is_global_shipping" value="1"
                       {{ $bool('is_global_shipping') ? 'checked' : '' }}
                       class="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                <span class="text-sm text-gray-700">{{ __('admin.admin_product_listings.is_global_shipping') }}</span>
            </label>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('admin.admin_product_listings.payment_options_required') }} <span class="text-red-500">*</span></label>
                <div class="flex flex-wrap gap-4">
                    @foreach(['both' => __('admin.admin_product_listings.both_cod_electronic'), 'cod_only' => __('admin.admin_product_listings.cod_only'), 'electronic_only' => __('admin.admin_product_listings.electronic_only')] as $v => $l)
                        <label class="flex items-center gap-1.5 cursor-pointer select-none text-sm text-gray-700">
                            <input type="radio" name="payment_options" value="{{ $v }}"
                                   {{ $val('payment_options', 'both') === $v ? 'checked' : '' }}
                                   class="text-primary-600 focus:ring-primary-500">
                            {{ $l }}
                        </label>
                    @endforeach
                </div>
                @error('payment_options')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        {{-- Section 5 — Condition --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm space-y-4" x-data="{ condition: '{{ $val('condition', 'new') }}' }">
            <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">{{ __('admin.admin_product_listings.condition_required') }}</h2>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.admin_product_listings.condition_required') }} <span class="text-red-500">*</span></label>
                <select name="condition" required x-model="condition"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                    @foreach([
                        'new' => __('admin.admin_product_listings.condition_new'),
                        'like_new' => __('admin.admin_product_listings.condition_like_new'),
                        'good' => __('admin.admin_product_listings.condition_good'),
                        'acceptable' => __('admin.admin_product_listings.condition_acceptable'),
                        'refurbished' => __('admin.admin_product_listings.condition_refurbished'),
                    ] as $v => $l)
                        <option value="{{ $v }}" {{ $val('condition', 'new') === $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
                @error('condition')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div x-show="condition !== 'new'" x-cloak>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.admin_product_listings.condition_notes') }}</label>
                <textarea name="condition_notes" rows="3"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">{{ $val('condition_notes') }}</textarea>
                @error('condition_notes')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        {{-- Section 6 — Physical (collapsed by default) --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm space-y-4">
            <button type="button" @click="physicalOpen = !physicalOpen"
                    class="w-full flex items-center justify-between text-sm font-semibold text-gray-700 uppercase tracking-wide">
                <span>{{ __('admin.admin_product_listings.toggle_physical_section') }}</span>
                <x-heroicon name="chevron-down" class="w-4 h-4 transition-transform" x-bind:class="physicalOpen ? 'rotate-180' : ''" />
            </button>

            <div x-show="physicalOpen" x-cloak class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.admin_product_listings.weight_class') }}</label>
                    <select name="weight_class"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="">—</option>
                        @foreach(['light' => __('admin.admin_product_listings.weight_class_light'), 'medium' => __('admin.admin_product_listings.weight_class_medium'), 'heavy' => __('admin.admin_product_listings.weight_class_heavy')] as $v => $l)
                            <option value="{{ $v }}" {{ $val('weight_class') === $v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                    @error('weight_class')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.admin_product_listings.handling_class_required') }} <span class="text-red-500">*</span></label>
                    <select name="handling_class" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                        @foreach([
                            'standard' => __('admin.admin_product_listings.handling_standard'),
                            'refrigerated' => __('admin.admin_product_listings.handling_refrigerated'),
                            'fragile' => __('admin.admin_product_listings.handling_fragile'),
                            'special_tech' => __('admin.admin_product_listings.handling_special_tech'),
                        ] as $v => $l)
                            <option value="{{ $v }}" {{ $val('handling_class', 'standard') === $v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                    @error('handling_class')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.admin_product_listings.declared_weight_grams') }}</label>
                    <input type="number" name="declared_weight_grams" min="0" step="1"
                           value="{{ $val('declared_weight_grams') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                    @error('declared_weight_grams')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div></div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.admin_product_listings.declared_length_cm') }}</label>
                    <input type="number" name="declared_length_cm" min="0" step="0.01"
                           value="{{ $val('declared_length_cm') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                    @error('declared_length_cm')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.admin_product_listings.declared_width_cm') }}</label>
                    <input type="number" name="declared_width_cm" min="0" step="0.01"
                           value="{{ $val('declared_width_cm') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                    @error('declared_width_cm')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.admin_product_listings.declared_height_cm') }}</label>
                    <input type="number" name="declared_height_cm" min="0" step="0.01"
                           value="{{ $val('declared_height_cm') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                    @error('declared_height_cm')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

    </div>

    {{-- ══════════════════════ RIGHT COLUMN ══════════════════════ --}}
    <div class="space-y-6">

        {{-- Section 9 — Platform SKU --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm space-y-4">
            <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">{{ __('admin.admin_product_listings.platform_sku') }}</h2>
            <div>
                <input type="text" name="platform_sku" maxlength="100"
                       value="{{ $val('platform_sku') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                @error('platform_sku')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        {{-- Section 8 — Flags --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm space-y-4">
            <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">{{ __('admin.admin_product_listings.status_required') }}</h2>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.admin_product_listings.status_required') }} <span class="text-red-500">*</span></label>
                <select name="status" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                    @foreach([
                        'active' => __('admin.admin_product_listings.active'),
                        'paused' => __('admin.admin_product_listings.paused'),
                        'out_of_stock' => __('admin.admin_product_listings.out_of_stock_status'),
                        'archived' => __('admin.admin_product_listings.archived'),
                    ] as $v => $l)
                        <option value="{{ $v }}" {{ $val('status', 'active') === $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
                @error('status')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.admin_product_listings.max_order_quantity') }}</label>
                    <input type="number" name="max_order_quantity" min="1" step="1"
                           value="{{ $val('max_order_quantity') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                    @error('max_order_quantity')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.admin_product_listings.low_stock_threshold') }}</label>
                    <input type="number" name="low_stock_threshold" min="0" step="1"
                           value="{{ $val('low_stock_threshold', 5) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                    @error('low_stock_threshold')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="flex flex-wrap gap-6 pt-2">
                @foreach([
                    ['name' => 'is_exclusive',           'label' => __('admin.admin_product_listings.exclusive_admin_only')],
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

        {{-- Now Nawy settings --}}
        <div class="bg-white rounded-xl border border-primary-200 p-6 shadow-sm space-y-4">
            <div class="flex items-center gap-2">
                <x-heroicon name="sparkles" class="w-5 h-5 text-primary-600" />
                <h2 class="text-sm font-semibold text-primary-700 uppercase tracking-wide">{{ __('admin.admin_product_listings.now_nawy_settings') }}</h2>
            </div>

            <label class="flex items-center gap-2 cursor-pointer select-none">
                <input type="hidden" name="featured_in_nawy" value="0">
                <input type="checkbox" name="featured_in_nawy" value="1" x-model="featuredInNawy"
                       {{ $bool('featured_in_nawy', true) ? 'checked' : '' }}
                       class="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                <span class="text-sm font-medium text-primary-700">{{ __('admin.admin_product_listings.feature_in_nawy_feed') }}</span>
            </label>

            <div x-show="featuredInNawy" x-cloak>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.admin_product_listings.nawy_category') }}</label>
                <select name="nawy_category_id" data-select2-init
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

        {{-- Section 7 — Influencer/Affiliate --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm space-y-4">
            <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">{{ __('admin.admin_product_listings.influencer_affiliate_settings') }}</h2>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.admin_product_listings.influencer_commission_percentage') }}</label>
                    <input type="number" name="influencer_commission_percentage" min="0" max="100" step="0.01"
                           value="{{ $val('influencer_commission_percentage') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                    @error('influencer_commission_percentage')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.admin_product_listings.influencer_sample_quota') }}</label>
                    <input type="number" name="influencer_sample_quota" min="0" step="1"
                           value="{{ $val('influencer_sample_quota') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                    @error('influencer_sample_quota')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.admin_product_listings.affiliate_commission_percentage') }}</label>
                    <input type="number" name="affiliate_commission_percentage" min="0" max="100" step="0.01"
                           value="{{ $val('affiliate_commission_percentage') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                    @error('affiliate_commission_percentage')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.admin_product_listings.affiliate_sample_quota') }}</label>
                    <input type="number" name="affiliate_sample_quota" min="0" step="1"
                           value="{{ $val('affiliate_sample_quota') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                    @error('affiliate_sample_quota')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
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

</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var countrySelect = document.querySelector('[name="country_id"]');
        var currencyDisplay = document.getElementById('currency-display');
        if (!countrySelect || !currencyDisplay) return;

        function syncCurrency() {
            var option = countrySelect.options[countrySelect.selectedIndex];
            currencyDisplay.value = (option && option.dataset.currencyCode) || '';
        }

        countrySelect.addEventListener('change', syncCurrency);
        // select2 (data-select2-init) fires jQuery 'change', not native DOM 'change'
        if (window.jQuery) {
            jQuery(countrySelect).on('change', syncCurrency);
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        var variantSelect = document.querySelector('[name="product_variant_id"]');
        var previewCard = document.getElementById('variant-url-preview-card');
        var previewInput = document.getElementById('variant-url-preview');
        var summaryEl = document.getElementById('variant-attribute-summary');
        if (!variantSelect || !window.jQuery) return;

        var urlTemplate = variantSelect.getAttribute('data-url-info-url-template') || '';

        function fetchUrlInfo(variantId) {
            if (!variantId || !urlTemplate) return;

            var url = urlTemplate.replace('__VARIANT__', variantId);

            fetch(url, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            })
                .then((res) => res.json())
                .then((data) => {
                    previewInput.value = data.preview_url ?? '';
                    summaryEl.textContent = data.attribute_summary || '—';
                    previewCard.classList.remove('hidden');
                })
                .catch(() => {});
        }

        jQuery(variantSelect).on('select2:select', function (e) {
            var data = e.params.data;
            fetchUrlInfo(data.id);
        });

        // Prefill on edit when a variant is already selected
        var selectedOption = variantSelect.querySelector('option[selected]');
        if (selectedOption) {
            fetchUrlInfo(selectedOption.value);
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.js-copy').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var value = btn.dataset.value;
                if (!value) return;
                navigator.clipboard.writeText(value).then(function () {
                    var original = btn.textContent;
                    btn.textContent = window.TRANSLATIONS?.copied || 'Copied!';
                    setTimeout(function () { btn.textContent = original; }, 1500);
                }).catch(function () {
                    window.Toast?.error(window.TRANSLATIONS?.copyFailed || 'Copy failed.');
                });
            });
        });
    });
</script>
