@extends('layouts.partner')

@php
    $isEdit = isset($coupon) && $coupon !== null;
    $couponProductIds = $isEdit ? collect($coupon['product_ids'] ?? [])->implode(',') : '';
@endphp

@section('title', $isEdit ? 'Edit Coupon' : 'New Coupon')
@section('page-title', $isEdit ? 'Edit Coupon' : 'New Coupon')

@push('scripts')
    @vite('resources/js/vendor/coupons.js')
@endpush

@section('content')
    <div class="px-4 py-6 sm:px-6 lg:px-8 max-w-3xl"
         x-data="{
            scope: '{{ $isEdit ? $coupon['scope'] : 'vendor' }}',
            type: '{{ $isEdit ? $coupon['type'] : 'percentage' }}',
            selectedProducts: [
                @if($isEdit && !empty($coupon['product_ids']))
                    @foreach($coupon['product_ids'] as $pid) {{ Js::from($pid) }}, @endforeach
                @endif
            ],
            productQuery: '',
            productResults: [],
            async searchProducts() {
                if (this.productQuery.length < 2) { this.productResults = []; return; }
                const res = await fetch('{{ route('partner.listings.product-search') }}?q=' + encodeURIComponent(this.productQuery));
                this.productResults = await res.json();
            },
            toggleProduct(id) {
                const idx = this.selectedProducts.indexOf(id);
                if (idx === -1) { this.selectedProducts.push(id); } else { this.selectedProducts.splice(idx, 1); }
            },
         }">

        <div class="mb-6">
            <a href="{{ route('partner.coupons.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Back to coupons</a>
        </div>

        <div id="coupon-form-error" class="hidden mb-4 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3"></div>

        <form id="coupon-form" class="bg-white rounded-2xl border border-gray-200 p-6 space-y-5"
              data-url="{{ $isEdit ? route('partner.coupons.update', $coupon['id']) : route('partner.coupons.store') }}"
              data-method="{{ $isEdit ? 'PUT' : 'POST' }}"
              data-success-message="{{ $isEdit ? 'Coupon updated.' : 'Coupon created.' }}">

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Code</label>
                    <input type="text" name="code" required maxlength="50"
                           value="{{ $isEdit ? $coupon['code'] : '' }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm uppercase">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                    <input type="text" name="name" required maxlength="150"
                           value="{{ $isEdit ? $coupon['name'] : '' }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" rows="2" maxlength="2000"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">{{ $isEdit ? $coupon['description'] : '' }}</textarea>
            </div>

            {{-- Bank offer (optional) --}}
            <div class="border border-gray-200 rounded-lg p-4 space-y-4">
                <div>
                    <p class="text-sm font-medium text-gray-700">Bank offer</p>
                    <p class="text-xs text-gray-400">Optional. Associate this coupon with a card-issuing bank to display it as a bank promotion on the product page.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Bank name</label>
                    <input type="text" name="bank_name" maxlength="100" placeholder="e.g. RAKBANK"
                           value="{{ $isEdit ? $coupon['bank_name'] : '' }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Promo title (English)</label>
                        <input type="text" name="title_en" maxlength="255" placeholder="e.g. Extra 15% off with RAKBANK Credit Cards"
                               value="{{ $isEdit ? $coupon['title_en'] : '' }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Promo title (Arabic)</label>
                        <input type="text" name="title_ar" dir="rtl" maxlength="255"
                               value="{{ $isEdit ? $coupon['title_ar'] : '' }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Terms (English) <span class="text-gray-400 font-normal">one per line</span></label>
                        <textarea name="terms_en" rows="4"
                                  class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">{{ $isEdit ? implode("\n", $coupon['terms_en'] ?? []) : '' }}</textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Terms (Arabic) <span class="text-gray-400 font-normal">one per line</span></label>
                        <textarea name="terms_ar" dir="rtl" rows="4"
                                  class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">{{ $isEdit ? implode("\n", $coupon['terms_ar'] ?? []) : '' }}</textarea>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Max orders per customer / month <span class="text-gray-400 font-normal">blank = unlimited</span></label>
                    <input type="number" min="1" name="max_orders_per_customer_per_month"
                           value="{{ $isEdit ? $coupon['max_orders_per_customer_per_month'] : '' }}"
                           class="w-40 border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                    <select name="type" x-model="type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="percentage">Percentage</option>
                        <option value="fixed_amount">Fixed amount</option>
                        <option value="free_shipping">Free shipping</option>
                        <option value="bogo">BOGO</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Value</label>
                    <input type="number" step="0.01" min="0" name="value" required
                           value="{{ $isEdit ? $coupon['value'] : '' }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Currency</label>
                    <input type="text" name="currency" maxlength="3" placeholder="SAR"
                           value="{{ $isEdit ? $coupon['currency'] : '' }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm uppercase">
                </div>
            </div>

            {{-- Scope --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Scope</label>
                <div class="flex gap-4">
                    <label class="inline-flex items-center gap-2 text-sm">
                        <input type="radio" name="scope" value="vendor" x-model="scope">
                        Store-wide (all my products)
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm">
                        <input type="radio" name="scope" value="product" x-model="scope">
                        Specific products
                    </label>
                </div>
            </div>

            {{-- Product multi-select, only visible when scope=product --}}
            <div x-show="scope === 'product'" x-cloak class="border border-gray-200 rounded-lg p-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Products</label>

                <template x-for="id in selectedProducts" :key="id">
                    <input type="hidden" name="product_ids[]" :value="id">
                </template>

                <input type="text" x-model="productQuery" @input.debounce.300ms="searchProducts()"
                       placeholder="Search your products..."
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm mb-2">

                <div class="max-h-48 overflow-y-auto divide-y divide-gray-100" x-show="productResults.length > 0">
                    <template x-for="product in productResults" :key="product.id">
                        <label class="flex items-center gap-2 py-2 text-sm cursor-pointer">
                            <input type="checkbox" :checked="selectedProducts.includes(product.id)"
                                   @change="toggleProduct(product.id)">
                            <span x-text="product.name_en ?? product.name"></span>
                        </label>
                    </template>
                </div>

                <p class="text-xs text-gray-400 mt-2" x-show="selectedProducts.length > 0">
                    <span x-text="selectedProducts.length"></span> product(s) selected.
                    Only products with an active listing owned by you are eligible.
                </p>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Min. order amount</label>
                    <input type="number" step="0.01" min="0" name="min_order_amount"
                           value="{{ $isEdit ? $coupon['min_order_amount'] : '' }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Max discount</label>
                    <input type="number" step="0.01" min="0" name="max_discount"
                           value="{{ $isEdit ? $coupon['max_discount'] : '' }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Total usage limit</label>
                    <input type="number" min="1" name="usage_limit_total"
                           value="{{ $isEdit ? $coupon['usage_limit_total'] : '' }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Usage limit per customer</label>
                    <input type="number" min="1" name="usage_limit_per_customer" required
                           value="{{ $isEdit ? $coupon['usage_limit_per_customer'] : 1 }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Valid from</label>
                    <input type="datetime-local" name="valid_from" required
                           value="{{ $isEdit ? \Illuminate\Support\Str::of($coupon['valid_from'])->before('+') : '' }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Valid until</label>
                    <input type="datetime-local" name="valid_until" required
                           value="{{ $isEdit ? \Illuminate\Support\Str::of($coupon['valid_until'])->before('+') : '' }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>

            <div class="flex items-center gap-6">
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_active" value="1" {{ !$isEdit || $coupon['is_active'] ? 'checked' : '' }}>
                    Active
                </label>
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_stackable" value="1" {{ $isEdit && $coupon['is_stackable'] ? 'checked' : '' }}>
                    Stackable with other coupons
                </label>
            </div>

            <div class="pt-2 flex justify-end gap-3">
                <a href="{{ route('partner.coupons.index') }}"
                   class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900">Cancel</a>
                <button type="submit"
                        class="bg-yellow-400 hover:bg-yellow-500 text-gray-900 text-sm font-semibold px-5 py-2 rounded-lg transition-colors">
                    {{ $isEdit ? 'Save changes' : 'Create coupon' }}
                </button>
            </div>
        </form>
    </div>
@endsection
