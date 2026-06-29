@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/column-renderers.js', 'resources/js/admin/travel-packages.js'])
@endpush

@section('title', 'Travel Packages')

@section('content')

    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Travel Packages</h1>
            <p class="text-sm text-gray-500 mt-0.5">Review, approve, and manage travel agency packages.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.travel.agencies.index') }}" class="btn btn-secondary btn-sm">Agencies</a>
            <a href="{{ route('admin.travel.bookings.index') }}" class="btn btn-primary btn-sm">Bookings</a>
        </div>
    </div>

    {{-- ─── Stats ────────────────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <x-stat-card
            title="Pending Review"
            :value="number_format($stats['pending'])"
            icon="clock"
            iconBg="bg-warning-100 text-warning-600" />
        <x-stat-card
            title="Active Packages"
            :value="number_format($stats['active'])"
            icon="check-circle"
            iconBg="bg-success-100 text-success-600" />
        <x-stat-card
            title="Sold Out"
            :value="number_format($stats['sold_out'])"
            icon="ticket"
            iconBg="bg-purple-100 text-purple-600" />
        <x-stat-card
            title="Urgent (depart ≤7d)"
            :value="number_format($stats['urgent'])"
            icon="exclamation-triangle"
            iconBg="{{ $stats['urgent'] > 0 ? 'bg-danger-100 text-danger-600' : 'bg-gray-100 text-gray-400' }}" />
    </div>

    {{-- ─── Filter bar ──────────────────────────────────────────────────────────── --}}
    <x-card class="mb-5">
        <form id="filter-form" class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[160px]">
                <label class="block text-xs font-medium text-gray-600 mb-1">Search</label>
                <input type="text" id="search-input" class="form-input w-full text-sm" placeholder="Package title, Arabic title…">
            </div>
            <div class="w-40">
                <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                <select id="filter-status" class="form-input w-full text-sm">
                    <option value="">All statuses</option>
                    <option value="pending_review">Pending Review</option>
                    <option value="active">Active</option>
                    <option value="sold_out">Sold Out</option>
                    <option value="expired">Expired</option>
                    <option value="draft">Draft</option>
                </select>
            </div>
            <div class="w-44">
                <label class="block text-xs font-medium text-gray-600 mb-1">Destination</label>
                <select id="filter-country" class="form-input w-full text-sm">
                    <option value="">All destinations</option>
                    @foreach($travelCountries as $c)
                        <option value="{{ $c->id }}">{{ $c->flag_emoji ? $c->flag_emoji . ' ' : '' }}{{ $c->name_en }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-36">
                <label class="block text-xs font-medium text-gray-600 mb-1">Departs from</label>
                <input type="date" id="filter-departure-from" class="form-input w-full text-sm">
            </div>
            <div class="w-36">
                <label class="block text-xs font-medium text-gray-600 mb-1">Departs by</label>
                <input type="date" id="filter-departure-to" class="form-input w-full text-sm">
            </div>
            <button type="button" id="clear-filters" class="btn btn-ghost btn-sm self-end">Reset</button>
        </form>
    </x-card>

    {{-- ─── DataTable ───────────────────────────────────────────────────────────── --}}
    <x-card>
        <div class="overflow-x-auto">
            <table id="packages-table" class="w-full text-sm" style="width:100%">
                <thead>
                    <tr class="border-b border-gray-100 text-left">
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Package</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Agency</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Destination</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Price</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Departure</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Seats</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="py-2 text-xs font-medium text-gray-500 uppercase text-right">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </x-card>

    {{-- ─── Approve Confirm Modal ───────────────────────────────────────────────── --}}
    <x-modal id="approve-modal" title="Approve Package" size="sm">
        <p class="text-sm text-gray-600">
            Approve <strong id="approve-package-name"></strong>?
            It will become live and visible to customers.
        </p>
        <div class="flex justify-end gap-3 mt-5">
            <button type="button" class="btn btn-secondary" onclick="$('#approve-modal').modal('close')">Cancel</button>
            <button type="button" id="confirm-approve-btn" class="btn btn-success">Approve & Publish</button>
        </div>
    </x-modal>

    {{-- ─── Reject Modal ────────────────────────────────────────────────────────── --}}
    <x-modal id="reject-modal" title="Return Package to Agency" size="md">
        <p class="text-sm text-gray-600 mb-3">
            Return <strong id="reject-package-name"></strong> to draft. The agency will see the reason and can resubmit.
        </p>
        <label class="block text-sm font-medium text-gray-700 mb-1">Reason <span class="text-red-500">*</span></label>
        <textarea id="reject-reason-input" rows="3" class="form-input w-full text-sm" placeholder="Explain what needs to be corrected before resubmission…"></textarea>
        <p class="text-xs text-red-500 hidden mt-1" id="reject-reason-error">Reason is required.</p>
        <div class="flex justify-end gap-3 mt-5">
            <button type="button" class="btn btn-secondary" onclick="$('#reject-modal').modal('close')">Cancel</button>
            <button type="button" id="confirm-reject-btn" class="btn btn-danger">Return to Draft</button>
        </div>
    </x-modal>

@endsection

@push('scripts')
<script>
window.routes = {
    packagesDatatable: '{{ route('admin.travel.packages.datatable') }}',
};
</script>
@endpush
