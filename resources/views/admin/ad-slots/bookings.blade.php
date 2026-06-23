@extends('layouts.admin')

@section('title', 'Bookings for: ' . $adSlot->name)

@section('content')

    <div class="mb-6 flex items-start justify-between gap-4 flex-wrap">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('admin.ad-slots.index') }}" class="hover:text-primary-600">Ad Slots</a>
                <span>/</span>
                <span class="text-gray-800 font-medium">{{ $adSlot->name }}</span>
                <span>/</span>
                <span class="text-gray-800 font-medium">Bookings</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">Bookings</h1>
            <p class="text-sm text-gray-500 mt-0.5">
                Slot: <strong>{{ $adSlot->name }}</strong>
                <span class="font-mono ml-2 text-xs text-gray-400">{{ $adSlot->slot_code }}</span>
            </p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.ad-slots.edit', $adSlot->id) }}" class="btn btn-secondary btn-sm">Edit Slot</a>
            <a href="{{ route('admin.ad-slots.index') }}" class="btn btn-secondary btn-sm">← All Slots</a>
        </div>
    </div>

    {{-- Slot summary card --}}
    <x-card class="mb-6">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
            <div>
                <dt class="text-xs text-gray-400 uppercase font-medium mb-0.5">Pricing Model</dt>
                <dd class="font-semibold uppercase">{{ $adSlot->pricing_model ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs text-gray-400 uppercase font-medium mb-0.5">Base Rate</dt>
                <dd class="font-semibold">${{ number_format(($adSlot->base_rate_cents ?? 0) / 100, 2) }}</dd>
            </div>
            <div>
                <dt class="text-xs text-gray-400 uppercase font-medium mb-0.5">Status</dt>
                <dd>
                    @if($adSlot->is_available)
                        <span
                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-success-100 text-success-700">Available</span>
                    @else
                        <span
                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Unavailable</span>
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-xs text-gray-400 uppercase font-medium mb-0.5">Country</dt>
                <dd>{{ $adSlot->country?->flag_emoji ?? '' }} {{ $adSlot->country?->name_en ?? '—' }}</dd>
            </div>
        </div>
    </x-card>

    {{-- Bookings table --}}
    <x-card title="Bookings">
        @if($bookings->isEmpty())
            <p class="text-sm text-gray-400 py-6 text-center">No bookings found for this slot.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-left">
                            <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Reference</th>
                            <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Vendor</th>
                            <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Dates</th>
                            <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Rate</th>
                            <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Payment</th>
                            <th class="py-2 text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($bookings as $booking)
                            @php
                                $statusColors = ['pending' => 'warning', 'active' => 'success', 'rejected' => 'danger', 'cancelled' => 'gray', 'ended' => 'gray'];
                                $sc = $statusColors[$booking->status] ?? 'gray';
                                $payColors = ['unpaid' => 'danger', 'paid' => 'success', 'invoiced' => 'warning', 'refunded' => 'gray'];
                                $pc = $payColors[$booking->payment_status ?? 'unpaid'] ?? 'gray';
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="py-2 pr-4 font-mono text-xs">{{ $booking->booking_reference }}</td>
                                <td class="py-2 pr-4 font-medium">{{ $booking->vendor?->store_name ?? '—' }}</td>
                                <td class="py-2 pr-4 text-gray-600">
                                    {{ $booking->booked_from ? \Carbon\Carbon::parse($booking->booked_from)->format('d M') : '—' }}
                                    –
                                    {{ $booking->booked_until ? \Carbon\Carbon::parse($booking->booked_until)->format('d M Y') : '—' }}
                                </td>
                                <td class="py-2 pr-4">${{ number_format($booking->agreed_rate_cents / 100, 2) }}</td>
                                <td class="py-2 pr-4">
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-{{ $sc }}-100 text-{{ $sc }}-700">
                                        {{ ucfirst($booking->status) }}
                                    </span>
                                </td>
                                <td class="py-2 pr-4">
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-{{ $pc }}-100 text-{{ $pc }}-700">
                                        {{ ucfirst($booking->payment_status ?? 'unpaid') }}
                                    </span>
                                </td>
                                <td class="py-2">
                                    <a href="{{ route('admin.paid-ad-bookings.show', $booking->id) }}"
                                        class="btn btn-xs btn-secondary">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $bookings->links() }}
            </div>
        @endif
    </x-card>

@endsection