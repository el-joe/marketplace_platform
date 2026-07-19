@extends('layouts.marketer')

@section('title', __('marketer.deals.title'))
@section('page-title', __('marketer.deals.title'))

@section('content')

    @php
        $statusOrder = ['proposed', 'accepted', 'in_progress', 'content_submitted', 'approved', 'paid', 'rejected', 'cancelled'];
        $statusColors = [
            'proposed' => 'bg-yellow-100 text-yellow-700',
            'accepted' => 'bg-blue-100 text-blue-700',
            'in_progress' => 'bg-blue-100 text-blue-700',
            'content_submitted' => 'bg-indigo-100 text-indigo-700',
            'approved' => 'bg-green-100 text-green-700',
            'paid' => 'bg-green-100 text-green-700',
            'rejected' => 'bg-red-100 text-red-600',
            'cancelled' => 'bg-gray-100 text-gray-600',
        ];
    @endphp

    <div class="mb-6">
        <h2 class="text-lg font-bold text-gray-800">{{ __('marketer.deals.title') }}</h2>
        <p class="text-sm text-gray-500">{{ __('marketer.deals.subtitle') }}</p>
    </div>

    @if($deals->isEmpty())
        <div class="bg-white rounded-2xl border border-gray-100 p-12 text-center">
            <div class="text-4xl mb-3">🤝</div>
            <h3 class="font-bold text-gray-700 mb-1">{{ __('marketer.deals.no_deals_title') }}</h3>
            <p class="text-sm text-gray-400">{{ __('marketer.deals.no_deals_desc') }}</p>
        </div>
    @else
        @foreach($statusOrder as $status)
            @continue(empty($deals[$status]))
            <div class="mb-8">
                <h3 class="text-sm font-bold uppercase tracking-wide text-gray-500 mb-3">
                    {{ __('marketer.deals.status_' . $status) }}
                    <span class="text-gray-400 font-normal">({{ $deals[$status]->count() }})</span>
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    @foreach($deals[$status] as $deal)
                        <div class="bg-white rounded-2xl border border-gray-100 p-5 flex flex-col">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex-1 min-w-0 pr-2">
                                    <a href="{{ route('marketer.deals.show', $deal->id) }}"
                                        class="font-bold text-gray-800 hover:text-blue-600 truncate block text-base">
                                        {{ $deal->deal_name }}
                                    </a>
                                    <p class="text-xs text-gray-400 mt-0.5">
                                        {{ $deal->vendor?->store_name ?? __('marketer.deals.no_vendor') }}
                                    </p>
                                </div>
                                <span class="flex-shrink-0 text-xs font-semibold rounded-full px-2.5 py-0.5 {{ $statusColors[$status] ?? 'bg-gray-100 text-gray-600' }}">
                                    {{ __('marketer.deals.status_' . $status) }}
                                </span>
                            </div>

                            <div class="grid grid-cols-2 gap-2 mb-4 text-center">
                                <div>
                                    <p class="text-base font-bold text-gray-800">
                                        {{ number_format($deal->flat_fee_amount) }} {{ $deal->currency }}
                                    </p>
                                    <p class="text-xs text-gray-400">{{ __('marketer.deals.fee') }}</p>
                                </div>
                                <div>
                                    <p class="text-base font-bold text-gray-800">{{ $deal->deliverables->count() }}</p>
                                    <p class="text-xs text-gray-400">{{ __('marketer.deals.deliverables') }}</p>
                                </div>
                            </div>

                            <div class="flex items-center justify-between pt-3 border-t border-gray-50 mt-auto">
                                <p class="text-xs text-gray-400">
                                    @if($deal->content_due_at)
                                        {{ __('marketer.deals.due', ['date' => $deal->content_due_at->format('d M Y')]) }}
                                    @else
                                        &nbsp;
                                    @endif
                                </p>
                                <a href="{{ route('marketer.deals.show', $deal->id) }}"
                                    class="text-xs font-semibold text-blue-600 hover:underline">
                                    {{ __('marketer.deals.view_details') }}
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    @endif

@endsection
