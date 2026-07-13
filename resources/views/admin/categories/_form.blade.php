{{--
    Shared Category form partial.
    Include with: @include('admin.categories._form', ['mode' => 'create'])
                  @include('admin.categories._form', ['mode' => 'edit', 'category' => $category])

    Parent view must wrap this in a <form> tag with proper action / @csrf / @method.
--}}
@php
    $category = $category ?? null;
    $isEdit = $category !== null;

    $val = function (string $field, $default = '') use ($isEdit, $category) {
        return old($field, $isEdit ? ($category->{$field} ?? $default) : $default);
    };

    $bool = function (string $field, bool $default = false) use ($isEdit, $category): bool {
        $raw = old($field, $isEdit ? ($category->{$field} ?? $default) : $default);
        return (bool) $raw;
    };
@endphp

<div
    x-data="{ activeTab: 'general' }"
    class="space-y-6"
>
    <input type="hidden" id="form-mode" name="_form_mode" value="{{ $isEdit ? 'edit' : 'create' }}">

    <div class="flex flex-col lg:flex-row gap-6 items-start">

        {{-- ═══════════════════════════════════════════ --}}
        {{-- LEFT COLUMN: tabbed panels                  --}}
        {{-- ═══════════════════════════════════════════ --}}
        <div class="flex-1 min-w-0">

            {{-- Tab navigation --}}
            <div class="bg-white rounded-t-xl border border-gray-200 overflow-hidden">
                <nav class="flex overflow-x-auto border-b border-gray-100" aria-label="{{ __('admin.categories.form_tabs_aria') }}">
                    @foreach([
                        ['id' => 'general',    'label' => __('admin.general'),    'icon' => 'information-circle'],
                        ['id' => 'attributes', 'label' => __('admin.categories.attributes_tab'), 'icon' => 'tag'],
                        ['id' => 'seo',        'label' => __('admin.categories.seo_tab'),        'icon' => 'magnifying-glass'],
                    ] as $tab)
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
                    @endforeach
                </nav>
            </div>

            {{-- TAB: General --}}
            <div
                x-show="activeTab === 'general'"
                class="bg-white rounded-b-xl border border-t-0 border-gray-200 p-6 shadow-sm space-y-5"
            >
                {{-- Names --}}
                <div class="grid grid-cols-2 gap-4">
                    <x-form.input
                        name="name_en"
                        label="{{ __('admin.name_en') }}"
                        :value="$val('name_en')"
                        required
                        maxlength="255"
                        dir="ltr"
                        placeholder="{{ __('admin.categories.name_en_placeholder') }}"
                    />
                    <x-form.input
                        name="name_ar"
                        label="{{ __('admin.name_ar') }}"
                        :value="$val('name_ar')"
                        required
                        maxlength="255"
                        dir="rtl"
                        placeholder="{{ __('admin.categories.name_ar_placeholder') }}"
                    />
                </div>

                {{-- Slug --}}
                <x-form.input
                    name="slug"
                    label="{{ __('admin.slug') }}"
                    :value="$val('slug')"
                    maxlength="255"
                    placeholder="{{ __('admin.categories.slug_auto_generated_placeholder') }}"
                    data-slug-input
                    data-slug-source="name_en"
                />

                {{-- Parent --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.categories.parent_category') }}</label>
                    <select name="parent_id" class="input w-full">
                        <option value="">{{ __('admin.categories.root_no_parent') }}</option>
                        @foreach($parents as $parent)
                            <option value="{{ $parent['id'] }}"
                                @selected(old('parent_id', $isEdit ? $category->parent_id : '') == $parent['id'])>
                                {!! e($parent['name']) !!}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Descriptions --}}
                <div class="grid grid-cols-2 gap-4">
                    <x-form.textarea
                        name="description_en"
                        label="{{ __('admin.description_en') }}"
                        :value="$val('description_en')"
                        rows="3"
                        maxlength="2000"
                        dir="ltr"
                    />
                    <x-form.textarea
                        name="description_ar"
                        label="{{ __('admin.description_ar') }}"
                        :value="$val('description_ar')"
                        rows="3"
                        maxlength="2000"
                        dir="rtl"
                    />
                </div>

                {{-- Commission Rates (FBP / FBN) --}}
                <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                    <h4 class="text-sm font-semibold text-gray-700 mb-3">
                        {{ __('admin.categories.commission_rates') }}
                        <span class="text-xs font-normal text-gray-400 ml-2">{{ __('admin.categories.commission_rates_hint') }}</span>
                    </h4>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {{-- FBP block --}}
                        <div class="bg-white rounded-lg border border-gray-200 p-4">
                            <p class="text-xs font-bold uppercase tracking-wide text-blue-600 mb-3">{{ __('admin.categories.fbp_label') }}</p>
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.categories.percentage_rate') }}</label>
                                    <input type="number" name="commission_fbp_pct"
                                        step="0.01" min="0" max="100"
                                        value="{{ old('commission_fbp_pct', $category->commission_fbp_pct ?? 0) }}"
                                        class="input w-full text-sm commission-fbp-input">
                                    <p class="text-xs text-gray-400 mt-1">{{ __('admin.categories.percentage_rate_hint') }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.categories.fixed_fee_per_unit') }}</label>
                                    <div class="flex items-center gap-2">
                                        <input type="number" name="commission_fbp_fixed_cents"
                                            step="1" min="0"
                                            value="{{ old('commission_fbp_fixed_cents', $category->commission_fbp_fixed_cents ?? 0) }}"
                                            class="input w-full text-sm commission-fbp-input">
                                        <span class="text-xs text-gray-400 whitespace-nowrap">{{ __('admin.categories.cents') }}</span>
                                    </div>
                                    <p class="text-xs text-gray-400 mt-1">{{ __('admin.categories.fixed_fee_hint') }}</p>
                                </div>
                                <div class="bg-blue-50 rounded-lg p-2.5 text-xs text-blue-700">
                                    <strong>{{ __('admin.categories.example') }}:</strong> {{ __('admin.categories.example_calc') }}<br>
                                    <span id="fbp-preview">—</span>
                                </div>
                            </div>
                        </div>

                        {{-- FBN block --}}
                        <div class="bg-white rounded-lg border border-gray-200 p-4">
                            <p class="text-xs font-bold uppercase tracking-wide text-green-600 mb-3">{{ __('admin.categories.fbn_label') }}</p>
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.categories.percentage_rate') }}</label>
                                    <input type="number" name="commission_fbn_pct"
                                        step="0.01" min="0" max="100"
                                        value="{{ old('commission_fbn_pct', $category->commission_fbn_pct ?? 0) }}"
                                        class="input w-full text-sm commission-fbn-input">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.categories.fixed_fee_per_unit') }}</label>
                                    <div class="flex items-center gap-2">
                                        <input type="number" name="commission_fbn_fixed_cents"
                                            step="1" min="0"
                                            value="{{ old('commission_fbn_fixed_cents', $category->commission_fbn_fixed_cents ?? 0) }}"
                                            class="input w-full text-sm commission-fbn-input">
                                        <span class="text-xs text-gray-400 whitespace-nowrap">{{ __('admin.categories.cents') }}</span>
                                    </div>
                                </div>
                                <div class="bg-green-50 rounded-lg p-2.5 text-xs text-green-700">
                                    <strong>{{ __('admin.categories.example') }}:</strong> {{ __('admin.categories.example_calc') }}<br>
                                    <span id="fbn-preview">—</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <p class="text-xs text-gray-400 mt-3">
                        {{ __('admin.categories.commission_formula_note') }}
                    </p>
                </div>

                <script>
                    (function () {
                        function updatePreview(type) {
                            const pct = parseFloat(document.querySelector('[name="commission_' + type + '_pct"]').value) || 0;
                            const fixed = parseInt(document.querySelector('[name="commission_' + type + '_fixed_cents"]').value) || 0;
                            const examplePrice = 10000;
                            const exampleQty = 2;
                            const commissionPerUnit = Math.round(examplePrice * pct / 100) + fixed;
                            const total = commissionPerUnit * exampleQty;
                            document.getElementById(type + '-preview').textContent =
                                '(' + (examplePrice / 100).toFixed(2) + ' × ' + pct + '%) + ' + (fixed / 100).toFixed(2) +
                                ' = ' + (commissionPerUnit / 100).toFixed(2) + ' per unit × ' + exampleQty +
                                ' = ' + (total / 100).toFixed(2) + ' total';
                        }
                        document.addEventListener('DOMContentLoaded', function () {
                            ['fbp', 'fbn'].forEach(function (t) {
                                document.querySelectorAll('.commission-' + t + '-input')
                                    .forEach(function (el) { el.addEventListener('input', function () { updatePreview(t); }); });
                                updatePreview(t);
                            });
                        });
                    })();
                </script>

                {{-- Delivery Options (edit mode only — requires a category ID) --}}
                @if($isEdit)
                <div class="border border-gray-200 rounded-xl overflow-hidden mt-1"
                    x-data="categoryShippingMethods('{{ route('admin.categories.shipping-methods', $category->id) }}', '{{ route('admin.categories.shipping-methods.update', $category->id) }}')">
                    <button type="button" @click="toggle"
                        class="w-full flex items-center justify-between p-4 bg-gray-50 text-sm font-semibold text-gray-700 hover:bg-gray-100 transition-colors">
                        <span>🚚 {{ __('admin.categories.delivery_options') }}</span>
                        <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="{'rotate-180': open}"
                            fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                        </svg>
                    </button>
                    <div x-show="open" x-cloak class="p-4 space-y-3">
                        <p class="text-xs text-gray-500 mb-3">
                            {{ __('admin.categories.delivery_options_hint') }}
                            <strong>{{ __('admin.categories.default_delivery') }}</strong> {{ __('admin.categories.delivery_options_hint_2') }}
                        </p>
                        <div id="shipping-methods-matrix">
                            <template x-if="loading">
                                <div class="text-center text-sm text-gray-400 py-4">{{ __('common.loading') }}</div>
                            </template>
                            <template x-if="!loading && methods.length === 0">
                                <div class="text-center text-sm text-gray-400 py-4">{{ __('admin.categories.no_shipping_methods_configured') }}</div>
                            </template>
                            <template x-if="!loading && methods.length > 0">
                                <div class="divide-y divide-gray-100">
                                    <template x-for="m in methods" :key="m.id">
                                        <div class="grid gap-3 items-center py-2.5"
                                            style="grid-template-columns: auto 1fr auto auto auto">
                                            {{-- Enable toggle --}}
                                            <input type="checkbox" class="rounded border-gray-300 text-primary-600 cursor-pointer"
                                                :checked="m.enabled"
                                                @change="m.enabled = $event.target.checked">
                                            {{-- Badge + name --}}
                                            <div class="flex items-center gap-2 min-w-0">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold whitespace-nowrap"
                                                    :style="'background:' + m.badge_color_hex + '; color:' + m.badge_text_color_hex"
                                                    x-text="m.badge_label_en || m.name"></span>
                                                <span class="text-sm font-medium text-gray-700 truncate" x-text="m.name"></span>
                                                <span class="text-xs text-gray-400 whitespace-nowrap" x-text="m.delivery_label_en"></span>
                                            </div>
                                            {{-- Default radio --}}
                                            <label class="flex items-center gap-1 text-xs text-gray-500 cursor-pointer whitespace-nowrap">
                                                <input type="radio" name="sm_default_method"
                                                    :value="m.id"
                                                    x-model="defaultMethodId"
                                                    class="text-primary-600">
                                                {{ __('admin.categories.default_delivery') }}
                                            </label>
                                            {{-- FBN --}}
                                            <label class="flex items-center gap-1 text-xs text-gray-500 cursor-pointer whitespace-nowrap">
                                                <input type="checkbox" class="rounded border-gray-300 text-primary-600"
                                                    :checked="m.is_available_for_express_fbn"
                                                    @change="m.is_available_for_express_fbn = $event.target.checked">
                                                FBN
                                            </label>
                                            {{-- FBP --}}
                                            <label class="flex items-center gap-1 text-xs text-gray-500 cursor-pointer whitespace-nowrap">
                                                <input type="checkbox" class="rounded border-gray-300 text-primary-600"
                                                    :checked="m.is_available_for_merchant_fbp"
                                                    @change="m.is_available_for_merchant_fbp = $event.target.checked">
                                                FBP
                                            </label>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                        <div class="flex items-center gap-3 mt-3">
                            <button type="button" @click="save"
                                :disabled="saving"
                                class="btn btn-primary btn-sm"
                                :class="{'opacity-60 cursor-not-allowed': saving}">
                                <span x-text="saving ? window.TRANSLATIONS.savingEllipsis : window.TRANSLATIONS.saveDeliveryOptions"></span>
                            </button>
                            <span x-show="savedMsg" x-cloak class="text-xs text-green-600 font-medium">✓ <span x-text="window.TRANSLATIONS.saved"></span></span>
                            <span x-show="errorMsg" x-cloak class="text-xs text-red-600 font-medium" x-text="errorMsg"></span>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Sort Order --}}
                <x-form.input
                    name="sort_order"
                    label="{{ __('admin.sort_order') }}"
                    type="number"
                    :value="$val('sort_order', '0')"
                    min="0"
                />

                {{-- Sample Quotas --}}
                <div class="border-t border-gray-100 pt-5 space-y-4">
                    <h4 class="text-sm font-semibold text-gray-700">{{ __('admin.categories.sample_quotas') }}</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-form.input
                                name="marketer_sample_quota"
                                label="{{ __('admin.categories.marketer_sample_quota') }}"
                                type="number"
                                :value="$val('marketer_sample_quota', '0')"
                                min="0"
                            />
                            <p class="mt-1 text-xs text-gray-500">{{ __('admin.categories.marketer_sample_quota_hint') }}</p>
                        </div>
                        <div>
                            <x-form.input
                                name="admin_sample_quota"
                                label="{{ __('admin.categories.admin_sample_quota') }}"
                                type="number"
                                :value="$val('admin_sample_quota', '0')"
                                min="0"
                            />
                            <p class="mt-1 text-xs text-gray-500">{{ __('admin.categories.admin_sample_quota_hint') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TAB: Attributes --}}
            <div
                x-show="activeTab === 'attributes'"
                class="bg-white rounded-b-xl border border-t-0 border-gray-200 p-6 shadow-sm space-y-4"
            >
                <p class="text-sm text-gray-500">
                    {{ __('admin.categories.attributes_tab_hint') }}
                </p>

                @if($allAttributes->isEmpty())
                    <p class="text-sm text-gray-400 italic">{{ __('admin.categories.no_attributes_defined') }} <a href="{{ route('admin.attributes.create') }}" class="text-primary-600 underline">{{ __('admin.categories.create_attributes_first') }}</a></p>
                @else
                    <div class="divide-y divide-gray-100 border border-gray-200 rounded-lg overflow-hidden">
                        @foreach($allAttributes as $attr)
                        @php
                            $assigned = $isEdit
                                ? $category->categoryAttributes->firstWhere('attribute_id', $attr->id)
                                : null;
                        @endphp
                        <div class="flex items-center gap-4 px-4 py-3 bg-white hover:bg-gray-50">
                            <input type="checkbox"
                                name="attributes[{{ $loop->index }}][attribute_id]"
                                value="{{ $attr->id }}"
                                id="attr_{{ $attr->id }}"
                                class="attr-assign-cb rounded border-gray-300 text-primary-600"
                                @checked($assigned !== null) />
                            <input type="hidden" name="attributes[{{ $loop->index }}][attribute_id]" value="{{ $attr->id }}" disabled />
                            <label for="attr_{{ $attr->id }}" class="flex-1 cursor-pointer">
                                <span class="text-sm font-medium text-gray-900">{{ $attr->name_en }}</span>
                                <span class="ml-2 text-xs text-gray-400">{{ $attr->code }}</span>
                                <span class="ml-1 text-xs px-1.5 py-0.5 bg-gray-100 rounded text-gray-500">{{ $attr->type?->value }}</span>
                            </label>
                            <label class="flex items-center gap-1 text-xs text-gray-600 cursor-pointer">
                                <input type="checkbox"
                                    name="attributes[{{ $loop->index }}][is_required]"
                                    value="1"
                                    class="rounded border-gray-300 text-primary-600"
                                    @checked($assigned?->is_required) />
                                {{ __('admin.required') }}
                            </label>
                            <input type="hidden" name="attributes[{{ $loop->index }}][sort_order]" value="{{ $loop->index }}" />
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- TAB: SEO --}}
            <div
                x-show="activeTab === 'seo'"
                class="bg-white rounded-b-xl border border-t-0 border-gray-200 p-6 shadow-sm space-y-5"
            >
                <div class="grid grid-cols-2 gap-4">
                    <x-form.input
                        name="seo_title_en"
                        label="{{ __('admin.categories.seo_title_en') }}"
                        :value="$val('seo_title_en')"
                        maxlength="70"
                        dir="ltr"
                        placeholder="{{ __('admin.categories.seo_title_placeholder') }}"
                    />
                    <x-form.input
                        name="seo_title_ar"
                        label="{{ __('admin.categories.seo_title_ar') }}"
                        :value="$val('seo_title_ar')"
                        maxlength="70"
                        dir="rtl"
                    />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <x-form.textarea
                        name="seo_description_en"
                        label="{{ __('admin.categories.seo_description_en') }}"
                        :value="$val('seo_description_en')"
                        rows="3"
                        maxlength="160"
                        dir="ltr"
                    />
                    <x-form.textarea
                        name="seo_description_ar"
                        label="{{ __('admin.categories.seo_description_ar') }}"
                        :value="$val('seo_description_ar')"
                        rows="3"
                        maxlength="160"
                        dir="rtl"
                    />
                </div>
            </div>

        </div>{{-- /left column --}}

        {{-- ═══════════════════════════════════════════ --}}
        {{-- RIGHT SIDEBAR                               --}}
        {{-- ═══════════════════════════════════════════ --}}
        <div class="w-full lg:w-72 flex-shrink-0 space-y-4">

            {{-- Visibility --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 space-y-3">
                <h3 class="text-sm font-semibold text-gray-900">{{ __('admin.visibility') }}</h3>
                <x-form.toggle name="is_active"  label="{{ __('common.active') }}"  :value="$bool('is_active', true)" />
                <x-form.toggle name="is_visible" label="{{ __('admin.is_visible') }}" :value="$bool('is_visible', true)" />
                <x-form.toggle name="is_featured" label="{{ __('admin.categories.featured_homepage') }}" :value="$bool('is_featured')" />
            </div>

            {{-- Save actions --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 space-y-2">
                @if($errors->any())
                <div class="rounded-lg bg-red-50 border border-red-200 p-3 text-xs text-red-700 space-y-1">
                    @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                    @endforeach
                </div>
                @endif

                <button type="submit" id="submit-btn"
                    class="btn btn-primary w-full justify-center">
                    {{ $isEdit ? __('admin.categories.save_changes') : __('admin.categories.create_category') }}
                </button>
                <a href="{{ route('admin.categories.index') }}"
                    class="btn btn-ghost w-full justify-center text-center block">
                    {{ __('common.cancel') }}
                </a>
            </div>

        </div>{{-- /right sidebar --}}

    </div>{{-- /flex row --}}

</div>{{-- /x-data root --}}

@if($isEdit)
<script>
window.TRANSLATIONS = window.TRANSLATIONS || {};
Object.assign(window.TRANSLATIONS, {
    savingEllipsis: @json(__('admin.products.saving_ellipsis')),
    saveDeliveryOptions: @json(__('admin.categories.save_delivery_options')),
    saved: @json(__('admin.categories.saved_short')),
    failedLoadMethods: @json(__('admin.categories.failed_load_methods')),
    saveFailed: @json(__('admin.categories.save_failed')),
    networkError: @json(__('admin.products.network_error')),
    categorySaved: @json(__('admin.categories.category_saved')),
    validationError: @json(__('admin.products.validation_error')),
    saveFailedRetry: @json(__('admin.products.save_failed_retry')),
    saveChangesBtn: @json(__('admin.categories.save_changes_btn')),
});

function categoryShippingMethods(getUrl, postUrl) {
    return {
        open: false,
        loaded: false,
        loading: false,
        saving: false,
        savedMsg: false,
        errorMsg: '',
        methods: [],
        defaultMethodId: null,

        toggle() {
            this.open = !this.open;
            if (this.open && !this.loaded) this.load();
        },

        async load() {
            this.loading = true;
            try {
                const r = await fetch(getUrl, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await r.json();
                this.methods = data.methods.map(m => ({...m}));
                this.defaultMethodId = this.methods.find(m => m.is_default)?.id ?? null;
                this.loaded = true;
            } catch (e) {
                this.errorMsg = window.TRANSLATIONS.failedLoadMethods;
            } finally {
                this.loading = false;
            }
        },

        async save() {
            this.saving = true;
            this.savedMsg = false;
            this.errorMsg = '';
            try {
                const r = await fetch(postUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        methods: this.methods.map(m => ({
                            shipping_method_id: m.id,
                            enabled: m.enabled,
                            is_default: m.id === this.defaultMethodId,
                            is_available_for_express_fbn: m.is_available_for_express_fbn,
                            is_available_for_merchant_fbp: m.is_available_for_merchant_fbp,
                        }))
                    }),
                });
                const data = await r.json();
                if (!r.ok) {
                    this.errorMsg = data.message || window.TRANSLATIONS.saveFailed;
                } else {
                    this.savedMsg = true;
                    setTimeout(() => { this.savedMsg = false; }, 3000);
                }
            } catch (e) {
                this.errorMsg = window.TRANSLATIONS.networkError;
            } finally {
                this.saving = false;
            }
        },
    };
}
</script>
@endif
