@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js'])
@endpush

@section('title', $vendor->store_name . ' — ' . __('admin.vendors.title'))

@section('content')

@php
    $statusColors = [
        \App\Enums\VendorGlobalStatus::Pending->value      => 'gray',
        \App\Enums\VendorGlobalStatus::Active->value       => 'success',
        \App\Enums\VendorGlobalStatus::Suspended->value    => 'warning',
        \App\Enums\VendorGlobalStatus::Rejected->value     => 'danger',
        \App\Enums\VendorGlobalStatus::Blacklisted->value  => 'danger',
        \App\Enums\VendorGlobalStatus::UnderReview->value  => 'primary',
    ];
    $docStatusColors = [
        \App\Enums\VendorDocumentStatus::Pending->value  => 'gray',
        \App\Enums\VendorDocumentStatus::Approved->value => 'success',
        \App\Enums\VendorDocumentStatus::Rejected->value => 'danger',
    ];
    $strikeColors = [
        \App\Enums\VendorStrikeSeverity::Minor->value    => 'warning',
        \App\Enums\VendorStrikeSeverity::Major->value    => 'warning',
        \App\Enums\VendorStrikeSeverity::Critical->value => 'danger',
    ];
    $activeStrikesCount = $vendor->strikes->where('is_active', true)->count();
@endphp

{{-- ─── Page header ─────────────────────────────────────────────────────────── --}}
<div class="mb-6 flex items-start justify-between gap-4">
    <div class="flex items-center gap-4 min-w-0">
        @if($vendor->avatar)
            <img src="{{ $vendor->avatar }}" class="w-14 h-14 rounded-xl object-cover flex-shrink-0 border border-gray-200">
        @else
            <div class="w-14 h-14 rounded-xl bg-primary-100 text-primary-700 flex items-center justify-center text-xl font-bold flex-shrink-0">
                {{ strtoupper(substr($vendor->store_name, 0, 1)) }}
            </div>
        @endif
        <div class="min-w-0">
            <h1 class="text-2xl font-bold text-gray-900 truncate">{{ $vendor->store_name }}</h1>
            <div class="flex items-center gap-2 mt-1 flex-wrap">
                <x-badge :color="$statusColors[$vendor->global_status->value] ?? 'gray'">
                    {{ __('admin.vendors.' . $vendor->global_status->value) }}
                </x-badge>
                @if($vendor->payout_hold_active)
                    <x-badge color="warning">{{ __('admin.vendors.payout_hold') }}</x-badge>
                @endif
                @if($activeStrikesCount > 0)
                    <x-badge color="danger">{{ $activeStrikesCount }} {{ __('admin.vendors.active_strikes') }}</x-badge>
                @endif
            </div>
        </div>
    </div>
    <a href="{{ route('admin.vendors.index') }}" class="text-sm text-gray-500 hover:text-gray-700 flex-shrink-0 mt-1">← {{ __('admin.vendors.back') }}</a>
</div>

{{-- ─── Two-column layout ───────────────────────────────────────────────────── --}}
<div class="grid grid-cols-12 gap-6">

    {{-- ══ MAIN (8/12) ════════════════════════════════════════════════════════ --}}
    <div class="col-span-12 lg:col-span-8 space-y-6">

        <div x-data="{ tab: 'profile' }">
            {{-- Tab bar --}}
            <div class="flex gap-1 border-b border-gray-200 overflow-x-auto pb-px mb-6">
                @foreach([
                    'profile'     => __('admin.vendors.tab_profile'),
                    'documents'   => __('admin.vendors.tab_documents'),
                    'bank'        => __('admin.vendors.tab_bank'),
                    'strikes'     => __('admin.vendors.tab_strikes'),
                    'performance' => __('admin.vendors.tab_performance'),
                    'orders'      => __('admin.vendors.tab_orders'),
                    'payouts'     => __('admin.vendors.tab_payouts'),
                    'activity'    => __('admin.vendors.tab_activity'),
                ] as $key => $label)
                    <button type="button"
                            @click="tab = '{{ $key }}'"
                            :class="tab === '{{ $key }}' ? 'border-b-2 border-primary-600 text-primary-700 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                            class="px-4 py-2 text-sm whitespace-nowrap transition-colors focus:outline-none">
                        {{ $label }}
                        @if($key === 'strikes' && $activeStrikesCount > 0)
                            <span class="ml-1 inline-flex items-center justify-center w-4 h-4 rounded-full bg-danger-500 text-white text-xs">{{ $activeStrikesCount }}</span>
                        @endif
                    </button>
                @endforeach
            </div>

            {{-- ── Profile ──────────────────────────────────────────────────── --}}
            <div x-show="tab === 'profile'">
                <x-card title="{{ __('admin.vendors.vendor_profile') }}">
                    <x-slot name="actions">
                        <button type="button" id="edit-profile-btn" class="btn btn-secondary btn-sm text-xs">{{ __('admin.vendors.edit') }}</button>
                    </x-slot>

                    <div id="profile-view" class="grid grid-cols-2 gap-x-6 gap-y-4 text-sm">
                        @foreach([
                            __('admin.vendors.store_name')      => $vendor->store_name,
                            __('admin.vendors.store_slug')      => $vendor->store_slug,
                            __('admin.vendors.business_name')   => $vendor->business_name ?: '—',
                            __('admin.vendors.business_type')   => $vendor->business_type ? __('admin.vendor_applications.business_type_' . $vendor->business_type->value) : '—',
                            __('admin.vendors.reg_number')      => $vendor->business_registration_number ?: '—',
                            __('admin.vendors.tax_id')          => $vendor->tax_id ?: '—',
                            __('admin.vendors.contact_email')   => $vendor->contact_email ?: '—',
                            __('admin.vendors.contact_phone')   => $vendor->contact_phone ?: '—',
                            __('admin.vendors.whatsapp')        => $vendor->whatsapp_number ?: '—',
                            __('admin.vendors.commission_rate') => $vendor->commission_rate ? $vendor->commission_rate . '%' : __('admin.vendors.platform_default'),
                            __('admin.vendors.payout_schedule') => $vendor->payout_schedule ? __('admin.vendors.payout_schedule_' . $vendor->payout_schedule) : '—',
                            __('admin.vendors.country')         => $vendor->country?->name_en ?? '—',
                            __('admin.vendors.approved_at')     => $vendor->approved_at?->format('d M Y') ?? '—',
                            __('admin.vendors.approved_by')     => $vendor->approvedByAdmin?->name ?? '—',
                            __('admin.vendors.joined')          => $vendor->created_at->format('d M Y'),
                        ] as $label => $value)
                            <div>
                                <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ $label }}</dt>
                                <dd class="mt-0.5 font-medium text-gray-900">{{ $value }}</dd>
                            </div>
                        @endforeach
                        @if($vendor->store_description)
                            <div class="col-span-2">
                                <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('admin.vendors.description') }}</dt>
                                <dd class="mt-0.5 text-gray-700">{{ $vendor->store_description }}</dd>
                            </div>
                        @endif
                    </div>

                    <form id="profile-edit-form" class="hidden" data-vendor-id="{{ $vendor->id }}">
                        @csrf
                        @method('PUT')
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <x-form.input name="store_name"    label="{{ __('admin.vendors.store_name') }}"    :value="$vendor->store_name"    required />
                            <x-form.input name="store_slug"    label="{{ __('admin.vendors.store_slug') }}"    :value="$vendor->store_slug"    required />
                            <x-form.input name="business_name" label="{{ __('admin.vendors.business_name') }}" :value="$vendor->business_name" />
                            <x-form.select name="business_type" label="{{ __('admin.vendors.business_type') }}" :value="$vendor->business_type?->value"
                                :options="['individual' => __('admin.vendors.individual'), 'sole_prop' => __('admin.vendors.sole_prop'), 'llc' => __('admin.vendors.llc'), 'corp' => __('admin.vendors.corp')]"/>
                            <x-form.input name="contact_email"   label="{{ __('admin.vendors.contact_email') }}" :value="$vendor->contact_email"   type="email"/>
                            <x-form.input name="contact_phone"   label="{{ __('admin.vendors.contact_phone') }}" :value="$vendor->contact_phone" />
                            <x-form.input name="commission_rate" label="{{ __('admin.vendors.commission_rate') }} (%)" :value="$vendor->commission_rate" type="number" step="0.01"/>
                            <x-form.select name="payout_schedule" label="{{ __('admin.vendors.payout_schedule') }}" :value="$vendor->payout_schedule"
                                :options="['weekly' => __('admin.vendors.weekly'), 'biweekly' => __('admin.vendors.biweekly'), 'monthly' => __('admin.vendors.monthly')]"/>
                            <div class="col-span-2">
                                <x-form.textarea name="store_description" label="{{ __('admin.vendors.store_description') }}" :value="$vendor->store_description" rows="3"/>
                            </div>
                        </div>
                        <div class="mt-4 flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm">{{ __('admin.vendors.save_changes_btn') }}</button>
                            <button type="button" id="cancel-edit-btn" class="btn btn-ghost btn-sm">{{ __('admin.vendors.cancel') }}</button>
                        </div>
                    </form>
                </x-card>
            </div>

            {{-- ── Documents ────────────────────────────────────────────────── --}}
            <div x-show="tab === 'documents'">
                <x-card title="{{ __('admin.vendors.documents') }}">
                    <x-slot name="actions">
                        <button type="button" data-modal-open="request-doc-modal" class="btn btn-secondary btn-sm text-xs">{{ __('admin.vendors.request_document_btn') }}</button>
                    </x-slot>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-100 text-start">
                                    <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">{{ __('admin.vendors.type_column') }}</th>
                                    <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">{{ __('admin.vendors.status_column') }}</th>
                                    <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">{{ __('admin.vendors.expires_column') }}</th>
                                    <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">{{ __('admin.vendors.verified_by_column') }}</th>
                                    <th class="py-2 text-xs font-medium text-gray-500 uppercase text-end">{{ __('admin.vendors.actions_column') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @forelse($vendor->documents as $doc)
                                    <tr data-doc-id="{{ $doc->id }}" class="hover:bg-gray-50/50">
                                        <td class="py-3 pr-4 font-medium text-gray-900">
                                            {{ ucwords(str_replace('_', ' ', $doc->document_type)) }}
                                        </td>
                                        <td class="py-3 pr-4">
                                            <x-badge :color="$docStatusColors[$doc->status->value] ?? 'gray'">{{ __('admin.vendor_applications.doc_status_' . $doc->status->value) }}</x-badge>
                                        </td>
                                        <td class="py-3 pr-4 text-gray-600">{{ $doc->expires_at?->format('d M Y') ?? '—' }}</td>
                                        <td class="py-3 pr-4 text-gray-600">{{ $doc->verifiedByAdmin?->name ?? '—' }}</td>
                                        <td class="py-3 text-end">
                                            <div class="flex items-center justify-end gap-2">
                                                @if($doc->file_path)
                                                    <a href="{{ $doc->full_file_path }}" target="_blank" class="text-xs text-primary-600 hover:underline">{{ __('admin.vendors.view') }}</a>
                                                @endif
                                                @if($doc->status !== \App\Enums\VendorDocumentStatus::Approved)
                                                    <button type="button" class="text-xs text-success-700 hover:underline" data-verify-doc="{{ $doc->id }}">{{ __('admin.vendors.verify') }}</button>
                                                @endif
                                                @if($doc->status !== \App\Enums\VendorDocumentStatus::Rejected)
                                                    <button type="button" class="text-xs text-danger-600 hover:underline" data-reject-doc="{{ $doc->id }}">{{ __('admin.vendors.reject') }}</button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="py-8 text-center text-sm text-gray-400">{{ __('admin.vendors.no_documents_uploaded') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-card>
            </div>

            {{-- ── Bank Accounts ─────────────────────────────────────────────── --}}
            <div x-show="tab === 'bank'">
                <x-card title="{{ __('admin.vendors.bank_accounts') }}">
                    @if($vendor->bankAccounts->isEmpty())
                        <p class="text-sm text-gray-400 text-center py-6">{{ __('admin.vendors.no_bank_accounts') }}</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-100 text-start">
                                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">{{ __('admin.vendors.bank_column') }}</th>
                                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">{{ __('admin.vendors.iban_last4_column') }}</th>
                                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">{{ __('admin.vendors.currency_column') }}</th>
                                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">{{ __('admin.vendors.primary_column') }}</th>
                                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">{{ __('admin.vendors.status_column') }}</th>
                                        <th class="py-2 text-end text-xs font-medium text-gray-500 uppercase">{{ __('admin.vendors.actions_column') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    @foreach($vendor->bankAccounts as $account)
                                        <tr class="hover:bg-gray-50/50">
                                            <td class="py-3 pr-4 font-medium">{{ $account->bank_name }}</td>
                                            <td class="py-3 pr-4 font-mono text-gray-600">•••• {{ $account->iban ? substr($account->iban, -4) : '——' }}</td>
                                            <td class="py-3 pr-4 text-gray-600">{{ strtoupper($account->currency) }}</td>
                                            <td class="py-3 pr-4">
                                                @if($account->is_primary)
                                                    <x-badge color="success">{{ __('admin.vendors.primary') }}</x-badge>
                                                @else
                                                    <span class="text-gray-400 text-xs">—</span>
                                                @endif
                                            </td>
                                            <td class="py-3 pr-4">
                                                <x-badge :color="$account->verification_status === \App\Enums\VendorBankAccountVerificationStatus::Verified ? 'success' : ($account->verification_status === \App\Enums\VendorBankAccountVerificationStatus::Rejected ? 'danger' : 'gray')">
                                                    {{ __('admin.vendor_applications.bank_status_' . $account->verification_status->value) }}
                                                </x-badge>
                                            </td>
                                            <td class="py-3 text-end">
                                                @if($account->verification_status !== \App\Enums\VendorBankAccountVerificationStatus::Verified)
                                                    <button type="button"
                                                            class="text-xs text-success-700 hover:underline"
                                                            data-verify-bank="{{ $account->id }}"
                                                            data-vendor-id="{{ $vendor->id }}">{{ __('admin.vendors.verify') }}</button>
                                                @else
                                                    <span class="text-xs text-gray-400">{{ __('admin.vendors.verified') }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </x-card>
            </div>

            {{-- ── Strikes ───────────────────────────────────────────────────── --}}
            <div x-show="tab === 'strikes'">
                <x-card title="{{ __('admin.vendors.strikes') }}">
                    <x-slot name="actions">
                        <button type="button" data-modal-open="issue-strike-modal" class="btn btn-danger btn-sm text-xs">{{ __('admin.vendors.issue_strike_btn') }}</button>
                    </x-slot>

                    <div class="grid grid-cols-2 gap-4 mb-5">
                        <div class="rounded-lg bg-gray-50 border border-gray-200 p-4 text-center">
                            <div class="text-2xl font-bold text-danger-600" id="active-strikes-count">{{ $activeStrikesCount }}</div>
                            <div class="text-xs text-gray-500 mt-0.5">{{ __('admin.vendors.active_strikes_stat') }}</div>
                        </div>
                        <div class="rounded-lg bg-gray-50 border border-gray-200 p-4 text-center">
                            <div class="text-2xl font-bold text-gray-700">{{ $vendor->strikes->count() }}</div>
                            <div class="text-xs text-gray-500 mt-0.5">{{ __('admin.vendors.total_strikes_stat') }}</div>
                        </div>
                    </div>

                    @if($activeStrikesCount >= 2)
                        <div class="mb-4 rounded-lg bg-warning-50 border border-warning-200 px-4 py-3 text-sm text-warning-800 flex items-center gap-2">
                            <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            {{ __('admin.vendors.strike_warning', ['count' => $activeStrikesCount]) }}
                        </div>
                    @endif

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-100 text-start">
                                    <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">{{ __('admin.vendors.date_column') }}</th>
                                    <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">{{ __('admin.vendors.reason_column') }}</th>
                                    <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">{{ __('admin.vendors.severity_column') }}</th>
                                    <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">{{ __('admin.vendors.issued_by_column') }}</th>
                                    <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">{{ __('admin.vendors.expires_column') }}</th>
                                    <th class="py-2 text-xs font-medium text-gray-500 uppercase">{{ __('admin.vendors.active_badge') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @forelse($vendor->strikes->sortByDesc('created_at') as $strike)
                                    <tr class="hover:bg-gray-50/50">
                                        <td class="py-3 pr-4 text-gray-600 whitespace-nowrap">{{ $strike->created_at->format('d M Y') }}</td>
                                        <td class="py-3 pr-4 text-gray-900 max-w-xs">
                                            <div class="font-medium">{{ $strike->reason->label() }}</div>
                                            @if($strike->description)
                                                <div class="text-xs text-gray-500 truncate">{{ $strike->description }}</div>
                                            @endif
                                        </td>
                                        <td class="py-3 pr-4">
                                            <x-badge :color="$strikeColors[$strike->severity->value] ?? 'gray'">{{ __('admin.vendors.severity_' . $strike->severity->value) }}</x-badge>
                                        </td>
                                        <td class="py-3 pr-4 text-gray-600">{{ $strike->issuedByAdmin?->name ?? '—' }}</td>
                                        <td class="py-3 pr-4 text-gray-600">{{ $strike->expires_at?->format('d M Y') ?? __('admin.vendors.never') }}</td>
                                        <td class="py-3">
                                            @if($strike->is_active)
                                                <x-badge color="danger">{{ __('admin.vendors.active_badge') }}</x-badge>
                                            @else
                                                <x-badge color="gray">{{ __('admin.vendors.expired_badge') }}</x-badge>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="py-8 text-center text-sm text-gray-400">{{ __('admin.vendors.no_strikes') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-card>
            </div>

            {{-- ── Performance ──────────────────────────────────────────────── --}}
            <div x-show="tab === 'performance'" id="performance-tab">
                <div class="grid grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
                    <x-stat-card title="{{ __('admin.vendors.total_gmv_stat') }}"    :value="'$' . number_format($vendor->total_sales, 2)"    icon="currency-dollar" icon-bg="bg-success-100 text-success-600"/>
                    <x-stat-card title="{{ __('admin.vendors.total_orders_stat') }}" :value="number_format($vendor->total_orders)"             icon="shopping-bag"    icon-bg="bg-primary-100 text-primary-600"/>
                    <x-stat-card title="{{ __('admin.vendors.avg_rating_stat') }}"   :value="number_format($vendor->store_rating_avg, 1) . ' / 5'" icon="star"       icon-bg="bg-warning-100 text-warning-600"/>
                    <x-stat-card title="{{ __('admin.vendors.return_rate_stat') }}"  :value="$vendor->return_rate_pct . '%'"                   icon="arrow-uturn-left" icon-bg="bg-danger-100 text-danger-600"/>
                </div>
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                    <x-card title="{{ __('admin.vendors.gmv_last_30_days') }}"><div class="h-56"><canvas id="gmv-chart"></canvas></div></x-card>
                    <x-card title="{{ __('admin.vendors.orders_by_status') }}"><div class="h-56"><canvas id="orders-status-chart"></canvas></div></x-card>
                </div>
                <x-card title="{{ __('admin.vendors.vs_platform_average') }}" class="mt-6">
                    <div class="grid grid-cols-3 gap-6 text-sm text-center">
                        <div>
                            <div class="text-xs text-gray-500 mb-1">{{ __('admin.vendors.total_gmv') }}</div>
                            <div class="text-lg font-bold text-gray-900">${{ number_format($vendor->total_sales, 0) }}</div>
                            <div class="text-xs text-gray-400">{{ __('admin.vendors.avg_label') }} <span id="avg-gmv">—</span></div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500 mb-1">{{ __('admin.vendors.tab_orders') }}</div>
                            <div class="text-lg font-bold text-gray-900">{{ number_format($vendor->total_orders) }}</div>
                            <div class="text-xs text-gray-400">{{ __('admin.vendors.avg_label') }} <span id="avg-orders">—</span></div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500 mb-1">{{ __('admin.vendors.rating') }}</div>
                            <div class="text-lg font-bold text-gray-900">{{ number_format($vendor->store_rating_avg, 1) }}</div>
                            <div class="text-xs text-gray-400">{{ __('admin.vendors.avg_label') }} <span id="avg-rating">—</span></div>
                        </div>
                    </div>
                </x-card>
            </div>

            {{-- ── Orders ────────────────────────────────────────────────────── --}}
            <div x-show="tab === 'orders'">
                <x-card title="{{ __('admin.vendors.order_history') }}">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-100 text-start">
                                    <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">{{ __('admin.vendors.sub_order_column') }}</th>
                                    <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">{{ __('admin.vendors.order_number_column') }}</th>
                                    <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">{{ __('admin.vendors.total_column') }}</th>
                                    <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">{{ __('admin.vendors.status_column') }}</th>
                                    <th class="py-2 text-xs font-medium text-gray-500 uppercase">{{ __('admin.vendors.date_column') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @forelse($subOrders as $so)
                                    <tr class="hover:bg-gray-50/50">
                                        <td class="py-3 pr-4 font-mono text-xs text-gray-700">{{ $so->sub_order_number }}</td>
                                        <td class="py-3 pr-4 text-primary-600">
                                            <a href="{{ route('admin.orders.show', $so->id) }}" class="hover:underline">{{ $so->order_number }}</a>
                                        </td>
                                        <td class="py-3 pr-4 tabular-nums">${{ number_format($so->vendor_payout / 100, 2) }}</td>
                                        <td class="py-3 pr-4">
                                            <x-badge color="gray">{{ ucwords(str_replace('_', ' ', $so->status->value)) }}</x-badge>
                                        </td>
                                        <td class="py-3 text-xs text-gray-500 whitespace-nowrap">{{ \Carbon\Carbon::parse($so->created_at)->format('d M Y') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="py-8 text-center text-sm text-gray-400">{{ __('admin.vendors.no_orders_yet') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-card>
            </div>

            {{-- ── Payouts ───────────────────────────────────────────────────── --}}
            <div x-show="tab === 'payouts'">
                <x-card title="{{ __('admin.vendors.payout_history') }}">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-100 text-start">
                                    <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">{{ __('admin.vendors.payout_number_column') }}</th>
                                    <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">{{ __('admin.vendors.period_column') }}</th>
                                    <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">{{ __('admin.vendors.gross_column') }}</th>
                                    <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">{{ __('admin.vendors.net_column') }}</th>
                                    <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">{{ __('admin.vendors.status_column') }}</th>
                                    <th class="py-2 text-xs font-medium text-gray-500 uppercase">{{ __('admin.vendors.processed_column') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @forelse($payouts as $payout)
                                    @php
                                        $pc = match($payout->status) {
                                            \App\Enums\PayoutStatus::Completed, \App\Enums\PayoutStatus::Approved => 'success',
                                            \App\Enums\PayoutStatus::Pending, \App\Enums\PayoutStatus::Processing => 'gray',
                                            \App\Enums\PayoutStatus::Failed => 'danger',
                                            \App\Enums\PayoutStatus::OnHold => 'warning',
                                            default => 'gray',
                                        };
                                    @endphp
                                    <tr class="hover:bg-gray-50/50">
                                        <td class="py-3 pr-4 font-mono text-xs">{{ $payout->payout_number }}</td>
                                        <td class="py-3 pr-4 text-xs text-gray-600 whitespace-nowrap">
                                            {{ \Carbon\Carbon::parse($payout->period_start)->format('d M') }} – {{ \Carbon\Carbon::parse($payout->period_end)->format('d M Y') }}
                                        </td>
                                        <td class="py-3 pr-4 tabular-nums">${{ number_format($payout->gross_sales / 100, 2) }}</td>
                                        <td class="py-3 pr-4 tabular-nums font-medium">${{ number_format($payout->net_amount / 100, 2) }}</td>
                                        <td class="py-3 pr-4"><x-badge :color="$pc">{{ __('admin.payouts.' . $payout->status->value) }}</x-badge></td>
                                        <td class="py-3 text-xs text-gray-500">{{ $payout->processed_at ? \Carbon\Carbon::parse($payout->processed_at)->format('d M Y') : '—' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="py-8 text-center text-sm text-gray-400">{{ __('admin.vendors.no_payouts_yet') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-card>
            </div>

            {{-- ── Activity Log ──────────────────────────────────────────────── --}}
            <div x-show="tab === 'activity'">
                <x-card title="{{ __('admin.vendors.activity_log') }}">
                    @if($activityLog->isEmpty())
                        <p class="text-sm text-gray-400 text-center py-6">{{ __('admin.vendors.no_activity_recorded') }}</p>
                    @else
                        <div class="space-y-3 text-sm">
                            @foreach($activityLog as $entry)
                                @php
                                    $props = is_string($entry->properties) ? json_decode($entry->properties, true) : (array) $entry->properties;
                                    $attrs = $props['attributes'] ?? [];
                                    $old   = $props['old'] ?? [];
                                @endphp
                                <div class="flex gap-3">
                                    <div class="w-8 h-8 rounded-full bg-primary-100 text-primary-700 flex items-center justify-center text-xs font-bold flex-shrink-0 mt-0.5">A</div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="font-medium text-gray-900">{{ $entry->causer_id ? __('admin.vendors.admin_label') : __('admin.vendors.system_label') }}</span>
                                            <span class="text-gray-500">{{ ucwords(str_replace('_', ' ', $entry->description)) }}</span>
                                            <span class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($entry->created_at)->diffForHumans() }}</span>
                                        </div>
                                        @foreach($attrs as $field => $newVal)
                                            @if(isset($old[$field]))
                                                <div class="text-xs text-gray-500 mt-0.5">
                                                    <span class="font-mono bg-gray-100 px-1 rounded">{{ $field }}</span>:
                                                    <span class="line-through text-danger-600">{{ $old[$field] }}</span>
                                                    → <span class="text-success-700">{{ $newVal }}</span>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-card>
            </div>

        </div>{{-- end x-data --}}
    </div>{{-- end main --}}

    {{-- ══ SIDEBAR (4/12) ════════════════════════════════════════════════════ --}}
    <div class="col-span-12 lg:col-span-4 space-y-4">

        <x-card title="{{ __('admin.vendors.vendor_info') }}">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <dt class="text-gray-500">{{ __('admin.vendors.store_name') }}</dt>
                    <dd class="font-medium text-primary-600 truncate ml-2">{{ $vendor->store_name }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">{{ __('admin.vendors.slug') }}</dt>
                    <dd class="font-mono text-xs text-gray-700">{{ $vendor->store_slug }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">{{ __('admin.vendors.account_manager') }}</dt>
                    <dd class="font-medium text-gray-900 truncate ml-2">{{ $vendor->accountManagerAdmin?->name ?? '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">{{ __('admin.vendors.rating') }}</dt>
                    <dd class="font-medium text-gray-900">{{ number_format($vendor->store_rating_avg, 1) }} ★ ({{ number_format($vendor->store_rating_count) }})</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">{{ __('admin.vendors.total_gmv') }}</dt>
                    <dd class="font-medium text-gray-900">${{ number_format($vendor->total_sales, 2) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">{{ __('admin.vendors.payout_hold') }}</dt>
                    <dd>
                        @if($vendor->payout_hold_active)
                            <x-badge color="warning">{{ __('admin.vendors.active_badge') }}</x-badge>
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </dd>
                </div>
            </dl>
            <div class="mt-4 pt-4 border-t border-gray-100">
                <button type="button" data-modal-open="assign-manager-modal" class="w-full btn btn-ghost btn-sm text-xs">
                    {{ __('admin.vendors.assign_manager') }}
                </button>
            </div>
        </x-card>

        <x-card title="{{ __('admin.vendors.quick_actions') }}">
            <div class="space-y-2">
                @if($vendor->global_status === \App\Enums\VendorGlobalStatus::Active)
                    <button type="button" data-modal-open="suspend-modal" class="w-full btn btn-warning btn-sm">{{ __('admin.vendors.suspend_vendor_action') }}</button>
                @elseif($vendor->global_status === \App\Enums\VendorGlobalStatus::Suspended)
                    <button type="button" data-reactivate-vendor="{{ $vendor->id }}" class="w-full btn btn-success btn-sm">{{ __('admin.vendors.reactivate_vendor_action') }}</button>
                @elseif($vendor->global_status === \App\Enums\VendorGlobalStatus::Pending)
                    <button type="button" data-approve-vendor="{{ $vendor->id }}" class="w-full btn btn-success btn-sm">{{ __('admin.vendors.approve_vendor_action') }}</button>
                    <button type="button" data-modal-open="reject-modal" class="w-full btn btn-ghost btn-sm text-danger-600">{{ __('admin.vendors.reject_application') }}</button>
                @endif

                @if($vendor->payout_hold_active)
                    <button type="button" data-release-hold="{{ $vendor->id }}" class="w-full btn btn-secondary btn-sm">{{ __('admin.vendors.release_payout_hold') }}</button>
                @else
                    <button type="button" data-modal-open="place-hold-modal" class="w-full btn btn-ghost btn-sm">{{ __('admin.vendors.place_payout_hold') }}</button>
                @endif

                @if($vendor->global_status !== \App\Enums\VendorGlobalStatus::Blacklisted)
                    <button type="button" data-modal-open="blacklist-modal" class="w-full btn btn-danger btn-sm">{{ __('admin.vendors.blacklist_vendor') }}</button>
                @endif

                <button type="button" data-modal-open="send-notification-modal" class="w-full btn btn-ghost btn-sm">{{ __('admin.vendors.send_notification') }}</button>
            </div>
        </x-card>

    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════════════
     MODALS
═══════════════════════════════════════════════════════════════════════════ --}}

{{-- Issue Strike --}}
<x-modal id="issue-strike-modal" title="{{ __('admin.vendors.issue_strike_title') }}" size="md">
    <form id="issue-strike-form">
        @csrf
        <input type="hidden" id="strike-vendor-id" value="{{ $vendor->id }}">
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('common.reason') }} <span class="text-danger-500">*</span></label>
                <select name="reason" class="form-input w-full" required>
                    <option value="">{{ __('admin.vendors.select_placeholder') }}</option>
                    <option value="late_shipment">{{ __('admin.vendors.late_shipment') }}</option>
                    <option value="poor_quality">{{ __('admin.vendors.poor_quality') }}</option>
                    <option value="customer_complaint">{{ __('admin.vendors.customer_complaint') }}</option>
                    <option value="policy_violation">{{ __('admin.vendors.policy_violation') }}</option>
                    <option value="other">{{ __('admin.vendors.other') }}</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.vendors.severity_label') }} <span class="text-danger-500">*</span></label>
                <select name="severity" class="form-input w-full" required>
                    <option value="">{{ __('admin.vendors.select_placeholder') }}</option>
                    <option value="minor">{{ __('admin.vendors.minor') }}</option>
                    <option value="major">{{ __('admin.vendors.major') }}</option>
                    <option value="critical">{{ __('admin.vendors.critical') }}</option>
                </select>
            </div>
            <div id="strike-warning" class="hidden rounded-lg bg-danger-50 border border-danger-200 px-3 py-2 text-sm text-danger-700"></div>
            <div id="strike-expiry-field">
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.vendors.expires_at_label') }}</label>
                <input type="date" name="expires_at" class="form-input w-full" min="{{ now()->addDay()->toDateString() }}">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.vendors.description') }}</label>
                <textarea name="description" class="form-input w-full resize-none" rows="3"></textarea>
            </div>
        </div>
        <x-slot name="footer">
            <button type="button" data-modal-close class="btn btn-ghost btn-sm">{{ __('admin.vendors.cancel') }}</button>
            <button type="submit" form="issue-strike-form" id="issue-strike-btn" class="btn btn-danger btn-sm">{{ __('admin.vendors.issue_strike_btn') }}</button>
        </x-slot>
    </form>
</x-modal>

{{-- Place Payout Hold --}}
<x-modal id="place-hold-modal" title="{{ __('admin.vendors.place_payout_hold_title') }}" size="sm">
    <form id="hold-form" data-vendor-id="{{ $vendor->id }}">
        @csrf
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('common.reason') }} <span class="text-danger-500">*</span></label>
            <textarea name="reason" class="form-input w-full resize-none" rows="3" required></textarea>
        </div>
        <x-slot name="footer">
            <button type="button" data-modal-close class="btn btn-ghost btn-sm">{{ __('admin.vendors.cancel') }}</button>
            <button type="submit" form="hold-form" class="btn btn-warning btn-sm">{{ __('admin.vendors.place_hold_btn2') }}</button>
        </x-slot>
    </form>
</x-modal>

{{-- Suspend --}}
<x-modal id="suspend-modal" title="{{ __('admin.vendors.suspend_vendor_title') }}" size="sm">
    <form id="suspend-form" data-vendor-id="{{ $vendor->id }}">
        @csrf
        <div class="space-y-4">
            <p class="text-sm text-gray-600">{{ \Illuminate\Support\Str::before(__('admin.vendors.suspending_notice'), ':name') }}<strong>{{ $vendor->store_name }}</strong>{{ \Illuminate\Support\Str::after(__('admin.vendors.suspending_notice'), ':name') }}</p>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('common.reason') }} <span class="text-danger-500">*</span></label>
                <textarea name="reason" class="form-input w-full resize-none" rows="3" required></textarea>
            </div>
        </div>
        <x-slot name="footer">
            <button type="button" data-modal-close class="btn btn-ghost btn-sm">{{ __('admin.vendors.cancel') }}</button>
            <button type="submit" form="suspend-form" class="btn btn-warning btn-sm">{{ __('admin.vendors.confirm_suspension') }}</button>
        </x-slot>
    </form>
</x-modal>

{{-- Reject Application --}}
<x-modal id="reject-modal" title="{{ __('admin.vendors.reject_application') }}" size="sm">
    <form id="reject-form" data-vendor-id="{{ $vendor->id }}">
        @csrf
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('common.reason') }} <span class="text-danger-500">*</span></label>
            <textarea name="reason" class="form-input w-full resize-none" rows="3" required></textarea>
        </div>
        <x-slot name="footer">
            <button type="button" data-modal-close class="btn btn-ghost btn-sm">{{ __('admin.vendors.cancel') }}</button>
            <button type="submit" form="reject-form" class="btn btn-danger btn-sm">{{ __('admin.vendors.confirm_rejection') }}</button>
        </x-slot>
    </form>
</x-modal>

{{-- Blacklist --}}
<x-modal id="blacklist-modal" title="{{ __('admin.vendors.blacklist_title') }}" size="sm">
    <form id="blacklist-form" data-vendor-id="{{ $vendor->id }}">
        @csrf
        <div class="rounded-lg bg-danger-50 border border-danger-200 px-4 py-3 text-sm text-danger-800 mb-4">
            {{ __('admin.vendors.blacklist_warning') }}
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('common.reason') }} <span class="text-danger-500">*</span></label>
            <textarea name="reason" class="form-input w-full resize-none" rows="3" required></textarea>
        </div>
        <x-slot name="footer">
            <button type="button" data-modal-close class="btn btn-ghost btn-sm">{{ __('admin.vendors.cancel') }}</button>
            <button type="submit" form="blacklist-form" class="btn btn-danger btn-sm">{{ __('admin.vendors.confirm_blacklist') }}</button>
        </x-slot>
    </form>
</x-modal>

{{-- Assign Manager --}}
<x-modal id="assign-manager-modal" title="{{ __('admin.vendors.assign_account_manager') }}" size="sm">
    <form id="assign-manager-form" data-vendor-id="{{ $vendor->id }}">
        @csrf
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.vendors.assign_to_admin') }} <span class="text-danger-500">*</span></label>
            <select name="admin_id" class="form-input w-full" required>
                <option value="">{{ __('admin.vendors.select_admin_placeholder') }}</option>
                @foreach($admins as $admin)
                    <option value="{{ $admin->id }}" {{ $vendor->account_manager_admin_id === $admin->id ? 'selected' : '' }}>
                        {{ $admin->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <x-slot name="footer">
            <button type="button" data-modal-close class="btn btn-ghost btn-sm">{{ __('admin.vendors.cancel') }}</button>
            <button type="submit" form="assign-manager-form" class="btn btn-primary btn-sm">{{ __('admin.vendors.assign') }}</button>
        </x-slot>
    </form>
</x-modal>

{{-- Reject Document --}}
<x-modal id="reject-document-modal" title="{{ __('admin.vendors.reject_document_title') }}" size="sm">
    <form id="reject-doc-form">
        @csrf
        <input type="hidden" id="reject-doc-id">
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.vendors.rejection_reason_label') }} <span class="text-danger-500">*</span></label>
                <select name="rejection_reason" class="form-input w-full" required>
                    <option value="">{{ __('admin.vendors.select_placeholder') }}</option>
                    <option value="document_expired">{{ __('admin.vendors.document_expired') }}</option>
                    <option value="poor_quality">{{ __('admin.vendors.poor_image_quality') }}</option>
                    <option value="incorrect_document">{{ __('admin.vendors.incorrect_document') }}</option>
                    <option value="information_mismatch">{{ __('admin.vendors.information_mismatch') }}</option>
                    <option value="other">{{ __('admin.vendors.other') }}</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.vendors.notes_label') }}</label>
                <textarea name="notes" class="form-input w-full resize-none" rows="2"></textarea>
            </div>
        </div>
        <x-slot name="footer">
            <button type="button" data-modal-close class="btn btn-ghost btn-sm">{{ __('admin.vendors.cancel') }}</button>
            <button type="submit" form="reject-doc-form" class="btn btn-danger btn-sm">{{ __('admin.vendors.reject_document_btn') }}</button>
        </x-slot>
    </form>
</x-modal>

{{-- Request Document --}}
<x-modal id="request-doc-modal" title="{{ __('admin.vendors.request_document_title') }}" size="md">
    <form id="request-doc-form" data-vendor-id="{{ $vendor->id }}">
        @csrf
        <div class="space-y-4">
            <p class="text-sm text-gray-600">{{ __('admin.vendors.request_document_notice') }}</p>
            <div class="space-y-2">
                @foreach(['business_license' => __('admin.vendors.business_license'), 'tax_certificate' => __('admin.vendors.tax_certificate'), 'owner_id' => __('admin.vendors.owner_id'), 'bank_proof' => __('admin.vendors.bank_proof'), 'vat_registration' => __('admin.vendors.vat_registration')] as $value => $label)
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="document_types[]" value="{{ $value }}" class="rounded border-gray-300 text-primary-600">
                        {{ $label }}
                    </label>
                @endforeach
            </div>
        </div>
        <x-slot name="footer">
            <button type="button" data-modal-close class="btn btn-ghost btn-sm">{{ __('admin.vendors.cancel') }}</button>
            <button type="submit" id="request-doc-btn" form="request-doc-form" class="btn btn-primary btn-sm">{{ __('admin.vendors.send_request') }}</button>
        </x-slot>
    </form>
</x-modal>

{{-- Send Notification --}}
<x-modal id="send-notification-modal" title="{{ __('admin.vendors.send_notification_title') }}" size="md">
    <form id="send-notification-form" data-vendor-id="{{ $vendor->id }}">
        @csrf
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.vendors.subject_label') }} <span class="text-danger-500">*</span></label>
                <input type="text" name="subject" class="form-input w-full" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.vendors.message_label') }} <span class="text-danger-500">*</span></label>
                <textarea name="message" class="form-input w-full resize-none" rows="5" required></textarea>
            </div>
        </div>
        <x-slot name="footer">
            <button type="button" data-modal-close class="btn btn-ghost btn-sm">{{ __('admin.vendors.cancel') }}</button>
            <button type="submit" form="send-notification-form" class="btn btn-primary btn-sm">{{ __('admin.vendors.send') }}</button>
        </x-slot>
    </form>
</x-modal>

@endsection

@push('scripts')
    @vite(['resources/js/admin/vendors.js'])
    <script>
        window.VENDOR_ID   = '{{ $vendor->id }}';
        window.VENDOR_NAME = @json($vendor->store_name);
    </script>
@endpush
