@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/admin/transactions.js'])
@endpush

@section('title', 'Transaction: ' . ($transaction->gateway_transaction_id ?? $transaction->id))

@section('content')

    {{-- ─── Header ───────────────────────────────────────────────────────────────── --}}
    <div class="mb-6 flex items-start justify-between gap-4 flex-wrap">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('admin.transactions.index') }}" class="hover:text-primary-600">Transactions</a>
                <span>/</span>
                <span class="text-gray-800 font-mono text-xs">{{ $transaction->gateway_transaction_id ?? $transaction->id }}</span>
            </div>
            <h1 class="text-xl font-bold text-gray-900">Transaction Detail</h1>
        </div>
        <a href="{{ route('admin.transactions.index') }}" class="btn btn-secondary btn-sm">← Back</a>
    </div>

    <div class="grid grid-cols-12 gap-6">

        {{-- ═══════ LEFT: Main detail (8 cols) ═══════ --}}
        <div class="col-span-12 lg:col-span-8 space-y-6">

            {{-- ─── Transaction Card ─────────────────────────────────────────────── --}}
            <x-card title="Transaction">
                <dl class="grid grid-cols-2 gap-x-8 gap-y-4 text-sm">

                    <div>
                        <dt class="text-xs font-medium text-gray-400 uppercase mb-0.5">Gateway TX ID</dt>
                        <dd class="font-mono text-xs text-gray-800 break-all flex items-center gap-1">
                            {{ $transaction->gateway_transaction_id }}
                            <button type="button" class="text-gray-400 hover:text-primary-600 js-copy" data-value="{{ $transaction->gateway_transaction_id }}" title="Copy">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            </button>
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium text-gray-400 uppercase mb-0.5">Idempotency Key</dt>
                        <dd class="font-mono text-xs text-gray-600 break-all">{{ $transaction->idempotency_key ?? '—' }}</dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium text-gray-400 uppercase mb-0.5">Type</dt>
                        <dd>
                            @php
                                $typeBadge = match($transaction->type) {
                                    'authorization' => 'bg-blue-100 text-blue-700',
                                    'capture'       => 'bg-indigo-100 text-indigo-700',
                                    'sale'          => 'bg-green-100 text-green-700',
                                    'refund'        => 'bg-orange-100 text-orange-700',
                                    'void'          => 'bg-gray-100 text-gray-600',
                                    'chargeback'    => 'bg-red-100 text-red-700',
                                    default         => 'bg-gray-100 text-gray-700',
                                };
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium {{ $typeBadge }}">
                                {{ $transaction->type }}
                            </span>
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium text-gray-400 uppercase mb-0.5">Gateway</dt>
                        <dd>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-700">
                                {{ $transaction->gateway }}
                            </span>
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium text-gray-400 uppercase mb-0.5">Status</dt>
                        <dd>
                            @php
                                $statusBadge = match($transaction->status) {
                                    'pending'   => 'bg-yellow-100 text-yellow-700',
                                    'succeeded' => 'bg-green-100 text-green-700',
                                    'failed'    => 'bg-red-100 text-red-700',
                                    'cancelled' => 'bg-gray-100 text-gray-500',
                                    default     => 'bg-gray-100 text-gray-700',
                                };
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium {{ $statusBadge }}">
                                {{ $transaction->status }}
                            </span>
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium text-gray-400 uppercase mb-0.5">Currency</dt>
                        <dd class="font-mono text-sm">{{ $transaction->currency }}</dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium text-gray-400 uppercase mb-0.5">Amount</dt>
                        <dd class="text-lg font-bold tabular-nums text-gray-900">
                            {{ number_format($transaction->amount / 100, 2) }} {{ $transaction->currency }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium text-gray-400 uppercase mb-0.5">Gateway Fee</dt>
                        <dd class="tabular-nums text-sm text-red-600">
                            − {{ number_format($transaction->gateway_fee / 100, 2) }} {{ $transaction->currency }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium text-gray-400 uppercase mb-0.5">Net Amount</dt>
                        <dd class="tabular-nums text-sm font-semibold text-green-700">
                            {{ number_format(($transaction->amount - $transaction->gateway_fee) / 100, 2) }} {{ $transaction->currency }}
                        </dd>
                    </div>

                    @if($transaction->status === 'failed' && $transaction->failure_code)
                        <div class="col-span-2">
                            <dt class="text-xs font-medium text-gray-400 uppercase mb-0.5">Failure</dt>
                            <dd class="rounded bg-red-50 border border-red-200 px-3 py-2 text-sm text-red-800">
                                <span class="font-mono font-medium">{{ $transaction->failure_code }}</span>
                                @if($transaction->failure_message)
                                    — {{ $transaction->failure_message }}
                                @endif
                            </dd>
                        </div>
                    @endif

                    <div>
                        <dt class="text-xs font-medium text-gray-400 uppercase mb-0.5">Processed At</dt>
                        <dd class="text-sm text-gray-700">{{ $transaction->processed_at?->format('M d, Y H:i:s') ?? '—' }}</dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium text-gray-400 uppercase mb-0.5">Created At</dt>
                        <dd class="text-sm text-gray-700">{{ $transaction->created_at->format('M d, Y H:i:s') }}</dd>
                    </div>

                </dl>
            </x-card>

            {{-- ─── Raw Request ─────────────────────────────────────────────────── --}}
            @if($transaction->raw_request)
                <x-card>
                    <div x-data="{ open: false }">
                        <button
                            type="button"
                            class="flex w-full items-center justify-between text-sm font-medium text-gray-700 hover:text-gray-900"
                            @click="open = !open">
                            <span>Raw Request</span>
                            <svg class="w-4 h-4 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="open" x-cloak class="mt-4">
                            <pre class="overflow-x-auto rounded bg-gray-900 text-gray-100 text-xs p-4 leading-relaxed max-h-96"><code>{{ json_encode($transaction->raw_request, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</code></pre>
                        </div>
                    </div>
                </x-card>
            @endif

            {{-- ─── Raw Response ────────────────────────────────────────────────── --}}
            @if($transaction->raw_response)
                <x-card>
                    <div x-data="{ open: false }">
                        <button
                            type="button"
                            class="flex w-full items-center justify-between text-sm font-medium text-gray-700 hover:text-gray-900"
                            @click="open = !open">
                            <span>Raw Response</span>
                            <svg class="w-4 h-4 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="open" x-cloak class="mt-4">
                            <pre class="overflow-x-auto rounded bg-gray-900 text-gray-100 text-xs p-4 leading-relaxed max-h-96"><code>{{ json_encode($transaction->raw_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</code></pre>
                        </div>
                    </div>
                </x-card>
            @endif

            {{-- ─── Associated Refunds ──────────────────────────────────────────── --}}
            @if($transaction->refunds->isNotEmpty())
                <x-card title="Associated Refunds">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100 text-xs text-gray-500 uppercase text-left">
                                <th class="pb-2 pr-4 font-medium">Amount</th>
                                <th class="pb-2 pr-4 font-medium">Type</th>
                                <th class="pb-2 pr-4 font-medium">Reason</th>
                                <th class="pb-2 pr-4 font-medium">Status</th>
                                <th class="pb-2 font-medium">Created</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($transaction->refunds as $refund)
                                <tr>
                                    <td class="py-2 pr-4 font-semibold tabular-nums">{{ number_format($refund->amount / 100, 2) }} {{ $refund->currency }}</td>
                                    <td class="py-2 pr-4">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-700">{{ str_replace('_', ' ', $refund->refund_type) }}</span>
                                    </td>
                                    <td class="py-2 pr-4 text-gray-600">{{ $refund->reason }}</td>
                                    <td class="py-2 pr-4">
                                        @php
                                            $rsBadge = match($refund->status) {
                                                'pending'    => 'bg-yellow-100 text-yellow-700',
                                                'approved'   => 'bg-blue-100 text-blue-700',
                                                'completed'  => 'bg-green-100 text-green-700',
                                                'rejected'   => 'bg-red-100 text-red-700',
                                                default      => 'bg-gray-100 text-gray-700',
                                            };
                                        @endphp
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $rsBadge }}">{{ $refund->status }}</span>
                                    </td>
                                    <td class="py-2 text-gray-500">{{ $refund->created_at->format('M d, Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </x-card>
            @endif

        </div>

        {{-- ═══════ RIGHT: Quick Info (4 cols) ═══════ --}}
        <div class="col-span-12 lg:col-span-4 space-y-4">

            {{-- Order Card --}}
            <x-card title="Order">
                @if($transaction->order)
                    <div class="text-sm space-y-2">
                        <div>
                            <p class="text-xs text-gray-400 uppercase font-medium mb-0.5">Order Number</p>
                            <a href="{{ route('admin.orders.show', $transaction->order->id) }}"
                               class="text-primary-600 hover:underline font-mono" target="_blank">
                                {{ $transaction->order->order_number }}
                            </a>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase font-medium mb-0.5">Order Total</p>
                            <p class="font-semibold tabular-nums">{{ number_format($transaction->order->total / 100, 2) }} {{ $transaction->currency }}</p>
                        </div>
                    </div>
                @else
                    <p class="text-sm text-gray-400">No linked order.</p>
                @endif
            </x-card>

            {{-- Customer Card --}}
            <x-card title="Customer">
                @if($transaction->customer)
                    <div class="text-sm space-y-2">
                        <div>
                            <p class="text-xs text-gray-400 uppercase font-medium mb-0.5">Name</p>
                            <a href="{{ route('admin.customers.show', $transaction->customer->id) }}"
                               class="text-primary-600 hover:underline">
                                {{ $transaction->customer->name }}
                            </a>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase font-medium mb-0.5">Email</p>
                            <p class="text-gray-700">{{ $transaction->customer->email }}</p>
                        </div>
                    </div>
                @else
                    <p class="text-sm text-gray-400">No linked customer.</p>
                @endif
            </x-card>

            {{-- Transaction ID Card --}}
            <x-card title="Internal ID">
                <p class="font-mono text-xs text-gray-600 break-all">{{ $transaction->id }}</p>
            </x-card>

        </div>

    </div>

@endsection
