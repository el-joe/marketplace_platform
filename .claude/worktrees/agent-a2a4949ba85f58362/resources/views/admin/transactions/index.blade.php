@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/column-renderers.js', 'resources/js/admin/transactions.js'])
@endpush

@section('title', 'Payment Transactions')

@section('content')

    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Payment Transactions</h1>
            <p class="text-sm text-gray-500 mt-0.5">View and search all gateway transactions.</p>
        </div>
        <a href="{{ route('admin.transactions.refunds.index') }}" class="btn btn-secondary btn-sm">
            Refund Queue
            @if($stats['pending_refunds'] > 0)
                <span
                    class="ml-1.5 inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-warning-100 text-warning-700">{{ $stats['pending_refunds'] }}</span>
            @endif
        </a>
    </div>

    {{-- ─── Stats ──────────────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-5">
        <x-stat-card title="Volume Today" :value="'$' . number_format($stats['volume_today'] / 100, 2)"
            iconBg="bg-green-100 text-green-600" />
        <x-stat-card title="Succeeded Today" :value="number_format($stats['succeeded_today'])"
            iconBg="bg-success-100 text-success-600" />
        <x-stat-card title="Failed Today" :value="number_format($stats['failed_today'])"
            iconBg="{{ $stats['failed_today'] > 0 ? 'bg-red-100 text-red-600' : 'bg-gray-100 text-gray-400' }}" />
        <x-stat-card title="Pending Refunds" :value="number_format($stats['pending_refunds'])"
            iconBg="{{ $stats['pending_refunds'] > 0 ? 'bg-warning-100 text-warning-600' : 'bg-gray-100 text-gray-400' }}" />
    </div>

    {{-- ─── Pending Refunds Alert ───────────────────────────────────────────────── --}}
    @if($stats['pending_refunds'] > 0)
        <div
            class="mb-5 flex items-center gap-3 rounded-lg border border-warning-200 bg-warning-50 px-4 py-3 text-sm text-warning-800">
            <svg class="w-5 h-5 flex-shrink-0 text-warning-500" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                    d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                    clip-rule="evenodd" />
            </svg>
            <span>
                <strong>{{ number_format($stats['pending_refunds']) }}</strong>
                refund{{ $stats['pending_refunds'] !== 1 ? 's' : '' }} pending approval —
                <a href="{{ route('admin.transactions.refunds.index') }}" class="underline font-medium">review now</a>
            </span>
        </div>
    @endif

    {{-- ─── Filter bar ──────────────────────────────────────────────────────────── --}}
    <x-card class="mb-5">
        <div class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[160px]">
                <label class="block text-xs font-medium text-gray-600 mb-1">Search gateway ID / order</label>
                <input type="text" id="search-input" class="form-input w-full text-sm"
                    placeholder="Gateway TX ID, order number…">
            </div>
            <div class="w-40">
                <label class="block text-xs font-medium text-gray-600 mb-1">Type</label>
                <select id="filter-type" class="form-input w-full text-sm">
                    <option value="">All types</option>
                    <option value="authorization">Authorization</option>
                    <option value="capture">Capture</option>
                    <option value="sale">Sale</option>
                    <option value="refund">Refund</option>
                    <option value="void">Void</option>
                    <option value="chargeback">Chargeback</option>
                </select>
            </div>
            <div class="w-36">
                <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                <select id="filter-status" class="form-input w-full text-sm">
                    <option value="">All statuses</option>
                    <option value="pending">Pending</option>
                    <option value="succeeded">Succeeded</option>
                    <option value="failed">Failed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div class="w-36">
                <label class="block text-xs font-medium text-gray-600 mb-1">Gateway</label>
                <select id="filter-gateway" class="form-input w-full text-sm">
                    <option value="">All gateways</option>
                    @foreach($gateways as $gw)
                        <option value="{{ $gw }}">{{ $gw }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-36">
                <label class="block text-xs font-medium text-gray-600 mb-1">Amount min ($)</label>
                <input type="number" step="0.01" min="0" id="filter-amount-min" class="form-input w-full text-sm"
                    placeholder="0.00">
            </div>
            <div class="w-36">
                <label class="block text-xs font-medium text-gray-600 mb-1">Amount max ($)</label>
                <input type="number" step="0.01" min="0" id="filter-amount-max" class="form-input w-full text-sm"
                    placeholder="9999.00">
            </div>
            <div class="w-36">
                <label class="block text-xs font-medium text-gray-600 mb-1">Date from</label>
                <input type="date" id="filter-date-from" class="form-input w-full text-sm">
            </div>
            <div class="w-36">
                <label class="block text-xs font-medium text-gray-600 mb-1">Date to</label>
                <input type="date" id="filter-date-to" class="form-input w-full text-sm">
            </div>
            <div>
                <button type="button" id="clear-filters" class="btn btn-secondary btn-sm">Clear</button>
            </div>
        </div>
    </x-card>

    {{-- ─── DataTable ──────────────────────────────────────────────────────────── --}}
    <x-card>
        <table id="transactions-table" class="w-full" style="width:100%">
            <thead>
                <tr>
                    <th>Gateway TX ID</th>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Type</th>
                    <th>Gateway</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Failure Code</th>
                    <th>Processed At</th>
                    <th>Created At</th>
                    <th class="no-sort"></th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </x-card>

@endsection