@extends('layouts.partner')
@section('title', 'Transfers')
@section('page-title', 'Inventory Transfers')

@push('scripts')
    @vite('resources/js/partner/warehouses.js')
    <script>
        window.TRANSFERS_CFG = {
            datatableUrl: '{{ route('partner.warehouses.transfers.datatable') }}',
            createUrl:    '{{ route('partner.warehouses.transfers.create') }}',
        };
    </script>
@endpush

@section('content')

    <div class="flex items-center justify-between mb-6">
        <div>
            <p class="text-sm text-gray-500">Track stock movements between your warehouses and platform FBN warehouses.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('partner.warehouses.index') }}"
                class="inline-flex items-center gap-1.5 px-3 py-2 border border-gray-200 text-gray-700 text-sm font-medium rounded-xl hover:bg-gray-50 transition-colors">
                ← My Warehouses
            </a>
            <a href="{{ route('partner.warehouses.transfers.create') }}"
                class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-xl hover:bg-primary-700 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                New transfer
            </a>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
        <table id="transfers-table" class="w-full text-sm">
            <thead class="bg-gray-50 border-b text-xs text-gray-500 uppercase">
                <tr>
                    <th class="px-4 py-3 text-left">Transfer #</th>
                    <th class="px-4 py-3 text-left">From</th>
                    <th class="px-4 py-3 text-left">To</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-left">Date</th>
                    <th class="px-4 py-3 text-left"></th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

@endsection
