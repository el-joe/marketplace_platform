@extends('layouts.marketer')

@section('title', 'My Campaigns')
@section('page-title', 'My Campaigns')

@section('content')

    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-lg font-bold text-gray-800">My Campaigns</h2>
            <p class="text-sm text-gray-500">{{ $campaigns->total() }} total campaigns</p>
        </div>
        <a href="{{ route('marketer.campaigns.create') }}"
            class="bg-yellow-400 hover:bg-yellow-300 text-slate-900 font-bold text-sm rounded-xl px-4 py-2.5 transition-colors">
            + New Campaign
        </a>
    </div>

    @if($campaigns->isEmpty())
        <div class="bg-white rounded-2xl border border-gray-100 p-12 text-center">
            <div class="text-4xl mb-3">📣</div>
            <h3 class="font-bold text-gray-700 mb-1">No campaigns yet</h3>
            <p class="text-sm text-gray-400 mb-5">Create your first campaign to start earning commissions.</p>
            <a href="{{ route('marketer.campaigns.create') }}"
                class="inline-block bg-yellow-400 text-slate-900 font-bold text-sm rounded-xl px-6 py-2.5">
                Create Campaign
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach($campaigns as $campaign)
                @php
                    $statusColors = [
                        'active' => 'bg-green-100 text-green-700',
                        'draft' => 'bg-gray-100 text-gray-600',
                        'paused' => 'bg-yellow-100 text-yellow-700',
                        'ended' => 'bg-blue-100 text-blue-700',
                        'cancelled' => 'bg-red-100 text-red-600',
                    ];
                    $sc = $statusColors[$campaign->status] ?? 'bg-gray-100 text-gray-600';
                @endphp
                <div class="bg-white rounded-2xl border border-gray-100 p-5 flex flex-col">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1 min-w-0 pr-2">
                            <a href="{{ route('marketer.campaigns.show', $campaign->id) }}"
                                class="font-bold text-gray-800 hover:text-blue-600 truncate block text-base">
                                {{ $campaign->name }}
                            </a>
                            <p class="text-xs text-gray-400 mt-0.5">
                                {{ ucfirst(str_replace('_', ' ', $campaign->campaign_type)) }}
                            </p>
                        </div>
                        <span class="flex-shrink-0 text-xs font-semibold rounded-full px-2.5 py-0.5 {{ $sc }}">
                            {{ ucfirst($campaign->status) }}
                        </span>
                    </div>

                    <div class="grid grid-cols-3 gap-2 mb-4">
                        <div class="text-center">
                            <p class="text-base font-bold text-gray-800">{{ number_format($campaign->total_clicks) }}</p>
                            <p class="text-xs text-gray-400">Clicks</p>
                        </div>
                        <div class="text-center">
                            <p class="text-base font-bold text-gray-800">{{ number_format($campaign->total_conversions) }}</p>
                            <p class="text-xs text-gray-400">Conv.</p>
                        </div>
                        <div class="text-center">
                            <p class="text-base font-bold text-green-600">
                                {{ number_format($campaign->total_revenue_cents / 100, 2) }}</p>
                            <p class="text-xs text-gray-400">{{ $marketer->country?->currency_code ?? '' }}</p>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-3 border-t border-gray-50 mt-auto">
                        <p class="text-xs text-gray-400">
                            @if($campaign->ends_at)
                                Ends {{ $campaign->ends_at->format('d M Y') }}
                            @else
                                No end date
                            @endif
                        </p>
                        <a href="{{ route('marketer.campaigns.show', $campaign->id) }}"
                            class="text-xs font-semibold text-blue-600 hover:underline">
                            View details →
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        @if($campaigns->hasPages())
            <div class="mt-6 flex justify-center">
                {{ $campaigns->links() }}
            </div>
        @endif
    @endif

@endsection
