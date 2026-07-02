@extends('layouts.admin')

@section('title', 'Request ' . $req->request_number)

@section('content')

    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <a href="{{ route('admin.packaging-supplies.requests') }}" class="text-sm text-gray-500 hover:text-gray-700">← Requests</a>
            <h1 class="text-2xl font-bold text-gray-900 mt-1">Request #{{ $req->request_number }}</h1>
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
                <div class="px-5 py-3 border-b bg-gray-50 font-medium text-sm text-gray-700">Requested Items</div>
                <table class="min-w-full divide-y divide-gray-100 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="th">Supply</th>
                            <th class="th">Type</th>
                            <th class="th">Unit Cost</th>
                            <th class="th">Qty</th>
                            <th class="th text-right">Line Total</th>
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
                                <td class="td text-right font-medium">{{ $item->line_total_formatted }}</td>
                            </tr>
                        @endforeach
                        <tr class="bg-gray-50">
                            <td colspan="4" class="td text-right font-semibold text-gray-700">Total</td>
                            <td class="td text-right font-bold text-gray-900">{{ $req->total_cost_formatted }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            @if($req->notes)
                <div class="card p-5">
                    <p class="text-sm font-medium text-gray-700 mb-1">Vendor Notes</p>
                    <p class="text-sm text-gray-600">{{ $req->notes }}</p>
                </div>
            @endif
        </div>

        {{-- ─── Sidebar ─────────────────────────────────────────────────────── --}}
        <div class="space-y-4">
            <div class="card p-5 space-y-3 text-sm">
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wide mb-0.5">Vendor</p>
                    <p class="font-medium text-gray-900">{{ $req->vendor->store_name }}</p>
                    <p class="text-gray-500">{{ $req->vendor->email }}</p>
                </div>
                @if($req->warehouse)
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wide mb-0.5">Delivery Warehouse</p>
                        <p class="text-gray-700">{{ $req->warehouse->name }}</p>
                    </div>
                @endif
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wide mb-0.5">Submitted</p>
                    <p class="text-gray-700">{{ $req->created_at->format('d M Y, H:i') }}</p>
                </div>
                @if($req->approvedBy)
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wide mb-0.5">Approved by</p>
                        <p class="text-gray-700">{{ $req->approvedBy->name }} on {{ $req->approved_at->format('d M Y') }}</p>
                    </div>
                @endif
            </div>

            {{-- Actions --}}
            @if($req->isPending())
                <div class="card p-5 space-y-3">
                    <p class="text-sm font-medium text-gray-700">Actions</p>
                    <form method="POST" action="{{ route('admin.packaging-supplies.approve-request', $req) }}">
                        @csrf @method('PATCH')
                        <button class="btn btn-primary w-full">Approve Request</button>
                    </form>
                    <form method="POST" action="{{ route('admin.packaging-supplies.reject-request', $req) }}">
                        @csrf @method('PATCH')
                        <button class="btn btn-danger w-full" onclick="return confirm('Reject this request?')">Reject</button>
                    </form>
                </div>
            @elseif(in_array($req->status, ['approved', 'shipped']))
                <div class="card p-5">
                    <p class="text-sm font-medium text-gray-700 mb-3">Update Status</p>
                    <form method="POST" action="{{ route('admin.packaging-supplies.update-request-status', $req) }}">
                        @csrf @method('PATCH')
                        <div class="flex gap-2">
                            @if($req->status === 'approved')
                                <button name="status" value="shipped" class="btn btn-secondary flex-1">Mark Shipped</button>
                            @endif
                            @if($req->status === 'shipped')
                                <button name="status" value="delivered" class="btn btn-primary flex-1">Mark Delivered</button>
                            @endif
                        </div>
                    </form>
                </div>
            @endif
        </div>

    </div>

@endsection
