@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/column-renderers.js', 'resources/js/admin/ad-campaigns.js'])
@endpush

@section('title', 'Fraud Alerts')

@section('content')

    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('admin.ad-campaigns.index') }}" class="hover:text-primary-600">Ad Campaigns</a>
                <span>/</span>
                <span class="text-gray-800 font-medium">Fraud Alerts</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">Ad Fraud Alerts</h1>
            <p class="text-sm text-gray-500 mt-0.5">Review and block suspicious click patterns across all campaigns.</p>
        </div>
    </div>

    {{-- ─── Stats ────────────────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mb-6">
        <x-stat-card title="Suspicious IPs" :value="number_format($stats['unblocked'])"
            iconBg="bg-warning-100 text-warning-600" />
        <x-stat-card title="Blocked IPs" :value="number_format($stats['blocked'])" iconBg="bg-red-100 text-red-600" />
        <x-stat-card title="Total Patterns" :value="number_format($stats['total'])" iconBg="bg-gray-100 text-gray-600" />
    </div>

    {{-- ─── Filter bar ──────────────────────────────────────────────────────────── --}}
    <x-card class="mb-5">
        <div class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[160px]">
                <label class="block text-xs font-medium text-gray-600 mb-1">Search IP / Campaign</label>
                <input type="text" id="fraud-search" class="form-input w-full text-sm" placeholder="IP address, campaign…">
            </div>
            <div class="w-40">
                <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                <select id="fraud-filter-status" class="form-input w-full text-sm">
                    <option value="">All</option>
                    <option value="0">Suspicious only</option>
                    <option value="1">Blocked only</option>
                </select>
            </div>
        </div>
    </x-card>

    {{-- ─── DataTable ───────────────────────────────────────────────────────────── --}}
    <x-card>
        <div class="overflow-x-auto">
            <table id="fraud-table" class="w-full text-sm" style="width:100%">
                <thead>
                    <tr class="border-b border-gray-100 text-left">
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">IP Address</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Campaign</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Vendor</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Clicks/Hr</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Clicks/24h</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Detected</th>
                        <th class="py-2 text-xs font-medium text-gray-500 uppercase">Action</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </x-card>

    {{-- ─── Block IP Modal ──────────────────────────────────────────────────────── --}}
    <x-modal id="block-ip-modal" title="Block IP Address" size="md">
        <p class="text-sm text-gray-600 mb-3">
            Block IP <strong id="block-ip-address" class="font-mono text-gray-800"></strong>?
            This will prevent all ad clicks from this IP.
        </p>
        <label class="block text-sm font-medium text-gray-700 mb-1">Block Reason</label>
        <input type="text" id="block-reason-input" class="form-input w-full text-sm"
            placeholder="e.g. Suspicious click pattern, exceeded threshold">
        <div class="flex justify-end gap-3 mt-5">
            <button type="button" class="btn btn-secondary" onclick="$('#block-ip-modal').modal('close')">Cancel</button>
            <button type="button" id="confirm-block-btn" class="btn btn-danger">Block IP</button>
        </div>
    </x-modal>

@endsection