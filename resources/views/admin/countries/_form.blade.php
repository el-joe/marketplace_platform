{{--
    Shared Country form partial.
    @include('admin.countries._form', ['mode' => 'create'])
    @include('admin.countries._form', ['mode' => 'edit', 'country' => $country])
--}}
@php
    $country = $country ?? null;
    $isEdit  = $country !== null;

    $val = function (string $field, $default = '') use ($isEdit, $country) {
        return old($field, $isEdit ? ($country->{$field} ?? $default) : $default);
    };

    $bool = function (string $field, bool $default = false) use ($isEdit, $country): bool {
        $raw = old($field, $isEdit ? ($country->{$field} ?? $default) : $default);
        return (bool) $raw;
    };
@endphp

<div
    x-data="{ activeTab: 'general' }"
    class="space-y-6"
>
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{-- Header row with breadcrumb + action buttons                           --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    <div class="flex items-center justify-between">
        <div></div>

        <div class="flex items-center gap-2">
            @if ($isEdit)
                {{-- Launch / Deactivate --}}
                @if (!$country->is_launched)
                    <button
                        type="button"
                        id="btn-launch"
                        data-country-id="{{ $country->id }}"
                        data-country-name="{{ $country->name_en }}"
                        data-url="{{ route('admin.countries.launch', $country->id) }}"
                        @if (count($launchErrors ?? []) > 0) disabled title="{{ implode(' | ', $launchErrors ?? []) }}" @endif
                        class="btn btn-success {{ count($launchErrors ?? []) > 0 ? 'opacity-50 cursor-not-allowed' : '' }}"
                    >
                        <x-heroicon name="rocket-launch" class="w-4 h-4" />
                        Launch
                    </button>
                @elseif ($country->is_active)
                    <button
                        type="button"
                        id="btn-deactivate"
                        data-country-id="{{ $country->id }}"
                        data-url="{{ route('admin.countries.deactivate', $country->id) }}"
                        class="btn btn-ghost"
                    >
                        Deactivate
                    </button>
                @else
                    <button
                        type="button"
                        id="btn-reactivate"
                        data-country-id="{{ $country->id }}"
                        data-url="{{ route('admin.countries.reactivate', $country->id) }}"
                        class="btn btn-secondary"
                    >
                        Reactivate
                    </button>
                @endif

                @if ($canDelete ?? false)
                    <button
                        type="button"
                        id="btn-delete-country"
                        data-country-id="{{ $country->id }}"
                        data-country-name="{{ $country->name_en }}"
                        data-url="{{ route('admin.countries.destroy', $country->id) }}"
                        class="btn btn-danger-ghost"
                    >
                        <x-heroicon name="trash" class="w-4 h-4" />
                    </button>
                @endif
            @endif

            <button type="submit" class="btn btn-primary">
                <x-heroicon name="check" class="w-4 h-4" />
                Save Country
            </button>
        </div>
    </div>

    @if ($errors->any())
        <div class="rounded-lg bg-red-50 border border-red-200 p-3 text-xs text-red-700 space-y-1">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    {{-- Launch prerequisite warnings (edit mode only) --}}
    @if ($isEdit && count($launchErrors ?? []) > 0 && !$country->is_launched)
        <div class="rounded-lg bg-yellow-50 border border-yellow-200 p-3 text-sm text-yellow-800">
            <strong class="block mb-1">Not ready to launch:</strong>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($launchErrors as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{-- Country status badge (edit mode)                                       --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    @if ($isEdit)
        <div class="flex items-center gap-3 text-sm text-gray-600">
            @if ($country->is_launched)
                <x-badge color="success">Launched</x-badge>
                <span class="text-gray-400">since {{ $country->launched_at?->format('d M Y') ?? '—' }}</span>
            @else
                <x-badge color="gray">Draft</x-badge>
            @endif

            @if (!$country->is_active)
                <x-badge color="warning">Inactive</x-badge>
            @endif
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{-- Tab nav                                                                --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    <div class="bg-white rounded-t-xl border border-gray-200 overflow-hidden">
        <nav class="flex overflow-x-auto border-b border-gray-100" aria-label="Country form tabs">
            @php
                $tabs = [
                    ['id' => 'general',   'label' => 'General',           'icon' => 'globe-alt',        'editOnly' => false],
                    ['id' => 'payment',   'label' => 'Payment Methods',   'icon' => 'credit-card',      'editOnly' => true],
                    ['id' => 'shipping',  'label' => 'Shipping',          'icon' => 'truck',            'editOnly' => true],
                    ['id' => 'categories','label' => 'Category Overrides','icon' => 'tag',              'editOnly' => true],
                ];
            @endphp

            @foreach ($tabs as $tab)
                @if (!$tab['editOnly'] || $isEdit)
                    <button
                        type="button"
                        @click="activeTab = '{{ $tab['id'] }}'"
                        :class="activeTab === '{{ $tab['id'] }}'
                            ? 'border-b-2 border-primary-600 text-primary-700 bg-primary-50/50'
                            : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'"
                        class="flex items-center gap-1.5 px-4 py-3.5 text-sm font-medium -mb-px whitespace-nowrap transition-colors"
                    >
                        <x-heroicon name="{{ $tab['icon'] }}" class="w-4 h-4" />
                        {{ $tab['label'] }}
                    </button>
                @endif
            @endforeach
        </nav>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{-- TAB: General                                                           --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    <div
        x-show="activeTab === 'general'"
        class="bg-white rounded-b-xl border border-t-0 border-gray-200 p-6 shadow-sm space-y-5"
    >
        <div class="grid grid-cols-2 gap-4">
            <x-form.input
                name="name_en"
                label="Name (English)"
                :value="$val('name_en')"
                required
                dir="ltr"
            />
            <x-form.input
                name="name_ar"
                label="Name (Arabic)"
                :value="$val('name_ar')"
                required
                dir="rtl"
            />
        </div>

        <div class="grid grid-cols-3 gap-4">
            <x-form.input
                name="iso_code_2"
                label="ISO-2 Code"
                :value="$val('iso_code_2')"
                maxlength="2"
                placeholder="SA"
                required
                class="uppercase"
            />
            <x-form.input
                name="iso_code_3"
                label="ISO-3 Code"
                :value="$val('iso_code_3')"
                maxlength="3"
                placeholder="SAU"
                class="uppercase"
            />
            <x-form.input
                name="phone_prefix"
                label="Phone Prefix"
                :value="$val('phone_prefix')"
                maxlength="5"
                placeholder="+966"
            />
        </div>

        <div class="grid grid-cols-3 gap-4">
            <x-form.input
                name="site_code"
                label="Site Code"
                :value="$val('site_code')"
                maxlength="20"
                placeholder="sa"
                hint="Lowercase letters only, unique"
            />
            <x-form.input
                name="site_domain"
                label="Site Domain"
                :value="$val('site_domain')"
                maxlength="100"
                placeholder="noon.com"
            />
            <x-form.select
                name="currency_code"
                label="Currency"
                :options="$currencies"
                :value="$val('currency_code')"
                :empty-option="'— Select —'"
            />
        </div>

        <div class="grid grid-cols-3 gap-4">
            <x-form.select
                name="default_locale"
                label="Default Locale"
                :options="$locales"
                :value="$val('default_locale', 'en')"
            />
            <x-form.select
                name="timezone"
                label="Timezone"
                :options="$timezones"
                :value="$val('timezone', 'UTC')"
            />
            <x-form.input
                name="vat_rate"
                label="VAT Rate (%)"
                type="number"
                step="0.01"
                min="0"
                max="50"
                :value="$val('vat_rate', '0.00')"
                required
            />
        </div>

        <div class="flex items-center gap-6 pt-2">
            <x-form.toggle
                name="cod_available"
                label="Cash on Delivery Available"
                :checked="$bool('cod_available')"
            />
            <x-form.toggle
                name="is_active"
                label="Active"
                :checked="$bool('is_active', true)"
            />
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{-- TAB: Payment Methods (edit only)                                       --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    @if ($isEdit)
    <div
        x-show="activeTab === 'payment'"
        x-cloak
        class="bg-white rounded-b-xl border border-t-0 border-gray-200 p-6 shadow-sm"
    >
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-700">Payment Methods for {{ $country->name_en }}</h3>
            <button
                type="button"
                id="btn-add-payment-method"
                class="btn btn-secondary btn-sm"
            >
                <x-heroicon name="plus" class="w-4 h-4" />
                Add Method
            </button>
        </div>

        <div id="payment-methods-list" class="space-y-2">
            @forelse ($country->countryPaymentMethods as $pm)
                <div class="flex items-center gap-3 px-4 py-3 bg-gray-50 rounded-lg border border-gray-200" data-pm-id="{{ $pm->id }}">
                    <span class="w-28 text-xs font-mono bg-gray-200 rounded px-1.5 py-0.5">{{ $pm->method_type }}</span>
                    <span class="flex-1 text-sm font-medium text-gray-800">{{ $pm->display_name_en }}</span>
                    <span class="text-xs text-gray-500">{{ $pm->provider }}</span>
                    <span class="text-xs text-gray-500">Fee: {{ $pm->fee_pct }}%</span>
                    @if ($pm->is_active)
                        <x-badge color="success" size="xs">Active</x-badge>
                    @else
                        <x-badge color="gray" size="xs">Inactive</x-badge>
                    @endif
                    <button
                        type="button"
                        class="btn-edit-pm btn btn-ghost btn-xs"
                        data-pm='@json($pm)'
                    >Edit</button>
                    <button
                        type="button"
                        class="btn-delete-pm btn btn-danger-ghost btn-xs"
                        data-pm-id="{{ $pm->id }}"
                        data-url="{{ route('admin.countries.payment-methods.destroy', [$country->id, $pm->id]) }}"
                    >
                        <x-heroicon name="trash" class="w-3.5 h-3.5" />
                    </button>
                </div>
            @empty
                <p class="text-sm text-gray-400 py-4 text-center">No payment methods configured yet.</p>
            @endforelse
        </div>

    </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{-- TAB: Shipping (edit only)                                              --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    @if ($isEdit)
    <div
        x-show="activeTab === 'shipping'"
        x-cloak
        class="bg-white rounded-b-xl border border-t-0 border-gray-200 p-6 shadow-sm"
    >
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-700">Shipping Methods for {{ $country->name_en }}</h3>
            <button
                type="button"
                id="btn-save-shipping"
                data-url="{{ route('admin.countries.shipping-settings.update', $country->id) }}"
                class="btn btn-primary btn-sm"
            >
                <x-heroicon name="check" class="w-4 h-4" />
                Save Shipping Settings
            </button>
        </div>

        <div id="shipping-settings-form">
            <div id="shipping-methods-list" class="space-y-2">
                @foreach ($allShippingMethods as $method)
                    @php
                        $setting = $country->countryShippingSettings
                            ->firstWhere('shipping_method_id', $method->id);
                    @endphp
                    <div class="flex items-center gap-4 px-4 py-3 bg-gray-50 rounded-lg border border-gray-200">
                        <input type="hidden" name="settings[{{ $loop->index }}][shipping_method_id]" value="{{ $method->id }}">

                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-800">{{ $method->name }}</p>
                            <p class="text-xs text-gray-500">{{ $method->description ?? $method->code }}</p>
                        </div>

                        <x-form.toggle
                            name="settings[{{ $loop->index }}][is_active]"
                            label="Enabled"
                            :checked="(bool) ($setting?->is_active)"
                        />

                        <x-form.input
                            name="settings[{{ $loop->index }}][free_shipping_threshold_cents]"
                            label="Free Shipping at ({{ $country?->currency?->symbol ?? $country?->currency_code ?? 'cents' }})"
                            type="number"
                            min="0"
                            :value="$setting?->free_shipping_threshold_cents"
                            placeholder="0 = never free"
                            class="w-48"
                        />
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{-- TAB: Category Overrides (edit only)                                    --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    @if ($isEdit)
    <div
        x-show="activeTab === 'categories'"
        x-cloak
        class="bg-white rounded-b-xl border border-t-0 border-gray-200 p-6 shadow-sm"
    >
        <p class="text-sm text-gray-500 mb-4">
            Override per-category availability and commission rates for <strong>{{ $country->name_en }}</strong>.
            Leave commission blank to inherit the global category rate.
        </p>

        @php
            $catColumns = [
                ['title' => 'Category',          'data' => 'name_en',         'name' => 'name_en'],
                ['title' => 'Available',          'data' => 'is_available',    'name' => 'is_available',   'searchable' => false, 'render' => 'Renderers.badge({true:{label:"Yes",color:"success"},false:{label:"No",color:"danger"}})'],
                ['title' => 'Commission',         'data' => 'commission_rate', 'name' => 'commission_rate','searchable' => false],
                ['title' => 'Override Reason',    'data' => 'unavailable_reason', 'name' => 'unavailable_reason', 'orderable' => false, 'searchable' => false],
                ['title' => '',                   'data' => 'actions',         'name' => 'actions',         'orderable' => false, 'searchable' => false,
                 'render' => 'Renderers.actions([{type:"button",label:"Edit Override",id:"editCatOverride",class:"btn-ghost btn-sm"}])'],
            ];

            $catFilters = [
                ['type' => 'text',   'name' => 'search',       'label' => 'Category name'],
                ['type' => 'select', 'name' => 'is_available', 'label' => 'Available',
                 'options' => ['' => 'All', '1' => 'Yes', '0' => 'No']],
            ];
        @endphp

        <x-table.datatable
            id="categories-override-table"
            url="{{ route('admin.countries.categories.datatable', $country->id) }}"
            :columns="$catColumns"
            :filters="$catFilters"
            :page-length="25"
        />

    </div>
    @endif

</div>{{-- end x-data --}}

@if ($isEdit)
@push('modals')
    {{-- Add/Edit Payment Method Modal --}}
    <x-modal id="pm-modal" title="Payment Method">
        <form id="pm-form" novalidate>
            @csrf
            <input type="hidden" id="pm-id" name="pm_id">
            <div class="space-y-4 p-4">
                <div class="grid grid-cols-2 gap-4">
                    <x-form.select
                        name="method_type"
                        label="Type"
                        id="pm-method-type"
                        :options="['card' => 'Card', 'wallet' => 'Wallet', 'cod' => 'COD', 'bnpl' => 'BNPL', 'bank_transfer' => 'Bank Transfer']"
                    />
                    <x-form.input name="provider" label="Provider" id="pm-provider" placeholder="e.g. Visa, Mada, Tamara" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <x-form.input name="display_name_en" label="Display Name (EN)" id="pm-display-en" required />
                    <x-form.input name="display_name_ar" label="Display Name (AR)" id="pm-display-ar" dir="rtl" />
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <x-form.input name="fee_pct" label="Fee %" id="pm-fee-pct" type="number" step="0.01" min="0" value="0" />
                    <x-form.input name="fee_fixed_cents" label="Fixed Fee (cents)" id="pm-fee-fixed" type="number" min="0" value="0" />
                    <x-form.input name="sort_order" label="Sort Order" id="pm-sort-order" type="number" min="0" value="0" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <x-form.input name="min_order_cents" label="Min Order (cents)" id="pm-min-order" type="number" min="0" value="0" />
                    <x-form.input name="max_order_cents" label="Max Order (cents, blank = unlimited)" id="pm-max-order" type="number" min="0" />
                </div>
                <x-form.toggle name="is_active" label="Active" id="pm-is-active" :checked="true" />
            </div>
            <div class="flex justify-end gap-2 px-4 pb-4">
                <button type="button" class="btn btn-ghost" data-modal-close="pm-modal">Cancel</button>
                <button type="submit" id="pm-submit-btn" class="btn btn-primary">Save</button>
            </div>
        </form>
    </x-modal>

    {{-- Edit Category Override Modal --}}
    <x-modal id="cat-override-modal" title="Edit Category Override">
        <form id="cat-override-form" novalidate>
            @csrf
            <input type="hidden" id="cat-category-id" name="overrides[0][category_id]">
            <div class="space-y-4 p-4">
                <p class="font-medium text-gray-800" id="cat-name-display"></p>
                <x-form.toggle name="overrides[0][is_available]" label="Available in this country" id="cat-is-available" :checked="true" />
                <x-form.input
                    name="overrides[0][commission_rate]"
                    label="Commission Rate Override (%)"
                    id="cat-commission-rate"
                    type="number" step="0.01" min="0" max="100"
                    placeholder="Leave blank to use global rate"
                />
                <x-form.input
                    name="overrides[0][unavailable_reason]"
                    label="Reason (if unavailable)"
                    id="cat-unavailable-reason"
                    maxlength="100"
                />
                <x-form.textarea
                    name="overrides[0][notes]"
                    label="Internal Notes"
                    id="cat-notes"
                    rows="2"
                />
            </div>
            <div class="flex justify-end gap-2 px-4 pb-4">
                <button type="button" class="btn btn-ghost" data-modal-close="cat-override-modal">Cancel</button>
                <button
                    type="submit"
                    id="cat-override-submit"
                    data-url="{{ route('admin.countries.category-overrides.update', $country->id) }}"
                    class="btn btn-primary"
                >Save</button>
            </div>
        </form>
    </x-modal>
@endpush
@endif
