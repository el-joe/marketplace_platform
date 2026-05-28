@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js'])
@endpush

@section('title', $customer->name . ' — Customer')

@section('content')

@php
    $statusColors = [
        'active'    => 'success',
        'suspended' => 'warning',
        'banned'    => 'danger',
    ];
    $statusColor = $statusColors[$customer->status] ?? 'gray';

    $reviewStatusColors = [
        'pending'  => 'gray',
        'approved' => 'success',
        'rejected' => 'danger',
    ];
@endphp

{{-- ─── Page header ─────────────────────────────────────────────────────────── --}}
<div class="mb-6 flex items-start justify-between gap-4">
    <div class="flex items-center gap-4 min-w-0">
        <div class="w-14 h-14 rounded-xl bg-primary-100 text-primary-700 flex items-center justify-center text-xl font-bold flex-shrink-0 select-none">
            {{ strtoupper(substr($customer->name, 0, 2)) }}
        </div>
        <div class="min-w-0">
            <h1 class="text-2xl font-bold text-gray-900 truncate">{{ $customer->name }}</h1>
            <div class="flex items-center gap-2 mt-1 flex-wrap">
                <x-badge :color="$statusColor" size="md">
                    {{ ucfirst($customer->status) }}
                </x-badge>
                @if($customer->email_verified_at)
                    <x-badge color="success">Email Verified</x-badge>
                @endif
            </div>
        </div>
    </div>
    <a href="{{ route('admin.customers.index') }}" class="text-sm text-gray-500 hover:text-gray-700 flex-shrink-0 mt-1">← Back</a>
</div>

{{-- ─── Two-column layout ───────────────────────────────────────────────────── --}}
<div class="grid grid-cols-12 gap-6">

    {{-- ══ MAIN (8/12) ══════════════════════════════════════════════════════ --}}
    <div class="col-span-12 lg:col-span-8 space-y-6">

        <div x-data="{ tab: 'orders' }">
            {{-- Tab bar --}}
            <div class="flex gap-1 border-b border-gray-200 overflow-x-auto pb-px mb-6">
                @foreach([
                    'orders'          => 'Orders',
                    'addresses'       => 'Addresses',
                    'reviews'         => 'Reviews',
                    'returns'         => 'Returns',
                    'disputes'        => 'Disputes',
                    'tickets'         => 'Tickets',
                    'payment_methods' => 'Payment Methods',
                    'activity'        => 'Activity',
                ] as $key => $label)
                    <button type="button"
                            @click="tab = '{{ $key }}'"
                            :class="tab === '{{ $key }}' ? 'border-b-2 border-primary-600 text-primary-700 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                            class="px-4 py-2 text-sm whitespace-nowrap transition-colors focus:outline-none">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            {{-- ── Orders ─────────────────────────────────────────────────── --}}
            <div x-show="tab === 'orders'">
                <x-card title="Recent Orders" subtitle="Latest 20 orders">
                    <x-slot name="actions">
                        <a href="{{ route('admin.customers.orders.datatable', $customer->id) }}" class="text-xs text-primary-600 hover:underline">Full list (DataTable)</a>
                    </x-slot>
                    @if($orders->isEmpty())
                        <p class="text-sm text-gray-500 py-4 text-center">No orders yet.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-100 text-left text-xs text-gray-500 uppercase">
                                        <th class="py-2 pr-4">Order #</th>
                                        <th class="py-2 pr-4">Total</th>
                                        <th class="py-2 pr-4">Status</th>
                                        <th class="py-2 pr-4">Placed</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    @foreach($orders as $order)
                                    @php
                                        $orderStatusColors = [
                                            'pending'          => 'gray',
                                            'confirmed'        => 'primary',
                                            'processing'       => 'primary',
                                            'shipped'          => 'primary',
                                            'out_for_delivery' => 'warning',
                                            'delivered'        => 'success',
                                            'completed'        => 'success',
                                            'cancelled'        => 'danger',
                                            'returned'         => 'danger',
                                            'refunded'         => 'danger',
                                        ];
                                        $oColor = $orderStatusColors[$order->status] ?? 'gray';
                                    @endphp
                                    <tr class="hover:bg-gray-50">
                                        <td class="py-2 pr-4 font-mono text-xs">{{ $order->order_number }}</td>
                                        <td class="py-2 pr-4">{{ number_format((float) $order->total, 2) }} {{ strtoupper($order->currency ?? '') }}</td>
                                        <td class="py-2 pr-4">
                                            <x-badge :color="$oColor">{{ ucwords(str_replace('_', ' ', $order->status)) }}</x-badge>
                                        </td>
                                        <td class="py-2 pr-4 text-gray-500">
                                            {{ $order->placed_at ? \Carbon\Carbon::parse($order->placed_at)->format('d M Y') : '—' }}
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </x-card>
            </div>

            {{-- ── Addresses ───────────────────────────────────────────────── --}}
            <div x-show="tab === 'addresses'">
                <x-card title="Saved Addresses">
                    @if($customer->addresses->isEmpty())
                        <p class="text-sm text-gray-500 py-4 text-center">No addresses saved.</p>
                    @else
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            @foreach($customer->addresses as $address)
                            <div class="rounded-lg border border-gray-200 p-4 text-sm space-y-1">
                                <div class="flex items-center justify-between gap-2 mb-2">
                                    <span class="font-semibold text-gray-800">{{ $address->label ?: ucfirst($address->address_type ?? 'Address') }}</span>
                                    @if($address->is_default)
                                        <x-badge color="primary">Default</x-badge>
                                    @endif
                                </div>
                                <p class="text-gray-700">{{ $address->recipient_name }}</p>
                                @if($address->recipient_phone)
                                    <p class="text-gray-500">{{ $address->recipient_phone }}</p>
                                @endif
                                <p class="text-gray-600">
                                    {{ implode(', ', array_filter([
                                        $address->street_address,
                                        $address->building,
                                        $address->floor ? 'Floor ' . $address->floor : null,
                                        $address->apartment ? 'Apt ' . $address->apartment : null,
                                        $address->area,
                                        $address->city?->name_en ?? null,
                                        $address->country?->name_en ?? null,
                                        $address->postal_code,
                                    ])) }}
                                </p>
                            </div>
                            @endforeach
                        </div>
                    @endif
                </x-card>
            </div>

            {{-- ── Reviews ─────────────────────────────────────────────────── --}}
            <div x-show="tab === 'reviews'">
                <x-card title="Reviews" subtitle="Latest 20">
                    @if($reviews->isEmpty())
                        <p class="text-sm text-gray-500 py-4 text-center">No reviews yet.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-100 text-left text-xs text-gray-500 uppercase">
                                        <th class="py-2 pr-4">Product</th>
                                        <th class="py-2 pr-4">Rating</th>
                                        <th class="py-2 pr-4">Status</th>
                                        <th class="py-2 pr-4">Verified</th>
                                        <th class="py-2 pr-4">Date</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    @foreach($reviews as $review)
                                    @php $rColor = $reviewStatusColors[$review->status] ?? 'gray'; @endphp
                                    <tr class="hover:bg-gray-50">
                                        <td class="py-2 pr-4 max-w-[180px] truncate">
                                            {{ $review->product?->name ?? '—' }}
                                        </td>
                                        <td class="py-2 pr-4">
                                            <span class="inline-flex items-center gap-0.5 text-warning-500">
                                                @for($i = 1; $i <= 5; $i++)
                                                    <span>{{ $i <= $review->rating ? '★' : '☆' }}</span>
                                                @endfor
                                            </span>
                                        </td>
                                        <td class="py-2 pr-4">
                                            <x-badge :color="$rColor">{{ ucfirst($review->status) }}</x-badge>
                                        </td>
                                        <td class="py-2 pr-4">
                                            @if($review->is_verified_purchase)
                                                <x-badge color="success">Verified</x-badge>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="py-2 pr-4 text-gray-500">{{ $review->created_at->format('d M Y') }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </x-card>
            </div>

            {{-- ── Returns ─────────────────────────────────────────────────── --}}
            <div x-show="tab === 'returns'">
                <x-card title="Return Requests" subtitle="Latest 20">
                    @if($returnRequests->isEmpty())
                        <p class="text-sm text-gray-500 py-4 text-center">No return requests.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-100 text-left text-xs text-gray-500 uppercase">
                                        <th class="py-2 pr-4">Return #</th>
                                        <th class="py-2 pr-4">Type</th>
                                        <th class="py-2 pr-4">Status</th>
                                        <th class="py-2 pr-4">Created</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    @foreach($returnRequests as $ret)
                                    <tr class="hover:bg-gray-50">
                                        <td class="py-2 pr-4 font-mono text-xs">{{ $ret->return_number }}</td>
                                        <td class="py-2 pr-4">{{ ucfirst(str_replace('_', ' ', $ret->return_type ?? '—')) }}</td>
                                        <td class="py-2 pr-4">
                                            <x-badge color="gray">{{ ucfirst($ret->status) }}</x-badge>
                                        </td>
                                        <td class="py-2 pr-4 text-gray-500">{{ $ret->created_at->format('d M Y') }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </x-card>
            </div>

            {{-- ── Disputes ────────────────────────────────────────────────── --}}
            <div x-show="tab === 'disputes'">
                <x-card title="Disputes" subtitle="Latest 20">
                    @if($disputes->isEmpty())
                        <p class="text-sm text-gray-500 py-4 text-center">No disputes.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-100 text-left text-xs text-gray-500 uppercase">
                                        <th class="py-2 pr-4">Dispute #</th>
                                        <th class="py-2 pr-4">Reason</th>
                                        <th class="py-2 pr-4">Status</th>
                                        <th class="py-2 pr-4">Created</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    @foreach($disputes as $dispute)
                                    <tr class="hover:bg-gray-50">
                                        <td class="py-2 pr-4 font-mono text-xs">{{ $dispute->dispute_number }}</td>
                                        <td class="py-2 pr-4 max-w-[200px] truncate">{{ $dispute->reason }}</td>
                                        <td class="py-2 pr-4">
                                            <x-badge color="gray">{{ ucfirst(str_replace('_', ' ', $dispute->status)) }}</x-badge>
                                        </td>
                                        <td class="py-2 pr-4 text-gray-500">{{ $dispute->created_at->format('d M Y') }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </x-card>
            </div>

            {{-- ── Tickets ─────────────────────────────────────────────────── --}}
            <div x-show="tab === 'tickets'">
                <x-card title="Support Tickets" subtitle="Latest 20">
                    @if($tickets->isEmpty())
                        <p class="text-sm text-gray-500 py-4 text-center">No support tickets.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-100 text-left text-xs text-gray-500 uppercase">
                                        <th class="py-2 pr-4">Ticket #</th>
                                        <th class="py-2 pr-4">Subject</th>
                                        <th class="py-2 pr-4">Category</th>
                                        <th class="py-2 pr-4">Priority</th>
                                        <th class="py-2 pr-4">Status</th>
                                        <th class="py-2 pr-4">Created</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    @foreach($tickets as $ticket)
                                    @php
                                        $ticketPriorityColors = ['low' => 'gray', 'medium' => 'primary', 'high' => 'warning', 'urgent' => 'danger'];
                                        $ticketStatusColors = ['open' => 'primary', 'in_progress' => 'warning', 'resolved' => 'success', 'closed' => 'gray'];
                                        $pColor = $ticketPriorityColors[$ticket->priority ?? 'low'] ?? 'gray';
                                        $sColor = $ticketStatusColors[$ticket->status ?? 'open'] ?? 'gray';
                                    @endphp
                                    <tr class="hover:bg-gray-50">
                                        <td class="py-2 pr-4 font-mono text-xs">{{ $ticket->ticket_number }}</td>
                                        <td class="py-2 pr-4 max-w-[200px] truncate">{{ $ticket->subject }}</td>
                                        <td class="py-2 pr-4 text-gray-500">{{ ucfirst(str_replace('_', ' ', $ticket->category ?? '—')) }}</td>
                                        <td class="py-2 pr-4">
                                            <x-badge :color="$pColor">{{ ucfirst($ticket->priority ?? '—') }}</x-badge>
                                        </td>
                                        <td class="py-2 pr-4">
                                            <x-badge :color="$sColor">{{ ucwords(str_replace('_', ' ', $ticket->status ?? '—')) }}</x-badge>
                                        </td>
                                        <td class="py-2 pr-4 text-gray-500">{{ $ticket->created_at->format('d M Y') }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </x-card>
            </div>

            {{-- ── Payment Methods ──────────────────────────────────────────── --}}
            <div x-show="tab === 'payment_methods'">
                <x-card title="Payment Methods">
                    @if($paymentMethods->isEmpty())
                        <p class="text-sm text-gray-500 py-4 text-center">No saved payment methods.</p>
                    @else
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            @foreach($paymentMethods as $pm)
                            <div class="rounded-lg border border-gray-200 p-4 text-sm flex items-start justify-between gap-3">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <span class="font-semibold text-gray-800">
                                            {{ strtoupper($pm->card_brand ?? $pm->type ?? 'Card') }}
                                        </span>
                                        @if($pm->is_default)
                                            <x-badge color="primary">Default</x-badge>
                                        @endif
                                    </div>
                                    @if($pm->card_last4)
                                        <p class="text-gray-600 font-mono">•••• •••• •••• {{ $pm->card_last4 }}</p>
                                    @endif
                                    @if($pm->card_exp_month && $pm->card_exp_year)
                                        <p class="text-gray-500 text-xs">Expires {{ str_pad($pm->card_exp_month, 2, '0', STR_PAD_LEFT) }}/{{ $pm->card_exp_year }}</p>
                                    @endif
                                    <p class="text-gray-400 text-xs">{{ ucfirst($pm->gateway ?? $pm->type ?? '') }}</p>
                                </div>
                                @can('customers.edit')
                                <button type="button"
                                    class="btn btn-xs btn-ghost text-danger-600 js-remove-payment-method flex-shrink-0"
                                    data-id="{{ $pm->id }}"
                                    data-customer="{{ $customer->id }}">
                                    Remove
                                </button>
                                @endcan
                            </div>
                            @endforeach
                        </div>
                    @endif
                </x-card>
            </div>

            {{-- ── Activity ─────────────────────────────────────────────────── --}}
            <div x-show="tab === 'activity'">
                <x-card title="Activity Log" subtitle="Showing last 50 entries">
                    @if($activityLog->isEmpty())
                        <p class="text-sm text-gray-500 py-4 text-center">No activity recorded.</p>
                    @else
                        <ol class="relative border-l border-gray-200 ml-3 space-y-4 py-2">
                            @foreach($activityLog as $entry)
                            <li class="ml-6">
                                <span class="absolute -left-2 flex items-center justify-center w-4 h-4 rounded-full bg-primary-100 ring-4 ring-white">
                                    <span class="w-1.5 h-1.5 rounded-full bg-primary-600"></span>
                                </span>
                                <div class="text-sm">
                                    <p class="font-medium text-gray-800">{{ $entry->description }}</p>
                                    @if($entry->event)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs bg-gray-100 text-gray-600 mr-2">{{ $entry->event }}</span>
                                    @endif
                                    <span class="text-xs text-gray-400">{{ $entry->created_at->format('d M Y, H:i') }}</span>
                                    @if($entry->ip_address)
                                        <span class="text-xs text-gray-400 ml-2">· {{ $entry->ip_address }}</span>
                                    @endif
                                    @if($entry->properties && $entry->properties !== '[]' && $entry->properties !== '{}')
                                        @php $props = is_array($entry->properties) ? $entry->properties : json_decode($entry->properties, true); @endphp
                                        @if(!empty($props))
                                        <div class="mt-1 text-xs text-gray-500 bg-gray-50 rounded px-2 py-1 max-w-sm">
                                            @foreach($props as $k => $v)
                                                <span class="font-medium">{{ $k }}:</span> {{ is_array($v) ? json_encode($v) : $v }}{{ !$loop->last ? ' · ' : '' }}
                                            @endforeach
                                        </div>
                                        @endif
                                    @endif
                                </div>
                            </li>
                            @endforeach
                        </ol>
                    @endif
                </x-card>
            </div>

        </div>{{-- end x-data tabs --}}
    </div>

    {{-- ══ SIDEBAR (4/12) ════════════════════════════════════════════════════ --}}
    <div class="col-span-12 lg:col-span-4 space-y-5">

        {{-- Customer Info card --}}
        <x-card title="Customer Info">
            <div class="space-y-3 text-sm">
                <div class="flex flex-col items-center py-2">
                    <div class="w-16 h-16 rounded-full bg-primary-100 text-primary-700 flex items-center justify-center text-2xl font-bold select-none mb-2">
                        {{ strtoupper(substr($customer->name, 0, 2)) }}
                    </div>
                    <p class="font-semibold text-gray-900 text-base">{{ $customer->name }}</p>
                    <p class="text-xs text-gray-400">#{{ $customer->id }}</p>
                </div>

                <div class="border-t border-gray-100 pt-3 space-y-2.5">
                    {{-- Email --}}
                    <div class="flex items-start justify-between gap-2">
                        <span class="text-gray-500 shrink-0">Email</span>
                        <div class="text-right min-w-0">
                            <button type="button"
                                class="text-gray-900 hover:text-primary-600 font-mono text-xs truncate js-copy"
                                data-value="{{ $customer->email }}"
                                title="Click to copy">
                                {{ $customer->email }}
                            </button>
                            @if($customer->email_verified_at)
                                <x-badge color="success" class="ml-1">✓</x-badge>
                            @else
                                <x-badge color="warning" class="ml-1">Unverified</x-badge>
                            @endif
                        </div>
                    </div>

                    {{-- Phone --}}
                    <div class="flex items-start justify-between gap-2">
                        <span class="text-gray-500 shrink-0">Phone</span>
                        <div class="text-right min-w-0">
                            @if($customer->phone)
                                <button type="button"
                                    class="text-gray-900 hover:text-primary-600 font-mono text-xs js-copy"
                                    data-value="{{ $customer->phone }}"
                                    title="Click to copy">
                                    {{ $customer->phone }}
                                </button>
                                @if($customer->phone_verified_at)
                                    <x-badge color="success" class="ml-1">✓</x-badge>
                                @else
                                    <x-badge color="warning" class="ml-1">Unverified</x-badge>
                                @endif
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </div>
                    </div>

                    {{-- Country --}}
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-gray-500">Country</span>
                        <span class="text-gray-900">
                            @if($customer->country)
                                {{ $customer->country->flag_emoji ?? '' }} {{ $customer->country->name_en }}
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </span>
                    </div>

                    {{-- Joined --}}
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-gray-500">Joined</span>
                        <span class="text-gray-900">{{ $customer->created_at->format('d M Y') }}</span>
                    </div>

                    {{-- Last login --}}
                    <div class="flex items-start justify-between gap-2">
                        <span class="text-gray-500 shrink-0">Last Login</span>
                        <div class="text-right">
                            <span class="text-gray-900 block">{{ $customer->last_login_at?->format('d M Y, H:i') ?? '—' }}</span>
                            @if($customer->last_login_ip)
                                <span class="text-xs text-gray-400 font-mono">{{ $customer->last_login_ip }}</span>
                            @endif
                        </div>
                    </div>

                    {{-- DOB --}}
                    @if($customer->date_of_birth)
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-gray-500">Date of Birth</span>
                        <span class="text-gray-900">{{ $customer->date_of_birth }}</span>
                    </div>
                    @endif
                </div>
            </div>
        </x-card>

        {{-- Stats card --}}
        <x-card title="Stats">
            <div class="space-y-2.5 text-sm">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-gray-500">Total Orders</span>
                    <span class="font-semibold text-gray-900">{{ number_format($customer->total_orders) }}</span>
                </div>
                <div class="flex items-center justify-between gap-2">
                    <span class="text-gray-500">Total Spent</span>
                    <span class="font-semibold text-gray-900">{{ number_format((float) $customer->total_spent, 2) }}</span>
                </div>
                <div class="flex items-center justify-between gap-2">
                    <span class="text-gray-500">Loyalty Points</span>
                    <span class="font-semibold text-primary-700" id="loyalty-balance-display">{{ number_format((float) $customer->loyalty_points, 2) }}</span>
                </div>
                <div class="flex items-center justify-between gap-2">
                    <span class="text-gray-500">Referral Code</span>
                    @if($customer->referral_code)
                        <button type="button"
                            class="text-gray-900 hover:text-primary-600 font-mono text-xs js-copy"
                            data-value="{{ $customer->referral_code }}"
                            title="Click to copy">
                            {{ $customer->referral_code }}
                        </button>
                    @else
                        <span class="text-gray-400">—</span>
                    @endif
                </div>
                @if($customer->referredBy)
                <div class="flex items-center justify-between gap-2">
                    <span class="text-gray-500">Referred By</span>
                    <a href="{{ route('admin.customers.show', $customer->referredBy->id) }}"
                       class="text-primary-600 hover:underline text-xs">
                        {{ $customer->referredBy->name }}
                    </a>
                </div>
                @endif
            </div>
        </x-card>

        {{-- Status & Actions card --}}
        <x-card title="Status & Actions">
            <div class="space-y-4">
                <div class="flex items-center gap-3">
                    <x-badge :color="$statusColor" size="md">{{ ucfirst($customer->status) }}</x-badge>
                </div>

                {{-- Action buttons --}}
                @can('customers.suspend')
                <div class="flex flex-col gap-2">
                    @if($customer->status === 'active')
                        <button type="button"
                            class="btn btn-warning btn-sm w-full js-suspend-btn"
                            data-url="{{ route('admin.customers.suspend', $customer->id) }}"
                            data-name="{{ $customer->name }}"
                            data-modal="suspend-modal">
                            Suspend Customer
                        </button>
                        <button type="button"
                            class="btn btn-danger btn-sm w-full js-ban-btn"
                            data-url="{{ route('admin.customers.ban', $customer->id) }}"
                            data-name="{{ $customer->name }}"
                            data-modal="ban-modal">
                            Ban Customer
                        </button>
                    @elseif($customer->status === 'suspended')
                        <button type="button"
                            class="btn btn-success btn-sm w-full js-reactivate-btn"
                            data-url="{{ route('admin.customers.reactivate', $customer->id) }}"
                            data-name="{{ $customer->name }}">
                            Reactivate
                        </button>
                        <button type="button"
                            class="btn btn-danger btn-sm w-full js-ban-btn"
                            data-url="{{ route('admin.customers.ban', $customer->id) }}"
                            data-name="{{ $customer->name }}"
                            data-modal="ban-modal">
                            Escalate to Ban
                        </button>
                    @elseif($customer->status === 'banned')
                        <button type="button"
                            class="btn btn-success btn-sm w-full js-reactivate-btn"
                            data-url="{{ route('admin.customers.reactivate', $customer->id) }}"
                            data-name="{{ $customer->name }}">
                            Reactivate
                        </button>
                    @endif
                </div>
                @endcan

                @can('customers.edit')
                <div class="border-t border-gray-100 pt-3 flex flex-col gap-2">
                    <button type="button"
                        id="open-loyalty-modal"
                        class="btn btn-secondary btn-sm w-full">
                        Adjust Loyalty Points
                    </button>
                    <button type="button"
                        id="open-notification-modal"
                        class="btn btn-ghost btn-sm w-full">
                        Send Notification
                    </button>
                </div>
                @endcan

                @can('customers.view')
                <div class="border-t border-gray-100 pt-3">
                    <a href="{{ route('admin.customers.export', $customer->id) }}"
                       class="btn btn-ghost btn-sm w-full text-center"
                       target="_blank">
                        Export Data (GDPR)
                    </a>
                </div>
                @endcan
            </div>
        </x-card>

    </div>{{-- end sidebar --}}
</div>{{-- end grid --}}

{{-- ═══════════════════════════════════════════════════════════════════════════
  ─── MODALS ───────────────────────────────────────────────────────────────
══════════════════════════════════════════════════════════════════════════════ --}}

{{-- ─── Suspend modal ──────────────────────────────────────────────────────── --}}
<x-modal id="suspend-modal" title="Suspend Customer" size="sm">
    <p class="text-sm text-gray-600 mb-3">You are about to suspend <strong>{{ $customer->name }}</strong>.</p>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Reason <span class="text-danger-500">*</span></label>
        <textarea id="suspend-reason" class="form-input w-full resize-none" rows="3" placeholder="Enter reason…"></textarea>
    </div>
    <x-slot name="footer">
        <button type="button" data-modal-close class="btn btn-ghost btn-sm">Cancel</button>
        <button type="button" id="suspend-confirm-btn"
            class="btn btn-warning btn-sm"
            data-url="{{ route('admin.customers.suspend', $customer->id) }}">
            Suspend
        </button>
    </x-slot>
</x-modal>

{{-- ─── Ban modal ───────────────────────────────────────────────────────────── --}}
<x-modal id="ban-modal" title="Ban Customer" size="sm" :persistent="true">
    <p class="text-sm text-gray-600 mb-3">You are about to <strong class="text-danger-700">permanently ban</strong> <strong>{{ $customer->name }}</strong>. This will revoke all active sessions.</p>
    <div class="mb-3">
        <label class="block text-sm font-medium text-gray-700 mb-1">Reason <span class="text-danger-500">*</span></label>
        <textarea id="ban-reason" class="form-input w-full resize-none" rows="3" placeholder="Enter reason…"></textarea>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Type <code class="bg-gray-100 px-1 rounded text-xs">CONFIRM</code> to proceed</label>
        <input type="text" id="ban-confirm-input" class="form-input w-full" placeholder="CONFIRM" autocomplete="off">
    </div>
    <x-slot name="footer">
        <button type="button" data-modal-close class="btn btn-ghost btn-sm">Cancel</button>
        <button type="button" id="ban-confirm-btn"
            class="btn btn-danger btn-sm"
            disabled
            data-url="{{ route('admin.customers.ban', $customer->id) }}">
            Ban Customer
        </button>
    </x-slot>
</x-modal>

{{-- ─── Adjust loyalty points modal ────────────────────────────────────────── --}}
<x-modal id="adjust-loyalty-modal" title="Adjust Loyalty Points" size="sm">
    <div class="space-y-4">
        <div class="rounded-lg bg-primary-50 border border-primary-100 px-4 py-3 text-sm">
            <div class="flex items-center justify-between">
                <span class="text-primary-700">Current balance</span>
                <span class="font-bold text-primary-800" id="current-loyalty">{{ number_format((float) $customer->loyalty_points, 2) }}</span>
            </div>
            <div class="flex items-center justify-between mt-1">
                <span class="text-primary-700">New balance</span>
                <span class="font-bold text-primary-900" id="preview-loyalty">{{ number_format((float) $customer->loyalty_points, 2) }}</span>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Adjustment <span class="text-danger-500">*</span>
                <span class="font-normal text-gray-400">(use negative to deduct)</span>
            </label>
            <input type="number" id="adjustment-input"
                class="form-input w-full"
                placeholder="e.g. 100 or -50"
                data-current="{{ (float) $customer->loyalty_points }}">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Reason <span class="text-danger-500">*</span></label>
            <input type="text" id="loyalty-reason" class="form-input w-full" placeholder="e.g. Manual compensation">
        </div>
    </div>
    <x-slot name="footer">
        <button type="button" data-modal-close class="btn btn-ghost btn-sm">Cancel</button>
        <button type="button" id="loyalty-confirm-btn"
            class="btn btn-primary btn-sm"
            data-url="{{ route('admin.customers.adjust-loyalty', $customer->id) }}">
            Apply
        </button>
    </x-slot>
</x-modal>

{{-- ─── Send notification modal ─────────────────────────────────────────────── --}}
<x-modal id="send-notification-modal" title="Send Notification" size="sm">
    <div class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Channel <span class="text-danger-500">*</span></label>
            <select id="notif-channel" class="form-input w-full">
                <option value="database">In-App (Database)</option>
                <option value="email">Email</option>
                <option value="sms">SMS</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Title <span class="text-danger-500">*</span></label>
            <input type="text" id="notif-title" class="form-input w-full" placeholder="Notification title">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Message <span class="text-danger-500">*</span></label>
            <textarea id="notif-message" class="form-input w-full resize-none" rows="4" placeholder="Message body…"></textarea>
        </div>
    </div>
    <x-slot name="footer">
        <button type="button" data-modal-close class="btn btn-ghost btn-sm">Cancel</button>
        <button type="button" id="notif-send-btn"
            class="btn btn-primary btn-sm"
            data-url="{{ route('admin.customers.send-notification', $customer->id) }}">
            Send
        </button>
    </x-slot>
</x-modal>

@endsection

@push('scripts')
    @vite(['resources/js/admin/customers.js'])
@endpush
