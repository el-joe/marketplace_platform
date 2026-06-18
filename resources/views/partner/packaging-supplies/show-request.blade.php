@extends('layouts.partner')

@section('title', 'Request ' . $req->request_number)

@section('content')

    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <a href="{{ route('partner.packaging-supplies.my-requests') }}" class="text-sm text-gray-500 hover:text-gray-700">← My Requests</a>
            <h1 class="text-2xl font-bold text-gray-900 mt-1">Request #{{ $req->request_number }}</h1>
        </div>
        <span class="badge text-sm px-3 py-1 {{ $req->statusBadgeClass() }}">{{ ucfirst($req->status) }}</span>
    </div>

    <div class="grid grid-cols-3 gap-6">

        {{-- ─── Items ─────────────────────────────────────────────────────────── --}}
        <div class="col-span-2">
            <div class="card overflow-hidden">
                <div class="px-5 py-3 border-b bg-gray-50 font-medium text-sm text-gray-700">Items</div>
                <table class="min-w-full divide-y divide-gray-100 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="th">Supply</th>
                            <th class="th">Type</th>
                            <th class="th">Unit Cost</th>
                            <th class="th">Qty</th>
                            <th class="th text-right">Total</th>
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
        </div>

        {{-- ─── Details ────────────────────────────────────────────────────── --}}
        <div class="space-y-4">
            <div class="card p-5 text-sm space-y-3">
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wide mb-0.5">Status</p>
                    <span class="badge {{ $req->statusBadgeClass() }}">{{ ucfirst($req->status) }}</span>
                </div>
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wide mb-0.5">Submitted</p>
                    <p class="text-gray-700">{{ $req->created_at->format('d M Y, H:i') }}</p>
                </div>
                @if($req->warehouse)
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wide mb-0.5">Delivery Warehouse</p>
                        <p class="text-gray-700">{{ $req->warehouse->name }}</p>
                    </div>
                @endif
                @if($req->notes)
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wide mb-0.5">Notes</p>
                        <p class="text-gray-600">{{ $req->notes }}</p>
                    </div>
                @endif
            </div>

            @if($req->status === 'delivered')
                <div class="card p-5 bg-green-50 border-green-200 text-sm text-green-800">
                    Your packaging supplies have been delivered. Enjoy!
                </div>
            @elseif($req->status === 'rejected')
                <div class="card p-5 bg-red-50 border-red-200 text-sm text-red-800">
                    This request was not approved. Please contact support if you have questions.
                </div>
            @endif
        </div>

    </div>

@endsection
