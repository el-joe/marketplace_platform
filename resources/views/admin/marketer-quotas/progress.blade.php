@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/flatpickr.js', 'resources/js/admin/marketer-quotas-progress.js'])
@endpush

@section('title', __('admin.marketer_quotas.progress_title'))

@section('content')

    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('admin.marketer_quotas.progress_title') }}</h1>
            <p class="text-sm text-gray-500 mt-0.5">{{ __('admin.marketer_quotas.progress_subtitle') }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.marketer-quotas.index') }}" class="btn btn-secondary btn-sm">
                {{ __('admin.marketer_quotas.manage_quotas') }}
            </a>
            <button type="button" id="btn-send-warnings" class="btn btn-warning btn-sm">
                {{ __('admin.marketer_quotas.send_warnings') }}
            </button>
        </div>
    </div>

    {{-- ─── Month/Year selector + legend ───────────────────────────────────── --}}
    <x-card class="mb-5">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="w-48">
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.marketer_quotas.month') }}</label>
                <input type="text" id="month-picker" data-flatpickr data-mode="month"
                       value="{{ sprintf('%04d-%02d', $year, $month) }}"
                       class="form-input w-full text-sm" placeholder="{{ __('admin.marketer_quotas.select_month') }}">
            </div>
            <div class="flex flex-wrap gap-3 text-xs text-gray-600">
                <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-green-500"></span>{{ __('admin.marketer_quotas.status_met') }}</span>
                <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-yellow-500"></span>{{ __('admin.marketer_quotas.status_warning_sent') }}</span>
                <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-red-500"></span>{{ __('admin.marketer_quotas.status_failed') }}</span>
                <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-gray-400"></span>{{ __('admin.marketer_quotas.status_in_progress') }}</span>
            </div>
        </div>
    </x-card>

    {{-- ─── Table ───────────────────────────────────────────────────────────── --}}
    <x-card>
        <div class="overflow-x-auto">
            <table id="marketer-quotas-progress-table"
                   data-url="{{ route('admin.marketer-quotas.progress.datatable') }}"
                   data-warnings-url="{{ route('admin.marketer-quotas.progress.send-warnings') }}"
                   data-year="{{ $year }}" data-month="{{ $month }}"
                   class="w-full text-sm">
                <thead>
                    <tr class="text-start text-xs font-medium text-gray-500 border-b border-gray-200">
                        <th class="pb-3 pr-4 whitespace-nowrap">{{ __('admin.marketer_quotas.marketer_column') }}</th>
                        @foreach($categories as $value => $label)
                            <th class="pb-3 pr-4 whitespace-nowrap">{{ $label }}</th>
                        @endforeach
                        <th class="pb-3 pr-4 whitespace-nowrap">{{ __('admin.marketer_quotas.status_column') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </x-card>

@endsection
