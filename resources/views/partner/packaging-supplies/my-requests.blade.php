@extends('layouts.partner')

@section('title', __('partner.packaging_supplies.my_requests_title'))

@section('content')

    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('partner.packaging_supplies.my_requests_title') }}</h1>
            <p class="text-sm text-gray-500 mt-0.5">{{ __('partner.packaging_supplies.subtitle') }}</p>
        </div>
        <a href="{{ route('partner.packaging-supplies.request') }}" class="btn btn-primary">{{ __('partner.packaging_supplies.new_request') }}</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4">{{ session('success') }}</div>
    @endif

    <div class="card overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="th">{{ __('partner.packaging_supplies.request_number') }}</th>
                    <th class="th">{{ __('partner.packaging_supplies.items') }}</th>
                    <th class="th">{{ __('partner.packaging_supplies.total_cost') }}</th>
                    <th class="th">{{ __('common.status') }}</th>
                    <th class="th">{{ __('common.date') }}</th>
                    <th class="th"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
                @forelse($supplyRequests as $req)
                    <tr class="hover:bg-gray-50">
                        <td class="td font-mono text-xs">{{ $req->request_number }}</td>
                        <td class="td text-gray-600">{{ __('partner.packaging_supplies.items_count', ['count' => $req->items->count()]) }}</td>
                        <td class="td font-medium">{{ $req->total_cost_formatted }}</td>
                        <td class="td">
                            <span class="badge {{ $req->statusBadgeClass() }}">{{ ucfirst($req->status) }}</span>
                        </td>
                        <td class="td text-gray-500 text-xs">{{ $req->created_at->format('d M Y') }}</td>
                        <td class="td text-right">
                            <a href="{{ route('partner.packaging-supplies.show-request', $req) }}"
                               class="text-primary-600 hover:underline text-xs font-medium">{{ __('common.view') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="td text-center text-gray-400 py-10">
                            {{ __('partner.packaging_supplies.no_requests_yet') }}
                            <a href="{{ route('partner.packaging-supplies.request') }}" class="text-primary-600 hover:underline">{{ __('partner.packaging_supplies.make_first_request') }}</a>.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $supplyRequests->links() }}</div>

@endsection
