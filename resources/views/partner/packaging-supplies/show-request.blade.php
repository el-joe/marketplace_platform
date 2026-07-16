@extends('layouts.partner')

@section('title', __('partner.packaging_supplies.request_title', ['number' => $req->request_number]))

@section('content')

    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <a href="{{ route('partner.packaging-supplies.my-requests') }}" class="text-sm text-gray-500 hover:text-gray-700">{{ __('partner.packaging_supplies.my_requests_link') }}</a>
            <h1 class="text-2xl font-bold text-gray-900 mt-1">{{ __('partner.packaging_supplies.request_number') }}{{ $req->request_number }}</h1>
        </div>
        <span class="badge text-sm px-3 py-1 {{ $req->statusBadgeClass() }}">{{ ucfirst($req->status->value) }}</span>
    </div>

    <div class="grid grid-cols-3 gap-6">

        {{-- ─── Items ─────────────────────────────────────────────────────────── --}}
        <div class="col-span-2">
            <div class="card overflow-hidden">
                <div class="px-5 py-3 border-b bg-gray-50 font-medium text-sm text-gray-700">{{ __('partner.packaging_supplies.items') }}</div>
                <table class="min-w-full divide-y divide-gray-100 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="th">{{ __('partner.packaging_supplies.supply') }}</th>
                            <th class="th">{{ __('partner.packaging_supplies.type') }}</th>
                            <th class="th">{{ __('partner.packaging_supplies.unit_cost') }}</th>
                            <th class="th">{{ __('partner.packaging_supplies.qty') }}</th>
                            <th class="th text-right">{{ __('partner.packaging_supplies.total') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @foreach($req->items as $item)
                            <tr>
                                <td class="td font-medium">{{ $item->supply->name_en }}</td>
                                <td class="td">
                                    <span class="badge {{ $item->supply->typeBadgeClass() }}">{{ ucfirst($item->supply->type->value) }}</span>
                                </td>
                                <td class="td">{{ $item->supply->unit_cost_formatted }}</td>
                                <td class="td">{{ number_format($item->quantity) }}</td>
                                <td class="td text-right font-medium">{{ $item->line_total_formatted }}</td>
                            </tr>
                        @endforeach
                        <tr class="bg-gray-50">
                            <td colspan="4" class="td text-right text-gray-700">{{ __('partner.packaging_supplies.items_subtotal') }}</td>
                            <td class="td text-right font-medium text-gray-900">{{ $req->total_cost_formatted }}</td>
                        </tr>
                        <tr class="bg-gray-50">
                            <td colspan="4" class="td text-right text-gray-700">{{ __('partner.packaging_supplies.delivery_fee') }}</td>
                            <td class="td text-right font-medium text-gray-900">{{ $req->delivery_fee_formatted }}</td>
                        </tr>
                        <tr class="bg-gray-50">
                            <td colspan="4" class="td text-right font-semibold text-gray-700">{{ __('partner.packaging_supplies.grand_total') }}</td>
                            <td class="td text-right font-bold text-gray-900">{{ $req->grand_total_formatted }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ─── Details ────────────────────────────────────────────────────── --}}
        <div class="space-y-4">
            @php
                $isRejected = $req->status === \App\Enums\PackagingSupplyRequestStatus::Rejected;
                $timelineSteps = ['pending', 'approved', 'shipped', 'delivered'];
                $currentIndex = array_search($req->status->value, $timelineSteps, true);
            @endphp

            @if(!$isRejected)
                <div class="card p-5">
                    <p class="text-xs text-gray-400 uppercase tracking-wide mb-3">{{ __('partner.packaging_supplies.request_status') }}</p>
                    <ol class="space-y-3">
                        @foreach($timelineSteps as $i => $step)
                            <li class="flex items-center gap-3">
                                <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold
                                    {{ $i <= $currentIndex ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-400' }}">
                                    {{ $i <= $currentIndex ? '✓' : '' }}
                                </span>
                                <span class="text-sm {{ $i <= $currentIndex ? 'text-gray-900 font-medium' : 'text-gray-400' }}">
                                    {{ ucfirst($step) }}
                                </span>
                            </li>
                        @endforeach
                    </ol>
                </div>
            @endif

            <div class="card p-5 text-sm space-y-3">
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wide mb-0.5">{{ __('common.status') }}</p>
                    <span class="badge {{ $req->statusBadgeClass() }}">{{ ucfirst($req->status->value) }}</span>
                </div>
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wide mb-0.5">{{ __('partner.packaging_supplies.submitted') }}</p>
                    <p class="text-gray-700">{{ $req->created_at->format('d M Y, H:i') }}</p>
                </div>
                @if($req->warehouse)
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wide mb-0.5">{{ __('partner.packaging_supplies.delivery_warehouse') }}</p>
                        <p class="text-gray-700">{{ $req->warehouse->name }}</p>
                    </div>
                @endif
                @if($req->notes)
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wide mb-0.5">{{ __('partner.packaging_supplies.notes') }}</p>
                        <p class="text-gray-600">{{ $req->notes }}</p>
                    </div>
                @endif
            </div>

            @if($req->status === \App\Enums\PackagingSupplyRequestStatus::Delivered)
                <div class="card p-5 bg-green-50 border-green-200 text-sm text-green-800">
                    {{ __('partner.packaging_supplies.delivered_message') }}
                </div>
            @elseif($req->status === \App\Enums\PackagingSupplyRequestStatus::Rejected)
                <div class="card p-5 bg-red-50 border-red-200 text-sm text-red-800">
                    {{ __('partner.packaging_supplies.rejected_message') }}
                </div>
            @endif
        </div>

    </div>

@endsection
