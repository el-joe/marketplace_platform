@extends('layouts.partner')

@section('title', 'Coupons')
@section('page-title', 'Coupons')

@push('scripts')
    @vite('resources/js/vendor/coupons.js')
    <script>
        window.COUPONS = {
            datatableUrl: '{{ route('partner.coupons.datatable') }}',
        };
    </script>
@endpush

@section('content')
    <div class="px-4 py-6 sm:px-6 lg:px-8">

        {{-- Page header --}}
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Coupons</h1>
                <p class="mt-1 text-sm text-gray-500">Create discount codes for your store or specific products.</p>
            </div>
            <a href="{{ route('partner.coupons.create') }}"
               class="inline-flex items-center gap-2 bg-yellow-400 hover:bg-yellow-500 text-gray-900 text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
                + New Coupon
            </a>
        </div>

        {{-- Filters --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-4 mb-4 flex flex-wrap items-center gap-3">
            <input type="text" id="coupon-search" placeholder="Search code or name..."
                   class="flex-1 min-w-[200px] border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400">

            <select id="coupon-type-filter" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="">All types</option>
                <option value="percentage">Percentage</option>
                <option value="fixed_amount">Fixed amount</option>
                <option value="free_shipping">Free shipping</option>
                <option value="bogo">BOGO</option>
            </select>

            <select id="coupon-active-filter" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="">All statuses</option>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
            </select>
        </div>

        {{-- DataTable --}}
        <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
            <table id="coupons-table" class="w-full text-sm" style="width:100%">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Scope</th>
                        <th>Value</th>
                        <th>Used</th>
                        <th>Status</th>
                        <th>Valid Until</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
@endsection
