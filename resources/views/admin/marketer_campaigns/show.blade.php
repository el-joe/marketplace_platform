@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/select2.js'])
@endpush

@section('title', ($marketerCampaign->title ?: __('admin.marketer_campaigns.title')) . ' — ' . __('admin.marketer_campaigns.title'))

@section('content')

@php
    $statusColors = [
        'pending_admin'  => 'warning',
        'active'         => 'success',
        'rejected'       => 'danger',
        'auto_approved'  => 'primary',
        'done'           => 'gray',
    ];
    $product = $marketerCampaign->vendorListing?->productVariant?->product
        ?? $marketerCampaign->adminListing?->productVariant?->product;
    $isPending = $marketerCampaign->status === 'pending_admin';
@endphp

{{-- ─── Page header ─────────────────────────────────────────────────────────── --}}
<div class="mb-6 flex items-start justify-between gap-4">
    <div class="min-w-0">
        <h1 class="text-2xl font-bold text-gray-900 truncate">{{ $marketerCampaign->title ?: __('admin.marketer_campaigns.title') }}</h1>
        <div class="flex items-center gap-2 mt-1 flex-wrap">
            <x-badge :color="$statusColors[$marketerCampaign->status] ?? 'gray'">
                {{ __('admin.marketer_campaigns.status_' . $marketerCampaign->status) }}
            </x-badge>
            <span class="text-sm text-gray-500">{{ $marketerCampaign->vendor?->store_name }}</span>
        </div>
    </div>
    <a href="{{ route('admin.marketer-campaigns.index') }}" class="text-sm text-gray-500 hover:text-gray-700 flex-shrink-0 mt-1">
        {{ __('admin.marketer_campaigns.back') }}
    </a>
</div>

<div x-data="{ tab: 'details' }">
    {{-- Tab bar --}}
    <div class="flex gap-1 border-b border-gray-200 overflow-x-auto pb-px mb-6">
        @foreach([
            'details'     => __('admin.marketer_campaigns.tab_details'),
            'commission'  => __('admin.marketer_campaigns.tab_commission'),
            'marketers'   => __('admin.marketer_campaigns.tab_marketers'),
            'tiered'      => __('admin.marketer_campaigns.tab_tiered'),
            'invitations' => __('admin.marketer_campaigns.tab_invitations'),
            'conversions' => __('admin.marketer_campaigns.tab_conversions'),
            'samples'     => __('admin.marketer_campaigns.tab_samples'),
        ] as $key => $label)
            <button type="button"
                    @click="tab = '{{ $key }}'"
                    :class="tab === '{{ $key }}' ? 'border-b-2 border-primary-600 text-primary-700 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                    class="px-4 py-2 text-sm whitespace-nowrap transition-colors focus:outline-none">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- ── Details ──────────────────────────────────────────────────────── --}}
    <div x-show="tab === 'details'">
        <x-card title="{{ __('admin.marketer_campaigns.tab_details') }}">
            <div class="grid grid-cols-2 gap-x-6 gap-y-4 text-sm">
                @foreach([
                    __('admin.marketer_campaigns.vendor')                     => $marketerCampaign->vendor?->store_name ?? '—',
                    __('admin.marketer_campaigns.product')                    => $product?->name ?? '—',
                    __('admin.marketer_campaigns.country')                    => $marketerCampaign->country?->name_en ?? '—',
                    __('admin.marketer_campaigns.currency')                   => $marketerCampaign->currency ?? '—',
                    __('admin.marketer_campaigns.commission_type')            => $marketerCampaign->commission_type ? __('admin.marketer_campaigns.commission_type_' . $marketerCampaign->commission_type) : '—',
                    __('admin.marketer_campaigns.max_commission_budget')      => $marketerCampaign->max_commission_budget !== null ? number_format($marketerCampaign->max_commission_budget) : '—',
                    __('admin.marketer_campaigns.platform_commission_amount') => $marketerCampaign->platform_commission_amount !== null ? number_format($marketerCampaign->platform_commission_amount) : '—',
                    __('admin.marketer_campaigns.marketer_commission_amount') => $marketerCampaign->marketer_commission_amount !== null ? number_format($marketerCampaign->marketer_commission_amount) : '—',
                    __('admin.marketer_campaigns.platform_sample_qty')        => $marketerCampaign->platform_sample_qty_snapshot ?? 0,
                    __('admin.marketer_campaigns.per_marketer_sample_qty')    => $marketerCampaign->per_marketer_sample_qty_snapshot ?? 0,
                    __('admin.marketer_campaigns.auto_approve_at')            => $marketerCampaign->auto_approve_at?->format('d M Y H:i') ?? '—',
                    __('admin.marketer_campaigns.reviewed_by')                => $marketerCampaign->reviewedBy?->name ?? '—',
                    __('admin.marketer_campaigns.reviewed_at')                => $marketerCampaign->reviewed_at?->format('d M Y H:i') ?? '—',
                ] as $label => $value)
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ $label }}</dt>
                        <dd class="mt-0.5 font-medium text-gray-900">{{ $value }}</dd>
                    </div>
                @endforeach
                @if($marketerCampaign->notes)
                    <div class="col-span-2">
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('admin.marketer_campaigns.notes') }}</dt>
                        <dd class="mt-0.5 text-gray-700">{{ $marketerCampaign->notes }}</dd>
                    </div>
                @endif
                @if($marketerCampaign->status === 'rejected' && $marketerCampaign->rejection_reason)
                    <div class="col-span-2">
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('admin.marketer_campaigns.rejection_reason') }}</dt>
                        <dd class="mt-0.5 text-danger-700">{{ $marketerCampaign->rejection_reason }}</dd>
                    </div>
                @endif
            </div>
        </x-card>
    </div>

    {{-- ── Approval ──────────────────────────────────────────────────────── --}}
    <div x-show="tab === 'commission'">
        <x-card title="{{ __('admin.marketer_campaigns.approve_form_title') }}">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-gray-50 rounded-lg p-3 text-center">
                    <div class="text-xs text-gray-500 mb-1">{{ __('admin.marketer_campaigns.commission_type') }}</div>
                    <div class="font-semibold text-gray-800">{{ $marketerCampaign->commission_type ? __('admin.marketer_campaigns.commission_type_' . $marketerCampaign->commission_type) : '—' }}</div>
                </div>
                <div class="bg-gray-50 rounded-lg p-3 text-center">
                    <div class="text-xs text-gray-500 mb-1">{{ __('admin.marketer_campaigns.max_commission_budget') }}</div>
                    <div class="font-semibold text-gray-800">
                        {{ $marketerCampaign->max_commission_budget !== null ? number_format($marketerCampaign->max_commission_budget) : '—' }}
                        {{ $marketerCampaign->currency }}
                    </div>
                </div>
                <div class="bg-gray-50 rounded-lg p-3 text-center">
                    <div class="text-xs text-gray-500 mb-1">{{ __('admin.marketer_campaigns.influencer_commission_label') }}</div>
                    <div class="font-semibold text-green-700">
                        {{ number_format($commissionSetting?->influencer_commission_amount ?? 0) }}
                        {{ $marketerCampaign->currency }}
                    </div>
                </div>
                <div class="bg-gray-50 rounded-lg p-3 text-center">
                    <div class="text-xs text-gray-500 mb-1">{{ __('admin.marketer_campaigns.affiliate_commission_label') }}</div>
                    <div class="font-semibold text-green-700">
                        {{ number_format($commissionSetting?->affiliate_commission_amount ?? 0) }}
                        {{ $marketerCampaign->currency }}
                    </div>
                </div>
            </div>

            @unless($isPending)
                <p class="text-sm text-gray-500">{{ __('admin.marketer_campaigns.not_pending_notice') }}</p>
            @endunless
        </x-card>
    </div>

    {{-- ── Marketers (single form) ──────────────────────────────────────── --}}
    <form method="POST" action="{{ route('admin.marketer-campaigns.approve', $marketerCampaign) }}">
        @csrf

        <div x-show="tab === 'marketers'">
            <x-card title="{{ __('admin.marketer_campaigns.tab_marketers') }}">
                @if($isPending)
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.marketer_campaigns.select_marketers_label') }}</label>
                    @if($availableMarketers->isEmpty())
                        <p class="text-sm text-gray-400">{{ __('admin.marketer_campaigns.no_marketers_available') }}</p>
                    @else
                        @if(!empty($marketerCampaign->requested_marketer_vendor_ids))
                            <p class="text-xs text-gray-400 mb-2">{{ __('admin.marketer_campaigns.vendor_requested_note') }}</p>
                        @endif
                        <select name="marketer_vendor_ids[]" multiple data-select2-init class="form-input w-full" required>
                            @foreach($availableMarketers as $marketer)
                                <option value="{{ $marketer->id }}"
                                    @selected(in_array($marketer->id, $marketerCampaign->requested_marketer_vendor_ids ?? []))>
                                    {{ $marketer->store_name }}
                                    ({{ ['influencer' => 'إنفلوينسر', 'affiliate' => 'أفيلييت'][$marketer->marketer_type] ?? '—' }})
                                    — {{ __('admin.marketer_campaigns.accepted_campaigns') }}: {{ $marketer->accepted_campaigns }}
                                </option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm mt-4">{{ __('admin.marketer_campaigns.approve_btn') }}</button>
                    @endif
                @else
                    <p class="text-sm text-gray-500">{{ __('admin.marketer_campaigns.not_pending_notice') }}</p>
                @endif
            </x-card>
        </div>
    </form>

    @if($isPending)
        <div x-show="tab === 'commission'" class="mt-4">
            <x-card title="{{ __('admin.marketer_campaigns.reject_form_title') }}">
                <form method="POST" action="{{ route('admin.marketer-campaigns.reject', $marketerCampaign) }}">
                    @csrf
                    <x-form.textarea name="rejection_reason" label="{{ __('admin.marketer_campaigns.rejection_reason_label') }}" rows="3" required />
                    <button type="submit" class="btn btn-danger btn-sm mt-3">{{ __('admin.marketer_campaigns.reject_btn') }}</button>
                </form>
            </x-card>
        </div>
    @endif

    {{-- ── Tiered Rules ─────────────────────────────────────────────────── --}}
    <div x-show="tab === 'tiered'">
        <x-card title="{{ __('admin.marketer_campaigns.tab_tiered') }}">
            @if($marketerCampaign->commission_type === 'tiered' && $marketerCampaign->tieredRules->isNotEmpty())
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 border-b border-gray-100">
                            <th class="py-2 pr-4">{{ __('admin.marketer_campaigns.from_sale_number') }}</th>
                            <th class="py-2 pr-4">{{ __('admin.marketer_campaigns.platform_commission_amount') }}</th>
                            <th class="py-2 pr-4">{{ __('admin.marketer_campaigns.currency') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($marketerCampaign->tieredRules as $rule)
                            <tr class="border-b border-gray-50">
                                <td class="py-2 pr-4">{{ $rule->from_sale_number }}</td>
                                <td class="py-2 pr-4">{{ number_format($rule->commission_amount) }}</td>
                                <td class="py-2 pr-4">{{ $rule->currency }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="text-sm text-gray-400">{{ __('admin.marketer_campaigns.no_tiered_rules') }}</p>
            @endif
        </x-card>
    </div>

    {{-- ── Invitations ──────────────────────────────────────────────────── --}}
    <div x-show="tab === 'invitations'">
        <x-card title="{{ __('admin.marketer_campaigns.tab_invitations') }}">
            @if($marketerCampaign->invitations->isEmpty())
                <p class="text-sm text-gray-400">{{ __('admin.marketer_campaigns.no_invitations') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500 border-b border-gray-100">
                                <th class="py-2 pr-4">{{ __('admin.marketer_campaigns.invitation_marketer') }}</th>
                                <th class="py-2 pr-4">{{ __('admin.marketer_campaigns.invitation_status') }}</th>
                                <th class="py-2 pr-4">{{ __('admin.marketer_campaigns.invitation_referral_code') }}</th>
                                <th class="py-2 pr-4">{{ __('admin.marketer_campaigns.invitation_referral_link') }}</th>
                                <th class="py-2 pr-4">{{ __('admin.marketer_campaigns.invitation_qr_code') }}</th>
                                <th class="py-2 pr-4">{{ __('admin.marketer_campaigns.invitation_sent_at') }}</th>
                                <th class="py-2 pr-4">{{ __('admin.marketer_campaigns.invitation_responded_at') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($marketerCampaign->invitations as $invitation)
                                <tr class="border-b border-gray-50">
                                    <td class="py-2 pr-4 font-medium text-gray-900">{{ $invitation->marketer?->store_name ?? '—' }}</td>
                                    <td class="py-2 pr-4"><x-badge color="gray">{{ $invitation->status }}</x-badge></td>
                                    <td class="py-2 pr-4 font-mono text-xs">{{ $invitation->referral_code ?? '—' }}</td>
                                    <td class="py-2 pr-4">
                                        @if($invitation->referral_link)
                                            <a href="{{ $invitation->referral_link }}" target="_blank" class="text-primary-600 hover:underline text-xs">{{ $invitation->referral_link }}</a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="py-2 pr-4">
                                        @if($invitation->qr_code_path)
                                            <img src="{{ \Illuminate\Support\Facades\Storage::url($invitation->qr_code_path) }}" class="w-10 h-10 object-contain" alt="QR">
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="py-2 pr-4">{{ $invitation->created_at?->format('d M Y H:i') }}</td>
                                    <td class="py-2 pr-4">{{ $invitation->responded_at?->format('d M Y H:i') ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>
    </div>

    {{-- ── Conversions ──────────────────────────────────────────────────── --}}
    <div x-show="tab === 'conversions'">
        <x-card title="{{ __('admin.marketer_campaigns.tab_conversions') }}">
            @if($marketerCampaign->conversions->isEmpty())
                <p class="text-sm text-gray-400">{{ __('admin.marketer_campaigns.no_conversions') }}</p>
            @else
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 border-b border-gray-100">
                            <th class="py-2 pr-4">{{ __('admin.marketer_campaigns.conversion_order') }}</th>
                            <th class="py-2 pr-4">{{ __('admin.marketer_campaigns.conversion_commission') }}</th>
                            <th class="py-2 pr-4">{{ __('admin.marketer_campaigns.conversion_clicked_at') }}</th>
                            <th class="py-2 pr-4">{{ __('admin.marketer_campaigns.conversion_paid_at') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($marketerCampaign->conversions as $conversion)
                            <tr class="border-b border-gray-50">
                                <td class="py-2 pr-4">{{ $conversion->order?->order_number ?? $conversion->order_id }}</td>
                                <td class="py-2 pr-4">{{ number_format($conversion->commission_amount) }} {{ $conversion->currency }}</td>
                                <td class="py-2 pr-4">{{ $conversion->referral_clicked_at?->format('d M Y H:i') ?? '—' }}</td>
                                <td class="py-2 pr-4">
                                    @if($conversion->paid_at)
                                        {{ $conversion->paid_at->format('d M Y H:i') }}
                                    @else
                                        <x-badge color="warning">{{ __('admin.marketer_campaigns.conversion_not_paid') }}</x-badge>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </x-card>
    </div>

    {{-- ── Samples ───────────────────────────────────────────────────────── --}}
    <div x-show="tab === 'samples'">
        <x-card title="{{ __('admin.marketer_campaigns.tab_samples') }}">
            @if($marketerCampaign->samples->isEmpty())
                <p class="text-sm text-gray-400">{{ __('admin.marketer_campaigns.no_samples') }}</p>
            @else
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 border-b border-gray-100">
                            <th class="py-2 pr-4">{{ __('admin.marketer_campaigns.sample_owner') }}</th>
                            <th class="py-2 pr-4">{{ __('admin.marketer_campaigns.sample_quantity') }}</th>
                            <th class="py-2 pr-4">{{ __('admin.marketer_campaigns.sample_status') }}</th>
                            <th class="py-2 pr-4">{{ __('admin.marketer_campaigns.sample_dispatched_at') }}</th>
                            <th class="py-2 pr-4">{{ __('admin.marketer_campaigns.sample_delivered_at') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($marketerCampaign->samples as $sample)
                            <tr class="border-b border-gray-50">
                                <td class="py-2 pr-4">
                                    {{ $sample->sample_owner === 'platform' ? __('admin.marketer_campaigns.sample_owner_platform') : __('admin.marketer_campaigns.sample_owner_marketer') }}
                                </td>
                                <td class="py-2 pr-4">{{ $sample->quantity }}</td>
                                <td class="py-2 pr-4"><x-badge color="gray">{{ $sample->status }}</x-badge></td>
                                <td class="py-2 pr-4">{{ $sample->dispatched_at?->format('d M Y H:i') ?? '—' }}</td>
                                <td class="py-2 pr-4">{{ $sample->delivered_at?->format('d M Y H:i') ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </x-card>
    </div>
</div>

@endsection
