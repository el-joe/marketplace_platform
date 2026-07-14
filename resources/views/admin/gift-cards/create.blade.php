@extends('layouts.admin')

@section('title', __('admin.gift_cards_section.new_gift_card'))

@push('styles')
    @vite(['resources/js/components/flatpickr.js'])
@endpush

@section('content')
    <form method="POST" action="{{ route('admin.gift-cards.store') }}" novalidate x-data="{ anonymous: false, denom: '' }">
        @csrf

        <div class="max-w-3xl space-y-6">

            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">
                    <ul class="list-disc ps-5 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-5">
                <h2 class="text-sm font-semibold text-gray-900">{{ __('admin.gift_cards_section.gift_card_details') }}</h2>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('admin.gift_cards_section.denomination_option') }}</label>
                    <div class="flex flex-wrap gap-3">
                        @foreach ($denominations as $amount)
                            <label class="flex items-center gap-2 border border-gray-300 rounded-lg px-3 py-2 cursor-pointer has-[:checked]:border-blue-600 has-[:checked]:bg-blue-50">
                                <input type="radio" name="denomination_option" value="{{ $amount }}" x-model="denom" class="form-radio" {{ old('denomination_option') == $amount ? 'checked' : '' }}>
                                <span class="text-sm">{{ number_format($amount / 100, 2) }}</span>
                            </label>
                        @endforeach
                        <label class="flex items-center gap-2 border border-gray-300 rounded-lg px-3 py-2 cursor-pointer">
                            <input type="radio" name="denomination_option" value="" x-model="denom" checked="{{ old('custom_amount') ? 'checked' : '' }}" class="form-radio" @click="denom = ''">
                            <span class="text-sm">{{ __('admin.gift_cards_section.custom_amount') }}</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label for="custom_amount" class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.gift_cards_section.custom_amount') }}</label>
                    <input type="number" id="custom_amount" name="custom_amount" value="{{ old('custom_amount') }}" min="100" class="form-input" placeholder="7500">
                    <p class="text-xs text-gray-500 mt-1">{{ __('admin.gift_cards_section.custom_amount_hint') }}</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="currency" class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.gift_cards_section.currency') }}</label>
                        <select id="currency" name="currency" class="form-select">
                            @foreach (($currencies ?? ['AED', 'SAR', 'USD']) as $code)
                                <option value="{{ $code }}" {{ old('currency') === $code ? 'selected' : '' }}>{{ $code }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="quantity" class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.gift_cards_section.quantity') }}</label>
                        <input type="number" id="quantity" name="quantity" value="{{ old('quantity', 1) }}" min="1" max="100" class="form-input">
                        <p class="text-xs text-gray-500 mt-1">{{ __('admin.gift_cards_section.quantity_hint') }}</p>
                    </div>
                </div>

                <div>
                    <label for="expires_at" class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.gift_cards_section.expires_at') }}</label>
                    <input type="text" id="expires_at" name="expires_at" value="{{ old('expires_at') }}" data-flatpickr class="form-input" placeholder="YYYY-MM-DD">
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-5">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-900">{{ __('admin.gift_cards_section.recipient') }}</h2>
                    <label class="flex items-center gap-2 text-sm text-gray-600">
                        <input type="checkbox" name="anonymous" value="1" x-model="anonymous" class="form-checkbox" {{ old('anonymous') ? 'checked' : '' }}>
                        {{ __('admin.gift_cards_section.anonymous') }}
                    </label>
                </div>

                <div x-show="!anonymous" class="space-y-4">
                    <div>
                        <label for="recipient_name" class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.gift_cards_section.recipient_name') }}</label>
                        <input type="text" id="recipient_name" name="recipient_name" value="{{ old('recipient_name') }}" class="form-input">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="recipient_email" class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.gift_cards_section.recipient_email') }}</label>
                            <input type="email" id="recipient_email" name="recipient_email" value="{{ old('recipient_email') }}" class="form-input">
                        </div>
                        <div>
                            <label for="recipient_phone" class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.gift_cards_section.recipient_phone') }}</label>
                            <input type="text" id="recipient_phone" name="recipient_phone" value="{{ old('recipient_phone') }}" class="form-input">
                        </div>
                    </div>
                    <div>
                        <label for="personal_message" class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.gift_cards_section.personal_message') }}</label>
                        <textarea id="personal_message" name="personal_message" rows="3" class="form-textarea" maxlength="500">{{ old('personal_message') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('admin.gift-cards.index') }}" class="btn btn-secondary">{{ __('admin.cancel') }}</a>
                <button type="submit" class="btn btn-primary">{{ __('admin.gift_cards_section.create_gift_card') }}</button>
            </div>

        </div>
    </form>
@endsection
