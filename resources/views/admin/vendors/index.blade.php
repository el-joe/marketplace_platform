@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/column-renderers.js'])
@endpush

@section('title', 'Vendors')

@section('content')

    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Vendors</h1>
            <p class="text-sm text-gray-500 mt-0.5">Manage all vendor accounts on the platform.</p>
        </div>
        <div class="flex items-center gap-3">
            @if($pendingCount > 0)
                <a href="{{ route('admin.vendors.applications') }}"
                    class="inline-flex items-center gap-2 rounded-lg bg-warning-100 text-warning-800 px-3 py-1.5 text-sm font-medium hover:bg-warning-200 transition-colors">
                    <span>Application Queue</span>
                    <span
                        class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-warning-600 text-white text-xs font-bold">{{ $pendingCount }}</span>
                </a>
            @endif
            <button type="button" id="export-btn" class="btn btn-secondary btn-sm">Export</button>
        </div>
    </div>

    {{-- ─── Filters ─────────────────────────────────────────────────────────────── --}}
    <x-card class="mb-5">
        <form id="filter-form" class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[180px]">
                <label class="block text-xs font-medium text-gray-600 mb-1">Search</label>
                <input type="text" id="search-input" class="form-input w-full text-sm" placeholder="Store name, email…">
            </div>
            <div class="w-40">
                <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                <select id="filter-status" class="form-input w-full text-sm">
                    <option value="">All statuses</option>
                    <option value="pending">Pending</option>
                    <option value="active">Active</option>
                    <option value="suspended">Suspended</option>
                    <option value="under_review">Under Review</option>
                    <option value="rejected">Rejected</option>
                    <option value="blacklisted">Blacklisted</option>
                </select>
            </div>
            <div class="w-44">
                <label class="block text-xs font-medium text-gray-600 mb-1">Country</label>
                <select id="filter-country" class="form-input w-full text-sm">
                    <option value="">All countries</option>
                    @foreach($countries as $country)
                        <option value="{{ $country->id }}">{{ $country->name_en }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-44">
                <label class="block text-xs font-medium text-gray-600 mb-1">Account Manager</label>
                <select id="filter-manager" class="form-input w-full text-sm">
                    <option value="">All managers</option>
                    @foreach($admins as $admin)
                        <option value="{{ $admin->id }}">{{ $admin->name }}</option>
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
            <button type="button" id="clear-filters" class="btn btn-ghost btn-sm self-end">Reset</button>
        </form>
    </x-card>

    {{-- ─── Bulk action bar (hidden until rows selected) ─────────────────────── --}}
    <div id="bulk-bar"
        class="hidden mb-4 flex items-center gap-3 rounded-lg bg-primary-50 border border-primary-200 px-4 py-2">
        <span class="text-sm font-medium text-primary-800"><span id="selected-count">0</span> selected</span>
        <div class="flex items-center gap-2 ml-2">
            <button type="button" data-bulk="suspend" class="btn btn-warning btn-xs">Suspend</button>
            <button type="button" data-bulk="reactivate" class="btn btn-success btn-xs">Reactivate</button>
            <button type="button" data-bulk="place_hold" class="btn btn-ghost btn-xs">Place Hold</button>
            <button type="button" data-bulk="assign_manager" class="btn btn-ghost btn-xs">Assign Manager</button>
            <button type="button" data-bulk="export" class="btn btn-ghost btn-xs">Export CSV</button>
        </div>
        <button type="button" id="clear-selection" class="btn btn-ghost btn-xs ml-auto text-gray-500">✕ Clear</button>
    </div>

    {{-- ─── DataTable ───────────────────────────────────────────────────────────── --}}
    <x-card>
        <div class="overflow-x-auto">
            <table id="vendors-table" class="w-full text-sm" style="width:100%">
                <thead>
                    <tr class="border-b border-gray-100 text-left">
                        <th class="py-2 pr-3 w-8">
                            <input type="checkbox" id="select-all" class="rounded border-gray-300">
                        </th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Store</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Owner</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Email</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">GMV</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Orders</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Rating</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Manager</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Joined</th>
                        <th class="py-2 text-xs font-medium text-gray-500 uppercase text-right">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </x-card>

    {{-- Bulk reason modal --}}
    <x-modal id="bulk-reason-modal" title="Bulk Action" size="sm">
        <div class="space-y-3">
            <div id="bulk-admin-select" class="hidden">
                <label class="block text-sm font-medium text-gray-700 mb-1">Assign to Admin</label>
                <select name="admin_id" id="bulk-admin-id" class="form-input w-full">
                    @foreach($admins as $admin)
                        <option value="{{ $admin->id }}">{{ $admin->name }}</option>
                    @endforeach
                </select>
            </div>
            <div id="bulk-reason-field">
                <label class="block text-sm font-medium text-gray-700 mb-1">Reason</label>
                <textarea id="bulk-reason" class="form-input w-full resize-none" rows="3"></textarea>
            </div>
        </div>
        <x-slot name="footer">
            <button type="button" data-modal-close class="btn btn-ghost btn-sm">Cancel</button>
            <button type="button" id="bulk-confirm-btn" class="btn btn-primary btn-sm">Confirm</button>
        </x-slot>
    </x-modal>

@endsection

@push('scripts')
    @vite(['resources/js/admin/vendors.js'])
@endpush