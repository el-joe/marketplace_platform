@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/column-renderers.js', 'resources/js/admin/disputes.js'])
@endpush

@section('title', 'Disputes')

@section('content')

    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Disputes</h1>
            <p class="text-sm text-gray-500 mt-0.5">Customer–vendor dispute cases and escalations.</p>
        </div>
    </div>

    {{-- ─── Stats ───────────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-5 gap-4 mb-5">
        <x-stat-card title="Open" :value="number_format($stats['open'])"
            iconBg="{{ $stats['open'] > 0 ? 'bg-yellow-100 text-yellow-600' : 'bg-gray-100 text-gray-400' }}" />
        <x-stat-card title="Under Review" :value="number_format($stats['under_review'])"
            iconBg="bg-indigo-100 text-indigo-600" />
        <x-stat-card title="Escalated" :value="number_format($stats['escalated'])"
            iconBg="{{ $stats['escalated'] > 0 ? 'bg-red-100 text-red-600' : 'bg-gray-100 text-gray-400' }}" />
        <x-stat-card title="Unassigned" :value="number_format($stats['unassigned'])"
            iconBg="{{ $stats['unassigned'] > 0 ? 'bg-orange-100 text-orange-600' : 'bg-gray-100 text-gray-400' }}" />
        <x-stat-card title="Resolved (30d)" :value="number_format($stats['resolved_30d'])"
            iconBg="bg-green-100 text-green-600" :subtitle="$stats['avg_resolution_hours'] ? 'Avg: ' . round($stats['avg_resolution_hours'] / 24, 1) . 'd' : null" />
    </div>

    {{-- ─── Status Tabs ─────────────────────────────────────────────────────── --}}
    <div class="flex gap-1 mb-5 border-b border-gray-200">
        @php
            $tabs = [
                '' => 'All',
                'open' => 'Open',
                'seller_responded' => 'Seller Responded',
                'under_review' => 'Under Review',
                'escalated' => 'Escalated',
                'resolved' => 'Resolved',
                'closed' => 'Closed',
                '__unassigned' => 'Unassigned',
            ];
        @endphp
        @foreach($tabs as $tabValue => $tabLabel)
            <button data-status-filter="{{ $tabValue }}"
                class="status-tab px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition-colors
                                    {{ $tabValue === '' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                {{ $tabLabel }}
            </button>
        @endforeach
    </div>

    {{-- ─── Filter bar ──────────────────────────────────────────────────────── --}}
    <x-card class="mb-5">
        <div class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-medium text-gray-600 mb-1">Search dispute # / order # / customer /
                    vendor</label>
                <input type="text" id="search-input" class="form-input w-full text-sm" placeholder="Search…">
            </div>
            <div class="w-48">
                <label class="block text-xs font-medium text-gray-600 mb-1">Reason</label>
                <select id="filter-reason" class="form-input w-full text-sm">
                    <option value="">All reasons</option>
                    <option value="item_not_received">Item not received</option>
                    <option value="item_damaged">Item damaged</option>
                    <option value="item_not_as_described">Not as described</option>
                    <option value="counterfeit">Counterfeit</option>
                    <option value="wrong_item">Wrong item</option>
                    <option value="quality_issue">Quality issue</option>
                    <option value="seller_unresponsive">Seller unresponsive</option>
                    <option value="refund_not_received">Refund not received</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="w-40">
                <label class="block text-xs font-medium text-gray-600 mb-1">Resolution</label>
                <select id="filter-resolution" class="form-input w-full text-sm">
                    <option value="">Any</option>
                    <option value="favor_customer">Favor customer</option>
                    <option value="favor_seller">Favor seller</option>
                    <option value="split">Split</option>
                    <option value="no_action">No action</option>
                </select>
            </div>
            <div class="w-44">
                <label class="block text-xs font-medium text-gray-600 mb-1">Assigned to</label>
                <select id="filter-assigned" class="form-input w-full text-sm">
                    <option value="">Anyone</option>
                    @foreach($admins as $a)
                        <option value="{{ $a->id }}">{{ $a->name }}</option>
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
            <button id="btn-reset-filters" class="btn btn-ghost btn-sm text-gray-500">Reset</button>
        </div>
    </x-card>

    {{-- ─── Table ───────────────────────────────────────────────────────────── --}}
    <x-card>
        <div class="overflow-x-auto">
            <table id="disputes-table" data-url="{{ route('admin.disputes.datatable') }}" class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs font-medium text-gray-500 border-b border-gray-200">
                        <th class="pb-3 pr-4 whitespace-nowrap">Dispute #</th>
                        <th class="pb-3 pr-4 whitespace-nowrap">Order</th>
                        <th class="pb-3 pr-4 whitespace-nowrap">Customer</th>
                        <th class="pb-3 pr-4 whitespace-nowrap">Vendor</th>
                        <th class="pb-3 pr-4 whitespace-nowrap">Reason</th>
                        <th class="pb-3 pr-4 whitespace-nowrap">Status</th>
                        <th class="pb-3 pr-4 whitespace-nowrap">Assigned to</th>
                        <th class="pb-3 pr-4 whitespace-nowrap">Opened</th>
                        <th class="pb-3"></th>
                    </tr>
                </thead>
            </table>
        </div>
    </x-card>

@endsection