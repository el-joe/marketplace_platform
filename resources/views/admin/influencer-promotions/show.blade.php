@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/admin/influencer-promotions-show.js'])
@endpush

@section('title', __('admin.influencer_promotions.header_title'))

@section('content')

@php
    $statusColors = [
        'pending' => 'gray',
        'partially_accepted' => 'primary',
        'fully_accepted' => 'primary',
        'completed' => 'success',
        'cancelled' => 'gray',
    ];
    $itemStatusColors = [
        'pending' => 'gray',
        'accepted' => 'success',
        'declined' => 'danger',
        'timed_out' => 'gray',
        'reassigned' => 'primary',
        'cancelled' => 'gray',
    ];
    $listing = $promotionRequest->vendorListing ?? $promotionRequest->adminProductListing;
    $productName = $listing?->productVariant?->product?->name_en ?? '—';
@endphp

<div class="mb-6 flex items-start justify-between gap-4">
    <div>
        <a href="{{ route('admin.influencer-promotions.index') }}" class="text-sm text-gray-500 hover:text-gray-700">{{ __('admin.influencer_promotions.back') }}</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-1">{{ __('admin.influencer_promotions.header_title') }} #{{ strtoupper(substr($promotionRequest->id, 0, 8)) }}</h1>
        <div class="flex items-center gap-2 mt-2">
            <x-badge :color="$statusColors[$promotionRequest->status] ?? 'gray'">{{ __('admin.influencer_promotions.status_' . $promotionRequest->status) }}</x-badge>
            <span class="text-sm text-gray-500">{{ $productName }}</span>
        </div>
    </div>
    <a href="{{ route('admin.vendors.show', $promotionRequest->vendor_id) }}" class="text-sm text-primary-600 hover:underline">{{ $promotionRequest->vendor?->store_name }}</a>
</div>

<div class="grid grid-cols-12 gap-6">
    <div class="col-span-12 lg:col-span-8 space-y-6">
        <x-card title="{{ __('admin.influencer_promotions.request_details') }}">
            <dl class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-xs font-medium text-gray-500 uppercase">{{ __('admin.influencer_promotions.total_promotion_fee') }}</dt>
                    <dd class="mt-1 font-medium text-gray-900">{{ number_format($promotionRequest->total_promotion_fee) }} {{ $promotionRequest->currency }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-500 uppercase">{{ __('admin.influencer_promotions.fulfillment_model') }}</dt>
                    <dd class="mt-1 font-medium text-gray-900">{{ $promotionRequest->listing_fulfillment_model ? strtoupper($promotionRequest->listing_fulfillment_model) : '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-500 uppercase">{{ __('admin.influencer_promotions.fee_deduction_timing') }}</dt>
                    <dd class="mt-1 font-medium text-gray-900">{{ ucfirst(str_replace('_', ' ', $promotionRequest->fee_deduction_timing)) }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-500 uppercase">{{ __('admin.influencer_promotions.fee_deducted') }}</dt>
                    <dd class="mt-1 font-medium text-gray-900">
                        {{ $promotionRequest->fee_deducted ? __('admin.influencer_promotions.yes') : __('admin.influencer_promotions.no') }}
                        @if($promotionRequest->fee_deducted && $promotionRequest->fee_deducted_at)
                            <span class="text-xs text-gray-500">({{ $promotionRequest->fee_deducted_at->format('d M Y H:i') }})</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-500 uppercase">{{ __('admin.influencer_promotions.requires_warehouse_receipt') }}</dt>
                    <dd class="mt-1 font-medium text-gray-900">{{ $promotionRequest->requires_warehouse_receipt ? __('admin.influencer_promotions.yes') : __('admin.influencer_promotions.no') }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-500 uppercase">{{ __('admin.influencer_promotions.warehouse_receipt_confirmed') }}</dt>
                    <dd class="mt-1 font-medium text-gray-900">
                        {{ $promotionRequest->warehouse_receipt_confirmed ? __('admin.influencer_promotions.yes') : __('admin.influencer_promotions.no') }}
                        @if($promotionRequest->warehouse_receipt_confirmed && $promotionRequest->confirmedByAdmin)
                            <span class="text-xs text-gray-500">— {{ $promotionRequest->confirmedByAdmin->name }}</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-500 uppercase">{{ __('admin.influencer_promotions.created_by') }}</dt>
                    <dd class="mt-1 font-medium text-gray-900">{{ $promotionRequest->createdByAdmin?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-500 uppercase">{{ __('admin.influencer_promotions.created_at_column') }}</dt>
                    <dd class="mt-1 font-medium text-gray-900">{{ $promotionRequest->created_at->format('d M Y H:i') }}</dd>
                </div>
            </dl>

            @if($promotionRequest->vendor_note)
                <div class="mt-4 pt-4 border-t border-gray-100">
                    <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('admin.influencer_promotions.vendor_note') }}</dt>
                    <dd class="mt-1 text-sm text-gray-700">{{ $promotionRequest->vendor_note }}</dd>
                </div>
            @endif
        </x-card>

        <x-card title="{{ __('admin.influencer_promotions.slots_title') }}">
            <div class="overflow-x-auto" data-request-id="{{ $promotionRequest->id }}" id="promotion-slots">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-start">
                            <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">{{ __('admin.influencer_promotions.slot_influencer') }}</th>
                            <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">{{ __('admin.influencer_promotions.slot_status') }}</th>
                            <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">{{ __('admin.influencer_promotions.slot_fee') }}</th>
                            <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">{{ __('admin.influencer_promotions.slot_expires_at') }}</th>
                            <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">{{ __('admin.influencer_promotions.slot_responded_at') }}</th>
                            <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">{{ __('admin.influencer_promotions.slot_campaign') }}</th>
                            <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($promotionRequest->items as $item)
                            <tr>
                                <td class="py-2 pr-4 text-gray-900">{{ $item->marketer?->name ?? '—' }}</td>
                                <td class="py-2 pr-4">
                                    <x-badge :color="$itemStatusColors[$item->status] ?? 'gray'">{{ ucfirst(str_replace('_', ' ', $item->status)) }}</x-badge>
                                </td>
                                <td class="py-2 pr-4 text-gray-700">{{ number_format($item->slot_promotion_fee) }} {{ $promotionRequest->currency }}</td>
                                <td class="py-2 pr-4 text-gray-500">{{ $item->expires_at?->format('d M Y H:i') ?? '—' }}</td>
                                <td class="py-2 pr-4 text-gray-500">{{ $item->responded_at?->format('d M Y H:i') ?? '—' }}</td>
                                <td class="py-2 pr-4 text-gray-700">
                                    @if($item->resultingCampaign)
                                        <a href="{{ route('admin.marketers.campaigns.show', $item->resulting_campaign_id) }}" class="text-primary-600 hover:underline">#{{ strtoupper(substr($item->resulting_campaign_id, 0, 8)) }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="py-2 pr-4 text-end">
                                    @if(in_array($item->status, ['pending', 'timed_out'], true))
                                        <button type="button" class="btn btn-xs btn-secondary btn-force-reassign" data-item-id="{{ $item->id }}">{{ __('admin.influencer_promotions.force_reassign') }}</button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-4 text-center text-gray-400">—</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>

        <x-card title="{{ __('admin.influencer_promotions.samples_title') }}">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-start">
                            <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">{{ __('admin.influencer_promotions.slot_influencer') }}</th>
                            <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">{{ __('admin.influencer_promotions.sample_celebrity') }}</th>
                            <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">{{ __('admin.influencer_promotions.sample_admin') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($promotionRequest->items->where('status', 'accepted') as $item)
                            <tr>
                                <td class="py-2 pr-4 text-gray-900">{{ $item->marketer?->name ?? '—' }}</td>
                                <td class="py-2 pr-4 text-gray-700">1 {{ __('admin.influencer_promotions.sample_qty') }}</td>
                                <td class="py-2 pr-4 text-gray-700">1 {{ __('admin.influencer_promotions.sample_qty') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-4 text-center text-gray-400">—</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4 pt-4 border-t border-gray-100 flex items-center justify-between">
                <div>
                    <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('admin.influencer_promotions.admin_sample_debt') }}</dt>
                    <dd class="mt-1 font-medium text-gray-900">
                        @if($promotionRequest->admin_sample_debt > 0)
                            {{ number_format($promotionRequest->admin_sample_debt) }} {{ $promotionRequest->currency }}
                            <x-badge :color="$promotionRequest->admin_sample_debt_settled ? 'success' : 'gray'">
                                {{ $promotionRequest->admin_sample_debt_settled ? __('admin.influencer_promotions.debt_settled') : __('admin.influencer_promotions.debt_outstanding') }}
                            </x-badge>
                        @else
                            {{ __('admin.influencer_promotions.no_debt') }}
                        @endif
                    </dd>
                </div>
                @if($promotionRequest->admin_sample_debt > 0 && !$promotionRequest->admin_sample_debt_settled)
                    <button type="button" id="btn-settle-debt" class="btn btn-success btn-sm">{{ __('admin.influencer_promotions.settle_debt') }}</button>
                @endif
            </div>
        </x-card>

        <x-card title="{{ __('admin.influencer_promotions.financial_title') }}">
            @php
                $numCelebrities = $promotionRequest->items->count();
                $feePerCelebrity = $promotionRequest->promotion_fee_per_celebrity_snapshot;
            @endphp
            <dl class="space-y-3 text-sm">
                <div class="flex items-center justify-between">
                    <dt class="text-gray-500">{{ __('admin.influencer_promotions.fixed_admin_commission') }}</dt>
                    <dd class="font-medium text-gray-900">{{ number_format($promotionRequest->fixed_admin_commission_snapshot) }} {{ $promotionRequest->currency }}</dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-gray-500">{{ __('admin.influencer_promotions.promotion_fees', ['count' => $numCelebrities, 'fee' => number_format($feePerCelebrity), 'currency' => $promotionRequest->currency]) }}</dt>
                    <dd class="font-medium text-gray-900">{{ number_format($feePerCelebrity * $numCelebrities) }} {{ $promotionRequest->currency }}</dd>
                </div>
                <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                    <dt class="font-semibold text-gray-900">{{ __('admin.influencer_promotions.total') }}</dt>
                    <dd class="font-semibold text-gray-900">{{ number_format($promotionRequest->total_promotion_fee) }} {{ $promotionRequest->currency }}</dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-gray-500">{{ __('admin.influencer_promotions.fee_deducted') }}</dt>
                    <dd class="font-medium text-gray-900">{{ $promotionRequest->fee_deducted ? __('admin.influencer_promotions.yes') : __('admin.influencer_promotions.no') }}</dd>
                </div>
            </dl>
        </x-card>

        <x-card title="{{ __('admin.influencer_promotions.reassignment_log_title') }}">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-start">
                            <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">{{ __('admin.influencer_promotions.reassignment_from') }}</th>
                            <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">{{ __('admin.influencer_promotions.reassignment_to') }}</th>
                            <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">{{ __('admin.influencer_promotions.reassignment_reason') }}</th>
                            <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">{{ __('admin.influencer_promotions.reassignment_count_30d') }}</th>
                            <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">{{ __('admin.influencer_promotions.reassignment_triggered_by') }}</th>
                            <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">{{ __('admin.influencer_promotions.reassignment_at') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($reassignmentLogs as $log)
                            <tr>
                                <td class="py-2 pr-4 text-gray-700">{{ $log->fromMarketer?->name ?? '—' }}</td>
                                <td class="py-2 pr-4 text-gray-700">{{ $log->toMarketer?->name ?? '—' }}</td>
                                <td class="py-2 pr-4 text-gray-500">{{ ucfirst(str_replace('_', ' ', $log->reason)) }}</td>
                                <td class="py-2 pr-4 text-gray-500">{{ $log->to_marketer_request_count_30d }}</td>
                                <td class="py-2 pr-4 text-gray-500">{{ $log->triggeredByAdmin?->name ?? '—' }}</td>
                                <td class="py-2 pr-4 text-gray-500 whitespace-nowrap">{{ $log->created_at?->format('d M Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-4 text-center text-gray-400">{{ __('admin.influencer_promotions.no_reassignments') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>

    <div class="col-span-12 lg:col-span-4 space-y-4">
        <x-card title="{{ __('admin.influencer_promotions.request_details') }}">
            <div class="space-y-2" data-request-id="{{ $promotionRequest->id }}" id="promotion-actions">
                @if(!in_array($promotionRequest->status, ['completed', 'cancelled'], true))
                    <button type="button" id="btn-cancel-request" class="btn btn-danger btn-sm w-full">{{ __('admin.influencer_promotions.cancel') }}</button>
                @endif
                @if($promotionRequest->requires_warehouse_receipt && !$promotionRequest->warehouse_receipt_confirmed)
                    <button type="button" id="btn-confirm-warehouse" class="btn btn-success btn-sm w-full">{{ __('admin.influencer_promotions.confirm_warehouse') }}</button>
                @endif
            </div>
        </x-card>
    </div>
</div>

@endsection
