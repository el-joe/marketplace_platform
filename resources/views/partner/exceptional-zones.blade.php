@extends('layouts.partner')

@push('styles')
    @vite(['resources/js/components/datatable.js'])
@endpush

@section('title', 'Exceptional Zones')

@section('content')

    <div class="mb-4">
        <a href="{{ route('partner.subsidy-settings.index') }}" class="text-sm text-gray-500 hover:text-gray-700">
            ← Back to Subsidy Settings
        </a>
    </div>

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Exceptional Zones</h1>
        <p class="text-sm text-gray-500 mt-0.5">Zones you've marked exceptional for shipping cost sharing.</p>
    </div>

    <div class="mb-4 rounded-lg border border-primary-200 bg-primary-50 px-4 py-3 text-sm text-primary-800">
        To select or deselect exceptional zones, use
        <a href="{{ route('partner.shipping.preferences.index') }}" class="font-medium underline">
            Shipping Preferences
        </a>. To change the cost split, update your
        <a href="{{ route('partner.subsidy-settings.index') }}" class="font-medium underline">
            Subsidy Settings
        </a>.
    </div>

    <x-card padding="none">
        <table id="exceptional-zones-table" class="table-base w-full">
            <thead>
                <tr>
                    <th>Zone Name</th>
                    <th>Country</th>
                    <th class="text-center">Is Active</th>
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </x-card>

@endsection

@push('scripts')
    @vite(['resources/js/components/datatable.js', 'resources/js/partner/vendor-exceptional-zones.js'])
    <script type="module">
        window.VENDOR_EXCEPTIONAL_ZONES_ROUTES = {
            index: @json(route('partner.subsidy-settings.exceptional-zones.index')),
        };
    </script>
@endpush
