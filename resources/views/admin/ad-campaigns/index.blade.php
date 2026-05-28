@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/column-renderers.js', 'resources/js/admin/ad-campaigns.js'])
@endpush

@section('title', 'Ad Campaigns')

@section('content')

    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Ad Campaigns</h1>
            <p class="text-sm text-gray-500 mt-0.5">Review, approve, and manage vendor advertising campaigns.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.ad-campaigns.fraud') }}" class="btn btn-secondary btn-sm">Fraud Alerts</a>
            <a href="{{ route('admin.ad-slots.index') }}" class="btn btn-secondary btn-sm">Ad Slots</a>
            <a href="{{ route('admin.paid-ad-bookings.index') }}" class="btn btn-primary btn-sm">Paid Bookings</a>
        </div>
    </div>

    {{-- ─── Stats ────────────────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <x-stat-card
            title="Pending Review"
            :value="number_format($stats['pending'])"
            icon="clock"
            iconBg="bg-warning-100 text-warning-600" />
        <x-stat-card
            title="Active Campaigns"
            :value="number_format($stats['active'])"
            icon="check-circle"
            iconBg="bg-success-100 text-success-600" />
        <x-stat-card
            title="Paused"
            :value="number_format($stats['paused'])"
            icon="pause-circle"
            iconBg="bg-gray-100 text-gray-600" />
        <x-stat-card
            title="Spend Today"
            :value="'$' . number_format($stats['spend_today'] / 100, 2)"
            icon="trending-up"
            iconBg="bg-primary-100 text-primary-600" />
    </div>

    {{-- ─── Tab navigation ──────────────────────────────────────────────────────── --}}
    <div class="flex border-b border-gray-200 mb-5 gap-1">
        @foreach([
            'all'      => 'All Campaigns',
            'cpc'      => 'CPC Campaigns',
            'cpm'      => 'CPM Campaigns',
        ] as $typeVal => $typeLabel)
            <button
                type="button"
                class="campaign-type-tab px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors
                    {{ $typeVal === 'all' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}"
                data-type="{{ $typeVal }}">
                {{ $typeLabel }}
            </button>
        @endforeach
    </div>

    {{-- ─── Filter bar ──────────────────────────────────────────────────────────── --}}
    <x-card class="mb-5">
        <form id="filter-form" class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[160px]">
                <label class="block text-xs font-medium text-gray-600 mb-1">Search</label>
                <input type="text" id="search-input" class="form-input w-full text-sm" placeholder="Campaign name, vendor…">
            </div>
            <div class="w-40">
                <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                <select id="filter-status" class="form-input w-full text-sm">
                    <option value="">All statuses</option>
                    <option value="pending_review">Pending Review</option>
                    <option value="active">Active</option>
                    <option value="paused">Paused</option>
                    <option value="rejected">Rejected</option>
                    <option value="ended">Ended</option>
                    <option value="draft">Draft</option>
                </select>
            </div>
            <div class="w-40">
                <label class="block text-xs font-medium text-gray-600 mb-1">Country</label>
                <select id="filter-country" class="form-input w-full text-sm">
                    <option value="">All countries</option>
                    @foreach($countries as $c)
                        <option value="{{ $c->id }}">{{ $c->flag_emoji ? $c->flag_emoji . ' ' : '' }}{{ $c->name_en }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-36">
                <label class="block text-xs font-medium text-gray-600 mb-1">Start from</label>
                <input type="date" id="filter-date-from" class="form-input w-full text-sm">
            </div>
            <div class="w-36">
                <label class="block text-xs font-medium text-gray-600 mb-1">Ends by</label>
                <input type="date" id="filter-date-to" class="form-input w-full text-sm">
            </div>
            <button type="button" id="clear-filters" class="btn btn-ghost btn-sm self-end">Reset</button>
        </form>
    </x-card>

    {{-- ─── DataTable ───────────────────────────────────────────────────────────── --}}
    <x-card>
        <div class="overflow-x-auto">
            <table id="campaigns-table" class="w-full text-sm" style="width:100%">
                <thead>
                    <tr class="border-b border-gray-100 text-left">
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Vendor</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Campaign</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Type</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Budget</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Spend</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Utilization</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Quality</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Dates</th>
                        <th class="py-2 text-xs font-medium text-gray-500 uppercase text-right">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </x-card>

    {{-- ─── Approve Confirm Modal ───────────────────────────────────────────────── --}}
    <x-modal id="approve-modal" title="Approve Campaign" size="sm">
        <p class="text-sm text-gray-600">
            Approve campaign <strong id="approve-campaign-name"></strong>?
            It will become active immediately.
        </p>
        <div class="flex justify-end gap-3 mt-5">
            <button type="button" class="btn btn-secondary" onclick="$('#approve-modal').modal('close')">Cancel</button>
            <button type="button" id="confirm-approve-btn" class="btn btn-success">Approve</button>
        </div>
    </x-modal>

    {{-- ─── Reject Modal ────────────────────────────────────────────────────────── --}}
    <x-modal id="reject-modal" title="Reject Campaign" size="md">
        <p class="text-sm text-gray-600 mb-3">
            Reject campaign <strong id="reject-campaign-name"></strong>.
        </p>
        <label class="block text-sm font-medium text-gray-700 mb-1">Rejection Reason <span class="text-red-500">*</span></label>
        <textarea id="reject-reason-input" rows="3" class="form-input w-full text-sm" placeholder="Explain why this campaign is being rejected…"></textarea>
        <p class="text-xs text-red-500 hidden mt-1" id="reject-reason-error">Reason is required.</p>
        <div class="flex justify-end gap-3 mt-5">
            <button type="button" class="btn btn-secondary" onclick="$('#reject-modal').modal('close')">Cancel</button>
            <button type="button" id="confirm-reject-btn" class="btn btn-danger">Reject</button>
        </div>
    </x-modal>

    {{-- ─── Pause / Resume Confirm Modals ──────────────────────────────────────── --}}
    <x-modal id="pause-modal" title="Pause Campaign" size="sm">
        <p class="text-sm text-gray-600">Pause <strong id="pause-campaign-name"></strong>? The campaign will stop serving ads.</p>
        <div class="flex justify-end gap-3 mt-5">
            <button type="button" class="btn btn-secondary" onclick="$('#pause-modal').modal('close')">Cancel</button>
            <button type="button" id="confirm-pause-btn" class="btn btn-warning">Pause</button>
        </div>
    </x-modal>

    <x-modal id="resume-modal" title="Resume Campaign" size="sm">
        <p class="text-sm text-gray-600">Resume <strong id="resume-campaign-name"></strong>? It will start serving ads again.</p>
        <div class="flex justify-end gap-3 mt-5">
            <button type="button" class="btn btn-secondary" onclick="$('#resume-modal').modal('close')">Cancel</button>
            <button type="button" id="confirm-resume-btn" class="btn btn-success">Resume</button>
        </div>
    </x-modal>

@endsection
