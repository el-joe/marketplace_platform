@extends('layouts.admin')

@section('title', __('admin.packaging_supplies.request_number_prefix') . $req->request_number)

@section('content')

    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <a href="{{ route('admin.packaging-supplies.requests') }}" class="text-sm text-gray-500 hover:text-gray-700">{{ __('admin.packaging_supplies.requests_back') }}</a>
            <h1 class="text-2xl font-bold text-gray-900 mt-1">{{ __('admin.packaging_supplies.request_number_prefix') }}{{ $req->request_number }}</h1>
        </div>
        <span class="badge text-sm px-3 py-1 {{ $req->statusBadgeClass() }}">{{ $req->status->label() }}</span>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4">{{ session('success') }}</div>
    @endif

    <div class="grid grid-cols-3 gap-6">

        {{-- ─── Items ─────────────────────────────────────────────────────────── --}}
        <div class="col-span-2 space-y-4">
            <div class="card overflow-hidden">
                <div class="px-5 py-3 border-b bg-gray-50 font-medium text-sm text-gray-700">{{ __('admin.packaging_supplies.requested_items') }}</div>
                <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="th">{{ __('admin.packaging_supplies.supply') }}</th>
                            <th class="th">{{ __('admin.packaging_supplies.type') }}</th>
                            <th class="th">{{ __('admin.packaging_supplies.unit_cost') }}</th>
                            <th class="th">{{ __('admin.packaging_supplies.qty') }}</th>
                            <th class="th text-end">{{ __('admin.packaging_supplies.line_total') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @foreach($req->items as $item)
                            <tr>
                                <td class="td font-medium">{{ $item->supply->name_en }}</td>
                                <td class="td">
                                    <span class="badge {{ $item->supply->typeBadgeClass() }}">{{ $item->supply->type->label() }}</span>
                                </td>
                                <td class="td">{{ $item->supply->unit_cost_formatted }}</td>
                                <td class="td">{{ number_format($item->quantity) }}</td>
                                <td class="td text-end font-medium">{{ $item->line_total_formatted }}</td>
                            </tr>
                        @endforeach
                        <tr class="bg-gray-50">
                            <td colspan="4" class="td text-end font-semibold text-gray-700">{{ __('admin.packaging_supplies.total') }}</td>
                            <td class="td text-end font-bold text-gray-900">{{ $req->total_cost_formatted }}</td>
                        </tr>
                        @if(isset($req->delivery_fee_cents))
                            <tr>
                                <td colspan="4" class="td text-end font-medium text-gray-600">{{ __('admin.packaging_supplies.delivery_fee') }}</td>
                                <td class="td text-end font-medium text-gray-900">{{ number_format($req->delivery_fee_cents / 100, 2) }}</td>
                            </tr>
                            <tr>
                                <td colspan="4" class="td text-end font-semibold text-gray-700">{{ __('admin.packaging_supplies.grand_total') }}</td>
                                <td class="td text-end font-bold text-gray-900">{{ number_format(($req->total_cost_cents + $req->delivery_fee_cents) / 100, 2) }}</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
                </div>
            </div>

            @if($req->notes)
                <div class="card p-5">
                    <p class="text-sm font-medium text-gray-700 mb-1">{{ __('admin.packaging_supplies.vendor_notes') }}</p>
                    <p class="text-sm text-gray-600 whitespace-pre-line">{{ $req->notes }}</p>
                </div>
            @endif

            {{-- ─── Status timeline ────────────────────────────────────────────── --}}
            <div class="card p-5">
                <p class="text-sm font-medium text-gray-700 mb-4">{{ __('admin.packaging_supplies.status_timeline') }}</p>
                @php
                    $timeline = collect([
                        ['label' => __('admin.packaging_supplies.submitted'), 'at' => $req->created_at, 'done' => true],
                        ['label' => __('admin.packaging_supplies.approved'), 'at' => $req->approved_at, 'done' => in_array($req->status, [\App\Enums\PackagingSupplyRequestStatus::Approved, \App\Enums\PackagingSupplyRequestStatus::Shipped, \App\Enums\PackagingSupplyRequestStatus::Delivered], true)],
                        ['label' => __('admin.packaging_supplies.shipped'), 'at' => null, 'done' => in_array($req->status, [\App\Enums\PackagingSupplyRequestStatus::Shipped, \App\Enums\PackagingSupplyRequestStatus::Delivered], true)],
                        ['label' => __('admin.packaging_supplies.delivered'), 'at' => null, 'done' => $req->status === \App\Enums\PackagingSupplyRequestStatus::Delivered],
                    ]);
                    if ($req->status === \App\Enums\PackagingSupplyRequestStatus::Rejected) {
                        $timeline = collect([
                            ['label' => __('admin.packaging_supplies.submitted'), 'at' => $req->created_at, 'done' => true],
                            ['label' => __('admin.packaging_supplies.rejected'), 'at' => $req->updated_at, 'done' => true],
                        ]);
                    }
                @endphp
                @foreach($timeline as $step)
                    <div class="relative pl-6 pb-4 last:pb-0">
                        <span class="absolute left-0 top-1.5 w-2.5 h-2.5 rounded-full {{ $step['done'] ? 'bg-primary-500' : 'bg-gray-200' }} ring-2 ring-white"></span>
                        @if(!$loop->last)
                            <span class="absolute left-1 top-4 bottom-0 w-px bg-gray-200"></span>
                        @endif
                        <div class="flex items-center justify-between">
                            <span class="text-sm {{ $step['done'] ? 'text-gray-900 font-medium' : 'text-gray-400' }}">{{ $step['label'] }}</span>
                            @if($step['at'])
                                <span class="text-xs text-gray-400">{{ $step['at']->format('d M Y, H:i') }}</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ─── Sidebar ─────────────────────────────────────────────────────── --}}
        <div class="space-y-4">
            <div class="card p-5 space-y-3 text-sm">
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wide mb-0.5">{{ __('admin.packaging_supplies.vendor') }}</p>
                    <p class="font-medium text-gray-900">{{ $req->vendor->store_name }}</p>
                    <p class="text-gray-500">{{ $req->vendor->email }}</p>
                </div>
                @if($req->warehouse)
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wide mb-0.5">{{ __('admin.packaging_supplies.delivery_warehouse') }}</p>
                        <p class="text-gray-700">{{ $req->warehouse->name }}</p>
                    </div>
                @endif
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wide mb-0.5">{{ __('admin.packaging_supplies.submitted') }}</p>
                    <p class="text-gray-700">{{ $req->created_at->format('d M Y, H:i') }}</p>
                </div>
                @if($req->approvedBy)
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wide mb-0.5">{{ __('admin.packaging_supplies.approved_by') }}</p>
                        <p class="text-gray-700">{{ $req->approvedBy->name }} {{ __('admin.packaging_supplies.on') }} {{ $req->approved_at->format('d M Y') }}</p>
                    </div>
                @endif
            </div>

            {{-- Actions --}}
            @if($req->isPending())
                <div class="card p-5 space-y-3">
                    <p class="text-sm font-medium text-gray-700">{{ __('admin.packaging_supplies.actions') }}</p>
                    <form method="POST" action="{{ route('admin.packaging-supplies.approve-request', $req) }}">
                        @csrf @method('PATCH')
                        <button class="btn btn-primary w-full">{{ __('admin.packaging_supplies.approve_request') }}</button>
                    </form>
                    <button type="button" class="btn btn-danger w-full" data-modal-open="reject-request-modal">{{ __('admin.packaging_supplies.reject') }}</button>
                </div>
            @elseif($req->status === \App\Enums\PackagingSupplyRequestStatus::Approved)
                <div class="card p-5">
                    <p class="text-sm font-medium text-gray-700 mb-3">{{ __('admin.packaging_supplies.update_status') }}</p>
                    <form method="POST" action="{{ route('admin.packaging-supplies.mark-shipped', $req) }}">
                        @csrf @method('PATCH')
                        <button class="btn btn-secondary w-full">{{ __('admin.packaging_supplies.mark_shipped') }}</button>
                    </form>
                </div>
            @elseif($req->status === \App\Enums\PackagingSupplyRequestStatus::Shipped)
                <div class="card p-5">
                    <p class="text-sm font-medium text-gray-700 mb-3">{{ __('admin.packaging_supplies.update_status') }}</p>
                    <form method="POST" action="{{ route('admin.packaging-supplies.mark-delivered', $req) }}">
                        @csrf @method('PATCH')
                        <button class="btn btn-primary w-full">{{ __('admin.packaging_supplies.mark_delivered') }}</button>
                    </form>
                </div>
            @endif
        </div>

    </div>

    {{-- Reject reason modal --}}
    <x-modal id="reject-request-modal" title="{{ __('admin.packaging_supplies.reject_request') }}" size="sm">
        <form id="reject-request-form" method="POST" action="{{ route('admin.packaging-supplies.reject-request', $req) }}">
            @csrf @method('PATCH')
            <label class="label">{{ __('admin.packaging_supplies.rejection_reason') }}</label>
            <textarea name="reason" rows="4" class="input w-full" placeholder="{{ __('admin.packaging_supplies.rejection_reason_placeholder') }}"></textarea>
        </form>
        <x-slot:footer>
            <button type="button" data-modal-close class="btn btn-secondary">{{ __('common.cancel') }}</button>
            <button type="submit" form="reject-request-form" class="btn btn-danger">{{ __('admin.packaging_supplies.reject') }}</button>
        </x-slot:footer>
    </x-modal>

@endsection
