@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/column-renderers.js', 'resources/js/admin/ad-campaigns.js'])
@endpush

@section('title', __('admin.ad_campaigns.paid_ad_bookings'))

@section('content')

    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('admin.ad-campaigns.index') }}" class="hover:text-primary-600">{{ __('admin.ad_campaigns.title') }}</a>
                <span>/</span>
                <span class="text-gray-800 font-medium">{{ __('admin.ad_campaigns.paid_ad_bookings') }}</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('admin.ad_campaigns.paid_ad_bookings') }}</h1>
            <p class="text-sm text-gray-500 mt-0.5">{{ __('admin.ad_campaigns.paid_bookings_subtitle') }}</p>
        </div>
        <div>
            <a href="{{ route('admin.ad-slots.index') }}" class="btn btn-secondary btn-sm">{{ __('admin.ad_campaigns.manage_slots') }}</a>
        </div>
    </div>

    {{-- ─── Stats ────────────────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mb-6">
        <x-stat-card title="{{ __('admin.ad_campaigns.pending_approval') }}" :value="number_format($stats['pending'])"
            iconBg="bg-warning-100 text-warning-600" />
        <x-stat-card title="{{ __('admin.ad_campaigns.active_bookings') }}" :value="number_format($stats['active'])"
            iconBg="bg-success-100 text-success-600" />
        <x-stat-card title="{{ __('admin.reject') }}" :value="number_format($stats['rejected'])" iconBg="bg-red-100 text-red-600" />
    </div>

    {{-- ─── Filter bar ──────────────────────────────────────────────────────────── --}}
    <x-card class="mb-5">
        <form id="bookings-filter-form" class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[160px]">
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.search') }}</label>
                <input type="text" id="bookings-search" class="form-input w-full text-sm" placeholder="{{ __('admin.ad_campaigns.bookings_search_placeholder') }}">
            </div>
            <div class="w-40">
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.status') }}</label>
                <select id="bookings-filter-status" class="form-input w-full text-sm">
                    <option value="">{{ __('admin.ad_campaigns.all_statuses') }}</option>
                    <option value="pending">{{ __('admin.ad_campaigns.pending') }}</option>
                    <option value="active">{{ __('common.active') }}</option>
                    <option value="rejected">{{ __('admin.reject') }}</option>
                    <option value="cancelled">{{ __('admin.ad_campaigns.cancelled') }}</option>
                    <option value="ended">{{ __('admin.ad_campaigns.ended') }}</option>
                </select>
            </div>
            <div class="w-40">
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.ad_campaigns.payment') }}</label>
                <select id="bookings-filter-payment" class="form-input w-full text-sm">
                    <option value="">{{ __('admin.ad_campaigns.all_payments') }}</option>
                    <option value="unpaid">{{ __('admin.ad_campaigns.unpaid') }}</option>
                    <option value="paid">{{ __('admin.ad_campaigns.paid') }}</option>
                    <option value="invoiced">{{ __('admin.ad_campaigns.invoiced') }}</option>
                    <option value="refunded">{{ __('admin.ad_campaigns.refunded') }}</option>
                </select>
            </div>
            <div class="w-36">
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.ad_campaigns.booked_from') }}</label>
                <input type="date" id="bookings-date-from" class="form-input w-full text-sm">
            </div>
            <div class="w-36">
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.ad_campaigns.ends_by') }}</label>
                <input type="date" id="bookings-date-to" class="form-input w-full text-sm">
            </div>
            <button type="button" id="clear-bookings-filters" class="btn btn-ghost btn-sm self-end">{{ __('common.reset') }}</button>
        </form>
    </x-card>

    {{-- ─── DataTable ───────────────────────────────────────────────────────────── --}}
    <x-card>
        <div class="overflow-x-auto">
            <table id="bookings-table" class="w-full text-sm" style="width:100%">
                <thead>
                    <tr class="border-b border-gray-100 text-start">
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">{{ __('admin.ad_campaigns.reference') }}</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">{{ __('admin.ad_campaigns.vendor') }}</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">{{ __('admin.ad_campaigns.slot_label') }}</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">{{ __('admin.ad_campaigns.dates') }}</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">{{ __('admin.ad_campaigns.rate') }}</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">{{ __('admin.status') }}</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">{{ __('admin.ad_campaigns.payment') }}</th>
                        <th class="py-2 text-xs font-medium text-gray-500 uppercase text-end">{{ __('admin.actions') }}</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </x-card>

    {{-- ─── Approve Confirm Modal ───────────────────────────────────────────────── --}}
    <x-modal id="approve-booking-modal" title="{{ __('admin.ad_campaigns.approve_booking') }}" size="sm">
        <p class="text-sm text-gray-600">
            {{ __('admin.ad_campaigns.approve_booking_prefix') }} <strong id="approve-booking-ref" class="font-mono text-gray-800"></strong>
            {{ __('admin.ad_campaigns.approve_booking_suffix') }}
        </p>
        <div class="flex justify-end gap-3 mt-5">
            <button type="button" class="btn btn-secondary"
                onclick="$('#approve-booking-modal').modal('close')">{{ __('common.cancel') }}</button>
            <button type="button" id="confirm-approve-booking-btn" class="btn btn-success">{{ __('admin.approve') }}</button>
        </div>
    </x-modal>

    {{-- ─── Reject Modal ────────────────────────────────────────────────────────── --}}
    <x-modal id="reject-booking-modal" title="{{ __('admin.ad_campaigns.reject_booking') }}" size="md">
        <p class="text-sm text-gray-600 mb-3">
            {{ __('admin.ad_campaigns.reject_booking_prefix') }} <strong id="reject-booking-ref" class="font-mono text-gray-800"></strong>.
        </p>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.ad_campaigns.rejection_reason') }} <span
                class="text-red-500">*</span></label>
        <textarea id="reject-booking-reason" rows="3" class="form-input w-full text-sm"
            placeholder="{{ __('admin.ad_campaigns.booking_rejection_placeholder') }}"></textarea>
        <p class="text-xs text-red-500 hidden mt-1" id="reject-booking-reason-error">{{ __('admin.ad_campaigns.reason_required') }}</p>
        <div class="flex justify-end gap-3 mt-5">
            <button type="button" class="btn btn-secondary"
                onclick="$('#reject-booking-modal').modal('close')">{{ __('common.cancel') }}</button>
            <button type="button" id="confirm-reject-booking-btn" class="btn btn-danger">{{ __('admin.reject') }}</button>
        </div>
    </x-modal>

@endsection
