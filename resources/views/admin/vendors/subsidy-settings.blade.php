@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/select2.js', 'resources/js/components/flatpickr.js'])
@endpush

@section('title', 'FBP Subsidy Settings — ' . $vendor->store_name)

@section('content')

    {{-- ─── Header ─────────────────────────────────────────────────────────── --}}
    <div class="mb-4">
        <a href="{{ route('admin.vendors.show', $vendor) }}" class="text-sm text-gray-500 hover:text-gray-700">
            ← Back to {{ $vendor->store_name }}
        </a>
    </div>

    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">FBP Subsidy Settings</h1>
            <p class="text-sm text-gray-500 mt-0.5">{{ $vendor->store_name }}</p>
        </div>
        <button type="button" id="btn-add-setting"
                class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700">
            <x-heroicon name="plus" class="w-4 h-4" />
            Add Setting
        </button>
    </div>

    {{-- ─── Info banner ────────────────────────────────────────────────────── --}}
    <div class="mb-4 rounded-lg border border-primary-200 bg-primary-50 px-4 py-3 text-sm text-primary-800">
        These settings control how delivery costs are split when this vendor marks zones as exceptional.
        The vendor self-selects zones; admin controls the split amounts.
        <a href="{{ route('admin.vendors.exceptional-zones.index', $vendor) }}" class="font-medium underline">
            View this vendor's exceptional zones →
        </a>
    </div>

    {{-- ─── DataTable ──────────────────────────────────────────────────────── --}}
    <x-card padding="none">
        <table id="subsidy-settings-table" class="table-base w-full">
            <thead>
                <tr>
                    <th>Country</th>
                    <th class="text-end">Admin Covers</th>
                    <th class="text-end">Vendor Pays</th>
                    <th>Effective From</th>
                    <th>Effective Until</th>
                    <th class="text-center">Status</th>
                    <th class="text-center w-20">Actions</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </x-card>

    {{-- ════════════════════════════════════════════════════════════════════
         Add / Edit Modal
         ════════════════════════════════════════════════════════════════════ --}}
    <x-modal id="subsidy-setting-modal" title="Subsidy Setting" size="lg">
        <form id="subsidy-setting-form" novalidate>
            @csrf
            <input type="hidden" id="setting-id">
            <input type="hidden" id="setting-http" value="POST">

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <x-form-select name="country_id" label="Country" :select2="true" required>
                        <option value="">Select country…</option>
                        @foreach($countries as $country)
                            <option value="{{ $country->id }}" data-currency="{{ $country->currency_code }}">{{ $country->name_en }}</option>
                        @endforeach
                    </x-form-select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Admin Covers <span class="text-danger-500">*</span></label>
                    <input type="number" id="setting-admin-support-display" step="0.01" min="0" placeholder="0.00"
                           class="block w-full rounded-lg border border-gray-300 py-2 px-3 text-sm text-gray-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-200 focus:border-primary-500" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Vendor Pays <span class="text-danger-500">*</span></label>
                    <input type="number" id="setting-vendor-share-display" step="0.01" min="0" placeholder="0.00"
                           class="block w-full rounded-lg border border-gray-300 py-2 px-3 text-sm text-gray-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-200 focus:border-primary-500" />
                </div>

                <input type="hidden" name="currency" value="">

                <div>
                    <x-form-date-picker name="effective_from" label="Effective From" required />
                </div>
                <div>
                    <x-form-date-picker name="effective_until" label="Effective Until" help-text="Leave empty for no end date." />
                </div>
            </div>

            <x-slot:footer>
                <button type="button" data-modal-close class="btn-secondary">Cancel</button>
                <button type="submit" form="subsidy-setting-form" class="btn-primary">Save</button>
            </x-slot:footer>
        </form>
    </x-modal>

@endsection

@push('scripts')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/select2.js', 'resources/js/components/flatpickr.js', 'resources/js/admin/vendor-subsidy-settings.js'])
    <script type="module">
        window.VENDOR_SUBSIDY_SETTINGS_ROUTES = {
            index: @json(route('admin.vendors.subsidy-settings.index', $vendor)),
            store: @json(route('admin.vendors.subsidy-settings.store', $vendor)),
            update: @json(route('admin.vendors.subsidy-settings.update', ['vendor' => $vendor, 'setting' => '__ID__'])),
            deactivate: @json(route('admin.vendors.subsidy-settings.deactivate', ['vendor' => $vendor, 'setting' => '__ID__'])),
        };

        // Country → currency auto-fill
        document.addEventListener('DOMContentLoaded', function () {
            const $country = window.jQuery('[name="country_id"]');
            $country.on('change', function () {
                const currency = window.jQuery(this).find(':selected').data('currency') || '';
                window.jQuery('[name="currency"]').val(currency);
            });
        });
    </script>
@endpush
