@extends('layouts.admin')

@section('title', 'New Flash Sale')

@push('styles')
    @vite(['resources/js/components/flatpickr.js', 'resources/js/components/select2.js'])
@endpush

@push('scripts')
    @vite('resources/js/admin/flash-sales.js')
@endpush

@section('content')

    <form id="flash-sale-form" class="flex gap-6 items-start" novalidate>
        @csrf

        {{-- ─── Left column ───────────────────────────────────────────────────── --}}
        <div class="flex-1 min-w-0">

            {{-- Tab nav --}}
            <div x-data="{ tab: 'details' }" class="space-y-6">
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex" aria-label="Flash sale form tabs">
                        <button type="button" @click="tab='details'"
                            :class="tab==='details' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                            class="border-b-2 py-3 px-6 text-sm font-medium transition-colors duration-150 focus:outline-none whitespace-nowrap">
                            Details
                        </button>
                        <button type="button" @click="tab='rules'"
                            :class="tab==='rules' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                            class="border-b-2 py-3 px-6 text-sm font-medium transition-colors duration-150 focus:outline-none whitespace-nowrap">
                            Rules &amp; Eligibility
                        </button>
                    </nav>
                </div>

                {{-- ── Tab: details ──────────────────────────────────────────── --}}
                <div x-show="tab==='details'" class="space-y-6">

                    <x-card title="Event Identity">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <x-form-input name="name_en" label="Name (English)" required />
                            <x-form-input name="name_ar" label="الاسم بالعربي" dir="rtl" required />
                            <div class="sm:col-span-2">
                                <x-form-textarea name="description_en" label="Description (English)" rows="3" />
                            </div>
                            <div class="sm:col-span-2">
                                <x-form-textarea name="description_ar" label="الوصف بالعربي" dir="rtl" rows="3" />
                            </div>
                            <x-form-select name="country_id" label="Country" :options="$countries->mapWithKeys(fn($c) => [$c->id => $c->name_en])->toArray()" :nullable="true" placeholder="All countries" />
                            <div class="flex items-end gap-6 pb-1">
                                <x-form-toggle name="is_featured" label="Featured on homepage" />
                                <x-form-toggle name="is_exclusive" label="Exclusive (invite-only)" />
                            </div>
                        </div>
                    </x-card>

                    <x-card title="Timeline" id="timeline-card">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <x-form-date-picker name="submission_opens_at" label="Submissions open" :enableTime="true"
                                required />
                            <x-form-date-picker name="submission_closes_at" label="Submissions close" :enableTime="true"
                                required />
                            <x-form-date-picker name="review_deadline_at" label="Review deadline" :enableTime="true"
                                required />
                            <div></div>
                            <x-form-date-picker name="sale_starts_at" label="Sale starts" :enableTime="true" required />
                            <x-form-date-picker name="sale_ends_at" label="Sale ends" :enableTime="true" required />
                        </div>
                        <div id="timeline-visual" class="mt-4"></div>
                    </x-card>

                </div>

                {{-- ── Tab: rules ────────────────────────────────────────────── --}}
                <div x-show="tab==='rules'" class="space-y-6">

                    <x-card title="Discount Requirements">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <x-form-input name="min_discount_pct" label="Minimum discount %" type="number" step="0.01"
                                min="0" max="100" required suffix="%" hint="Vendors must discount at least this much" />
                            <x-form-input name="max_products_per_seller" label="Max products per vendor" type="number"
                                min="1" hint="Leave empty for unlimited" />
                            <div class="sm:col-span-2">
                                <x-form-toggle name="price_drop_required" label="Price drop required"
                                    hint="Flash price must be below vendor's 30-day average price" :value="true" />
                            </div>
                        </div>
                    </x-card>

                    <x-card title="Vendor Eligibility">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <x-form-checkbox-group name="eligible_seller_tiers" label="Eligible tiers (empty = all)"
                                :options="['bronze' => 'Bronze', 'silver' => 'Silver', 'gold' => 'Gold', 'platinum' => 'Platinum']" />
                            <x-form-input name="min_seller_rating" label="Minimum seller rating" type="number" step="0.1"
                                min="0" max="5" />
                        </div>
                    </x-card>

                    <x-card title="Capacity &amp; Commission">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <x-form-input name="max_total_slots" label="Maximum approved slots" type="number" min="1"
                                hint="Empty = unlimited" />
                            <x-form-input name="commission_override_pct" label="Commission override %" type="number"
                                step="0.01" min="0" max="100" hint="Overrides normal rate during this sale" />
                        </div>
                    </x-card>

                    <x-card title="Eligible Categories">
                        <x-form-checkbox-group name="eligible_categories" label="Limit to specific categories (empty = all)"
                            :options="$categories->mapWithKeys(fn($c) => [$c->id => $c->name_en])->toArray()" />
                    </x-card>

                </div>
            </div>
        </div>

        {{-- ─── Right sidebar ──────────────────────────────────────────────────── --}}
        <div class="w-72 flex-shrink-0 space-y-4 sticky top-20">
            <x-card>
                <div class="space-y-2">
                    <button type="submit" id="flash-sale-submit-btn" class="btn btn-primary w-full justify-center">
                        Create Flash Sale
                    </button>
                    <a href="{{ route('admin.flash-sales.index') }}" class="btn btn-ghost w-full justify-center">
                        Cancel
                    </a>
                </div>
            </x-card>
        </div>

    </form>

    <script>
        window.FLASH_SALE_STORE_URL = '{{ route('admin.flash-sales.store') }}';
    </script>

@endsection