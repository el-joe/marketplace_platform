@extends('layouts.marketer')

@section('title', __('marketer.promotion_requests.title'))
@section('page-title', __('marketer.promotion_requests.title'))

@section('content')

    <div class="mb-6">
        <h2 class="text-lg font-bold text-gray-800">{{ __('marketer.promotion_requests.title') }}</h2>
        <p class="text-sm text-gray-500">{{ __('marketer.promotion_requests.subtitle') }}</p>
    </div>

    @if($items->isEmpty())
        <div class="bg-white rounded-2xl border border-gray-100 p-12 text-center">
            <div class="text-4xl mb-3">📨</div>
            <h3 class="font-bold text-gray-700 mb-1">{{ __('marketer.promotion_requests.none_title') }}</h3>
            <p class="text-sm text-gray-400">{{ __('marketer.promotion_requests.none_desc') }}</p>
        </div>
    @else
        <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left font-semibold text-gray-500 px-4 py-3">{{ __('marketer.promotion_requests.product') }}</th>
                            <th class="text-left font-semibold text-gray-500 px-4 py-3">{{ __('marketer.promotion_requests.vendor') }}</th>
                            <th class="text-left font-semibold text-gray-500 px-4 py-3">{{ __('marketer.promotion_requests.slot_fee') }}</th>
                            <th class="text-left font-semibold text-gray-500 px-4 py-3">{{ __('marketer.promotion_requests.expires_at') }}</th>
                            <th class="text-right font-semibold text-gray-500 px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($items as $item)
                            @php
                                $promotionRequest = $item->promotionRequest;
                                $product = $promotionRequest?->vendorListing?->productVariant?->product
                                    ?? $promotionRequest?->adminProductListing?->productVariant?->product;
                            @endphp
                            <tr x-data="{
                                    remaining: {{ max(0, now()->diffInSeconds($item->expires_at, false)) }},
                                    declining: false,
                                    note: '',
                                    tick() {
                                        if (this.remaining > 0) this.remaining--;
                                    },
                                    format() {
                                        if (this.remaining <= 0) return '{{ __('marketer.promotion_requests.expired') }}';
                                        const d = Math.floor(this.remaining / 86400);
                                        const h = Math.floor((this.remaining % 86400) / 3600);
                                        const m = Math.floor((this.remaining % 3600) / 60);
                                        const s = this.remaining % 60;
                                        let parts = [];
                                        if (d > 0) parts.push(d + '{{ __('marketer.promotion_requests.days') }}');
                                        parts.push(String(h).padStart(2, '0') + '{{ __('marketer.promotion_requests.hours') }}');
                                        parts.push(String(m).padStart(2, '0') + '{{ __('marketer.promotion_requests.minutes') }}');
                                        parts.push(String(s).padStart(2, '0') + '{{ __('marketer.promotion_requests.seconds') }}');
                                        return parts.join(' ');
                                    }
                                }"
                                x-init="setInterval(() => tick(), 1000)">
                                <td class="px-4 py-3 font-semibold text-gray-800">
                                    {{ $product?->name_en ?? __('marketer.promotion_requests.unknown_product') }}
                                </td>
                                <td class="px-4 py-3 text-gray-500">
                                    {{ $promotionRequest?->vendor?->store_name ?? $promotionRequest?->vendor?->name }}
                                </td>
                                <td class="px-4 py-3 font-semibold text-gray-800">
                                    {{ number_format($item->slot_promotion_fee) }} {{ $promotionRequest?->currency }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="font-mono text-xs px-2 py-1 rounded-lg"
                                        :class="remaining > 0 ? 'bg-amber-50 text-amber-700' : 'bg-red-50 text-red-600'"
                                        x-text="format()"></span>
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <form method="POST" action="{{ route('marketer.promotion-requests.accept', $item->id) }}" class="inline">
                                        @csrf
                                        <button type="submit" x-bind:disabled="remaining <= 0"
                                            class="bg-yellow-400 hover:bg-yellow-300 disabled:opacity-40 disabled:cursor-not-allowed text-slate-900 font-bold text-xs rounded-lg px-3 py-2 transition-colors">
                                            {{ __('marketer.promotion_requests.accept') }}
                                        </button>
                                    </form>

                                    <button type="button" @click="declining = true" x-bind:disabled="remaining <= 0"
                                        class="ms-2 bg-white border border-gray-200 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed text-gray-600 font-bold text-xs rounded-lg px-3 py-2 transition-colors">
                                        {{ __('marketer.promotion_requests.decline') }}
                                    </button>

                                    <div x-show="declining" x-cloak
                                        class="fixed inset-0 bg-black/40 flex items-center justify-center z-50"
                                        style="inset-inline-start:0;">
                                        <div class="bg-white rounded-2xl p-5 w-full max-w-sm text-left" @click.outside="declining = false">
                                            <form method="POST" action="{{ route('marketer.promotion-requests.decline', $item->id) }}">
                                                @csrf
                                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">
                                                    {{ __('marketer.promotion_requests.decline_note') }}
                                                </label>
                                                <textarea name="note" x-model="note" rows="3" maxlength="500"
                                                    class="w-full rounded-lg border border-gray-200 text-sm p-2 mb-4"
                                                    placeholder="{{ __('marketer.promotion_requests.decline_note_placeholder') }}"></textarea>
                                                <div class="flex justify-end gap-2">
                                                    <button type="button" @click="declining = false"
                                                        class="text-xs font-semibold text-gray-500 px-3 py-2">
                                                        {{ __('marketer.promotion_requests.cancel') }}
                                                    </button>
                                                    <button type="submit"
                                                        class="bg-red-500 hover:bg-red-600 text-white font-bold text-xs rounded-lg px-3 py-2">
                                                        {{ __('marketer.promotion_requests.confirm_decline') }}
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

@endsection
