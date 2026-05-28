@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/column-renderers.js', 'resources/js/admin/reviews.js'])
@endpush

@section('title', 'Reviews Management')

@section('content')

    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Reviews</h1>
            <p class="text-sm text-gray-500 mt-0.5">Moderate customer reviews across all products.</p>
        </div>
    </div>

    {{-- ─── Stats ──────────────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-5">
        <x-stat-card title="Pending" :value="number_format($stats['pending'])" iconBg="bg-warning-100 text-warning-600" />
        <x-stat-card title="Auto-Flagged" :value="number_format($stats['auto_flagged'])" iconBg="{{ $stats['auto_flagged'] > 0 ? 'bg-orange-100 text-orange-600' : 'bg-gray-100 text-gray-400' }}" />
        <x-stat-card title="Published Today" :value="number_format($stats['published_today'])" iconBg="bg-success-100 text-success-600" />
        <x-stat-card title="Avg Rating" :value="$stats['avg_rating'] ? number_format($stats['avg_rating'], 1) . ' ★' : '—'" iconBg="bg-yellow-100 text-yellow-600" />
    </div>

    {{-- ─── AI Flag Alert ────────────────────────────────────────────────────────── --}}
    @if($aiFlaggedCount > 0)
        <div class="mb-5 flex items-center gap-3 rounded-lg border border-orange-200 bg-orange-50 px-4 py-3 text-sm text-orange-800">
            <svg class="w-5 h-5 flex-shrink-0 text-orange-500" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
            <span><strong>{{ number_format($aiFlaggedCount) }}</strong> review{{ $aiFlaggedCount !== 1 ? 's' : '' }} flagged by AI — <button type="button" class="underline font-medium" id="tab-ai-flagged-trigger">review now</button></span>
        </div>
    @endif

    {{-- ─── Quick Action Tabs ────────────────────────────────────────────────────── --}}
    <div class="flex items-center gap-1 mb-4 border-b border-gray-200">
        @foreach([
            ['id' => 'all',         'label' => 'All',        'status' => ''],
            ['id' => 'pending',     'label' => 'Pending',    'status' => 'pending'],
            ['id' => 'ai_flagged',  'label' => 'AI Flagged', 'status' => 'auto_flagged'],
            ['id' => 'rejected',    'label' => 'Rejected',   'status' => 'rejected'],
        ] as $tab)
            <button
                type="button"
                class="tab-btn px-4 py-2 text-sm font-medium border-b-2 transition-colors
                    {{ $loop->first ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}"
                data-tab="{{ $tab['id'] }}"
                data-status="{{ $tab['status'] }}">
                {{ $tab['label'] }}
                @if($tab['status'] === 'pending' && $stats['pending'] > 0)
                    <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-warning-100 text-warning-700">{{ $stats['pending'] }}</span>
                @endif
                @if($tab['id'] === 'ai_flagged' && $aiFlaggedCount > 0)
                    <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-700">{{ $aiFlaggedCount }}</span>
                @endif
            </button>
        @endforeach
    </div>

    {{-- ─── Filter bar ──────────────────────────────────────────────────────────── --}}
    <x-card class="mb-5">
        <div class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[160px]">
                <label class="block text-xs font-medium text-gray-600 mb-1">Search product / customer</label>
                <input type="text" id="search-input" class="form-input w-full text-sm" placeholder="Product name, customer…">
            </div>
            <div class="w-44">
                <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                <select id="filter-status" class="form-input w-full text-sm">
                    <option value="">All statuses</option>
                    <option value="pending">Pending</option>
                    <option value="published">Published</option>
                    <option value="rejected">Rejected</option>
                    <option value="flagged">Flagged</option>
                    <option value="auto_flagged">Auto-Flagged</option>
                </select>
            </div>
            <div class="w-44">
                <label class="block text-xs font-medium text-gray-600 mb-1">Rating</label>
                <div class="flex gap-1">
                    @foreach([1,2,3,4,5] as $r)
                        <label class="flex items-center cursor-pointer" title="{{ $r }} star{{ $r > 1 ? 's' : '' }}">
                            <input type="checkbox" class="rating-checkbox sr-only" value="{{ $r }}">
                            <span class="px-2 py-1 rounded border text-sm rating-star-btn border-gray-200 text-gray-400 hover:border-yellow-400 hover:text-yellow-500 transition-colors cursor-pointer select-none">
                                {{ $r }}★
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>
            <div class="w-36">
                <label class="block text-xs font-medium text-gray-600 mb-1">Country</label>
                <select id="filter-country" class="form-input w-full text-sm">
                    <option value="">All countries</option>
                    @foreach($countries as $c)
                        <option value="{{ $c->id }}">{{ $c->flag_emoji ? $c->flag_emoji . ' ' : '' }}{{ $c->name_en }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-32">
                <label class="block text-xs font-medium text-gray-600 mb-1">From date</label>
                <input type="date" id="filter-date-from" class="form-input w-full text-sm">
            </div>
            <div class="w-32">
                <label class="block text-xs font-medium text-gray-600 mb-1">To date</label>
                <input type="date" id="filter-date-to" class="form-input w-full text-sm">
            </div>
            <label class="flex items-center gap-2 cursor-pointer self-end pb-1">
                <input type="checkbox" id="filter-verified" class="form-checkbox">
                <span class="text-sm text-gray-600">Verified only</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer self-end pb-1">
                <input type="checkbox" id="filter-ai-flagged" class="form-checkbox">
                <span class="text-sm text-gray-600">AI flagged</span>
            </label>
            <button type="button" id="clear-filters" class="btn btn-ghost btn-sm self-end">Reset</button>
        </div>
    </x-card>

    {{-- ─── Bulk action bar ─────────────────────────────────────────────────────── --}}
    <div id="bulk-action-bar" class="hidden mb-4 flex items-center gap-3 rounded-lg border border-primary-200 bg-primary-50 px-4 py-2.5">
        <span class="text-sm text-primary-700 font-medium"><span id="selected-count">0</span> selected</span>
        <button type="button" class="btn btn-success btn-sm" id="bulk-approve-btn">Approve all</button>
        <button type="button" class="btn btn-danger btn-sm" id="bulk-reject-btn">Reject all</button>
        <button type="button" class="btn btn-secondary btn-sm" id="bulk-delete-btn">Delete all</button>
        <button type="button" class="btn btn-ghost btn-xs ml-auto" id="deselect-all-btn">✕ Clear</button>
    </div>

    {{-- ─── DataTable ───────────────────────────────────────────────────────────── --}}
    <x-card>
        <div class="overflow-x-auto">
            <table id="reviews-table" class="w-full text-sm" style="width:100%">
                <thead>
                    <tr class="border-b border-gray-100 text-left">
                        <th class="py-2 pr-3 w-8">
                            <input type="checkbox" id="select-all-checkbox" class="form-checkbox">
                        </th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Product</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Customer</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Rating</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">AI</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Verified</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">👍</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">👎</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="py-2 text-xs font-medium text-gray-500 uppercase text-right">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </x-card>

    {{-- ─── Reject Modal ─────────────────────────────────────────────────────────── --}}
    <x-modal id="reject-modal" title="Reject Review" size="sm">
        <p class="text-sm text-gray-600 mb-3">Add an optional internal note for why this review is being rejected:</p>
        <textarea id="reject-reason-input" rows="3" class="form-input w-full text-sm" placeholder="Optional rejection reason…"></textarea>
        <div class="flex justify-end gap-3 mt-4">
            <button type="button" class="btn btn-secondary" onclick="$('#reject-modal').modal('close')">Cancel</button>
            <button type="button" id="confirm-reject-btn" class="btn btn-danger">Reject Review</button>
        </div>
    </x-modal>

@endsection
