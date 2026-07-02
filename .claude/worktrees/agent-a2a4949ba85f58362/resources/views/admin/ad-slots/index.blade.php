@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/column-renderers.js', 'resources/js/admin/ad-campaigns.js'])
@endpush

@section('title', __('admin.ad_campaigns.ad_slots'))

@section('content')

    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('admin.ad-campaigns.index') }}" class="hover:text-primary-600">{{ __('admin.ad_campaigns.title') }}</a>
                <span>/</span>
                <span class="text-gray-800 font-medium">{{ __('admin.ad_campaigns.ad_slots') }}</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('admin.ad_campaigns.ad_slots') }}</h1>
            <p class="text-sm text-gray-500 mt-0.5">{{ __('admin.ad_campaigns.ad_slots_subtitle') }}</p>
        </div>
        @if(auth('admin')->user()->can('create', App\Models\PaidAdSlot::class))
            <a href="{{ route('admin.ad-slots.create') }}" class="btn btn-primary btn-sm">{{ __('admin.ad_campaigns.new_slot') }}</a>
        @endif
    </div>

    {{-- ─── Stats ────────────────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mb-6">
        <x-stat-card title="{{ __('admin.ad_campaigns.total_slots') }}" :value="number_format($stats['total'])" iconBg="bg-gray-100 text-gray-600" />
        <x-stat-card title="{{ __('admin.ad_campaigns.available') }}" :value="number_format($stats['available'])"
            iconBg="bg-success-100 text-success-600" />
        <x-stat-card title="{{ __('admin.ad_campaigns.unavailable') }}" :value="number_format($stats['total'] - $stats['available'])"
            iconBg="bg-warning-100 text-warning-600" />
    </div>

    {{-- ─── Filter bar ──────────────────────────────────────────────────────────── --}}
    <x-card class="mb-5">
        <div class="flex flex-wrap gap-3 items-end">
            <div class="w-40">
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.ad_campaigns.availability') }}</label>
                <select id="slots-filter-available" class="form-input w-full text-sm">
                    <option value="">{{ __('common.all') }}</option>
                    <option value="1">{{ __('admin.ad_campaigns.available_only') }}</option>
                    <option value="0">{{ __('admin.ad_campaigns.unavailable_only') }}</option>
                </select>
            </div>
            <div class="w-40">
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.ad_campaigns.country') }}</label>
                <select id="slots-filter-country" class="form-input w-full text-sm">
                    <option value="">{{ __('admin.ad_campaigns.all_countries') }}</option>
                    @foreach($countries as $c)
                        <option value="{{ $c->id }}">{{ $c->name_en }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </x-card>

    {{-- ─── DataTable ───────────────────────────────────────────────────────────── --}}
    <x-card>
        <div class="overflow-x-auto">
            <table id="slots-table" class="w-full text-sm" style="width:100%">
                <thead>
                    <tr class="border-b border-gray-100 text-start">
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">{{ __('admin.name') }}</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">{{ __('admin.ad_campaigns.placement') }}</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">{{ __('admin.ad_campaigns.country') }}</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">{{ __('admin.ad_campaigns.pricing') }}</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">{{ __('admin.ad_campaigns.base_rate') }}</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">{{ __('admin.ad_campaigns.booking_days') }}</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">{{ __('admin.ad_campaigns.available') }}</th>
                        <th class="py-2 text-xs font-medium text-gray-500 uppercase text-end">{{ __('admin.actions') }}</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </x-card>

@endsection
