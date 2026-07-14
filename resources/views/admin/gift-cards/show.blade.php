@extends('layouts.admin')

@section('title', __('admin.gift_cards_section.gift_card_details') . ': ' . $giftCard->code)

@push('styles')
    @vite(['resources/js/components/flatpickr.js'])
@endpush

@section('content')
    @php
        $statusClasses = [
            'active' => 'bg-green-100 text-green-700',
            'redeemed' => 'bg-blue-100 text-blue-700',
            'expired' => 'bg-gray-100 text-gray-700',
            'cancelled' => 'bg-red-100 text-red-700',
            'pending_activation' => 'bg-amber-100 text-amber-700',
        ];
        $statusClass = $statusClasses[$giftCard->status] ?? 'bg-gray-100 text-gray-700';
    @endphp

    <div class="space-y-6" x-data="{ extendOpen: false, adjustOpen: false }">

        @if (session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">
                <ul class="list-disc ps-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="flex items-center justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <code class="font-mono text-sm bg-gray-100 px-2 py-1 rounded">{{ $giftCard->code }}</code>
                    <span class="badge {{ $statusClass }}">{{ __('admin.gift_cards_section.' . $giftCard->status) }}</span>
                </div>
                <h1 class="text-xl font-semibold text-gray-900 mt-1">
                    {{ number_format($giftCard->denomination_cents / 100, 2) }} {{ $giftCard->currency }}
                </h1>
            </div>
            <a href="{{ route('admin.gift-cards.index') }}" class="btn btn-secondary">{{ __('admin.gift_cards_section.back_to_list') }}</a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm px-5 py-4">
                <p class="text-xs text-gray-500">{{ __('admin.gift_cards_section.balance') }}</p>
                <p class="text-xl font-semibold text-gray-900 mt-1">{{ number_format($giftCard->balance_cents / 100, 2) }} {{ $giftCard->currency }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm px-5 py-4">
                <p class="text-xs text-gray-500">{{ __('admin.gift_cards_section.recipient') }}</p>
                <p class="text-sm font-medium text-gray-900 mt-1">
                    {{ $giftCard->recipient_name ?? '—' }}<br>
                    <span class="text-gray-500 text-xs">{{ $giftCard->recipient_email ?? $giftCard->recipient_phone ?? '' }}</span>
                </p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm px-5 py-4">
                <p class="text-xs text-gray-500">{{ __('admin.gift_cards_section.expiry') }}</p>
                <p class="text-sm font-medium text-gray-900 mt-1">{{ $giftCard->expires_at?->format('Y-m-d') ?? '—' }}</p>
            </div>
        </div>

        @if ($giftCard->personal_message)
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm px-5 py-4">
                <p class="text-xs text-gray-500 mb-1">{{ __('admin.gift_cards_section.personal_message') }}</p>
                <p class="text-sm text-gray-700">{{ $giftCard->personal_message }}</p>
            </div>
        @endif

        @if ($giftCard->status !== 'cancelled')
            <div class="flex flex-wrap items-center gap-3">
                <button type="button" @click="extendOpen = true" class="btn btn-secondary btn-sm">{{ __('admin.gift_cards_section.extend_expiry') }}</button>
                <button type="button" @click="adjustOpen = true" class="btn btn-secondary btn-sm">{{ __('admin.gift_cards_section.adjust_balance') }}</button>

                <form method="POST" action="{{ route('admin.gift-cards.cancel', $giftCard->id) }}"
                    onsubmit="return confirm({{ Js::from(__('admin.gift_cards_section.cancel_confirm', ['code' => $giftCard->code])) }});">
                    @csrf
                    <button type="submit" class="btn btn-danger btn-sm">{{ __('admin.gift_cards_section.cancel') }}</button>
                </form>
            </div>
        @endif

        {{-- Extend expiry modal --}}
        <div x-show="extendOpen" x-cloak class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl shadow-lg w-full max-w-sm p-6" @click.outside="extendOpen = false">
                <h3 class="text-sm font-semibold text-gray-900 mb-4">{{ __('admin.gift_cards_section.extend_expiry') }}</h3>
                <form method="POST" action="{{ route('admin.gift-cards.extend', $giftCard->id) }}">
                    @csrf
                    <label for="expires_at" class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.gift_cards_section.expires_at') }}</label>
                    <input type="text" id="expires_at" name="expires_at" data-flatpickr class="form-input w-full" placeholder="YYYY-MM-DD" required>
                    <div class="flex justify-end gap-2 mt-4">
                        <button type="button" @click="extendOpen = false" class="btn btn-secondary btn-sm">{{ __('admin.cancel') }}</button>
                        <button type="submit" class="btn btn-primary btn-sm">{{ __('admin.save') }}</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Adjust balance modal --}}
        <div x-show="adjustOpen" x-cloak class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl shadow-lg w-full max-w-sm p-6" @click.outside="adjustOpen = false">
                <h3 class="text-sm font-semibold text-gray-900 mb-4">{{ __('admin.gift_cards_section.adjust_balance') }}</h3>
                <form method="POST" action="{{ route('admin.gift-cards.adjust', $giftCard->id) }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.gift_cards_section.adjustment_type') }}</label>
                        <select name="type" class="form-select w-full">
                            <option value="add">{{ __('admin.gift_cards_section.add') }}</option>
                            <option value="subtract">{{ __('admin.gift_cards_section.subtract') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.gift_cards_section.adjustment_amount') }}</label>
                        <input type="number" name="amount_cents" min="1" class="form-input w-full" placeholder="1000" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.gift_cards_section.adjustment_notes') }}</label>
                        <textarea name="notes" rows="2" class="form-textarea w-full" required></textarea>
                    </div>
                    <div class="flex justify-end gap-2 pt-1">
                        <button type="button" @click="adjustOpen = false" class="btn btn-secondary btn-sm">{{ __('admin.cancel') }}</button>
                        <button type="submit" class="btn btn-primary btn-sm">{{ __('admin.save') }}</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-900">{{ __('admin.gift_cards_section.transactions') }}</h2>
            </div>
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                    <tr>
                        <th class="px-5 py-2 text-start">{{ __('admin.gift_cards_section.type') }}</th>
                        <th class="px-5 py-2 text-end">{{ __('admin.gift_cards_section.amount') }}</th>
                        <th class="px-5 py-2 text-end">{{ __('admin.gift_cards_section.balance_after') }}</th>
                        <th class="px-5 py-2 text-start">{{ __('admin.gift_cards_section.performed_by') }}</th>
                        <th class="px-5 py-2 text-start">{{ __('admin.gift_cards_section.notes') }}</th>
                        <th class="px-5 py-2 text-end">{{ __('admin.gift_cards_section.date') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($giftCard->transactions as $transaction)
                        <tr>
                            <td class="px-5 py-2">{{ __('admin.gift_cards_section.' . $transaction->type) }}</td>
                            <td class="px-5 py-2 text-end {{ $transaction->amount_cents < 0 ? 'text-red-600' : 'text-green-600' }}">
                                {{ number_format($transaction->amount_cents / 100, 2) }}
                            </td>
                            <td class="px-5 py-2 text-end">{{ number_format($transaction->balance_after_cents / 100, 2) }}</td>
                            <td class="px-5 py-2">{{ $transaction->performedByAdmin?->name ?? $transaction->performedByCustomer?->name ?? '—' }}</td>
                            <td class="px-5 py-2 text-gray-500">{{ $transaction->notes ?? '—' }}</td>
                            <td class="px-5 py-2 text-end text-gray-500">{{ $transaction->created_at?->format('Y-m-d H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-6 text-center text-gray-400">{{ __('admin.gift_cards_section.no_transactions_yet') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
@endsection
