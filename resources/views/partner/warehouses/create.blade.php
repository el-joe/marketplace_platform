@extends('layouts.partner')
@section('title', 'Register Warehouse')
@section('page-title', 'Register a Warehouse')

@push('scripts')
    @vite('resources/js/partner/warehouse-create.js')
    <script>
        window.WAREHOUSE_CREATE = {
            storeUrl:   '{{ route('partner.warehouses.store') }}',
            indexUrl:   '{{ route('partner.warehouses.index') }}',
            citiesUrl:  '{{ url('/api/cities') }}',
        };
    </script>
@endpush

@section('content')
    <div class="max-w-2xl">

        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6">
            <p class="text-sm text-blue-700">
                Registering your own warehouse lets you fulfill orders directly (FBM/FBP) using your own stock location, instead of relying solely on our platform warehouses.
            </p>
        </div>

        <form id="warehouse-create-form" novalidate>
            <div class="bg-white rounded-2xl border border-gray-200 p-6 space-y-4">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Warehouse name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="wh-name" required maxlength="150"
                        placeholder="e.g. My Main Storage — Riyadh"
                        class="block w-full rounded-xl border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Country <span class="text-red-500">*</span></label>
                    <select name="country_id" id="wh-country" required
                        class="block w-full rounded-xl border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                        <option value="">Select country…</option>
                        @foreach($countries as $country)
                            <option value="{{ $country->id }}">{{ $country->name_en }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
                    <select name="city_id" id="wh-city"
                        class="block w-full rounded-xl border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                        <option value="">Select country first…</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Area / District</label>
                    <input type="text" name="area" id="wh-area" maxlength="255"
                        class="block w-full rounded-xl border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Street address <span class="text-red-500">*</span></label>
                    <input type="text" name="street_address" id="wh-street" required maxlength="255"
                        class="block w-full rounded-xl border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Building</label>
                        <input type="text" name="building" id="wh-building" maxlength="100"
                            class="block w-full rounded-xl border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Storage capacity (m³)</label>
                        <input type="number" name="total_capacity_m3" id="wh-capacity" min="0" step="0.01"
                            placeholder="Optional"
                            class="block w-full rounded-xl border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent" />
                        <p class="text-xs text-gray-400 mt-1">Helps you track how full your warehouse is</p>
                    </div>
                </div>

            </div>

            <div id="wh-error" class="hidden mt-3 p-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700"></div>

            <div class="flex gap-3 mt-5">
                <button type="submit" id="register-warehouse-btn"
                    class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-primary-600 text-white text-sm font-semibold rounded-xl hover:bg-primary-700 disabled:opacity-50 transition-colors">
                    Register warehouse
                </button>
                <a href="{{ route('partner.warehouses.index') }}"
                    class="inline-flex items-center px-4 py-2.5 border border-gray-200 text-gray-700 text-sm font-medium rounded-xl hover:bg-gray-50 transition-colors">
                    Cancel
                </a>
            </div>
        </form>

    </div>
@endsection
