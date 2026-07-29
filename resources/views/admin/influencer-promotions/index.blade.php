@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/admin/influencer-promotions.js'])
@endpush

@section('title', __('admin.influencer_promotions.title'))

@section('content')

    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('admin.influencer_promotions.title') }}</h1>
            <p class="text-sm text-gray-500 mt-0.5">{{ __('admin.influencer_promotions.subtitle') }}</p>
        </div>
    </div>

    {{-- ─── Filter bar ──────────────────────────────────────────────────────── --}}
    <x-card class="mb-5">
        <div class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.influencer_promotions.search_label') }}</label>
                <input type="text" id="search-input" class="form-input w-full text-sm" placeholder="{{ __('admin.influencer_promotions.search_placeholder') }}">
            </div>
            <div class="w-48">
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.influencer_promotions.status') }}</label>
                <select id="filter-status" class="form-input w-full text-sm">
                    <option value="">{{ __('admin.influencer_promotions.any_status') }}</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}">{{ __('admin.influencer_promotions.status_' . $status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-56">
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.influencer_promotions.vendor') }}</label>
                <select id="filter-vendor_id" class="form-input w-full text-sm">
                    <option value="">{{ __('admin.influencer_promotions.any_vendor') }}</option>
                    @foreach($vendors as $v)
                        <option value="{{ $v->id }}">{{ $v->store_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-48">
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.influencer_promotions.fulfillment_model') }}</label>
                <select id="filter-listing_fulfillment_model" class="form-input w-full text-sm">
                    <option value="">{{ __('admin.influencer_promotions.any_fulfillment_model') }}</option>
                    @foreach($fulfillmentModels as $model)
                        <option value="{{ $model }}">{{ strtoupper($model) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-48">
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.influencer_promotions.warehouse_required') }}</label>
                <select id="filter-warehouse_receipt_confirmed" class="form-input w-full text-sm">
                    <option value="">{{ __('admin.influencer_promotions.any_warehouse') }}</option>
                    <option value="1">{{ __('admin.influencer_promotions.warehouse_confirmed') }}</option>
                    <option value="0">{{ __('admin.influencer_promotions.warehouse_pending') }}</option>
                </select>
            </div>
            <button id="btn-reset-filters" class="btn btn-ghost btn-sm text-gray-500">{{ __('admin.influencer_promotions.reset') }}</button>
        </div>
    </x-card>

    {{-- ─── Table ───────────────────────────────────────────────────────────── --}}
    <x-card>
        <div class="overflow-x-auto">
            <table id="influencer-promotions-table" data-url="{{ route('admin.influencer-promotions.datatable') }}" class="w-full text-sm">
                <thead>
                    <tr class="text-start text-xs font-medium text-gray-500 border-b border-gray-200">
                        <th class="pb-3 pr-4 whitespace-nowrap">{{ __('admin.influencer_promotions.request_column') }}</th>
                        <th class="pb-3 pr-4 whitespace-nowrap">{{ __('admin.influencer_promotions.vendor_column') }}</th>
                        <th class="pb-3 pr-4 whitespace-nowrap">{{ __('admin.influencer_promotions.listing_column') }}</th>
                        <th class="pb-3 pr-4 whitespace-nowrap">{{ __('admin.influencer_promotions.total_slots_column') }}</th>
                        <th class="pb-3 pr-4 whitespace-nowrap">{{ __('admin.influencer_promotions.status_column') }}</th>
                        <th class="pb-3 pr-4 whitespace-nowrap">{{ __('admin.influencer_promotions.fee_column') }}</th>
                        <th class="pb-3 pr-4 whitespace-nowrap">{{ __('admin.influencer_promotions.fulfillment_model_column') }}</th>
                        <th class="pb-3 pr-4 whitespace-nowrap">{{ __('admin.influencer_promotions.warehouse_required_column') }}</th>
                        <th class="pb-3 pr-4 whitespace-nowrap">{{ __('admin.influencer_promotions.created_at_column') }}</th>
                        <th class="pb-3"></th>
                    </tr>
                </thead>
            </table>
        </div>
    </x-card>

@endsection
