@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/admin/marketer-quotas.js'])
@endpush

@section('title', __('admin.marketer_quotas.title'))

@section('content')

    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('admin.marketer_quotas.title') }}</h1>
            <p class="text-sm text-gray-500 mt-0.5">{{ __('admin.marketer_quotas.subtitle') }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.marketer-quotas.progress') }}" class="btn btn-secondary btn-sm">
                {{ __('admin.marketer_quotas.view_progress') }}
            </a>
            <button type="button" id="btn-new-quota" class="btn btn-primary btn-sm">
                {{ __('admin.marketer_quotas.new_quota') }}
            </button>
        </div>
    </div>

    {{-- ─── Filter bar ──────────────────────────────────────────────────────── --}}
    <x-card class="mb-5">
        <div class="flex flex-wrap gap-3 items-end">
            <div class="w-56">
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.marketer_quotas.marketer') }}</label>
                <select id="filter-marketer_id" class="form-input w-full text-sm">
                    <option value="">{{ __('admin.marketer_quotas.any_marketer') }}</option>
                    @foreach($marketers as $m)
                        <option value="{{ $m->id }}">{{ $m->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-56">
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.marketer_quotas.category') }}</label>
                <select id="filter-promotion_category" class="form-input w-full text-sm">
                    <option value="">{{ __('admin.marketer_quotas.any_category') }}</option>
                    @foreach($categories as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-40">
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('common.active') }}</label>
                <select id="filter-is_active" class="form-input w-full text-sm">
                    <option value="">{{ __('admin.marketer_quotas.any_status') }}</option>
                    <option value="1">{{ __('common.active') }}</option>
                    <option value="0">{{ __('common.inactive') }}</option>
                </select>
            </div>
            <button id="btn-reset-filters" class="btn btn-ghost btn-sm text-gray-500">{{ __('admin.marketer_quotas.reset') }}</button>
        </div>
    </x-card>

    {{-- ─── Table ───────────────────────────────────────────────────────────── --}}
    <x-card>
        <div class="overflow-x-auto">
            <table id="marketer-quotas-table" data-url="{{ route('admin.marketer-quotas.datatable') }}" class="w-full text-sm">
                <thead>
                    <tr class="text-start text-xs font-medium text-gray-500 border-b border-gray-200">
                        <th class="pb-3 pr-4 whitespace-nowrap">{{ __('admin.marketer_quotas.marketer_column') }}</th>
                        <th class="pb-3 pr-4 whitespace-nowrap">{{ __('admin.marketer_quotas.category_column') }}</th>
                        <th class="pb-3 pr-4 whitespace-nowrap">{{ __('admin.marketer_quotas.min_per_month_column') }}</th>
                        <th class="pb-3 pr-4 whitespace-nowrap">{{ __('admin.marketer_quotas.penalty_column') }}</th>
                        <th class="pb-3 pr-4 whitespace-nowrap">{{ __('common.active') }}</th>
                        <th class="pb-3"></th>
                    </tr>
                </thead>
            </table>
        </div>
    </x-card>

    {{-- Create / Edit modal --}}
    <x-modal id="quota-modal" title="{{ __('admin.marketer_quotas.quota') }}" size="md">
        <form id="quota-form" class="space-y-4">
            @csrf
            <input type="hidden" id="form-quota-id" value="">

            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('admin.marketer_quotas.marketer') }}</label>
                <select id="f-marketer-id" class="form-input w-full text-sm">
                    <option value="">{{ __('admin.marketer_quotas.global_default') }}</option>
                    @foreach($marketers as $m)
                        <option value="{{ $m->id }}">{{ $m->name }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-400 mt-1">{{ __('admin.marketer_quotas.global_default_hint') }}</p>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('admin.marketer_quotas.category') }} <span class="text-red-500">*</span></label>
                <select id="f-promotion-category" required class="form-input w-full text-sm">
                    @foreach($categories as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('admin.marketer_quotas.min_per_month') }} <span class="text-red-500">*</span></label>
                    <input type="number" id="f-min-per-month" required min="0" class="form-input w-full text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('admin.marketer_quotas.penalty_per_missing') }}</label>
                    <input type="number" id="f-penalty" min="0" value="0" class="form-input w-full text-sm">
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('admin.marketer_quotas.penalty_currency') }}</label>
                <input type="text" id="f-penalty-currency" maxlength="3" placeholder="EGP" class="form-input w-32 text-sm uppercase">
            </div>

            <label class="flex items-center gap-2 cursor-pointer select-none">
                <input type="checkbox" id="f-is-active" class="rounded text-primary-600" checked>
                <span class="text-sm text-gray-700">{{ __('common.active') }}</span>
            </label>

            <div id="form-error" class="hidden rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700"></div>
        </form>

        <x-slot:footer>
            <button type="button" data-modal-close class="btn btn-secondary">{{ __('common.cancel') }}</button>
            <button type="button" id="btn-save-quota" class="btn btn-primary">{{ __('common.save') }}</button>
        </x-slot:footer>
    </x-modal>

    {{-- Delete confirmation modal --}}
    <x-modal id="delete-quota-modal" title="{{ __('admin.marketer_quotas.delete_quota') }}" size="sm">
        <p class="text-sm text-gray-700">{{ __('admin.marketer_quotas.delete_confirm') }}</p>
        <input type="hidden" id="delete-quota-id">
        <div id="delete-quota-error" class="hidden mt-3 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700"></div>
        <x-slot:footer>
            <button type="button" data-modal-close class="btn btn-secondary">{{ __('common.cancel') }}</button>
            <button type="button" id="btn-confirm-delete-quota" class="btn btn-danger">{{ __('common.delete') }}</button>
        </x-slot:footer>
    </x-modal>

@endsection
