@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/column-renderers.js', 'resources/js/admin/transactions.js'])
@endpush

@section('title', 'Refund Queue')

@section('content')

    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('admin.transactions.index') }}" class="hover:text-primary-600">Transactions</a>
                <span>/</span>
                <span class="text-gray-800">Refund Queue</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">Refund Queue</h1>
            <p class="text-sm text-gray-500 mt-0.5">Review and approve or reject pending customer refund requests.</p>
        </div>
    </div>

    {{-- ─── Pending Alert ───────────────────────────────────────────────────────── --}}
    @if($pendingCount > 0)
        <div
            class="mb-5 flex items-center gap-3 rounded-lg border border-warning-200 bg-warning-50 px-4 py-3 text-sm text-warning-800">
            <svg class="w-5 h-5 flex-shrink-0 text-warning-500" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                    d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                    clip-rule="evenodd" />
            </svg>
            <span><strong>{{ number_format($pendingCount) }}</strong> refund{{ $pendingCount !== 1 ? 's' : '' }} awaiting
                approval.</span>
        </div>
    @endif

    {{-- ─── Filter bar ──────────────────────────────────────────────────────────── --}}
    <x-card class="mb-5">
        <div class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[160px]">
                <label class="block text-xs font-medium text-gray-600 mb-1">Search order / customer</label>
                <input type="text" id="refund-search-input" class="form-input w-full text-sm"
                    placeholder="Order number, customer name…">
            </div>
            <div class="w-40">
                <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                <select id="refund-filter-status" class="form-input w-full text-sm">
                    <option value="">All statuses</option>
                    <option value="pending" selected>Pending</option>
                    <option value="approved">Approved</option>
                    <option value="processing">Processing</option>
                    <option value="completed">Completed</option>
                    <option value="rejected">Rejected</option>
                    <option value="failed">Failed</option>
                </select>
            </div>
            <div class="w-36">
                <label class="block text-xs font-medium text-gray-600 mb-1">Date from</label>
                <input type="date" id="refund-filter-date-from" class="form-input w-full text-sm">
            </div>
            <div class="w-36">
                <label class="block text-xs font-medium text-gray-600 mb-1">Date to</label>
                <input type="date" id="refund-filter-date-to" class="form-input w-full text-sm">
            </div>
            <div>
                <button type="button" id="refund-clear-filters" class="btn btn-secondary btn-sm">Clear</button>
            </div>
        </div>
    </x-card>

    {{-- ─── DataTable ──────────────────────────────────────────────────────────── --}}
    <x-card>
        <table id="refunds-table" class="w-full" style="width:100%">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Amount</th>
                    <th>Reason</th>
                    <th>Type</th>
                    <th>Vendor Charged</th>
                    <th>Status</th>
                    <th class="no-sort">Actions</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </x-card>

    {{-- ─── Reject Modal ───────────────────────────────────────────────────────── --}}
    <x-modal id="refund-reject-modal" title="Reject Refund" size="sm">
        <div class="space-y-4">
            <p class="text-sm text-gray-600">Provide an optional reason for rejecting this refund.</p>
            <p id="refund-reject-amount" class="text-sm font-semibold text-gray-800"></p>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Reason <span
                        class="text-gray-400 font-normal">(optional)</span></label>
                <textarea id="refund-reject-reason" rows="3" class="form-input w-full text-sm"
                    placeholder="Explain why this refund is being rejected…"></textarea>
            </div>
        </div>
        <x-slot name="footer">
            <button type="button" class="btn btn-secondary"
                onclick="$('#refund-reject-modal').modal('close')">Cancel</button>
            <button type="button" id="confirm-refund-reject-btn" class="btn btn-danger">Confirm Reject</button>
        </x-slot>
    </x-modal>

@endsection