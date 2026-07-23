@extends('layouts.marketer')

@section('title', __('marketer.samples.title'))
@section('page-title', __('marketer.samples.title'))

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

<div class="flex justify-end mb-4">
    <x-export-dropdown />
</div>

<form method="GET" action="{{ route('marketer.samples.index') }}" class="flex flex-wrap items-end gap-3 mb-4 bg-white rounded-2xl border border-gray-100 p-4">
    <div>
        <label class="text-xs text-gray-400 font-semibold">{{ __('common.status') }}</label>
        <select name="status" class="w-full rounded-lg border-gray-200 text-sm mt-1">
            <option value="">{{ __('common.all') }}</option>
            @foreach(\App\Enums\MarketerSampleRequestStatus::cases() as $status)
                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ ucfirst($status->value) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="text-xs text-gray-400 font-semibold">{{ __('common.date_from') }}</label>
        <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full rounded-lg border-gray-200 text-sm mt-1">
    </div>
    <div>
        <label class="text-xs text-gray-400 font-semibold">{{ __('common.date_to') }}</label>
        <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full rounded-lg border-gray-200 text-sm mt-1">
    </div>
    <button type="submit" class="bg-slate-800 text-white text-sm font-semibold rounded-lg px-4 py-2">{{ __('common.filter') }}</button>
</form>

@if($sampleRequests->isEmpty())
    <div class="bg-white rounded-2xl border border-gray-200 p-12 text-center">
        <p class="text-5xl mb-4">📦</p>
        <p class="font-semibold text-gray-700">{{ __('marketer.samples.no_requests') }}</p>
        <p class="text-sm text-gray-400 mt-1">{{ __('marketer.samples.no_requests_desc') }}</p>
        <a href="{{ route('marketer.campaigns.index') }}" class="btn btn-primary mt-4">{{ __('marketer.samples.view_campaigns') }}</a>
    </div>
@else
    <div class="overflow-x-auto bg-white rounded-2xl border border-gray-100">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 text-xs text-gray-400 uppercase tracking-wide">
                    <th class="text-left px-5 py-3">{{ __('marketer.samples.campaign') }}</th>
                    <th class="text-left px-5 py-3">{{ __('marketer.samples.items') }}</th>
                    <th class="text-left px-5 py-3">{{ __('marketer.samples.status') }}</th>
                    <th class="text-left px-5 py-3">{{ __('marketer.samples.submitted') }}</th>
                    <th class="text-left px-5 py-3">{{ __('marketer.samples.updated') }}</th>
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
                        {{ __('marketer.samples.items_count', ['count' => $sr->items->sum('marketer_quantity')]) }}
                    </td>
                    <td class="px-5 py-3">
                        <span class="text-xs font-semibold rounded-full px-2.5 py-1 {{ $statusColors[$sr->status->value] ?? 'bg-gray-100 text-gray-600' }}">
                            {{ ucfirst($sr->status->value) }}
                        </span>
                        @if($sr->status === \App\Enums\MarketerSampleRequestStatus::Rejected && $sr->rejection_reason)
                            <p class="text-xs text-red-500 mt-1">{{ $sr->rejection_reason }}</p>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-gray-400 text-xs">{{ $sr->created_at->format('d M Y') }}</td>
                    <td class="px-5 py-3 text-gray-400 text-xs">{{ $sr->updated_at->format('d M Y') }}</td>
                    <td class="px-5 py-3 text-right">
                        @if($sr->status === \App\Enums\MarketerSampleRequestStatus::Dispatched)
                            <form method="POST"
                                  action="{{ route('marketer.samples.mark-received', $sr) }}"
                                  onsubmit="return confirm(@json(__('marketer.samples.confirm_received')))">
                                @csrf
                                <button type="submit"
                                        class="text-xs font-semibold px-3 py-1 rounded-full bg-purple-100 text-purple-700 hover:bg-purple-200 transition-colors">
                                    {{ __('marketer.samples.mark_received') }}
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
