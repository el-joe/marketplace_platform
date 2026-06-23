@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/column-renderers.js'])
@endpush

@section('title', 'Customers')

@section('content')

    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Customers</h1>
            <p class="text-sm text-gray-500 mt-0.5">Manage all customer accounts on the platform.</p>
        </div>
        <div class="flex items-center gap-3">
            <button type="button" id="export-all-btn" class="btn btn-secondary btn-sm">Export CSV</button>
        </div>
    </div>

    {{-- ─── Stats row ───────────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
        <x-stat-card title="Total Customers" :value="number_format($stats['total'])" icon="users" />
        <x-stat-card title="Active" :value="number_format($stats['active'])" icon="check-circle"
            iconBg="bg-success-100 text-success-600" />
        <x-stat-card title="Suspended" :value="number_format($stats['suspended'])" icon="pause-circle"
            iconBg="bg-warning-100 text-warning-600" />
        <x-stat-card title="Banned" :value="number_format($stats['banned'])" icon="x-circle"
            iconBg="bg-danger-100 text-danger-600" />
        <x-stat-card title="New This Week" :value="number_format($stats['new_this_week'])" icon="user-plus"
            iconBg="bg-primary-100 text-primary-600" />
    </div>

    {{-- ─── Filters ─────────────────────────────────────────────────────────────── --}}
    <x-card class="mb-5">
        <form id="filter-form" class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[180px]">
                <label class="block text-xs font-medium text-gray-600 mb-1">Search</label>
                <input type="text" id="search-input" class="form-input w-full text-sm" placeholder="Name, email, phone…">
            </div>
            <div class="w-40">
                <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                <select id="filter-status" class="form-input w-full text-sm">
                    <option value="">All statuses</option>
                    <option value="active">Active</option>
                    <option value="suspended">Suspended</option>
                    <option value="banned">Banned</option>
                </select>
            </div>
            <div class="w-44">
                <label class="block text-xs font-medium text-gray-600 mb-1">Country</label>
                <select id="filter-country" class="form-input w-full text-sm">
                    <option value="">All countries</option>
                    @foreach($countries as $country)
                        <option value="{{ $country->id }}">
                            {{ $country->flag_emoji ? $country->flag_emoji . ' ' : '' }}{{ $country->name_en }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="w-36">
                <label class="block text-xs font-medium text-gray-600 mb-1">From</label>
                <input type="date" id="filter-date-from" class="form-input w-full text-sm">
            </div>
            <div class="w-36">
                <label class="block text-xs font-medium text-gray-600 mb-1">To</label>
                <input type="date" id="filter-date-to" class="form-input w-full text-sm">
            </div>
            <div class="w-28">
                <label class="block text-xs font-medium text-gray-600 mb-1">Min Orders</label>
                <input type="number" id="filter-min-orders" min="0" class="form-input w-full text-sm" placeholder="0">
            </div>
            <div class="flex items-end gap-2">
                <label class="flex items-center gap-1.5 text-sm text-gray-700 pb-2 cursor-pointer">
                    <input type="checkbox" id="filter-verified" class="rounded border-gray-300">
                    Email verified only
                </label>
            </div>
            <button type="button" id="clear-filters" class="btn btn-ghost btn-sm self-end">Reset</button>
        </form>
    </x-card>

    {{-- ─── Bulk action bar ─────────────────────────────────────────────────────── --}}
    <div id="bulk-bar"
        class="hidden mb-4 flex items-center gap-3 rounded-lg bg-primary-50 border border-primary-200 px-4 py-2">
        <span class="text-sm font-medium text-primary-800"><span id="selected-count">0</span> selected</span>
        <div class="flex items-center gap-2 ml-2">
            <button type="button" data-bulk="suspend" class="btn btn-warning btn-xs">Suspend Selected</button>
            <button type="button" data-bulk="export" class="btn btn-ghost btn-xs">Export Selected</button>
        </div>
        <button type="button" id="clear-selection" class="btn btn-ghost btn-xs ml-auto text-gray-500">✕ Clear</button>
    </div>

    {{-- ─── DataTable ───────────────────────────────────────────────────────────── --}}
    <x-card>
        <div class="overflow-x-auto">
            <table id="customers-table" class="w-full text-sm" style="width:100%">
                <thead>
                    <tr class="border-b border-gray-100 text-left">
                        <th class="py-2 pr-3 w-8">
                            <input type="checkbox" id="select-all" class="rounded border-gray-300">
                        </th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Email</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Phone</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Country</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Orders</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Spent</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Points</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Joined</th>
                        <th class="py-2 text-xs font-medium text-gray-500 uppercase text-right">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </x-card>

    {{-- ─── Suspend reason modal ────────────────────────────────────────────────── --}}
    <x-modal id="suspend-modal" title="Suspend Customer" size="sm">
        <p class="text-sm text-gray-600 mb-3">You are about to suspend <strong id="suspend-customer-name"></strong>.</p>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Reason <span
                    class="text-danger-500">*</span></label>
            <textarea id="suspend-reason" class="form-input w-full resize-none" rows="3"
                placeholder="Enter reason…"></textarea>
        </div>
        <x-slot name="footer">
            <button type="button" data-modal-close class="btn btn-ghost btn-sm">Cancel</button>
            <button type="button" id="suspend-confirm-btn" class="btn btn-warning btn-sm">Suspend</button>
        </x-slot>
    </x-modal>

    {{-- ─── Ban modal (requires typed confirmation) ────────────────────────────── --}}
    <x-modal id="ban-modal" title="Ban Customer" size="sm" :persistent="true">
        <p class="text-sm text-gray-600 mb-3">You are about to <strong class="text-danger-700">permanently ban</strong>
            <strong id="ban-customer-name"></strong>. This will revoke all active sessions.</p>
        <div class="mb-3">
            <label class="block text-sm font-medium text-gray-700 mb-1">Reason <span
                    class="text-danger-500">*</span></label>
            <textarea id="ban-reason" class="form-input w-full resize-none" rows="3" placeholder="Enter reason…"></textarea>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Type <code
                    class="bg-gray-100 px-1 rounded">CONFIRM</code> to proceed</label>
            <input type="text" id="ban-confirm-input" class="form-input w-full" placeholder="CONFIRM" autocomplete="off">
        </div>
        <x-slot name="footer">
            <button type="button" data-modal-close class="btn btn-ghost btn-sm">Cancel</button>
            <button type="button" id="ban-confirm-btn" class="btn btn-danger btn-sm" disabled>Ban Customer</button>
        </x-slot>
    </x-modal>

@endsection

@push('scripts')
    @vite(['resources/js/admin/customers.js'])
    <script>
        window.customersTableUrl = '{{ route('admin.customers.datatable') }}';
    </script>
@endpush