@extends('layouts.admin')

@section('title', $listing->productVariant->product->name_en)

@push('styles')
    @vite(['resources/js/components/flatpickr.js'])
@endpush

@section('content')
    @php
        $canEditRef = auth('admin')->user()?->can('products.cost_data.edit') ?? true;
    @endphp

    <div x-data="nawyListingShow(@js($costReference ? [
            'manufacturer_name' => $costReference->manufacturer_name,
            'manufacturer_url' => $costReference->manufacturer_url,
            'manufacturer_sku' => $costReference->manufacturer_sku,
            'manufacturer_cost' => $costReference->manufacturer_cost,
            'shipping_cost' => $costReference->shipping_cost,
            'landed_cost' => $costReference->landed_cost,
            'platform_margin_pct' => $costReference->platform_margin_pct,
            'competitor_links' => $costReference->competitorLinksNormalized(),
            'notes' => $costReference->notes,
            'created_by' => $costReference->createdByAdmin?->name,
            'updated_by' => $costReference->updatedByAdmin?->name,
            'updated_at' => $costReference->updated_at?->toISOString(),
        ] : null), '{{ $listing->currency }}', {{ $listing->price }})"
        class="p-6 space-y-5">

        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900">{{ $listing->productVariant->product->name_en }}</h1>
                <p class="text-sm text-gray-500 mt-0.5 font-mono">{{ $listing->productVariant->sku }} · {{ $listing->country->name_en }}</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.admin-product-listings.edit', $listing) }}" class="btn btn-secondary btn-sm">
                    {{ __('admin.admin_product_listings.edit_listing') }}
                </a>
                <a href="{{ route('admin.admin-product-listings.index') }}" class="btn btn-ghost btn-sm">
                    {{ __('common.back') }}
                </a>
            </div>
        </div>

        {{-- ── Tabs ─────────────────────────────────────────────────────── --}}
        <div class="border-b border-gray-200 flex gap-6">
            <button type="button" @click="tab = 'details'"
                :class="tab === 'details' ? 'border-primary-600 text-primary-700' : 'border-transparent text-gray-500 hover:text-gray-700'"
                class="pb-3 border-b-2 text-sm font-medium transition-colors">
                {{ __('admin.admin_product_listings.tab_listing_details') }}
            </button>
            <button type="button" @click="tab = 'reference'"
                :class="tab === 'reference' ? 'border-primary-600 text-primary-700' : 'border-transparent text-gray-500 hover:text-gray-700'"
                class="pb-3 border-b-2 text-sm font-medium flex items-center gap-1.5 transition-colors">
                <x-heroicon name="lock-closed" class="w-3.5 h-3.5" />
                {{ __('admin.admin_product_listings.tab_product_reference') }} / {{ __('admin.admin_product_listings.product_reference_ar') }}
            </button>
        </div>

        {{-- ── Tab 1: Listing Details (read-only) ──────────────────────── --}}
        <div x-show="tab === 'details'" class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <dl class="grid grid-cols-1 sm:grid-cols-3 gap-6 text-sm">
                <div>
                    <dt class="text-gray-400 text-xs uppercase tracking-wide">{{ __('admin.admin_product_listings.product_variant_required') }}</dt>
                    <dd class="mt-1 font-medium text-gray-900">{{ $listing->productVariant->product->name_en }}</dd>
                    <dd class="text-xs text-gray-400 font-mono">{{ $listing->productVariant->sku }}</dd>
                </div>
                <div>
                    <dt class="text-gray-400 text-xs uppercase tracking-wide">{{ __('admin.admin_product_listings.country_required') }}</dt>
                    <dd class="mt-1 font-medium text-gray-900">{{ $listing->country->name_en }}</dd>
                </div>
                <div>
                    <dt class="text-gray-400 text-xs uppercase tracking-wide">{{ __('common.status') }}</dt>
                    <dd class="mt-1">
                        @php
                            $sc = ['active' => 'bg-green-100 text-green-800', 'paused' => 'bg-yellow-100 text-yellow-700', 'archived' => 'bg-gray-100 text-gray-500'];
                        @endphp
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $sc[$listing->status->value] ?? '' }}">
                            {{ ucfirst($listing->status->value) }}
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-400 text-xs uppercase tracking-wide">{{ __('admin.admin_product_listings.price_required') }}</dt>
                    <dd class="mt-1 font-semibold text-gray-900">{{ number_format($listing->price, 2) }} {{ $listing->currency }}</dd>
                </div>
                <div>
                    <dt class="text-gray-400 text-xs uppercase tracking-wide">{{ __('admin.admin_product_listings.shipping_cost') }}</dt>
                    <dd class="mt-1 text-gray-800">{{ number_format($listing->shipping_cost, 2) }} {{ $listing->currency }}</dd>
                </div>
                <div>
                    <dt class="text-gray-400 text-xs uppercase tracking-wide">{{ __('admin.admin_product_listings.commission_type_required') }}</dt>
                    <dd class="mt-1 text-gray-800">{{ ucfirst($listing->commission_type->value) }} — {{ $listing->commission_value }}</dd>
                </div>
                <div>
                    <dt class="text-gray-400 text-xs uppercase tracking-wide">{{ __('admin.admin_product_listings.payment_options_required') }}</dt>
                    <dd class="mt-1 text-gray-800">{{ ['cod_only' => __('admin.admin_product_listings.cod_only'), 'electronic_only' => __('admin.admin_product_listings.electronic_only'), 'both' => __('admin.admin_product_listings.both_cod_electronic')][$listing->payment_options] ?? $listing->payment_options }}</dd>
                </div>
                <div>
                    <dt class="text-gray-400 text-xs uppercase tracking-wide">{{ __('admin.admin_product_listings.fulfillment_type_required') }}</dt>
                    <dd class="mt-1 text-gray-800">{{ __('admin.admin_product_listings.' . $listing->fulfillment_type) }}</dd>
                </div>
                <div>
                    <dt class="text-gray-400 text-xs uppercase tracking-wide">{{ __('admin.admin_product_listings.nawy_category') }}</dt>
                    <dd class="mt-1 text-gray-800">{{ $listing->nawyCategory?->name_en ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-400 text-xs uppercase tracking-wide">{{ __('admin.admin_product_listings.now_nawy_col') }}</dt>
                    <dd class="mt-1">
                        @if($listing->featured_in_nawy)
                            <span class="inline-flex items-center gap-1 text-xs text-primary-700 font-medium">
                                <x-heroicon name="sparkles" class="w-3.5 h-3.5" /> {{ __('admin.admin_product_listings.featured') }}
                            </span>
                        @else
                            <span class="text-xs text-gray-400">—</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-400 text-xs uppercase tracking-wide">{{ __('admin.admin_product_listings.exclusive_admin_only') }}</dt>
                    <dd class="mt-1 text-gray-800">{{ $listing->is_exclusive ? __('admin.admin_product_listings.yes') : __('admin.admin_product_listings.no') }}</dd>
                </div>
                <div>
                    <dt class="text-gray-400 text-xs uppercase tracking-wide">{{ __('admin.admin_product_listings.available_for_vendors') }} / {{ __('admin.admin_product_listings.available_for_marketers') }}</dt>
                    <dd class="mt-1 text-gray-800">
                        {{ $listing->available_for_vendors ? __('admin.admin_product_listings.yes') : __('admin.admin_product_listings.no') }} /
                        {{ $listing->available_for_marketers ? __('admin.admin_product_listings.yes') : __('admin.admin_product_listings.no') }}
                    </dd>
                </div>
            </dl>
        </div>

        {{-- ── Tab 2: Product Reference (confidential) ─────────────────── --}}
        <div x-show="tab === 'reference'" x-cloak class="bg-white rounded-xl border border-gray-200 shadow-sm divide-y divide-gray-100">

            <div class="flex items-start gap-3 bg-red-50 border-b border-red-200 px-5 py-3 rounded-t-xl">
                <x-heroicon name="lock-closed" class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" />
                <div>
                    <p class="text-sm font-semibold text-red-800">
                        {{ __('admin.admin_product_listings.tab_product_reference') }} / {{ __('admin.admin_product_listings.product_reference_ar') }}
                    </p>
                    <p class="text-xs text-red-600 mt-0.5">{{ __('admin.admin_product_listings.confidential_warning') }}</p>
                </div>
            </div>

            {{-- Manufacturer --}}
            <div class="px-5 py-4 space-y-3">
                <h4 class="text-sm font-semibold text-gray-700">{{ __('admin.admin_product_listings.manufacturer_details') }}</h4>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="label-sm">{{ __('admin.admin_product_listings.manufacturer_name') }}</label>
                        <input type="text" x-model="form.manufacturer_name" class="form-input w-full text-sm">
                    </div>
                    <div>
                        <label class="label-sm">{{ __('admin.admin_product_listings.manufacturer_url') }}</label>
                        <input type="url" x-model="form.manufacturer_url" dir="ltr" class="form-input w-full text-sm">
                    </div>
                    <div>
                        <label class="label-sm">{{ __('admin.admin_product_listings.manufacturer_sku') }}</label>
                        <input type="text" x-model="form.manufacturer_sku" class="form-input w-full text-sm font-mono">
                    </div>
                </div>
            </div>

            {{-- Cost breakdown --}}
            <div class="px-5 py-4 space-y-3">
                <h4 class="text-sm font-semibold text-gray-700">{{ __('admin.admin_product_listings.cost_breakdown') }}</h4>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="label-sm">{{ __('admin.admin_product_listings.factory_cost_label') }}</label>
                        <input type="number" x-model.number="form.manufacturer_cost" min="0" class="form-input w-full text-sm font-mono" @input="syncLanded()">
                        <p class="text-xs text-gray-400 mt-0.5" x-text="centsToCurrency(form.manufacturer_cost)"></p>
                    </div>
                    <div>
                        <label class="label-sm">{{ __('admin.admin_product_listings.shipping_cost_label') }}</label>
                        <input type="number" x-model.number="form.shipping_cost" min="0" class="form-input w-full text-sm font-mono" @input="syncLanded()">
                        <p class="text-xs text-gray-400 mt-0.5" x-text="centsToCurrency(form.shipping_cost)"></p>
                    </div>
                    <div>
                        <label class="label-sm">{{ __('admin.admin_product_listings.landed_cost_label') }}
                            <span class="text-gray-400 font-normal">({{ __('admin.admin_product_listings.landed_cost_override_note') }})</span>
                        </label>
                        <input type="number" x-model.number="form.landed_cost" min="0" class="form-input w-full text-sm font-mono">
                        <p class="text-xs text-gray-400 mt-0.5" x-text="centsToCurrency(form.landed_cost)"></p>
                    </div>
                </div>

                <div class="bg-gray-50 rounded-xl p-3 grid grid-cols-2 gap-3 text-center text-sm">
                    <div>
                        <p class="text-xs text-gray-400 mb-0.5">{{ __('admin.admin_product_listings.landed_cost_label') }}</p>
                        <p class="font-bold text-indigo-700" x-text="centsToCurrency(form.landed_cost) || '—'"></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 mb-0.5">{{ __('admin.admin_product_listings.platform_margin') }}</p>
                        <p class="font-bold" :class="marginPct !== null && marginPct < 0 ? 'text-red-700' : 'text-green-700'"
                           x-text="marginPct !== null ? marginPct.toFixed(1) + '%' : '—'"></p>
                    </div>
                </div>
            </div>

            {{-- Competitor links repeater --}}
            <div class="px-5 py-4 space-y-3">
                <div class="flex items-center justify-between">
                    <h4 class="text-sm font-semibold text-gray-700">{{ __('admin.admin_product_listings.competitor_tracking') }}</h4>
                    <button type="button" @click="addCompetitor()" class="btn btn-ghost btn-xs">{{ __('admin.admin_product_listings.add_competitor') }}</button>
                </div>

                <div x-show="form.competitor_links.length === 0" class="text-sm text-gray-400 italic py-2">—</div>

                <div class="space-y-2">
                    <template x-for="(comp, idx) in form.competitor_links" :key="idx">
                        <div class="flex flex-wrap sm:flex-nowrap items-center gap-2 bg-gray-50 rounded-lg p-2.5">
                            <input type="text" x-model="comp.name" class="form-input text-xs w-full sm:w-32 flex-shrink-0"
                                placeholder="{{ __('admin.admin_product_listings.competitor_platform') }}">
                            <input type="url" x-model="comp.url" dir="ltr" class="form-input text-xs w-full sm:flex-1 font-mono min-w-0"
                                placeholder="{{ __('admin.admin_product_listings.competitor_url') }}">
                            <input type="number" x-model.number="comp.price" class="form-input text-xs w-full sm:w-28 font-mono flex-shrink-0"
                                placeholder="{{ __('admin.admin_product_listings.competitor_price') }}">
                            <input type="text" x-model="comp.last_checked" data-flatpickr class="form-input text-xs w-full sm:w-32 flex-shrink-0"
                                placeholder="{{ __('admin.admin_product_listings.competitor_last_checked') }}">
                            <button type="button" @click="removeCompetitor(idx)" class="text-red-400 hover:text-red-600 p-0.5 flex-shrink-0">
                                <x-heroicon name="x-mark" class="w-3.5 h-3.5" />
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Notes --}}
            <div class="px-5 py-4 space-y-2">
                <h4 class="text-sm font-semibold text-gray-700">{{ __('admin.admin_product_listings.internal_notes') }}</h4>
                <textarea x-model="form.notes" rows="3" class="form-input w-full text-sm"></textarea>
            </div>

            {{-- Footer --}}
            <div class="px-5 py-3 bg-gray-50 rounded-b-xl flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <p class="text-xs text-gray-400 leading-snug">
                    <span x-show="ref?.created_by">{{ __('admin.admin_product_listings.created_by_label') }} <span class="font-medium" x-text="ref?.created_by"></span></span>
                    <span x-show="ref?.updated_by"> · {{ __('admin.admin_product_listings.last_edited_by') }} <span class="font-medium" x-text="ref?.updated_by"></span></span>
                    <span x-show="!ref">{{ __('admin.admin_product_listings.not_yet_saved') }}</span>
                </p>
                <button type="button" @click="saveRef()" :disabled="saving" class="btn btn-primary btn-sm min-w-32 flex items-center justify-center gap-2">
                    <span x-show="saving" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                    <span x-text="saving ? 'Saving…' : '{{ __('admin.admin_product_listings.save_reference') }}'"></span>
                </button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function nawyListingShow(initialRef, currency, price) {
            return {
                tab: 'details',
                saving: false,
                currency,
                priceCents: price,
                ref: initialRef,
                form: {
                    manufacturer_name: initialRef?.manufacturer_name ?? '',
                    manufacturer_url: initialRef?.manufacturer_url ?? '',
                    manufacturer_sku: initialRef?.manufacturer_sku ?? '',
                    manufacturer_cost: initialRef?.manufacturer_cost ?? null,
                    shipping_cost: initialRef?.shipping_cost ?? null,
                    landed_cost: initialRef?.landed_cost ?? null,
                    competitor_links: initialRef?.competitor_links ?? [],
                    notes: initialRef?.notes ?? '',
                },

                get marginPct() {
                    const landed = this.form.landed_cost;
                    if (!landed || !this.priceCents) return null;
                    return ((this.priceCents - landed) / this.priceCents) * 100;
                },

                syncLanded() {
                    const mfr = parseInt(this.form.manufacturer_cost) || 0;
                    const ship = parseInt(this.form.shipping_cost) || 0;
                    if (mfr + ship > 0) this.form.landed_cost = mfr + ship;
                },

                addCompetitor() {
                    this.form.competitor_links.push({ name: '', url: '', price: null, last_checked: null });
                },

                removeCompetitor(idx) {
                    this.form.competitor_links.splice(idx, 1);
                },

                centsToCurrency(cents) {
                    if (cents === null || cents === undefined || cents === '' || isNaN(cents)) return '';
                    return (parseInt(cents)).toLocaleString('en-EG', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ' + this.currency;
                },

                async saveRef() {
                    this.saving = true;
                    try {
                        const res = await fetch('{{ route('admin.admin-product-listings.save-reference', $listing) }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Content-Type': 'application/json',
                                Accept: 'application/json',
                            },
                            body: JSON.stringify(this.form),
                        });
                        const data = await res.json();
                        if (data.success) {
                            window.Toast ? window.Toast.success(data.message) : null;
                            this.ref = data.ref;
                        } else {
                            window.Toast ? window.Toast.error(data.message || '{{ __('admin.admin_product_listings.reference_save_failed') }}') : null;
                        }
                    } catch (e) {
                        window.Toast ? window.Toast.error('{{ __('admin.admin_product_listings.reference_save_failed') }}') : null;
                    } finally {
                        this.saving = false;
                    }
                },
            };
        }
    </script>
@endpush
