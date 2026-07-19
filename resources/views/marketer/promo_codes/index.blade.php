@extends('layouts.marketer')

@section('title', __('marketer.promo_codes.title'))
@section('page-title', __('marketer.promo_codes.title'))

@section('content')

    <div x-data="{ showModal: {{ $errors->any() ? 'true' : 'false' }} }" class="space-y-6">

        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-bold text-gray-800">{{ __('marketer.promo_codes.title') }}</h2>
                <p class="text-sm text-gray-500">{{ __('marketer.promo_codes.total_codes', ['count' => $promoCodes->total()]) }}</p>
            </div>
            <button type="button" @click="showModal = true" class="btn btn-primary">
                {{ __('marketer.promo_codes.request_new') }}
            </button>
        </div>

        @if(session('success'))
            <div class="bg-green-50 text-green-700 text-sm rounded-xl px-4 py-3">{{ session('success') }}</div>
        @endif

        @if($promoCodes->isEmpty())
            <div class="bg-white rounded-2xl border border-gray-100 p-12 text-center">
                <div class="text-4xl mb-3">🏷️</div>
                <h3 class="font-bold text-gray-700 mb-1">{{ __('marketer.promo_codes.no_codes_title') }}</h3>
                <p class="text-sm text-gray-400 mb-5">{{ __('marketer.promo_codes.no_codes_desc') }}</p>
                <button type="button" @click="showModal = true" class="inline-block bg-yellow-400 text-slate-900 font-bold text-sm rounded-xl px-6 py-2.5">
                    {{ __('marketer.promo_codes.request_new') }}
                </button>
            </div>
        @else
            <div class="bg-white rounded-2xl border border-gray-100 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-gray-400 border-b border-gray-100">
                            <th class="px-4 py-3">{{ __('marketer.promo_codes.code') }}</th>
                            <th class="px-4 py-3">{{ __('marketer.promo_codes.discount') }}</th>
                            <th class="px-4 py-3">{{ __('marketer.promo_codes.uses') }}</th>
                            <th class="px-4 py-3">{{ __('marketer.promo_codes.revenue') }}</th>
                            <th class="px-4 py-3">{{ __('marketer.promo_codes.commission') }}</th>
                            <th class="px-4 py-3">{{ __('marketer.promo_codes.status') }}</th>
                            <th class="px-4 py-3">{{ __('marketer.promo_codes.expiry') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($promoCodes as $promoCode)
                            @php
                                $discount = $promoCode->discount_type->value === 'percentage'
                                    ? $promoCode->discount_value . '%'
                                    : ($promoCode->discount_type->value === 'free_shipping'
                                        ? __('marketer.promo_codes.free_shipping')
                                        : number_format($promoCode->discount_value, 2) . ' ' . ($promoCode->currency ?? ''));
                            @endphp
                            <tr class="border-b border-gray-50 hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <a href="{{ route('marketer.promo-codes.show', $promoCode->id) }}"
                                        class="font-mono text-xs font-semibold text-blue-600 hover:underline">
                                        {{ $promoCode->code }}
                                    </a>
                                </td>
                                <td class="px-4 py-3">{{ $discount }}</td>
                                <td class="px-4 py-3">{{ number_format($promoCode->times_used) }} / {{ $promoCode->max_uses ? number_format($promoCode->max_uses) : '∞' }}</td>
                                <td class="px-4 py-3">{{ number_format($promoCode->total_revenue_generated / 100, 2) }}</td>
                                <td class="px-4 py-3">{{ number_format($promoCode->total_commission_earned / 100, 2) }}</td>
                                <td class="px-4 py-3">
                                    <span class="text-xs font-semibold rounded-full px-2.5 py-0.5 {{ $promoCode->is_active ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                        {{ $promoCode->is_active ? __('marketer.promo_codes.active') : __('marketer.promo_codes.pending') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-500">{{ $promoCode->valid_until?->format('d M Y') ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($promoCodes->hasPages())
                <div class="flex justify-center">{{ $promoCodes->links() }}</div>
            @endif
        @endif

        {{-- Request New Code Modal --}}
        <div x-show="showModal" x-cloak class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl w-full max-w-lg shadow-xl" @click.stop>
                <div class="flex items-center justify-between p-5 border-b border-gray-200">
                    <h3 class="font-bold text-gray-800">{{ __('marketer.promo_codes.modal_title') }}</h3>
                    <button type="button" @click="showModal = false" class="text-gray-400 hover:text-gray-700">✕</button>
                </div>

                <form method="POST" action="{{ route('marketer.promo-codes.store') }}" class="p-5 space-y-4" x-data="{ discountType: '{{ old('discount_type', 'percentage') }}' }">
                    @csrf

                    <div>
                        <label class="form-label text-xs">{{ __('marketer.promo_codes.code_optional') }}</label>
                        <input type="text" name="code" value="{{ old('code') }}" class="form-input text-sm py-2 font-mono uppercase" maxlength="50">
                        @error('code') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="form-label text-xs">{{ __('marketer.promo_codes.discount_type') }}</label>
                            <select name="discount_type" x-model="discountType" class="form-input text-sm py-2" required>
                                <option value="percentage">{{ __('marketer.promo_codes.percentage') }}</option>
                                <option value="fixed_amount">{{ __('marketer.promo_codes.fixed_amount') }}</option>
                                <option value="free_shipping">{{ __('marketer.promo_codes.free_shipping') }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label text-xs">{{ __('marketer.promo_codes.discount_value') }}</label>
                            <input type="number" name="discount_value" value="{{ old('discount_value', 0) }}" step="0.01" min="0" class="form-input text-sm py-2" required>
                        </div>
                    </div>

                    <div x-show="discountType === 'fixed_amount'">
                        <label class="form-label text-xs">{{ __('marketer.promo_codes.currency') }}</label>
                        <input type="text" name="currency" value="{{ old('currency') }}" maxlength="3" class="form-input text-sm py-2 uppercase" placeholder="KWD">
                        @error('currency') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="form-label text-xs">{{ __('marketer.promo_codes.max_uses') }}</label>
                            <input type="number" name="max_uses" value="{{ old('max_uses') }}" min="1" class="form-input text-sm py-2">
                        </div>
                        <div>
                            <label class="form-label text-xs">{{ __('marketer.promo_codes.min_order_amount') }}</label>
                            <input type="number" name="min_order_amount" value="{{ old('min_order_amount') }}" min="0" class="form-input text-sm py-2">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="form-label text-xs">{{ __('marketer.promo_codes.valid_from') }}</label>
                            <input type="date" name="valid_from" value="{{ old('valid_from', now()->toDateString()) }}" class="form-input text-sm py-2" required>
                        </div>
                        <div>
                            <label class="form-label text-xs">{{ __('marketer.promo_codes.valid_until') }}</label>
                            <input type="date" name="valid_until" value="{{ old('valid_until') }}" class="form-input text-sm py-2" required>
                        </div>
                    </div>

                    <div class="flex gap-3 pt-1">
                        <button type="submit" class="btn btn-primary flex-1">{{ __('marketer.promo_codes.submit') }}</button>
                        <button type="button" @click="showModal = false" class="btn btn-secondary flex-1">{{ __('marketer.promo_codes.cancel') }}</button>
                    </div>
                </form>
            </div>
        </div>

    </div>

@endsection
