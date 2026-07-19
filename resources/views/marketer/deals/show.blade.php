@extends('layouts.marketer')

@section('title', $deal->deal_name)
@section('page-title', $deal->deal_name)

@section('content')

    @php
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

    <a href="{{ route('marketer.deals.index') }}" class="text-xs font-semibold text-blue-600 hover:underline mb-4 inline-block">
        &larr; {{ __('marketer.deals.back_to_deals') }}
    </a>

    <div class="bg-white rounded-2xl border border-gray-100 p-6 mb-6">
        <div class="flex items-start justify-between mb-4">
            <div>
                <h2 class="text-lg font-bold text-gray-800">{{ $deal->deal_name }}</h2>
                <p class="text-sm text-gray-500">{{ $deal->vendor?->store_name ?? __('marketer.deals.no_vendor') }}</p>
            </div>
            <span class="text-xs font-semibold rounded-full px-2.5 py-0.5 {{ $statusColors[$deal->status] ?? 'bg-gray-100 text-gray-600' }}">
                {{ __('marketer.deals.status_' . $deal->status) }}
            </span>
        </div>

        @if($deal->description)
            <p class="text-sm text-gray-600 mb-4">{{ $deal->description }}</p>
        @endif

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
            <div>
                <p class="text-xs text-gray-400">{{ __('marketer.deals.fee') }}</p>
                <p class="text-sm font-bold text-gray-800">{{ number_format($deal->flat_fee_amount) }} {{ $deal->currency }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400">{{ __('marketer.deals.deal_type') }}</p>
                <p class="text-sm font-bold text-gray-800">{{ ucfirst(str_replace('_', ' ', $deal->deal_type)) }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400">{{ __('marketer.deals.content_due') }}</p>
                <p class="text-sm font-bold text-gray-800">{{ $deal->content_due_at?->format('d M Y') ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400">{{ __('marketer.deals.payment_due') }}</p>
                <p class="text-sm font-bold text-gray-800">{{ $deal->payment_due_at?->format('d M Y') ?? '—' }}</p>
            </div>
        </div>

        @if($deal->status === 'proposed')
            <div class="flex items-center gap-3 pt-4 border-t border-gray-50">
                <form method="POST" action="{{ route('marketer.deals.accept', $deal->id) }}">
                    @csrf
                    <button type="submit" class="bg-yellow-400 hover:bg-yellow-300 text-slate-900 font-bold text-sm rounded-xl px-4 py-2.5 transition-colors">
                        {{ __('marketer.deals.accept') }}
                    </button>
                </form>

                <div x-data="{ open: false, reason: '' }">
                    <button type="button" @click="open = true" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-sm rounded-xl px-4 py-2.5 transition-colors">
                        {{ __('marketer.deals.reject') }}
                    </button>

                    <div x-show="open" x-cloak
                        class="fixed inset-0 bg-slate-900/40 z-50 flex items-center justify-center p-4">
                        <div @click.outside="open = false" class="bg-white rounded-2xl p-6 max-w-sm w-full">
                            <h3 class="font-bold text-gray-800 mb-3">{{ __('marketer.deals.reject_deal') }}</h3>
                            <form method="POST" action="{{ route('marketer.deals.reject', $deal->id) }}">
                                @csrf
                                <textarea name="reason" x-model="reason" rows="3" required maxlength="500"
                                    placeholder="{{ __('marketer.deals.reject_reason_placeholder') }}"
                                    class="w-full rounded-xl border border-gray-200 text-sm p-3 mb-3 focus:ring-2 focus:ring-red-200 focus:border-red-300"></textarea>
                                <div class="flex justify-end gap-2">
                                    <button type="button" @click="open = false" class="text-sm text-gray-500 px-3 py-2">
                                        {{ __('marketer.deals.cancel') }}
                                    </button>
                                    <button type="submit" class="bg-red-600 hover:bg-red-500 text-white font-bold text-sm rounded-xl px-4 py-2">
                                        {{ __('marketer.deals.reject') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 p-6">
        <h3 class="font-bold text-gray-800 mb-4">{{ __('marketer.deals.deliverables') }}</h3>

        @if($deal->deliverables->isEmpty())
            <p class="text-sm text-gray-400">{{ __('marketer.deals.no_deliverables') }}</p>
        @else
            <div class="space-y-4">
                @foreach($deal->deliverables as $deliverable)
                    @php
                        $dStatusColors = [
                            'pending' => 'bg-gray-100 text-gray-600',
                            'submitted' => 'bg-indigo-100 text-indigo-700',
                            'approved' => 'bg-green-100 text-green-700',
                            'rejected' => 'bg-red-100 text-red-600',
                        ];
                        $canSubmit = in_array($deal->status, ['accepted', 'in_progress'], true)
                            && in_array($deliverable->status, ['pending', 'rejected'], true);
                    @endphp
                    <div class="border border-gray-100 rounded-xl p-4">
                        <div class="flex items-start justify-between mb-2">
                            <div>
                                <p class="text-sm font-bold text-gray-800">
                                    {{ ucfirst($deliverable->platform) }} — {{ ucfirst(str_replace('_', ' ', $deliverable->content_type)) }}
                                </p>
                                @if($deliverable->description)
                                    <p class="text-xs text-gray-500 mt-0.5">{{ $deliverable->description }}</p>
                                @endif
                                @if($deliverable->due_at)
                                    <p class="text-xs text-gray-400 mt-0.5">{{ __('marketer.deals.due', ['date' => $deliverable->due_at->format('d M Y')]) }}</p>
                                @endif
                            </div>
                            <span class="text-xs font-semibold rounded-full px-2.5 py-0.5 {{ $dStatusColors[$deliverable->status] ?? 'bg-gray-100 text-gray-600' }}">
                                {{ ucfirst($deliverable->status) }}
                            </span>
                        </div>

                        @if($deliverable->rejection_reason && $deliverable->status === 'rejected')
                            <p class="text-xs text-red-600 bg-red-50 border border-red-100 rounded-lg p-2 mb-3">
                                {{ __('marketer.deals.rejection_reason') }}: {{ $deliverable->rejection_reason }}
                            </p>
                        @endif

                        @if($deliverable->content_url && !$canSubmit)
                            <p class="text-xs text-gray-500">
                                {{ __('marketer.deals.submitted_url') }}:
                                <a href="{{ $deliverable->content_url }}" target="_blank" rel="noopener" class="text-blue-600 hover:underline break-all">{{ $deliverable->content_url }}</a>
                            </p>
                        @endif

                        @if($canSubmit)
                            <form method="POST" action="{{ route('marketer.deals.deliverables.submit', [$deal->id, $deliverable->id]) }}" class="mt-2 space-y-2">
                                @csrf
                                <input type="url" name="content_url" required maxlength="2048"
                                    value="{{ old('content_url', $deliverable->content_url) }}"
                                    placeholder="{{ __('marketer.deals.content_url_placeholder') }}"
                                    class="w-full rounded-xl border border-gray-200 text-sm p-2.5 focus:ring-2 focus:ring-blue-200 focus:border-blue-300">
                                <textarea name="content_notes" rows="2" maxlength="2000"
                                    placeholder="{{ __('marketer.deals.content_notes_placeholder') }}"
                                    class="w-full rounded-xl border border-gray-200 text-sm p-2.5 focus:ring-2 focus:ring-blue-200 focus:border-blue-300">{{ old('content_notes', $deliverable->content_notes) }}</textarea>
                                <button type="submit" class="bg-yellow-400 hover:bg-yellow-300 text-slate-900 font-bold text-xs rounded-xl px-4 py-2 transition-colors">
                                    {{ __('marketer.deals.submit_deliverable') }}
                                </button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>

@endsection
