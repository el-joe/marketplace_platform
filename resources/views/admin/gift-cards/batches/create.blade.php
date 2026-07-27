@extends('layouts.admin')

@section('title', __('admin.gift_cards_section.new_batch'))

@section('content')
    <form method="POST" action="{{ route('admin.gift-cards.batches.store') }}" novalidate
          x-data="{
              amount: {{ old('amount', 0) }},
              quantity: {{ old('quantity', 1) }},
              currency: '{{ old('currency_code', 'SAR') }}',
              get total() { return (Number(this.amount) || 0) * (Number(this.quantity) || 0); }
          }">
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
                <h2 class="text-sm font-semibold text-gray-900">{{ __('admin.gift_cards_section.batch_details') }}</h2>

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.gift_cards_section.name') }}</label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" class="form-input" maxlength="255" required>
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.gift_cards_section.description') }}</label>
                    <textarea id="description" name="description" rows="3" class="form-textarea">{{ old('description') }}</textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="currency_code" class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.gift_cards_section.currency_code') }}</label>
                        <select id="currency_code" name="currency_code" class="form-select" x-model="currency">
                            @foreach (['SAR', 'AED', 'EGP', 'KWD', 'OMR', 'QAR', 'BHD', 'JOD'] as $code)
                                <option value="{{ $code }}" {{ old('currency_code') === $code ? 'selected' : '' }}>{{ $code }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="amount" class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.gift_cards_section.amount') }}</label>
                        <input type="number" id="amount" name="amount" value="{{ old('amount') }}" min="1" class="form-input" required x-model.number="amount">
                        <p class="text-xs text-gray-500 mt-1">{{ __('admin.gift_cards_section.face_value_hint') }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="quantity" class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.gift_cards_section.quantity') }}</label>
                        <input type="number" id="quantity" name="quantity" value="{{ old('quantity', 1) }}" min="1" max="10000" class="form-input" required x-model.number="quantity">
                        <p class="text-xs text-gray-500 mt-1">{{ __('admin.gift_cards_section.quantity_hint') }}</p>
                    </div>
                    <div>
                        <label for="expires_at" class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.gift_cards_section.expires_at') }}</label>
                        <input type="text" id="expires_at" name="expires_at" value="{{ old('expires_at') }}" data-flatpickr class="form-input" placeholder="YYYY-MM-DD">
                    </div>
                </div>

                <div class="bg-indigo-50 border border-indigo-100 text-indigo-700 text-sm rounded-lg px-4 py-3" x-show="amount > 0 && quantity > 0" x-cloak>
                    {{ __('admin.gift_cards_section.preview') }}:
                    <span x-text="'This will generate ' + quantity + ' cards worth ' + amount + ' ' + currency + ' each = ' + total + ' ' + currency + ' total'"></span>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('admin.gift-cards.batches.index') }}" class="btn btn-secondary">{{ __('admin.cancel') }}</a>
                <button type="submit" class="btn btn-primary">{{ __('admin.gift_cards_section.create_batch') }}</button>
            </div>

        </div>
    </form>
@endsection

@push('styles')
    @vite(['resources/js/components/flatpickr.js'])
@endpush
