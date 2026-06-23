@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/column-renderers.js', 'resources/js/admin/ad-campaigns.js'])
@endpush

@section('title', 'Paid Ad Bookings')

@section('content')

    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('admin.ad-campaigns.index') }}" class="hover:text-primary-600">Ad Campaigns</a>
                <span>/</span>
                <span class="text-gray-800 font-medium">Paid Ad Bookings</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">Paid Ad Bookings</h1>
            <p class="text-sm text-gray-500 mt-0.5">Review, approve, and manage vendor paid ad slot bookings.</p>
        </div>
        <div>
            <a href="{{ route('admin.ad-slots.index') }}" class="btn btn-secondary btn-sm">Manage Slots →</a>
        </div>
    </div>

    {{-- ─── Stats ────────────────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mb-6">
        <x-stat-card title="Pending Approval" :value="number_format($stats['pending'])"
            iconBg="bg-warning-100 text-warning-600" />
        <x-stat-card title="Active Bookings" :value="number_format($stats['active'])"
            iconBg="bg-success-100 text-success-600" />
        <x-stat-card title="Rejected" :value="number_format($stats['rejected'])" iconBg="bg-red-100 text-red-600" />
    </div>

    {{-- ─── Filter bar ──────────────────────────────────────────────────────────── --}}
    <x-card class="mb-5">
        <form id="bookings-filter-form" class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[160px]">
                <label class="block text-xs font-medium text-gray-600 mb-1">Search</label>
                <input type="text" id="bookings-search" class="form-input w-full text-sm" placeholder="Reference, vendor…">
            </div>
            <div class="w-40">
                <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                <select id="bookings-filter-status" class="form-input w-full text-sm">
                    <option value="">All statuses</option>
                    <option value="pending">Pending</option>
                    <option value="active">Active</option>
                    <option value="rejected">Rejected</option>
                    <option value="cancelled">Cancelled</option>
                    <option value="ended">Ended</option>
                </select>
            </div>
            <div class="w-40">
                <label class="block text-xs font-medium text-gray-600 mb-1">Payment</label>
                <select id="bookings-filter-payment" class="form-input w-full text-sm">
                    <option value="">All payments</option>
                    <option value="unpaid">Unpaid</option>
                    <option value="paid">Paid</option>
                    <option value="invoiced">Invoiced</option>
                    <option value="refunded">Refunded</option>
                </select>
            </div>
            <div class="w-36">
                <label class="block text-xs font-medium text-gray-600 mb-1">Booked from</label>
                <input type="date" id="bookings-date-from" class="form-input w-full text-sm">
            </div>
            <div class="w-36">
                <label class="block text-xs font-medium text-gray-600 mb-1">Ends by</label>
                <input type="date" id="bookings-date-to" class="form-input w-full text-sm">
            </div>
            <button type="button" id="clear-bookings-filters" class="btn btn-ghost btn-sm self-end">Reset</button>
        </form>
    </x-card>

    {{-- ─── DataTable ───────────────────────────────────────────────────────────── --}}
    <x-card>
        <div class="overflow-x-auto">
            <table id="bookings-table" class="w-full text-sm" style="width:100%">
                <thead>
                    <tr class="border-b border-gray-100 text-left">
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Reference</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Vendor</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Slot</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Dates</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Rate</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Payment</th>
                        <th class="py-2 text-xs font-medium text-gray-500 uppercase text-right">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </x-card>

    {{-- ─── Approve Confirm Modal ───────────────────────────────────────────────── --}}
    <x-modal id="approve-booking-modal" title="Approve Booking" size="sm">
        <p class="text-sm text-gray-600">
            Approve booking <strong id="approve-booking-ref" class="font-mono text-gray-800"></strong>?
            The booking will become active.
        </p>
        <div class="flex justify-end gap-3 mt-5">
            <button type="button" class="btn btn-secondary"
                onclick="$('#approve-booking-modal').modal('close')">Cancel</button>
            <button type="button" id="confirm-approve-booking-btn" class="btn btn-success">Approve</button>
        </div>
    </x-modal>

    {{-- ─── Reject Modal ────────────────────────────────────────────────────────── --}}
    <x-modal id="reject-booking-modal" title="Reject Booking" size="md">
        <p class="text-sm text-gray-600 mb-3">
            Reject booking <strong id="reject-booking-ref" class="font-mono text-gray-800"></strong>.
        </p>
        <label class="block text-sm font-medium text-gray-700 mb-1">Rejection Reason <span
                class="text-red-500">*</span></label>
        <textarea id="reject-booking-reason" rows="3" class="form-input w-full text-sm"
            placeholder="Explain why this booking is being rejected…"></textarea>
        <p class="text-xs text-red-500 hidden mt-1" id="reject-booking-reason-error">Reason is required.</p>
        <div class="flex justify-end gap-3 mt-5">
            <button type="button" class="btn btn-secondary"
                onclick="$('#reject-booking-modal').modal('close')">Cancel</button>
            <button type="button" id="confirm-reject-booking-btn" class="btn btn-danger">Reject</button>
        </div>
    </x-modal>

@endsection