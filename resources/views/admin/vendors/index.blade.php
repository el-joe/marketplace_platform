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
            <button type="button" data-bulk="suspend" class="btn btn-warning btn-xs">Bulk Suspend</button>
            <button type="button" data-bulk="reactivate" class="btn btn-success btn-xs">Bulk Reactivate</button>
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

    {{-- ─── Per-row action dropdown (shared, positioned via JS) ────────────────── --}}
    <div id="vendor-row-dropdown"
        class="hidden fixed z-50 w-48 bg-white rounded-lg shadow-lg border border-gray-100 py-1"
        style="top:0;left:0">
        <a id="vrd-view" href="#"
            class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">View</a>
        <!-- <a id="vrd-edit" href="#"
            class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">Edit</a> -->
        <div id="vrd-divider" class="hidden border-t border-gray-100 my-1"></div>
        <button id="vrd-approve" type="button"
            class="hidden w-full text-left px-3 py-2 text-sm text-success-600 hover:bg-gray-50">Approve Vendor</button>
        <button id="vrd-reject" type="button"
            class="hidden w-full text-left px-3 py-2 text-sm text-danger-600 hover:bg-gray-50">Reject Vendor</button>
        <button id="vrd-suspend" type="button"
            class="hidden w-full text-left px-3 py-2 text-sm text-danger-600 hover:bg-gray-50">Suspend Vendor</button>
        <button id="vrd-reactivate" type="button"
            class="hidden w-full text-left px-3 py-2 text-sm text-success-600 hover:bg-gray-50">Reactivate Vendor</button>
    </div>

    {{-- ─── Suspend vendor modal ─────────────────────────────────────────────── --}}
    <x-modal id="suspend-modal" title="Suspend Vendor" size="sm">
        <p class="text-sm text-gray-600 mb-3">Suspend <strong id="suspend-vendor-name"></strong>?</p>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Reason <span class="text-danger-500">*</span>
            </label>
            <textarea id="suspend-reason" class="form-input w-full resize-none" rows="3"
                placeholder="Provide a reason for suspension…"></textarea>
            <p id="suspend-reason-error" class="hidden text-xs text-danger-600 mt-1">
                Reason is required (min 5 characters).
            </p>
        </div>
        <x-slot name="footer">
            <button type="button" data-modal-close class="btn btn-ghost btn-sm">Cancel</button>
            <button type="button" id="suspend-confirm-btn" class="btn btn-danger btn-sm">Suspend Vendor</button>
        </x-slot>
    </x-modal>

    {{-- ─── Approve vendor modal ─────────────────────────────────────────────── --}}
    <x-modal id="approve-modal" title="Approve Vendor" size="sm">
        <p class="text-sm text-gray-600">
            Approve <strong id="approve-vendor-name"></strong>?
            This will activate their store on the platform.
        </p>
        <x-slot name="footer">
            <button type="button" data-modal-close class="btn btn-ghost btn-sm">Cancel</button>
            <button type="button" id="approve-confirm-btn" class="btn btn-success btn-sm">Approve Vendor</button>
        </x-slot>
    </x-modal>

    {{-- ─── Reject vendor modal ──────────────────────────────────────────────── --}}
    <x-modal id="reject-modal" title="Reject Vendor" size="sm">
        <p class="text-sm text-gray-600 mb-3">Reject <strong id="reject-vendor-name"></strong>?</p>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Reason <span class="text-danger-500">*</span>
            </label>
            <textarea id="reject-reason" class="form-input w-full resize-none" rows="3"
                placeholder="Provide a reason for rejection…"></textarea>
            <p id="reject-reason-error" class="hidden text-xs text-danger-600 mt-1">
                Reason is required (min 5 characters).
            </p>
        </div>
        <x-slot name="footer">
            <button type="button" data-modal-close class="btn btn-ghost btn-sm">Cancel</button>
            <button type="button" id="reject-confirm-btn" class="btn btn-danger btn-sm">Reject Vendor</button>
        </x-slot>
    </x-modal>

    {{-- ─── Reactivate vendor modal ──────────────────────────────────────────── --}}
    <x-modal id="reactivate-modal" title="Reactivate Vendor" size="sm">
        <p class="text-sm text-gray-600">
            Reactivate <strong id="reactivate-vendor-name"></strong>?
            Their store will be restored to active status.
        </p>
        <x-slot name="footer">
            <button type="button" data-modal-close class="btn btn-ghost btn-sm">Cancel</button>
            <button type="button" id="reactivate-confirm-btn" class="btn btn-success btn-sm">Reactivate Vendor</button>
        </x-slot>
    </x-modal>

    {{-- ─── Bulk Suspend modal ───────────────────────────────────────────────── --}}
    <x-modal id="bulk-suspend-modal" title="Bulk Suspend Vendors" size="sm">
        <p class="text-sm text-gray-600 mb-3">
            Suspend <strong><span id="bulk-suspend-count">0</span> selected vendor(s)</strong>?
        </p>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Reason <span class="text-danger-500">*</span>
            </label>
            <textarea id="bulk-suspend-reason" class="form-input w-full resize-none" rows="3"
                placeholder="Reason applied to all selected vendors…"></textarea>
            <p id="bulk-suspend-reason-error" class="hidden text-xs text-danger-600 mt-1">
                Reason is required (min 5 characters).
            </p>
        </div>
        <x-slot name="footer">
            <button type="button" data-modal-close class="btn btn-ghost btn-sm">Cancel</button>
            <button type="button" id="bulk-suspend-confirm-btn" class="btn btn-danger btn-sm">Suspend All</button>
        </x-slot>
    </x-modal>

    {{-- ─── Bulk Reactivate modal ────────────────────────────────────────────── --}}
    <x-modal id="bulk-reactivate-modal" title="Bulk Reactivate Vendors" size="sm">
        <p class="text-sm text-gray-600">
            Reactivate <strong><span id="bulk-reactivate-count">0</span> selected vendor(s)</strong>?
            All selected vendors will be restored to active status.
        </p>
        <x-slot name="footer">
            <button type="button" data-modal-close class="btn btn-ghost btn-sm">Cancel</button>
            <button type="button" id="bulk-reactivate-confirm-btn" class="btn btn-success btn-sm">Reactivate All</button>
        </x-slot>
    </x-modal>

    {{-- ─── Generic bulk modal (place_hold / assign_manager / export) ──────────── --}}
    <x-modal id="bulk-misc-modal" title="Bulk Action" size="sm">
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
            <button type="button" id="bulk-misc-confirm-btn" class="btn btn-primary btn-sm">Confirm</button>
        </x-slot>
    </x-modal>

@endsection

@push('scripts')
    @vite(['resources/js/admin/vendors.js'])
@endpush
