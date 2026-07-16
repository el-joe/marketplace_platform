@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js'])
@endpush

@section('title', 'Exceptional Zones — ' . $vendor->store_name)

@section('content')

    {{-- ─── Header ─────────────────────────────────────────────────────────── --}}
    <div class="mb-4">
        <a href="{{ route('admin.vendors.show', $vendor) }}" class="text-sm text-gray-500 hover:text-gray-700">
            ← Back to {{ $vendor->store_name }}
        </a>
    </div>

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Exceptional Zones</h1>
        <p class="text-sm text-gray-500 mt-0.5">{{ $vendor->store_name }}</p>
    </div>

    {{-- ─── Info banner ────────────────────────────────────────────────────── --}}
    <div class="mb-4 rounded-lg border border-primary-200 bg-primary-50 px-4 py-3 text-sm text-primary-800">
        These zones were selected by the vendor. To change the cost split, update the
        <a href="{{ route('admin.vendors.subsidy-settings.index', $vendor) }}" class="font-medium underline">
            Subsidy Settings
        </a> above.
    </div>

    {{-- ─── DataTable (read-only) ──────────────────────────────────────────── --}}
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
    @vite(['resources/js/components/datatable.js', 'resources/js/admin/vendor-exceptional-zones.js'])
    <script type="module">
        window.VENDOR_EXCEPTIONAL_ZONES_ROUTES = {
            index: @json(route('admin.vendors.exceptional-zones.index', $vendor)),
        };
    </script>
@endpush
