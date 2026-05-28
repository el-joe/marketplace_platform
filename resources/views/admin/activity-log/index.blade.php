@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/column-renderers.js', 'resources/js/admin/settings.js'])
@endpush

@section('title', 'Activity Log')

@section('content')

    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Activity Log</h1>
            <p class="text-sm text-gray-500 mt-0.5">Audit trail of admin and system actions.</p>
        </div>
    </div>

    {{-- ─── Filters ─────────────────────────────────────────────────────────── --}}
    <x-card class="mb-5">
        <div class="flex flex-wrap gap-3 items-end">

            <div class="w-44">
                <label class="block text-xs font-medium text-gray-600 mb-1">Log Name</label>
                <select id="filter-log-name" class="form-input w-full text-sm">
                    <option value="">All logs</option>
                    @foreach($logNames as $name)
                        <option value="{{ $name }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="w-40">
                <label class="block text-xs font-medium text-gray-600 mb-1">Event</label>
                <select id="filter-event" class="form-input w-full text-sm">
                    <option value="">All events</option>
                    @foreach($events as $event)
                        <option value="{{ $event }}">{{ ucfirst($event) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="w-40">
                <label class="block text-xs font-medium text-gray-600 mb-1">Actor Type</label>
                <select id="filter-causer-type" class="form-input w-full text-sm">
                    <option value="">All actors</option>
                    @foreach($causerTypes as $causerType)
                        <option value="{{ $causerType }}">{{ $causerType }}</option>
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
            <table id="activity-log-table"
                   data-url="{{ route('admin.activity-log.datatable') }}"
                   class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs font-medium text-gray-500 border-b border-gray-200">
                        <th class="pb-3 pr-4 whitespace-nowrap">Time</th>
                        <th class="pb-3 pr-4 whitespace-nowrap">By</th>
                        <th class="pb-3 pr-4 whitespace-nowrap">Event</th>
                        <th class="pb-3 pr-4 whitespace-nowrap">Subject</th>
                        <th class="pb-3 pr-4 whitespace-nowrap">Description</th>
                        <th class="pb-3 pr-4 whitespace-nowrap">Log</th>
                        <th class="pb-3 pr-4 whitespace-nowrap">IP</th>
                        <th class="pb-3"></th>
                    </tr>
                </thead>
            </table>
        </div>
    </x-card>

@endsection
