@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/column-renderers.js', 'resources/js/admin/support-tickets.js'])
@endpush

@section('title', __('admin.support_tickets.title'))

@section('content')

    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('admin.support_tickets.title') }}</h1>
            <p class="text-sm text-gray-500 mt-0.5">Customer and seller support requests.</p>
        </div>
    </div>

    {{-- ─── Stats ───────────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-5">
        <x-stat-card title="{{ __('admin.support_tickets.open') }}" :value="number_format($stats['open'])"
            iconBg="{{ $stats['open'] > 0 ? 'bg-yellow-100 text-yellow-600' : 'bg-gray-100 text-gray-400' }}" />
        <x-stat-card title="{{ __('admin.support_tickets.in_progress') }}" :value="number_format($stats['in_progress'])" iconBg="bg-blue-100 text-blue-600" />
        <x-stat-card title="{{ __('admin.support_tickets.urgent') }}" :value="number_format($stats['urgent'])"
            iconBg="{{ $stats['urgent'] > 0 ? 'bg-red-100 text-red-600' : 'bg-gray-100 text-gray-400' }}" />
        <x-stat-card title="Unassigned" :value="number_format($stats['unassigned'])"
            iconBg="{{ $stats['unassigned'] > 0 ? 'bg-orange-100 text-orange-600' : 'bg-gray-100 text-gray-400' }}"
            :subtitle="$stats['avg_first_response_minutes'] ? 'Avg resp: ' . round($stats['avg_first_response_minutes'] / 60, 1) . 'h' : null" />
    </div>

    {{-- ─── Status Tabs ─────────────────────────────────────────────────────── --}}
    <div class="flex gap-1 mb-5 border-b border-gray-200">
        @php
            $tabs = [
                '' => __('common.all'),
                'open' => __('admin.support_tickets.open'),
                'in_progress' => __('admin.support_tickets.in_progress'),
                'waiting_customer' => __('admin.support_tickets.waiting'),
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
            <div class="flex-1 min-w-[180px]">
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.search') }}</label>
                <input type="text" id="search-input" class="form-input w-full text-sm" placeholder="Search…">
            </div>
            <div class="w-44">
                <label class="block text-xs font-medium text-gray-600 mb-1">Category</label>
                <select id="filter-category" class="form-input w-full text-sm">
                    <option value="">All categories</option>
                    <option value="order_issue">Order Issue</option>
                    <option value="payment_issue">Payment Issue</option>
                    <option value="account">Account</option>
                    <option value="technical">Technical</option>
                    <option value="product_inquiry">Product Inquiry</option>
                    <option value="policy">Policy</option>
                    <option value="payout">Payout</option>
                    <option value="catalog">Catalog</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="w-36">
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.support_tickets.priority') }}</label>
                <select id="filter-priority" class="form-input w-full text-sm">
                    <option value="">All priorities</option>
                    <option value="urgent">{{ __('admin.support_tickets.urgent') }}</option>
                    <option value="high">{{ __('admin.support_tickets.high') }}</option>
                    <option value="normal">Normal</option>
                    <option value="low">{{ __('admin.support_tickets.low') }}</option>
                </select>
            </div>
            <div class="w-36">
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.support_tickets.reporter_type') }}</label>
                <select id="filter-requester-role" class="form-input w-full text-sm">
                    <option value="">{{ __('common.all') }}</option>
                    <option value="customer">Customer</option>
                    <option value="seller">Vendor/Seller</option>
                </select>
            </div>
            <div class="w-44">
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.support_tickets.assigned_to') }}</label>
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
            <button id="btn-reset-filters" class="btn btn-ghost btn-sm text-gray-500">{{ __('common.reset') }}</button>
        </div>
    </x-card>

    {{-- ─── Table ───────────────────────────────────────────────────────────── --}}
    <x-card>
        <div class="overflow-x-auto">
            <table id="support-tickets-table" data-url="{{ route('admin.support-tickets.datatable') }}"
                class="w-full text-sm">
                <thead>
                    <tr class="text-start text-xs font-medium text-gray-500 border-b border-gray-200">
                        <th class="pb-3 pr-4 whitespace-nowrap">{{ __('admin.support_tickets.ticket_id') }}</th>
                        <th class="pb-3 pr-4 whitespace-nowrap">{{ __('admin.support_tickets.reporter') }}</th>
                        <th class="pb-3 pr-4 whitespace-nowrap">Category</th>
                        <th class="pb-3 pr-4 whitespace-nowrap">{{ __('admin.support_tickets.priority') }}</th>
                        <th class="pb-3 pr-4 whitespace-nowrap">{{ __('admin.status') }}</th>
                        <th class="pb-3 pr-4">Subject</th>
                        <th class="pb-3 pr-4 whitespace-nowrap">{{ __('admin.support_tickets.assigned_to') }}</th>
                        <th class="pb-3 pr-4 whitespace-nowrap">{{ __('admin.support_tickets.last_reply') }}</th>
                        <th class="pb-3 pr-4 whitespace-nowrap">Opened</th>
                        <th class="pb-3"></th>
                    </tr>
                </thead>
            </table>
        </div>
    </x-card>

@endsection
