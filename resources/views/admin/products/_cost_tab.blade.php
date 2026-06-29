{{--
Cost Reference Tab — admin/products/_cost_tab.blade.php
Include in admin.products._form with:
@include('admin.products._cost_tab', ['product' => $product])

Only rendered when the logged-in admin has 'products.cost_data.view'.
--}}

@php
    $canView = auth('admin')->user()?->hasPermissionTo('products.cost_data.view');
    $canEdit = auth('admin')->user()?->hasPermissionTo('products.cost_data.edit');
    $costCurrency = $product->variants()->first()?->vendorListings()->value('currency') ?? 'EGP';
@endphp

@if($canView)
    <div x-data="costReferencePanel('{{ $product->id }}', '{{ $costCurrency }}', @js(route('admin.products.cost.show', $product->id)),
                                                          @js(route('admin.products.cost.save', $product->id)),
                                                          @js(route('admin.products.cost.calculate', $product->id)),
                                                          @js(route('admin.products.cost.check-competitors', $product->id)))"
        x-init="loadRef()" x-show="activeTab === 'cost'"
        class="bg-white rounded-b-xl border border-t-0 border-gray-200 shadow-sm">

        {{-- ─── Loading skeleton ────────────────────────────────────────────── --}}
        <div x-show="loading" class="p-8 text-center text-gray-400 text-sm">
            <svg class="animate-spin h-5 w-5 mx-auto mb-2 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
            </svg>
            Loading cost data…
        </div>

        {{-- ─── Below-cost warning banner ──────────────────────────────────── --}}
        <div x-show="!loading && belowCostWarning" x-cloak
            class="flex items-start gap-3 bg-red-50 border-b border-red-200 px-5 py-3">
            <span class="text-xl leading-none mt-0.5">⚠️</span>
            <div>
                <p class="text-sm font-semibold text-red-800">Vendor Selling Below Platform Cost</p>
                <p class="text-xs text-red-600 mt-0.5">
                    The lowest active vendor price (<span x-text="lowestPriceFormatted"></span>)
                    is at or below the landed cost for this product.
                    <strong>This alert is only visible to admins.</strong>
                </p>
            </div>
        </div>

        <div x-show="!loading" class="divide-y divide-gray-100">

            {{-- ─── Section 1: Manufacturer ────────────────────────────────── --}}
            <div class="px-5 py-4 space-y-4">
                <div class="flex items-center justify-between">
                    <h4 class="text-sm font-semibold text-gray-700 flex items-center gap-1.5">
                        <x-heroicon name="building-office" class="w-4 h-4 text-gray-400" />
                        Manufacturer Details
                    </h4>
                    <span
                        class="text-[10px] bg-red-100 text-red-700 px-2 py-0.5 rounded-full font-medium uppercase tracking-wide">
                        🔒 Confidential
                    </span>
                </div>

                @if($canEdit)
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="label-sm">Manufacturer Name</label>
                            <input type="text" x-model="form.manufacturer_name" class="form-input w-full text-sm"
                                placeholder="e.g. Shenzhen ABC Ltd.">
                        </div>
                        <div>
                            <label class="label-sm">Manufacturer URL</label>
                            <input type="url" x-model="form.manufacturer_url" class="form-input w-full text-sm"
                                placeholder="https://manufacturer.com">
                        </div>
                        <div>
                            <label class="label-sm">Manufacturer SKU</label>
                            <input type="text" x-model="form.manufacturer_sku" class="form-input w-full text-sm font-mono"
                                placeholder="MFR-SKU-001">
                        </div>
                    </div>
                @else
                    <dl class="grid grid-cols-3 gap-3 text-sm">
                        <div>
                            <dt class="text-gray-400 text-xs">Manufacturer</dt>
                            <dd class="font-medium text-gray-800 mt-0.5" x-text="ref?.manufacturer_name || '—'"></dd>
                        </div>
                        <div>
                            <dt class="text-gray-400 text-xs">URL</dt>
                            <dd class="mt-0.5">
                                <a x-show="ref?.manufacturer_url" :href="ref?.manufacturer_url" target="_blank"
                                    class="text-blue-600 hover:underline text-xs truncate block"
                                    x-text="ref?.manufacturer_url"></a>
                                <span x-show="!ref?.manufacturer_url" class="text-gray-400">—</span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-gray-400 text-xs">SKU</dt>
                            <dd class="font-mono text-gray-700 mt-0.5" x-text="ref?.manufacturer_sku || '—'"></dd>
                        </div>
                    </dl>
                @endif
            </div>

            {{-- ─── Section 2: Cost Breakdown ───────────────────────────────── --}}
            <div class="px-5 py-4 space-y-4">
                <h4 class="text-sm font-semibold text-gray-700 flex items-center gap-1.5">
                    <x-heroicon name="banknotes" class="w-4 h-4 text-gray-400" />
                    Cost Breakdown
                </h4>

                @if($canEdit)
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="label-sm">Factory / Purchase Cost (cents)</label>
                            <input type="number" x-model.number="form.manufacturer_cost_cents" min="0"
                                class="form-input w-full text-sm font-mono" :placeholder="'e.g. 15000 = 150.00 ' + currencyCode"
                                @input="syncLandedCost()">
                            <p class="text-xs text-gray-400 mt-0.5" x-text="centsToCurrency(form.manufacturer_cost_cents, currencyCode)"></p>
                        </div>
                        <div>
                            <label class="label-sm">Shipping / Logistics Cost (cents)</label>
                            <input type="number" x-model.number="form.shipping_cost_cents" min="0"
                                class="form-input w-full text-sm font-mono" :placeholder="'e.g. 3000 = 30.00 ' + currencyCode"
                                @input="syncLandedCost()">
                            <p class="text-xs text-gray-400 mt-0.5" x-text="centsToCurrency(form.shipping_cost_cents, currencyCode)"></p>
                        </div>
                        <div>
                            <label class="label-sm">
                                Landed Cost (cents)
                                <span class="text-gray-400 font-normal">(auto or override)</span>
                            </label>
                            <input type="number" x-model.number="form.landed_cost_cents" min="0"
                                class="form-input w-full text-sm font-mono" placeholder="Auto-calculated">
                            <p class="text-xs text-gray-400 mt-0.5" x-text="centsToCurrency(form.landed_cost_cents, currencyCode)"></p>
                        </div>
                    </div>

                    {{-- Cost summary row --}}
                    <div class="grid grid-cols-3 gap-3 bg-gray-50 rounded-xl p-3 text-center text-sm">
                        <div>
                            <p class="text-xs text-gray-400 mb-0.5">Factory</p>
                            <p class="font-semibold text-gray-800" x-text="centsToCurrency(form.manufacturer_cost_cents, currencyCode) || '—'"></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 mb-0.5">+ Shipping</p>
                            <p class="font-semibold text-gray-800" x-text="centsToCurrency(form.shipping_cost_cents, currencyCode) || '—'"></p>
                        </div>
                        <div class="border-l border-gray-200">
                            <p class="text-xs text-gray-400 mb-0.5">= Landed Cost</p>
                            <p class="font-bold text-indigo-700" x-text="centsToCurrency(form.landed_cost_cents, currencyCode) || '—'"></p>
                        </div>
                    </div>
                @else
                    <div class="grid grid-cols-3 gap-3 bg-gray-50 rounded-xl p-3 text-center text-sm">
                        <div>
                            <p class="text-xs text-gray-400 mb-0.5">Factory Cost</p>
                            <p class="font-semibold text-gray-800" x-text="ref?.manufacturer_cost_formatted || '—'"></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 mb-0.5">+ Shipping</p>
                            <p class="font-semibold text-gray-800" x-text="ref?.shipping_cost_formatted || '—'"></p>
                        </div>
                        <div class="border-l border-gray-200">
                            <p class="text-xs text-gray-400 mb-0.5">= Landed</p>
                            <p class="font-bold text-indigo-700" x-text="ref?.landed_cost_formatted || '—'"></p>
                        </div>
                    </div>
                @endif
            </div>

            {{-- ─── Section 3: Margin Calculator ────────────────────────────── --}}
            <div class="px-5 py-4 space-y-4">
                <h4 class="text-sm font-semibold text-gray-700 flex items-center gap-1.5">
                    <x-heroicon name="calculator" class="w-4 h-4 text-gray-400" />
                    Margin Calculator
                </h4>

                <div class="flex flex-wrap items-end gap-3">
                    <div>
                        <label class="label-sm">Selling Price (cents)</label>
                        <input type="number" x-model.number="calcPrice" min="1" class="form-input text-sm font-mono w-44"
                            :placeholder="'e.g. 25000 = 250 ' + currencyCode">
                        <p class="text-xs text-gray-400 mt-0.5" x-text="centsToCurrency(calcPrice, currencyCode)"></p>
                    </div>
                    <div>
                        <label class="label-sm">Landed Cost Override (cents)</label>
                        <input type="number" x-model.number="calcLanded" min="0" class="form-input text-sm font-mono w-44"
                            :placeholder="'Saved: ' + (form.landed_cost_cents || 'none')">
                        <p class="text-xs text-gray-400 mt-0.5" x-text="centsToCurrency(calcLanded, currencyCode)"></p>
                    </div>
                    <button type="button" @click="runCalculator()" class="btn btn-secondary btn-sm mb-0.5">
                        Calculate →
                    </button>
                </div>

                {{-- Result --}}
                <div x-show="calcResult" x-cloak>
                    <div :class="calcResult?.below_cost ? 'bg-red-50 border-red-200' : 'bg-green-50 border-green-200'"
                        class="border rounded-xl p-4 grid grid-cols-4 gap-4 text-center text-sm">
                        <div>
                            <p class="text-xs text-gray-500 mb-0.5">Selling Price</p>
                            <p class="font-bold text-gray-900" x-text="calcResult?.selling_formatted"></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-0.5">Landed Cost</p>
                            <p class="font-bold text-gray-900" x-text="calcResult?.landed_formatted"></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-0.5">Gross Profit</p>
                            <p class="font-bold" :class="calcResult?.profit_cents >= 0 ? 'text-green-700' : 'text-red-700'"
                                x-text="calcResult?.profit_formatted"></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-0.5">Margin %</p>
                            <p class="text-xl font-extrabold"
                                :class="calcResult?.below_cost ? 'text-red-700' : (calcResult?.margin_pct >= 20 ? 'text-green-700' : 'text-yellow-600')"
                                x-text="(calcResult?.margin_pct ?? 0).toFixed(1) + '%'"></p>
                        </div>
                    </div>
                    <p x-show="calcResult?.below_cost"
                        class="text-xs font-semibold text-red-700 mt-2 flex items-center gap-1">
                        ⚠️ Selling at or below landed cost — platform is at a loss at this price.
                    </p>
                </div>
            </div>

            {{-- ─── Section 4: Competitor Tracking ─────────────────────────── --}}
            <div class="px-5 py-4 space-y-3">
                <div class="flex items-center justify-between">
                    <h4 class="text-sm font-semibold text-gray-700 flex items-center gap-1.5">
                        <x-heroicon name="magnifying-glass-circle" class="w-4 h-4 text-gray-400" />
                        Competitor Price Tracking
                    </h4>
                    @if($canEdit)
                        <div class="flex gap-2">
                            <button type="button" @click="addCompetitor()" class="btn btn-ghost btn-xs">+ Add</button>
                            <button type="button" @click="checkCompetitors()" :disabled="checkingPrices"
                                class="btn btn-secondary btn-xs flex items-center gap-1">
                                <span x-show="checkingPrices"
                                    class="w-3 h-3 border-2 border-gray-400 border-t-transparent rounded-full animate-spin"></span>
                                🔍 Check Prices
                            </button>
                        </div>
                    @endif
                </div>

                {{-- Competitor rows --}}
                <div x-show="form.competitor_links.length === 0" class="text-sm text-gray-400 italic py-2">
                    No competitor links configured.
                </div>

                <div class="space-y-2">
                    <template x-for="(comp, idx) in form.competitor_links" :key="idx">
                        <div class="flex items-center gap-2 bg-gray-50 rounded-lg p-2.5">
                            @if($canEdit)
                                <input type="text" x-model="comp.name" class="form-input text-xs w-28 flex-shrink-0"
                                    placeholder="Name">
                                <input type="url" x-model="comp.url" class="form-input text-xs flex-1 font-mono min-w-0"
                                    placeholder="https://competitor.com/product">
                                <input type="number" x-model.number="comp.price_cents"
                                    class="form-input text-xs w-28 font-mono flex-shrink-0" placeholder="Price (cents)">
                            @else
                                <span class="text-xs font-medium text-gray-700 w-28 flex-shrink-0 truncate"
                                    x-text="comp.name || '—'"></span>
                                <a :href="comp.url" target="_blank"
                                    class="text-xs text-blue-600 hover:underline flex-1 truncate font-mono"
                                    x-text="comp.url"></a>
                                <span class="text-xs font-semibold text-gray-800 w-28 flex-shrink-0 text-right"
                                    x-text="comp.price_cents ? centsToCurrency(comp.price_cents, currencyCode) : '—'"></span>
                            @endif
                            <div class="text-[10px] text-gray-400 flex-shrink-0 w-28 text-right leading-tight">
                                <span x-show="comp.last_checked">
                                    Checked:<br>
                                    <span x-text="formatDate(comp.last_checked)"></span>
                                </span>
                                <span x-show="!comp.last_checked">Not checked</span>
                            </div>
                            @if($canEdit)
                                <button type="button" @click="removeCompetitor(idx)"
                                    class="text-red-400 hover:text-red-600 p-0.5 flex-shrink-0 ml-1">
                                    <x-heroicon name="x-mark" class="w-3.5 h-3.5" />
                                </button>
                            @endif
                        </div>
                    </template>
                </div>

                <p x-show="ref?.competitor_last_checked" x-cloak class="text-xs text-gray-400">
                    Last batch check: <span x-text="formatDate(ref?.competitor_last_checked)"></span>
                </p>
            </div>

            {{-- ─── Section 5: Notes ────────────────────────────────────────── --}}
            <div class="px-5 py-4 space-y-2">
                <h4 class="text-sm font-semibold text-gray-700 flex items-center gap-1.5">
                    <x-heroicon name="clipboard-document-list" class="w-4 h-4 text-gray-400" />
                    Internal Notes
                </h4>
                @if($canEdit)
                    <textarea x-model="form.notes" rows="3" class="form-input w-full text-sm"
                        placeholder="Admin-only notes about sourcing, supplier negotiations, etc."></textarea>
                @else
                    <p class="text-sm text-gray-600 bg-gray-50 rounded-lg p-3 whitespace-pre-wrap"
                        x-text="ref?.notes || 'No notes.'"></p>
                @endif
            </div>

            {{-- ─── Footer: Save + Audit Trail ─────────────────────────────── --}}
            <div class="px-5 py-3 bg-gray-50 rounded-b-xl flex items-center justify-between gap-3">
                <p class="text-xs text-gray-400 leading-snug">
                    <span x-show="ref?.created_by">Created by <span class="font-medium"
                            x-text="ref?.created_by"></span></span>
                    <span x-show="ref?.updated_by"> · Last edited by <span class="font-medium"
                            x-text="ref?.updated_by"></span>
                        (<span x-text="formatDate(ref?.updated_at)"></span>)
                    </span>
                    <span x-show="!ref">Not yet saved.</span>
                </p>
                @if($canEdit)
                    <button type="button" @click="saveRef()" :disabled="saving"
                        class="btn btn-primary btn-sm flex items-center gap-2 min-w-28 justify-center">
                        <span x-show="saving"
                            class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                        <span x-show="!saving">Save Cost Data</span>
                        <span x-show="saving">Saving…</span>
                    </button>
                @endif
            </div>

        </div>{{-- /!loading --}}
    </div>{{-- /x-data costReferencePanel --}}

    <script>
        function costReferencePanel(productId, currencyCode, showUrl, saveUrl, calcUrl, checkUrl) {
            return {
                productId, currencyCode, showUrl, saveUrl, calcUrl, checkUrl,
                loading: true,
                saving: false,
                checkingPrices: false,
                ref: null,
                belowCostWarning: false,
                lowestPriceFormatted: '',
                form: {
                    manufacturer_name: '',
                    manufacturer_url: '',
                    manufacturer_sku: '',
                    manufacturer_cost_cents: null,
                    shipping_cost_cents: null,
                    landed_cost_cents: null,
                    competitor_links: [],
                    notes: '',
                },
                calcPrice: null,
                calcLanded: null,
                calcResult: null,

                async loadRef() {
                    this.loading = true;
                    try {
                        const res = await fetch(this.showUrl, { headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });
                        const data = await res.json();
                        this.belowCostWarning = data.below_cost_warning;
                        this.lowestPriceFormatted = data.lowest_price_cents
                            ? this.centsToCurrency(data.lowest_price_cents, this.currencyCode) : '';
                        if (data.ref) {
                            this.ref = data.ref;
                            this.form = {
                                manufacturer_name: data.ref.manufacturer_name ?? '',
                                manufacturer_url: data.ref.manufacturer_url ?? '',
                                manufacturer_sku: data.ref.manufacturer_sku ?? '',
                                manufacturer_cost_cents: data.ref.manufacturer_cost_cents ?? null,
                                shipping_cost_cents: data.ref.shipping_cost_cents ?? null,
                                landed_cost_cents: data.ref.landed_cost_cents ?? null,
                                competitor_links: data.ref.competitor_links ?? [],
                                notes: data.ref.notes ?? '',
                            };
                        }
                    } catch (e) {
                        console.error('Cost ref load error', e);
                    } finally {
                        this.loading = false;
                    }
                },

                syncLandedCost() {
                    const mfr = parseInt(this.form.manufacturer_cost_cents) || 0;
                    const ship = parseInt(this.form.shipping_cost_cents) || 0;
                    if (mfr + ship > 0) {
                        this.form.landed_cost_cents = mfr + ship;
                    }
                },

                async saveRef() {
                    this.saving = true;
                    try {
                        const res = await fetch(this.saveUrl, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
                            body: JSON.stringify(this.form),
                        });
                        const data = await res.json();
                        if (data.success) { window.Toast.success(data.message); this.ref = data.ref; }
                        else { window.Toast.error(data.message ?? 'Save failed.'); }
                    } catch (e) {
                        window.Toast.error('Network error.');
                    } finally {
                        this.saving = false;
                    }
                },

                async runCalculator() {
                    const landed = this.calcLanded || this.form.landed_cost_cents;
                    if (!this.calcPrice) { window.Toast.error('Enter a selling price.'); return; }
                    if (!landed) { window.Toast.error('Enter or save a landed cost first.'); return; }
                    try {
                        const res = await fetch(this.calcUrl, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                selling_price_cents: this.calcPrice,
                                landed_cost_cents: landed,
                            }),
                        });
                        this.calcResult = await res.json();
                    } catch (e) {
                        window.Toast.error('Calculation error.');
                    }
                },

                async checkCompetitors() {
                    this.checkingPrices = true;
                    try {
                        // Save current links first so the server has them
                        await this.saveRef();
                        const res = await fetch(this.checkUrl, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
                            body: '{}',
                        });
                        const data = await res.json();
                        if (data.success) {
                            this.form.competitor_links = data.links;
                            if (this.ref) this.ref.competitor_links = data.links;
                            window.Toast.success(data.message);
                        } else {
                            window.Toast.error(data.message ?? 'Check failed.');
                        }
                    } catch (e) {
                        window.Toast.error('Network error checking competitors.');
                    } finally {
                        this.checkingPrices = false;
                    }
                },

                addCompetitor() {
                    this.form.competitor_links.push({ name: '', url: '', price_cents: null, last_checked: null });
                },

                removeCompetitor(idx) {
                    this.form.competitor_links.splice(idx, 1);
                },

                centsToCurrency(cents, currencyCode) {
                    if (cents === null || cents === undefined || cents === '' || isNaN(cents)) return '';
                    return (parseInt(cents) / 100).toLocaleString('en-EG', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ' + (currencyCode || '');
                },

                formatDate(iso) {
                    if (!iso) return '';
                    try { return new Date(iso).toLocaleDateString('en-EG', { day: 'numeric', month: 'short', year: 'numeric' }); }
                    catch { return iso; }
                },
            };
        }
    </script>
@endif
