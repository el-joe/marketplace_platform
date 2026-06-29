@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/column-renderers.js', 'resources/js/admin/travel-bookings.js'])
@endpush

@section('title', 'Travel Bookings')

@section('content')

    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Travel Bookings</h1>
            <p class="text-sm text-gray-500 mt-0.5">Oversight of all customer travel bookings.</p>
        </div>
        <a href="{{ route('admin.travel.packages.index') }}" class="btn btn-secondary btn-sm">← Packages</a>
    </div>

    {{-- ─── Stats ────────────────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <x-stat-card
            title="Total Bookings"
            :value="number_format($stats['total'])"
            icon="ticket"
            iconBg="bg-primary-100 text-primary-600" />
        <x-stat-card
            title="This Month"
            :value="number_format($stats['this_month'])"
            icon="calendar"
            iconBg="bg-info-100 text-info-600" />
        <x-stat-card
            title="Confirmed"
            :value="number_format($stats['confirmed'])"
            icon="check-circle"
            iconBg="bg-success-100 text-success-600" />
        <x-stat-card
            title="Cancelled"
            :value="number_format($stats['cancelled'])"
            icon="x-circle"
            iconBg="bg-danger-100 text-danger-600" />
    </div>

    {{-- ─── Revenue by currency ─────────────────────────────────────────────────── --}}
    @if($revenueByCurrency->count())
    <x-card class="mb-5">
        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Confirmed Revenue by Currency</h3>
        <div class="flex flex-wrap gap-4">
            @foreach($revenueByCurrency as $rev)
            <div class="text-center">
                <p class="text-lg font-bold text-gray-900">{{ $rev['formatted'] }}</p>
            </div>
            @endforeach
        </div>
        <p class="text-xs text-gray-400 mt-2">Revenue is shown per currency. Multi-currency totals are never blended.</p>
    </x-card>
    @endif

    {{-- ─── Filter bar ──────────────────────────────────────────────────────────── --}}
    <x-card class="mb-5">
        <form id="filter-form" class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[160px]">
                <label class="block text-xs font-medium text-gray-600 mb-1">Search</label>
                <input type="text" id="search-input" class="form-input w-full text-sm" placeholder="Booking number, customer name…">
            </div>
            <div class="w-40">
                <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                <select id="filter-status" class="form-input w-full text-sm">
                    <option value="">All statuses</option>
                    <option value="pending_documents">Pending Documents</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="cancelled">Cancelled</option>
                    <option value="completed">Completed</option>
                </select>
            </div>
            <div class="w-36">
                <label class="block text-xs font-medium text-gray-600 mb-1">Booked from</label>
                <input type="date" id="filter-date-from" class="form-input w-full text-sm">
            </div>
            <div class="w-36">
                <label class="block text-xs font-medium text-gray-600 mb-1">Booked by</label>
                <input type="date" id="filter-date-to" class="form-input w-full text-sm">
            </div>
            <button type="button" id="clear-filters" class="btn btn-ghost btn-sm self-end">Reset</button>
        </form>
    </x-card>

    {{-- ─── DataTable ───────────────────────────────────────────────────────────── --}}
    <x-card>
        <div class="overflow-x-auto">
            <table id="bookings-table" class="w-full text-sm" style="width:100%">
                <thead>
                    <tr class="border-b border-gray-100 text-left">
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Reference</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Package</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Customer</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Travel Dates</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Amount</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="py-2 text-xs font-medium text-gray-500 uppercase text-right">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </x-card>

@endsection

@push('scripts')
<script>
window.routes = {
    bookingsDatatable: '{{ route('admin.travel.bookings.datatable') }}',
};
</script>
@endpush
