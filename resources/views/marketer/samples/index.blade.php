@extends('layouts.marketer')

@section('title', 'Sample Requests')
@section('page-title', 'Sample Requests')

@section('content')

@php
$statusColors = [
    'requested'  => 'bg-yellow-100 text-yellow-700',
    'approved'   => 'bg-green-100 text-green-700',
    'dispatched' => 'bg-blue-100 text-blue-700',
    'received'   => 'bg-purple-100 text-purple-700',
    'rejected'   => 'bg-red-100 text-red-600',
];
@endphp

@if(session('success'))
    <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-600">{{ session('error') }}</div>
@endif

@if($sampleRequests->isEmpty())
    <div class="bg-white rounded-2xl border border-gray-200 p-12 text-center">
        <p class="text-5xl mb-4">📦</p>
        <p class="font-semibold text-gray-700">No sample requests yet</p>
        <p class="text-sm text-gray-400 mt-1">Visit an active vendor campaign to request product samples.</p>
        <a href="{{ route('marketer.campaigns.index') }}" class="btn btn-primary mt-4">View Campaigns</a>
    </div>
@else
    <div class="overflow-x-auto bg-white rounded-2xl border border-gray-100">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 text-xs text-gray-400 uppercase tracking-wide">
                    <th class="text-left px-5 py-3">Campaign</th>
                    <th class="text-left px-5 py-3">Items</th>
                    <th class="text-left px-5 py-3">Status</th>
                    <th class="text-left px-5 py-3">Submitted</th>
                    <th class="text-left px-5 py-3">Updated</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($sampleRequests as $sr)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-5 py-3">
                        @if($sr->campaign)
                            <a href="{{ route('marketer.campaigns.show', $sr->campaign) }}"
                               class="font-medium text-gray-800 hover:text-yellow-600">
                                {{ $sr->campaign->name }}
                            </a>
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-gray-600">
                        {{ $sr->items->sum('quantity') }} item(s)
                    </td>
                    <td class="px-5 py-3">
                        <span class="text-xs font-semibold rounded-full px-2.5 py-1 {{ $statusColors[$sr->status] ?? 'bg-gray-100 text-gray-600' }}">
                            {{ ucfirst($sr->status) }}
                        </span>
                        @if($sr->status === 'rejected' && $sr->rejection_reason)
                            <p class="text-xs text-red-500 mt-1">{{ $sr->rejection_reason }}</p>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-gray-400 text-xs">{{ $sr->created_at->format('d M Y') }}</td>
                    <td class="px-5 py-3 text-gray-400 text-xs">{{ $sr->updated_at->format('d M Y') }}</td>
                    <td class="px-5 py-3 text-right">
                        @if($sr->status === 'dispatched')
                            <form method="POST"
                                  action="{{ route('marketer.samples.mark-received', $sr) }}"
                                  onsubmit="return confirm('Confirm you have received this sample?')">
                                @csrf
                                <button type="submit"
                                        class="text-xs font-semibold px-3 py-1 rounded-full bg-purple-100 text-purple-700 hover:bg-purple-200 transition-colors">
                                    Mark Received
                                </button>
                            </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $sampleRequests->links() }}</div>
@endif

@endsection
