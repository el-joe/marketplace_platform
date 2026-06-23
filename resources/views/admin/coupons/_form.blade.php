{{--
    Shared Coupon form partial.
    Include with: @include('admin.coupons._form', ['mode' => 'create'])
                  @include('admin.coupons._form', ['mode' => 'edit', 'coupon' => $coupon])

    Parent view must wrap this in a <form> tag with proper action / @csrf / @method.
    Requires vendors and categories variables from the controller.
--}}
@php
    $coupon = $coupon ?? null;
    $isEdit = $coupon !== null;

    $val = function (string $field, $default = '') use ($isEdit, $coupon) {
        return old($field, $isEdit ? ($coupon->{$field} ?? $default) : $default);
    };

    $bool = function (string $field, bool $default = false) use ($isEdit, $coupon): bool {
        $raw = old($field, $isEdit ? ($coupon->{$field} ?? $default) : $default);
        return (bool) $raw;
    };

    $currentType  = old('type',  $isEdit ? $coupon->type  : 'percentage');
    $currentScope = old('scope', $isEdit ? $coupon->scope : 'platform');
@endphp

<div class="space-y-6" x-data="{
    type:  '{{ $currentType }}',
    scope: '{{ $currentScope }}'
}">
    <input type="hidden" id="form-mode" name="_form_mode" value="{{ $isEdit ? 'edit' : 'create' }}">

    {{-- ─── Page header ─────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">
                {{ $isEdit ? 'Edit Coupon: ' . e($coupon->code) : 'New Coupon' }}
            </h1>
            @if($isEdit)
                <p class="text-sm text-gray-500 mt-1">
                    Used {{ $usageCount }} time(s) so far.
                </p>
            @endif
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.coupons.index') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary" id="save-btn">
                {{ $isEdit ? 'Save Changes' : 'Create Coupon' }}
            </button>
        </div>
    </div>

    <div class="flex gap-6 items-start">

        {{-- ═══════════════════════════════════════════════════════════════ --}}
        {{-- LEFT: main fields                                               --}}
        {{-- ═══════════════════════════════════════════════════════════════ --}}
        <div class="flex-1 min-w-0 space-y-4">

            {{-- Basic Info ─────────────────────────────────────────────── --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="text-sm font-semibold text-gray-900">Coupon Details</h2>
                </div>
                <div class="px-5 py-5 space-y-4">

                    {{-- Code --}}
                    <div>
                        <label for="code" class="block text-xs font-medium text-gray-700 mb-1">
                            Code <span class="text-red-500">*</span>
                        </label>
                        <div class="flex gap-2">
                            <input
                                type="text"
                                id="code"
                                name="code"
                                value="{{ strtoupper($val('code')) }}"
                                class="input flex-1 font-mono uppercase @error('code') border-red-400 @enderror"
                                placeholder="e.g. WELCOME20"
                                maxlength="50"
                                oninput="this.value = this.value.toUpperCase()"
                                required
                            />
                            <button type="button"
                                    id="btn-generate-code"
                                    data-url="{{ route('admin.coupons.generate-code') }}"
                                    class="btn btn-secondary btn-sm shrink-0 whitespace-nowrap">
                                Generate
                            </button>
                        </div>
                        @error('code') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Name --}}
                    <div>
                        <label for="name" class="block text-xs font-medium text-gray-700 mb-1">
                            Internal Name <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            value="{{ $val('name') }}"
                            class="input w-full @error('name') border-red-400 @enderror"
                            placeholder="e.g. Welcome 20% Off"
                            maxlength="150"
                            required
                        />
                        @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Description --}}
                    <div>
                        <label for="description" class="block text-xs font-medium text-gray-700 mb-1">Description</label>
                        <textarea
                            id="description"
                            name="description"
                            rows="3"
                            class="input w-full @error('description') border-red-400 @enderror"
                            placeholder="Shown to the customer at checkout…"
                        >{{ $val('description') }}</textarea>
                        @error('description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                </div>
            </div>

            {{-- Discount Type & Value ──────────────────────────────────── --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="text-sm font-semibold text-gray-900">Discount</h2>
                </div>
                <div class="px-5 py-5 space-y-4">

                    {{-- Type --}}
                    <div>
                        <label for="type" class="block text-xs font-medium text-gray-700 mb-1">
                            Type <span class="text-red-500">*</span>
                        </label>
                        <select
                            id="type"
                            name="type"
                            x-model="type"
                            class="input w-full @error('type') border-red-400 @enderror"
                            required
                        >
                            <option value="percentage"   {{ $currentType === 'percentage'   ? 'selected' : '' }}>Percentage (%)</option>
                            <option value="fixed_amount" {{ $currentType === 'fixed_amount' ? 'selected' : '' }}>Fixed Amount</option>
                            <option value="free_shipping"{{ $currentType === 'free_shipping'? 'selected' : '' }}>Free Shipping</option>
                            <option value="bogo"         {{ $currentType === 'bogo'         ? 'selected' : '' }}>BOGO (Buy One Get One)</option>
                        </select>
                        @error('type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Value (hidden for free_shipping) --}}
                    <div x-show="type !== 'free_shipping'">
                        <label for="value" class="block text-xs font-medium text-gray-700 mb-1">
                            Value <span class="text-red-500">*</span>
                            <span x-show="type === 'percentage'" class="text-gray-400 font-normal">(0–100)</span>
                        </label>
                        <input
                            type="number"
                            id="value"
                            name="value"
                            value="{{ $val('value', 0) }}"
                            class="input w-full @error('value') border-red-400 @enderror"
                            step="0.01"
                            min="0"
                        />
                        @error('value') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Currency (only for fixed_amount) --}}
                    <div x-show="type === 'fixed_amount'">
                        <label for="currency" class="block text-xs font-medium text-gray-700 mb-1">Currency</label>
                        <input
                            type="text"
                            id="currency"
                            name="currency"
                            value="{{ $val('currency') }}"
                            class="input w-32 font-mono uppercase @error('currency') border-red-400 @enderror"
                            placeholder="EGP"
                            maxlength="3"
                            oninput="this.value = this.value.toUpperCase()"
                        />
                        @error('currency') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Max Discount (only for percentage) --}}
                    <div x-show="type === 'percentage'">
                        <label for="max_discount" class="block text-xs font-medium text-gray-700 mb-1">
                            Max Discount Cap
                            <span class="text-gray-400 font-normal">(in smallest currency unit, leave blank for no cap)</span>
                        </label>
                        <input
                            type="number"
                            id="max_discount"
                            name="max_discount"
                            value="{{ $val('max_discount') }}"
                            class="input w-full @error('max_discount') border-red-400 @enderror"
                            min="0"
                            placeholder="e.g. 10000 for 100.00"
                        />
                        @error('max_discount') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                </div>
            </div>

            {{-- Scope ─────────────────────────────────────────────────── --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="text-sm font-semibold text-gray-900">Scope</h2>
                </div>
                <div class="px-5 py-5 space-y-4">

                    <div>
                        <label for="scope" class="block text-xs font-medium text-gray-700 mb-1">
                            Applies To <span class="text-red-500">*</span>
                        </label>
                        <select
                            id="scope"
                            name="scope"
                            x-model="scope"
                            class="input w-full @error('scope') border-red-400 @enderror"
                            required
                        >
                            <option value="platform" {{ $currentScope === 'platform' ? 'selected' : '' }}>Platform (all products)</option>
                            <option value="vendor"   {{ $currentScope === 'vendor'   ? 'selected' : '' }}>Specific Vendor</option>
                            <option value="category" {{ $currentScope === 'category' ? 'selected' : '' }}>Specific Category</option>
                            <option value="product"  {{ $currentScope === 'product'  ? 'selected' : '' }}>Specific Products</option>
                        </select>
                        @error('scope') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Vendor picker --}}
                    <div x-show="scope === 'vendor'">
                        <label for="vendor_id" class="block text-xs font-medium text-gray-700 mb-1">Vendor</label>
                        <select
                            id="vendor_id"
                            name="vendor_id"
                            class="input w-full @error('vendor_id') border-red-400 @enderror"
                        >
                            <option value="">— Select vendor —</option>
                            @foreach($vendors as $vendor)
                                <option value="{{ $vendor->id }}" {{ $val('vendor_id') === $vendor->id ? 'selected' : '' }}>
                                    {{ e($vendor->store_name) }}
                                </option>
                            @endforeach
                        </select>
                        @error('vendor_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Category picker --}}
                    <div x-show="scope === 'category'">
                        <label for="category_id" class="block text-xs font-medium text-gray-700 mb-1">Category</label>
                        <select
                            id="category_id"
                            name="category_id"
                            class="input w-full @error('category_id') border-red-400 @enderror"
                        >
                            <option value="">— Select category —</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ $val('category_id') === $cat->id ? 'selected' : '' }}>
                                    {{ e($cat->name_en) }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Product note --}}
                    <div x-show="scope === 'product'" class="rounded-lg bg-blue-50 border border-blue-200 px-4 py-3 text-sm text-blue-700">
                        Product-level targeting uses the <strong>coupon_products</strong> table.
                        You can attach specific products after saving the coupon.
                    </div>

                </div>
            </div>

            {{-- Usage Limits ───────────────────────────────────────────── --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="text-sm font-semibold text-gray-900">Usage Limits</h2>
                </div>
                <div class="px-5 py-5 grid grid-cols-2 gap-4">

                    <div>
                        <label for="usage_limit_total" class="block text-xs font-medium text-gray-700 mb-1">
                            Total Uses
                            <span class="text-gray-400 font-normal">(blank = unlimited)</span>
                        </label>
                        <input
                            type="number"
                            id="usage_limit_total"
                            name="usage_limit_total"
                            value="{{ $val('usage_limit_total') }}"
                            class="input w-full @error('usage_limit_total') border-red-400 @enderror"
                            min="1"
                            placeholder="∞"
                        />
                        @error('usage_limit_total') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="usage_limit_per_customer" class="block text-xs font-medium text-gray-700 mb-1">
                            Per Customer <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="number"
                            id="usage_limit_per_customer"
                            name="usage_limit_per_customer"
                            value="{{ $val('usage_limit_per_customer', 1) }}"
                            class="input w-full @error('usage_limit_per_customer') border-red-400 @enderror"
                            min="1"
                            required
                        />
                        @error('usage_limit_per_customer') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="min_order_amount" class="block text-xs font-medium text-gray-700 mb-1">
                            Minimum Order Amount
                            <span class="text-gray-400 font-normal">(smallest unit)</span>
                        </label>
                        <input
                            type="number"
                            id="min_order_amount"
                            name="min_order_amount"
                            value="{{ $val('min_order_amount') }}"
                            class="input w-full @error('min_order_amount') border-red-400 @enderror"
                            min="0"
                            placeholder="e.g. 50000 for 500.00"
                        />
                        @error('min_order_amount') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="customer_eligibility" class="block text-xs font-medium text-gray-700 mb-1">
                            Customer Eligibility <span class="text-red-500">*</span>
                        </label>
                        <select
                            id="customer_eligibility"
                            name="customer_eligibility"
                            class="input w-full @error('customer_eligibility') border-red-400 @enderror"
                            required
                        >
                            <option value="all"              {{ $val('customer_eligibility', 'all') === 'all'              ? 'selected' : '' }}>All Customers</option>
                            <option value="new_customers"    {{ $val('customer_eligibility', 'all') === 'new_customers'    ? 'selected' : '' }}>New Customers Only</option>
                            <option value="specific_segment" {{ $val('customer_eligibility', 'all') === 'specific_segment' ? 'selected' : '' }}>Specific Segment</option>
                            <option value="specific_users"   {{ $val('customer_eligibility', 'all') === 'specific_users'   ? 'selected' : '' }}>Specific Users</option>
                        </select>
                        @error('customer_eligibility') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                </div>
            </div>

            {{-- Validity Period ─────────────────────────────────────────── --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="text-sm font-semibold text-gray-900">Validity Period</h2>
                </div>
                <div class="px-5 py-5 grid grid-cols-2 gap-4">

                    <div>
                        <label for="valid_from" class="block text-xs font-medium text-gray-700 mb-1">
                            Valid From <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            id="valid_from"
                            name="valid_from"
                            value="{{ $val('valid_from') }}"
                            class="input w-full flatpickr-input @error('valid_from') border-red-400 @enderror"
                            data-flatpickr
                            data-enable-time="true"
                            placeholder="YYYY-MM-DD HH:MM"
                            required
                        />
                        @error('valid_from') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="valid_until" class="block text-xs font-medium text-gray-700 mb-1">
                            Valid Until <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            id="valid_until"
                            name="valid_until"
                            value="{{ $val('valid_until') }}"
                            class="input w-full flatpickr-input @error('valid_until') border-red-400 @enderror"
                            data-flatpickr
                            data-enable-time="true"
                            placeholder="YYYY-MM-DD HH:MM"
                            required
                        />
                        @error('valid_until') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                </div>
            </div>

        </div>

        {{-- ═══════════════════════════════════════════════════════════════ --}}
        {{-- RIGHT: sidebar options                                         --}}
        {{-- ═══════════════════════════════════════════════════════════════ --}}
        <div class="w-72 shrink-0 space-y-4">

            {{-- Status card --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="text-sm font-semibold text-gray-900">Status</h2>
                </div>
                <div class="px-5 py-5 space-y-4">

                    <label class="flex items-center gap-3 cursor-pointer">
                        <input
                            type="hidden"
                            name="is_active"
                            value="0"
                        />
                        <input
                            type="checkbox"
                            id="is_active"
                            name="is_active"
                            value="1"
                            {{ $bool('is_active', true) ? 'checked' : '' }}
                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                        />
                        <span class="text-sm text-gray-700">Active</span>
                    </label>

                    <label class="flex items-center gap-3 cursor-pointer">
                        <input
                            type="hidden"
                            name="is_stackable"
                            value="0"
                        />
                        <input
                            type="checkbox"
                            id="is_stackable"
                            name="is_stackable"
                            value="1"
                            {{ $bool('is_stackable', false) ? 'checked' : '' }}
                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                        />
                        <span class="text-sm text-gray-700">Stackable
                            <span class="text-xs text-gray-400">(can combine with other coupons)</span>
                        </span>
                    </label>

                </div>
            </div>

            {{-- Usage stats (edit mode only) --}}
            @if($isEdit)
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="text-sm font-semibold text-gray-900">Usage Stats</h2>
                </div>
                <div class="px-5 py-5 space-y-2 text-sm text-gray-700">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Times Used</span>
                        <span class="font-medium">{{ number_format($coupon->times_used) }}</span>
                    </div>
                    @if($coupon->usage_limit_total)
                    <div class="flex justify-between">
                        <span class="text-gray-500">Limit</span>
                        <span class="font-medium">{{ number_format($coupon->usage_limit_total) }}</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-1.5 mt-1">
                        <div
                            class="bg-blue-500 h-1.5 rounded-full"
                            style="width: {{ min(100, round(($coupon->times_used / $coupon->usage_limit_total) * 100)) }}%"
                        ></div>
                    </div>
                    @endif
                    <div class="flex justify-between pt-1">
                        <span class="text-gray-500">DB Usages</span>
                        <span class="font-medium">{{ number_format($usageCount) }}</span>
                    </div>
                </div>
            </div>
            @endif

            {{-- Summary card --}}
            <div class="bg-blue-50 rounded-xl border border-blue-200 px-5 py-4 text-xs text-blue-700 space-y-1">
                <p class="font-semibold">How coupon values work</p>
                <p>• <strong>Percentage</strong>: deduct % of cart total.</p>
                <p>• <strong>Fixed Amount</strong>: deduct flat value in given currency.</p>
                <p>• <strong>Free Shipping</strong>: waive shipping cost (value ignored).</p>
                <p>• <strong>BOGO</strong>: buy 1 get 1 free on eligible items.</p>
                <p class="pt-1">Amounts (min order / max discount) are stored in the <em>smallest currency unit</em> (e.g. cents).</p>
            </div>

        </div>

    </div>
</div>
