@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/flatpickr.js', 'resources/js/admin/celebrity-monthly-report.js'])
@endpush

@section('title', __('admin.celebrity_report.title'))

@section('content')

    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('admin.celebrity_report.title') }}</h1>
            <p class="text-sm text-gray-500 mt-0.5">{{ __('admin.celebrity_report.subtitle') }}</p>
        </div>
        <a id="btn-export-csv" href="{{ route('admin.celebrities.monthly-report.export', ['year' => $year, 'month' => $month]) }}"
           class="btn btn-secondary btn-sm">
            {{ __('admin.celebrity_report.export_csv') }}
        </a>
    </div>

    {{-- ─── Stats ───────────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-5">
        <x-stat-card title="{{ __('admin.celebrity_report.stat_active_celebrities') }}" :value="number_format($stats['active_celebrities'])"
            iconBg="bg-primary-100 text-primary-600" />
        <x-stat-card title="{{ __('admin.celebrity_report.stat_meeting_minimum') }}" :value="number_format($stats['meeting_minimum'])"
            iconBg="bg-green-100 text-green-600" />
        <x-stat-card title="{{ __('admin.celebrity_report.stat_below_minimum') }}" :value="number_format($stats['below_minimum'])"
            iconBg="bg-orange-100 text-orange-600" />
        <x-stat-card title="{{ __('admin.celebrity_report.stat_penalties_applied') }}" :value="number_format($stats['penalties_applied'])"
            iconBg="bg-red-100 text-red-600" />
    </div>

    {{-- ─── Filter bar ──────────────────────────────────────────────────────── --}}
    <x-card class="mb-5">
        <div class="flex flex-wrap gap-3 items-end">
            <div class="w-40">
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.celebrity_report.tier') }}</label>
                <select id="filter-tier" class="form-input w-full text-sm">
                    <option value="">{{ __('admin.celebrity_report.any_tier') }}</option>
                    @foreach($tiers as $tier)
                        <option value="{{ $tier }}">{{ __('admin.celebrity_report.tier_label', ['tier' => $tier]) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-48">
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.celebrity_report.month') }}</label>
                <input type="text" id="month-picker" data-flatpickr data-mode="month"
                       value="{{ sprintf('%04d-%02d', $year, $month) }}"
                       class="form-input w-full text-sm" placeholder="{{ __('admin.celebrity_report.select_month') }}">
            </div>
            <div class="w-48">
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.celebrity_report.status') }}</label>
                <select id="filter-status" class="form-input w-full text-sm">
                    <option value="">{{ __('admin.celebrity_report.any_status') }}</option>
                    <option value="meeting">{{ __('admin.celebrity_report.status_meeting') }}</option>
                    <option value="below">{{ __('admin.celebrity_report.status_below') }}</option>
                    <option value="penalty">{{ __('admin.celebrity_report.status_penalty') }}</option>
                </select>
            </div>
            <div class="flex flex-wrap gap-3 text-xs text-gray-600 ms-auto">
                <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-green-500"></span>{{ __('admin.celebrity_report.legend_meeting') }}</span>
                <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-orange-500"></span>{{ __('admin.celebrity_report.legend_partial') }}</span>
                <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-red-500"></span>{{ __('admin.celebrity_report.legend_below') }}</span>
            </div>
        </div>
    </x-card>

    {{-- ─── Table ───────────────────────────────────────────────────────────── --}}
    <x-card>
        <div class="overflow-x-auto">
            <table id="celebrity-monthly-report-table"
                   data-url="{{ route('admin.celebrities.monthly-report.datatable') }}"
                   data-export-url="{{ route('admin.celebrities.monthly-report.export') }}"
                   data-year="{{ $year }}" data-month="{{ $month }}"
                   class="w-full text-sm">
                <thead>
                    <tr class="text-start text-xs font-medium text-gray-500 border-b border-gray-200">
                        <th class="pb-3 pr-4 whitespace-nowrap">{{ __('admin.celebrity_report.marketer_column') }}</th>
                        <th class="pb-3 pr-4 whitespace-nowrap">{{ __('admin.celebrity_report.tier_column') }}</th>
                        <th class="pb-3 pr-4 whitespace-nowrap">{{ __('admin.celebrity_report.period_column') }}</th>
                        <th class="pb-3 pr-4 whitespace-nowrap">{{ __('admin.celebrity_report.completed_column') }}</th>
                        <th class="pb-3 pr-4 whitespace-nowrap">{{ __('admin.celebrity_report.minimum_column') }}</th>
                        <th class="pb-3 pr-4 whitespace-nowrap">{{ __('admin.celebrity_report.progress_column') }}</th>
                        <th class="pb-3 pr-4 whitespace-nowrap">{{ __('admin.celebrity_report.status_column') }}</th>
                        <th class="pb-3 pr-4 whitespace-nowrap">{{ __('admin.celebrity_report.penalty_column') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </x-card>

@endsection
