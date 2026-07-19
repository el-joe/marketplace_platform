@extends('layouts.marketer')

@section('title', $promoCode->code)
@section('page-title', $promoCode->code)

@section('content')

    <div class="space-y-6">

        <a href="{{ route('marketer.promo-codes.index') }}" class="text-sm text-blue-600 hover:underline">
            &larr; {{ __('marketer.promo_codes.back_to_codes') }}
        </a>

        {{-- Stats card --}}
        <div class="bg-white rounded-2xl border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="font-mono text-lg font-bold text-gray-800">{{ $promoCode->code }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">
                        {{ $promoCode->valid_from?->format('d M Y') }} &mdash; {{ $promoCode->valid_until?->format('d M Y') }}
                    </p>
                </div>
                <span class="text-xs font-semibold rounded-full px-2.5 py-0.5 {{ $promoCode->is_active ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                    {{ $promoCode->is_active ? __('marketer.promo_codes.active') : __('marketer.promo_codes.pending') }}
                </span>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="text-center">
                    <p class="text-lg font-bold text-gray-800">
                        {{ $promoCode->discount_type->value === 'percentage'
                            ? $promoCode->discount_value . '%'
                            : ($promoCode->discount_type->value === 'free_shipping'
                                ? __('marketer.promo_codes.free_shipping')
                                : number_format($promoCode->discount_value, 2) . ' ' . ($promoCode->currency ?? '')) }}
                    </p>
                    <p class="text-xs text-gray-400">{{ __('marketer.promo_codes.discount') }}</p>
                </div>
                <div class="text-center">
                    <p class="text-lg font-bold text-gray-800">
                        {{ number_format($promoCode->times_used) }} / {{ $promoCode->max_uses ? number_format($promoCode->max_uses) : '∞' }}
                    </p>
                    <p class="text-xs text-gray-400">{{ __('marketer.promo_codes.uses') }}</p>
                </div>
                <div class="text-center">
                    <p class="text-lg font-bold text-green-600">{{ number_format($promoCode->total_revenue_generated / 100, 2) }}</p>
                    <p class="text-xs text-gray-400">{{ __('marketer.promo_codes.revenue') }}</p>
                </div>
                <div class="text-center">
                    <p class="text-lg font-bold text-blue-600">{{ number_format($promoCode->total_commission_earned / 100, 2) }}</p>
                    <p class="text-xs text-gray-400">{{ __('marketer.promo_codes.commission') }}</p>
                </div>
            </div>
        </div>

        {{-- Conversion history --}}
        <div class="bg-white rounded-2xl border border-gray-100 overflow-x-auto">
            <div class="px-4 py-3 border-b border-gray-100">
                <h3 class="font-bold text-gray-800 text-sm">{{ __('marketer.promo_codes.conversions') }}</h3>
            </div>

            @if($conversions->isEmpty())
                <p class="text-sm text-gray-400 text-center py-10">{{ __('marketer.promo_codes.no_conversions') }}</p>
            @else
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-gray-400 border-b border-gray-100">
                            <th class="px-4 py-3">{{ __('marketer.promo_codes.order') }}</th>
                            <th class="px-4 py-3">{{ __('marketer.promo_codes.order_value') }}</th>
                            <th class="px-4 py-3">{{ __('marketer.promo_codes.commission') }}</th>
                            <th class="px-4 py-3">{{ __('marketer.promo_codes.status') }}</th>
                            <th class="px-4 py-3">{{ __('marketer.promo_codes.date') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($conversions as $conversion)
                            <tr class="border-b border-gray-50 hover:bg-gray-50">
                                <td class="px-4 py-3 font-mono text-xs">{{ $conversion->order_id }}</td>
                                <td class="px-4 py-3">{{ number_format($conversion->order_value / 100, 2) }} {{ $conversion->currency }}</td>
                                <td class="px-4 py-3">{{ number_format($conversion->commission_amount / 100, 2) }} {{ $conversion->currency }}</td>
                                <td class="px-4 py-3">
                                    <span class="text-xs font-semibold rounded-full px-2.5 py-0.5 bg-gray-100 text-gray-700">
                                        {{ ucfirst($conversion->status->value) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-500">{{ $conversion->created_at->format('d M Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                @if($conversions->hasPages())
                    <div class="flex justify-center py-4">{{ $conversions->links() }}</div>
                @endif
            @endif
        </div>

    </div>

@endsection
