@extends('layouts.admin')

@section('title', __('admin.packaging_supplies.request_number_prefix') . $req->request_number)

@section('content')

    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <a href="{{ route('admin.packaging-supplies.requests') }}" class="text-sm text-gray-500 hover:text-gray-700">{{ __('admin.packaging_supplies.requests_back') }}</a>
            <h1 class="text-2xl font-bold text-gray-900 mt-1">{{ __('admin.packaging_supplies.request_number_prefix') }}{{ $req->request_number }}</h1>
        </div>
        <span class="badge text-sm px-3 py-1 {{ $req->statusBadgeClass() }}">{{ ucfirst($req->status) }}</span>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4">{{ session('success') }}</div>
    @endif

    <div class="grid grid-cols-3 gap-6">

        {{-- ─── Items ─────────────────────────────────────────────────────────── --}}
        <div class="col-span-2 space-y-4">
            <div class="card overflow-hidden">
                <div class="px-5 py-3 border-b bg-gray-50 font-medium text-sm text-gray-700">{{ __('admin.packaging_supplies.requested_items') }}</div>
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
                                    <span class="badge {{ $item->supply->typeBadgeClass() }}">{{ ucfirst($item->supply->type) }}</span>
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
                    </tbody>
                </table>
            </div>

            @if($req->notes)
                <div class="card p-5">
                    <p class="text-sm font-medium text-gray-700 mb-1">{{ __('admin.packaging_supplies.vendor_notes') }}</p>
                    <p class="text-sm text-gray-600">{{ $req->notes }}</p>
                </div>
            @endif
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
                    <form method="POST" action="{{ route('admin.packaging-supplies.reject-request', $req) }}">
                        @csrf @method('PATCH')
                        <button class="btn btn-danger w-full" onclick="return confirm('{{ __('admin.packaging_supplies.reject_request_confirm') }}')">{{ __('admin.packaging_supplies.reject') }}</button>
                    </form>
                </div>
            @elseif(in_array($req->status, ['approved', 'shipped']))
                <div class="card p-5">
                    <p class="text-sm font-medium text-gray-700 mb-3">{{ __('admin.packaging_supplies.update_status') }}</p>
                    <form method="POST" action="{{ route('admin.packaging-supplies.update-request-status', $req) }}">
                        @csrf @method('PATCH')
                        <div class="flex gap-2">
                            @if($req->status === 'approved')
                                <button name="status" value="shipped" class="btn btn-secondary flex-1">{{ __('admin.packaging_supplies.mark_shipped') }}</button>
                            @endif
                            @if($req->status === 'shipped')
                                <button name="status" value="delivered" class="btn btn-primary flex-1">{{ __('admin.packaging_supplies.mark_delivered') }}</button>
                            @endif
                        </div>
                    </form>
                </div>
            @endif
        </div>

    </div>

@endsection
