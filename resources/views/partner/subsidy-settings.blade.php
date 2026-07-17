@extends('layouts.partner')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/select2.js', 'resources/js/components/flatpickr.js'])
@endpush

@section('title', 'FBP Subsidy Settings')

@section('content')

    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">FBP Subsidy Settings</h1>
            <p class="text-sm text-gray-500 mt-0.5">Configure how delivery costs are split for your exceptional zones.</p>
        </div>
        <button type="button" id="btn-add-setting"
                class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700">
            <x-heroicon name="plus" class="w-4 h-4" />
            Add Setting
        </button>
    </div>

    <div class="mb-4 rounded-lg border border-primary-200 bg-primary-50 px-4 py-3 text-sm text-primary-800">
        These settings control how delivery costs are split for zones you mark as exceptional.
        <a href="{{ route('partner.subsidy-settings.exceptional-zones.index') }}" class="font-medium underline">
            View your exceptional zones →
        </a>
    </div>

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
    @vite(['resources/js/components/datatable.js', 'resources/js/components/select2.js', 'resources/js/components/flatpickr.js', 'resources/js/partner/vendor-subsidy-settings.js'])
    <script type="module">
        window.VENDOR_SUBSIDY_SETTINGS_ROUTES = {
            index: @json(route('partner.subsidy-settings.index')),
            store: @json(route('partner.subsidy-settings.store')),
            update: @json(route('partner.subsidy-settings.update', ['setting' => '__ID__'])),
            deactivate: @json(route('partner.subsidy-settings.deactivate', ['setting' => '__ID__'])),
        };

        document.addEventListener('DOMContentLoaded', function () {
            const $country = window.jQuery('[name="country_id"]');
            $country.on('change', function () {
                const currency = window.jQuery(this).find(':selected').data('currency') || '';
                window.jQuery('[name="currency"]').val(currency);
            });
        });
    </script>
@endpush
