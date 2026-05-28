@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/column-renderers.js', 'resources/js/admin/vendor-applications.js'])
@endpush

@section('title', 'Vendor Applications Queue')

@section('content')

    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Vendor Applications</h1>
            <p class="text-sm text-gray-500 mt-0.5">Review completed onboarding applications awaiting approval.</p>
        </div>
        <button type="button" id="assign-me-btn" class="btn btn-secondary btn-sm"
            title="Assign oldest unassigned application to yourself">
            Auto-assign to me
        </button>
    </div>

    {{-- ─── Stats ──────────────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <x-stat-card title="Pending" :value="number_format($stats['pending'])" iconBg="bg-warning-100 text-warning-600" />
        <x-stat-card title="Under Review" :value="number_format($stats['under_review'])"
            iconBg="bg-primary-100 text-primary-600" />
        <x-stat-card title="Waiting &gt; 5 days" :value="number_format($stats['waiting_5plus'])"
            iconBg="{{ $stats['waiting_5plus'] > 0 ? 'bg-red-100 text-red-600' : 'bg-gray-100 text-gray-400' }}" />
    </div>

    {{-- ─── Filter bar ──────────────────────────────────────────────────────────── --}}
    <x-card class="mb-5">
        <div class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[160px]">
                <label class="block text-xs font-medium text-gray-600 mb-1">Search</label>
                <input type="text" id="search-input" class="form-input w-full text-sm"
                    placeholder="Store name, business name…">
            </div>
            <div class="w-44">
                <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                <select id="filter-status" class="form-input w-full text-sm">
                    <option value="">All</option>
                    <option value="pending">Pending</option>
                    <option value="under_review">Under Review</option>
                </select>
            </div>
            <div class="w-44">
                <label class="block text-xs font-medium text-gray-600 mb-1">Country</label>
                <select id="filter-country" class="form-input w-full text-sm">
                    <option value="">All countries</option>
                    @foreach($countries as $c)
                        <option value="{{ $c->id }}">{{ $c->flag_emoji ? $c->flag_emoji . ' ' : '' }}{{ $c->name_en }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-36">
                <label class="block text-xs font-medium text-gray-600 mb-1">Waiting ≥ (days)</label>
                <input type="number" id="filter-days-min" class="form-input w-full text-sm" placeholder="e.g. 3" min="0">
            </div>
            <button type="button" id="clear-filters" class="btn btn-ghost btn-sm self-end">Reset</button>
        </div>
    </x-card>

    {{-- ─── Urgency legend ──────────────────────────────────────────────────────── --}}
    <div class="flex items-center gap-4 mb-4 text-xs text-gray-500">
        <span>Waiting time:</span>
        <span class="flex items-center gap-1"><span class="inline-block w-2 h-2 rounded-full bg-green-500"></span> &lt; 2
            days</span>
        <span class="flex items-center gap-1"><span class="inline-block w-2 h-2 rounded-full bg-yellow-500"></span> 2–5
            days</span>
        <span class="flex items-center gap-1"><span class="inline-block w-2 h-2 rounded-full bg-red-500"></span> &gt; 5
            days</span>
    </div>

    {{-- ─── DataTable ───────────────────────────────────────────────────────────── --}}
    <x-card>
        <div class="overflow-x-auto">
            <table id="applications-table" class="w-full text-sm" style="width:100%">
                <thead>
                    <tr class="border-b border-gray-100 text-left">
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Store</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Business</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Country</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Type</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Docs</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Bank</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Waiting</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Submitted</th>
                        <th class="py-2 text-xs font-medium text-gray-500 uppercase text-right">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </x-card>

@endsection