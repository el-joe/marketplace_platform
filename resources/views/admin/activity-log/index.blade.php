@extends('layouts.admin')

@section('title', 'Activity Log')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/column-renderers.js'])
@endpush

@push('scripts')
    @vite('resources/js/admin/activity-log.js')
@endpush

@section('content')

    {{-- ─── Header ─────────────────────────────────────────────────────────── --}}
    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Activity Log</h1>
            <p class="text-sm text-gray-500 mt-0.5">Audit trail of admin, vendor, customer and system actions.</p>
        </div>
    </div>

    {{-- ─── Stats ─────────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
        <x-stat-card title="Total today" :value="number_format($stats['total_today'])" />
        <x-stat-card title="Admin actions" :value="number_format($stats['admin_actions'])" />
        <x-stat-card title="Deletions" :value="number_format($stats['deletions'])" />
        <x-stat-card title="Last activity" :value="$stats['last_activity']" />
    </div>

    {{-- ─── Filters ───────────────────────────────────────────────────────── --}}
    <x-card class="mb-5">
        <form id="activity-filter-form" class="grid grid-cols-1 md:grid-cols-3 gap-3">

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Log name</label>
                <input type="text" name="log_name" class="form-input w-full text-sm" placeholder="e.g. catalog, orders">
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Event</label>
                <select name="event" class="form-input w-full text-sm">
                    <option value="">All events</option>
                    <option value="created">Created</option>
                    <option value="updated">Updated</option>
                    <option value="deleted">Deleted</option>
                    <option value="restored">Restored</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Causer type</label>
                <select name="causer_type" class="form-input w-full text-sm">
                    <option value="">All causers</option>
                    @foreach($causerTypes as $class => $info)
                        <option value="{{ $class }}">{{ $info['label'] }}s</option>
                    @endforeach
                    <option value="system">System (no causer)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Subject type</label>
                <select name="subject_type" class="form-input w-full text-sm">
                    <option value="">All subjects</option>
                    @foreach($subjectTypes as $class => $label)
                        <option value="{{ $class }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Causer</label>
                <select name="causer_id" id="filter-causer-id"
                    class="block w-full rounded-lg border border-gray-300 text-sm" data-async-select data-config='@json([
                        'url' => route('admin.activity-log.causer-search'),
                        'param' => 'q',
                        'multiple' => false,
                        'minLength' => 2,
                        'delay' => 300,
                    ])' placeholder="Search admin / vendor / customer…"></select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">IP address</label>
                <input type="text" name="ip_address" class="form-input w-full text-sm" placeholder="e.g. 192.168.1.">
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">From date</label>
                <input type="date" name="date_from" class="form-input w-full text-sm">
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">To date</label>
                <input type="date" name="date_to" class="form-input w-full text-sm">
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="btn btn-primary btn-sm">Apply filters</button>
                <button type="button" id="clear-activity-filters" class="btn btn-ghost btn-sm text-gray-500">Clear</button>
            </div>
        </form>
    </x-card>

    {{-- ─── Table ─────────────────────────────────────────────────────────── --}}
    <x-card>
        <div class="overflow-x-auto">
            <table id="activity-log-table" data-url="{{ route('admin.activity-log.datatable') }}" class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs font-medium text-gray-500 border-b border-gray-200">
                        <th class="pb-3 pr-4 whitespace-nowrap">Time</th>
                        <th class="pb-3 pr-4 whitespace-nowrap">Causer</th>
                        <th class="pb-3 pr-4 whitespace-nowrap">Event</th>
                        <th class="pb-3 pr-4 whitespace-nowrap">Subject</th>
                        <th class="pb-3 pr-4">Description</th>
                        <th class="pb-3 pr-4 whitespace-nowrap">Log</th>
                        <th class="pb-3 pr-4 whitespace-nowrap hidden md:table-cell">IP</th>
                        <th class="pb-3"></th>
                    </tr>
                </thead>
            </table>
        </div>
    </x-card>

    {{-- ─── Detail modal ──────────────────────────────────────────────────── --}}
    <x-modal id="activity-detail-modal" size="xl" title="Activity Detail">
        <div class="space-y-5">

            {{-- Header: event + description --}}
            <div class="flex items-start gap-3">
                <span id="detail-event-badge"
                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">—</span>
                <p id="detail-description" class="text-sm text-gray-700 flex-1"></p>
            </div>

            {{-- Two-column meta --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="space-y-2.5">
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Who &amp; When</h4>
                    <dl class="text-sm space-y-1.5">
                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500">Causer</dt>
                            <dd id="detail-causer-name" class="text-gray-900 font-medium text-right"></dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500">Type</dt>
                            <dd id="detail-causer-type" class="text-gray-700 text-right"></dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500">Email</dt>
                            <dd id="detail-causer-email" class="text-gray-700 text-right break-all"></dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500">When</dt>
                            <dd id="detail-created-at" class="text-gray-700 text-right"></dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500">IP</dt>
                            <dd id="detail-ip" class="text-gray-700 font-mono text-xs text-right"></dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500">Log name</dt>
                            <dd id="detail-log-name" class="text-gray-700 text-right"></dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500">Batch</dt>
                            <dd id="detail-batch" class="text-gray-700 font-mono text-xs text-right break-all"></dd>
                        </div>
                    </dl>
                    <details class="mt-2">
                        <summary class="text-xs text-gray-500 cursor-pointer hover:text-gray-700">User agent</summary>
                        <p id="detail-user-agent" class="text-xs text-gray-600 mt-1 break-all"></p>
                    </details>
                </div>

                <div class="space-y-2.5">
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Subject</h4>
                    <dl class="text-sm space-y-1.5">
                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500">Type</dt>
                            <dd id="detail-subject-type" class="text-gray-700 text-right"></dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500">Name</dt>
                            <dd id="detail-subject-name" class="text-gray-900 font-medium text-right break-all"></dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500">ID</dt>
                            <dd id="detail-subject-id" class="text-gray-500 font-mono text-xs text-right break-all"></dd>
                        </div>
                    </dl>
                    <a id="detail-subject-link" href="#" target="_blank" class="hidden btn btn-secondary btn-xs">Open
                        subject →</a>
                </div>
            </div>

            {{-- Changes diff --}}
            <div id="detail-changes-section" class="hidden">
                <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Changes</h4>
                <div class="overflow-x-auto border border-gray-200 rounded-lg">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-medium text-gray-500 border-b border-gray-200">
                                <th class="px-3 py-2">Field</th>
                                <th class="px-3 py-2">Old</th>
                                <th class="px-3 py-2">New</th>
                            </tr>
                        </thead>
                        <tbody id="detail-changes-tbody" class="divide-y divide-gray-100"></tbody>
                    </table>
                </div>
            </div>

            {{-- Raw JSON --}}
            <details>
                <summary class="text-xs text-gray-500 cursor-pointer hover:text-gray-700">Raw JSON</summary>
                <pre id="detail-raw-json" class="text-xs bg-gray-50 p-3 rounded overflow-x-auto mt-2"></pre>
            </details>
        </div>
    </x-modal>

@endsection