@extends('layouts.partner')

@section('title', 'My Packaging Requests')

@section('content')

    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">My Packaging Requests</h1>
            <p class="text-sm text-gray-500 mt-0.5">Track the status of your packaging supply requests.</p>
        </div>
        <a href="{{ route('partner.packaging-supplies.request') }}" class="btn btn-primary">+ New Request</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4">{{ session('success') }}</div>
    @endif

    <div class="card overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="th">Request #</th>
                    <th class="th">Items</th>
                    <th class="th">Total Cost</th>
                    <th class="th">Status</th>
                    <th class="th">Date</th>
                    <th class="th"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
                @forelse($supplyRequests as $req)
                    <tr class="hover:bg-gray-50">
                        <td class="td font-mono text-xs">{{ $req->request_number }}</td>
                        <td class="td text-gray-600">{{ $req->items->count() }} item(s)</td>
                        <td class="td font-medium">{{ $req->total_cost_formatted }}</td>
                        <td class="td">
                            <span class="badge {{ $req->statusBadgeClass() }}">{{ ucfirst($req->status) }}</span>
                        </td>
                        <td class="td text-gray-500 text-xs">{{ $req->created_at->format('d M Y') }}</td>
                        <td class="td text-right">
                            <a href="{{ route('partner.packaging-supplies.show-request', $req) }}"
                               class="text-primary-600 hover:underline text-xs font-medium">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="td text-center text-gray-400 py-10">
                            No requests yet.
                            <a href="{{ route('partner.packaging-supplies.request') }}" class="text-primary-600 hover:underline">Make your first request</a>.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $supplyRequests->links() }}</div>

@endsection
