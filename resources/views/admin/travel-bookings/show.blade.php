@extends('layouts.admin')

@section('title', 'Booking ' . $travelBooking->booking_number)

@section('content')
<div class="p-6 space-y-6 max-w-4xl">

    {{-- ─── Header ──────────────────────────────────────────────────────────────── --}}
    <div class="flex items-start justify-between gap-4">
        <div>
            <a href="{{ route('admin.travel.bookings.index') }}" class="text-xs text-gray-400 hover:text-gray-600 mb-1 inline-block">← Travel Bookings</a>
            <h1 class="text-2xl font-bold text-gray-900 font-mono">{{ $travelBooking->booking_number }}</h1>
            <p class="text-sm text-gray-500 mt-0.5">Booked {{ $travelBooking->created_at->format('d M Y H:i') }}</p>
        </div>
        @php
        $statusColors = [
            'pending_documents' => 'bg-amber-100 text-amber-700',
            'confirmed'         => 'bg-emerald-100 text-emerald-700',
            'cancelled'         => 'bg-red-100 text-red-700',
            'completed'         => 'bg-gray-100 text-gray-600',
        ];
        @endphp
        <span class="inline-block px-3 py-1 rounded-full text-sm font-medium {{ $statusColors[$travelBooking->status] ?? 'bg-gray-100 text-gray-600' }}">
            {{ ucfirst(str_replace('_', ' ', $travelBooking->status)) }}
        </span>
    </div>

    {{-- ─── Main grid ───────────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

        {{-- Customer --}}
        <x-card>
            <h3 class="font-semibold text-gray-700 text-xs uppercase tracking-wide mb-3">Customer</h3>
            @php $customer = $travelBooking->customer; @endphp
            @if($customer)
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-gray-500">Name</dt><dd class="text-gray-900 font-medium">{{ $customer->name }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Email</dt><dd class="text-gray-900">{{ $customer->email }}</dd></div>
                @if($customer->phone)
                <div class="flex justify-between"><dt class="text-gray-500">Phone</dt><dd class="text-gray-900">{{ $customer->phone }}</dd></div>
                @endif
            </dl>
            @else
                <p class="text-sm text-gray-400">Customer not found.</p>
            @endif
        </x-card>

        {{-- Booking Details --}}
        <x-card>
            <h3 class="font-semibold text-gray-700 text-xs uppercase tracking-wide mb-3">Booking Details</h3>
            @php $package = $travelBooking->package; @endphp
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-gray-500">Travelers</dt><dd class="text-gray-900 font-semibold">{{ $travelBooking->travelers_count }}</dd></div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Total Amount</dt>
                    <dd class="text-gray-900 font-semibold">
                        {{ $package?->currency ?? '' }} {{ number_format($travelBooking->total_price_cents / 100, 2) }}
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Contract Signed</dt>
                    <dd class="text-gray-900">
                        {{ $travelBooking->contract_signed_at ? $travelBooking->contract_signed_at->format('d M Y H:i') : '—' }}
                    </dd>
                </div>
                @if($travelBooking->passport_file_path)
                <div class="flex justify-between">
                    <dt class="text-gray-500">Passport</dt>
                    <dd><a href="/storage/{{ $travelBooking->passport_file_path }}" target="_blank" class="text-primary-600 text-xs hover:underline">View file</a></dd>
                </div>
                @endif
            </dl>
        </x-card>

    </div>

    {{-- ─── Package ──────────────────────────────────────────────────────────────── --}}
    @if($package)
    <x-card>
        <h3 class="font-semibold text-gray-700 text-xs uppercase tracking-wide mb-3">Package</h3>
        <div class="flex items-start gap-4">
            @php $cover = $package->media->where('media_type', 'image')->first(); @endphp
            @if($cover)
            <img src="/storage/{{ $cover->file_path }}" class="w-24 h-24 rounded-lg object-cover border border-gray-200 shrink-0" alt="">
            @endif
            <div class="flex-1">
                <p class="font-semibold text-gray-900">{{ $package->title_en }}</p>
                <p class="text-sm text-gray-500 mt-0.5">{{ $package->agency?->name }}</p>
                <dl class="mt-2 grid grid-cols-2 gap-x-8 gap-y-1 text-sm">
                    <div class="flex justify-between"><dt class="text-gray-500">Departure</dt><dd class="text-gray-900">{{ $package->departure_date->format('d M Y') }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Return</dt><dd class="text-gray-900">{{ $package->return_date->format('d M Y') }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Duration</dt><dd class="text-gray-900">{{ $package->duration_days }}d / {{ $package->duration_nights }}n</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Price/seat</dt><dd class="text-gray-900">{{ $package->priceFormatted() }}</dd></div>
                </dl>
                <div class="mt-3">
                    <a href="{{ route('admin.travel.packages.show', $package->id) }}" class="btn btn-secondary btn-sm">View Package</a>
                </div>
            </div>
        </div>
    </x-card>
    @endif

    {{-- ─── Read-only note ──────────────────────────────────────────────────────── --}}
    <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 text-sm text-blue-700">
        This is a read-only oversight view. Booking modifications and cancellations are handled through documented agency and customer support processes, not direct status edits.
    </div>

</div>
@endsection
