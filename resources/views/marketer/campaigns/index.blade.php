@extends('layouts.marketer')

@section('title', __('marketer.campaigns.title'))
@section('page-title', __('marketer.campaigns.title'))

@section('content')

    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-lg font-bold text-gray-800">{{ __('marketer.campaigns.title') }}</h2>
            <p class="text-sm text-gray-500">{{ __('marketer.campaigns.total_campaigns', ['count' => $campaigns->total()]) }}</p>
        </div>
        <a href="{{ route('marketer.campaigns.create') }}"
            class="bg-yellow-400 hover:bg-yellow-300 text-slate-900 font-bold text-sm rounded-xl px-4 py-2.5 transition-colors">
            {{ __('marketer.campaigns.create_campaign') }}
        </a>
    </div>

    {{-- Status filter tabs --}}
    @php
        $tabDefs = [
            null => __('marketer.campaigns.all_statuses'),
            'draft' => \App\Enums\MarketerCampaignStatus::Draft->label(),
            'pending_review' => \App\Enums\MarketerCampaignStatus::PendingReview->label(),
            'active' => \App\Enums\MarketerCampaignStatus::Active->label(),
            'paused' => \App\Enums\MarketerCampaignStatus::Paused->label(),
            'rejected' => \App\Enums\MarketerCampaignStatus::Rejected->label(),
            'ended' => \App\Enums\MarketerCampaignStatus::Ended->label(),
            'cancelled' => \App\Enums\MarketerCampaignStatus::Cancelled->label(),
        ];
    @endphp
    <div class="flex flex-wrap gap-2 mb-6 border-b border-gray-100 pb-3">
        @foreach($tabDefs as $value => $label)
            @php
                $count = $value === null ? $totalCount : ($tabs[$value] ?? 0);
                $isCurrent = $statusFilter === $value;
            @endphp
            <a href="{{ route('marketer.campaigns.index', $value ? ['status' => $value] : []) }}"
                class="text-xs font-semibold rounded-full px-3 py-1.5 transition-colors {{ $isCurrent ? 'bg-slate-800 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                {{ $label }}
                <span class="ml-1 opacity-70">{{ $count }}</span>
            </a>
        @endforeach
    </div>

    @if($campaigns->isEmpty())
        <div class="bg-white rounded-2xl border border-gray-100 p-12 text-center">
            <div class="text-4xl mb-3">📣</div>
            <h3 class="font-bold text-gray-700 mb-1">{{ __('marketer.campaigns.no_campaigns_title') }}</h3>
            <p class="text-sm text-gray-400 mb-5">{{ __('marketer.campaigns.no_campaigns_desc') }}</p>
            <a href="{{ route('marketer.campaigns.create') }}"
                class="inline-block bg-yellow-400 text-slate-900 font-bold text-sm rounded-xl px-6 py-2.5">
                {{ __('marketer.campaigns.create_campaign_btn') }}
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach($campaigns as $campaign)
                @php
                    $statusColors = [
                        'active' => 'bg-green-100 text-green-700',
                        'draft' => 'bg-gray-100 text-gray-600',
                        'pending_review' => 'bg-blue-100 text-blue-700',
                        'paused' => 'bg-yellow-100 text-yellow-700',
                        'rejected' => 'bg-red-100 text-red-600',
                        'ended' => 'bg-blue-100 text-blue-700',
                        'cancelled' => 'bg-red-100 text-red-600',
                    ];
                    $sc = $statusColors[$campaign->status->value] ?? 'bg-gray-100 text-gray-600';
                @endphp
                <div class="bg-white rounded-2xl border border-gray-100 p-5 flex flex-col">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1 min-w-0 pr-2">
                            <a href="{{ route('marketer.campaigns.show', $campaign->id) }}"
                                class="font-bold text-gray-800 hover:text-blue-600 truncate block text-base">
                                {{ $campaign->name }}
                            </a>
                            <p class="text-xs text-gray-400 mt-0.5">
                                {{ $campaign->campaign_type->label() }}
                            </p>
                        </div>
                        <span class="flex-shrink-0 text-xs font-semibold rounded-full px-2.5 py-0.5 {{ $sc }}">
                            {{ $campaign->status->label() }}
                        </span>
                    </div>

                    <div class="grid grid-cols-3 gap-2 mb-4">
                        <div class="text-center">
                            <p class="text-base font-bold text-gray-800">{{ number_format($campaign->total_clicks) }}</p>
                            <p class="text-xs text-gray-400">{{ __('marketer.campaigns.clicks') }}</p>
                        </div>
                        <div class="text-center">
                            <p class="text-base font-bold text-gray-800">{{ number_format($campaign->total_conversions) }}</p>
                            <p class="text-xs text-gray-400">{{ __('marketer.campaigns.conv') }}</p>
                        </div>
                        <div class="text-center">
                            <p class="text-base font-bold text-green-600">
                                {{ number_format($campaign->total_revenue, 2) }}</p>
                            <p class="text-xs text-gray-400">{{ $marketer->country?->currency_code ?? '' }}</p>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-3 border-t border-gray-50 mt-auto">
                        <p class="text-xs text-gray-400">
                            @if($campaign->ends_at)
                                {{ __('marketer.campaigns.ends', ['date' => $campaign->ends_at->format('d M Y')]) }}
                            @else
                                {{ __('marketer.campaigns.no_end_date') }}
                            @endif
                        </p>
                        <a href="{{ route('marketer.campaigns.show', $campaign->id) }}"
                            class="text-xs font-semibold text-blue-600 hover:underline">
                            {{ __('marketer.campaigns.view_details') }}
                        </a>
                    </div>

                    @if(in_array($campaign->status->value, ['active', 'paused', 'draft', 'pending_review']))
                        <div class="flex items-center gap-2 mt-3" x-data="{ busy: false }">
                            @if($campaign->status->value === 'active')
                                <button type="button" :disabled="busy"
                                    @click="busy = true; fetch('{{ route('marketer.campaigns.pause', $campaign->id) }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' } }).then(() => location.reload())"
                                    class="flex-1 text-xs font-semibold rounded-lg px-3 py-1.5 bg-yellow-100 text-yellow-700 hover:bg-yellow-200 disabled:opacity-50">
                                    {{ __('marketer.campaigns.pause') }}
                                </button>
                            @elseif($campaign->status->value === 'paused')
                                <button type="button" :disabled="busy"
                                    @click="busy = true; fetch('{{ route('marketer.campaigns.resume', $campaign->id) }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' } }).then(() => location.reload())"
                                    class="flex-1 text-xs font-semibold rounded-lg px-3 py-1.5 bg-green-100 text-green-700 hover:bg-green-200 disabled:opacity-50">
                                    {{ __('marketer.campaigns.resume') }}
                                </button>
                            @endif
                            @if(in_array($campaign->status->value, ['active', 'paused', 'draft', 'pending_review']))
                                <button type="button" :disabled="busy"
                                    @click="if (confirm(@json(__('marketer.campaigns.confirm_cancel')))) { busy = true; fetch('{{ route('marketer.campaigns.cancel', $campaign->id) }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' } }).then(() => location.reload()) }"
                                    class="flex-1 text-xs font-semibold rounded-lg px-3 py-1.5 bg-red-100 text-red-600 hover:bg-red-200 disabled:opacity-50">
                                    {{ __('marketer.campaigns.cancel') }}
                                </button>
                            @endif
                        </div>
                    @endif
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
