@extends('layouts.admin')

@section('title', 'COD Settlement — ' . $settlement->agent->name)

@section('content')

{{-- ─── Header ──────────────────────────────────────────────────────────────── --}}
<div class="mb-6 flex items-start justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">COD Settlement</h1>
        <p class="text-sm text-gray-500 mt-0.5">
            {{ $settlement->agent->name }} &bull;
            {{ $settlement->period_start->format('M d, Y') }} – {{ $settlement->period_end->format('M d, Y') }}
        </p>
    </div>
    <div class="flex gap-2">
        @php
            $badgeClass = match($settlement->status) {
                'settled'  => 'badge-success',
                'disputed' => 'badge-warning',
                default    => 'badge-gray',
            };
        @endphp
        <span class="badge {{ $badgeClass }} text-sm capitalize">{{ $settlement->status }}</span>
    </div>
</div>

{{-- ─── Net to Remit Banner ─────────────────────────────────────────────────── --}}
<div class="bg-blue-50 border border-blue-200 rounded-xl p-5 mb-6 flex items-center justify-between gap-4">
    <div>
        <p class="text-xs font-semibold text-blue-600 uppercase tracking-wide mb-0.5">Agent Must Remit</p>
        <p class="text-3xl font-bold text-blue-800">{{ number_format($settlement->net_to_remit_cents / 100, 2) }}</p>
        <p class="text-xs text-blue-500 mt-1">
            Collected {{ number_format($settlement->total_cod_collected_cents / 100, 2) }}
            &minus; Earnings {{ number_format($settlement->total_earnings_owed_cents / 100, 2) }}
        </p>
    </div>
    @if($settlement->status === 'pending')
        <div class="flex gap-2">
            <button type="button" id="settle-btn" class="btn btn-success btn-sm">Mark as Settled</button>
            <button type="button" id="dispute-btn" class="btn btn-warning btn-sm">Flag Dispute</button>
        </div>
    @elseif($settlement->status === 'settled')
        <div class="text-right">
            <p class="text-xs text-green-600 font-semibold">Settled {{ $settlement->settled_at?->format('M d, Y H:i') }}</p>
            @if($settlement->notes)
                <p class="text-xs text-gray-500 mt-1 max-w-xs">{{ $settlement->notes }}</p>
            @endif
        </div>
    @endif
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- ─── Left: Deliveries & Earnings ──────────────────────────────────────── --}}
    <div class="lg:col-span-2 space-y-6">

        {{-- Delivery Assignments --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-semibold text-gray-800 text-sm">Deliveries in This Settlement</h2>
                <span class="text-xs text-gray-500">{{ $assignments->count() }} deliveries</span>
            </div>
            @if($assignments->isEmpty())
                <p class="px-4 py-6 text-sm text-gray-400 text-center">No assignments linked to this settlement.</p>
            @else
                <table class="min-w-full divide-y divide-gray-100 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Order</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Delivered</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">COD Collected</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($assignments as $a)
                            <tr>
                                <td class="px-4 py-2">
                                    @if($a->subOrder?->order)
                                        <span class="font-mono text-xs">{{ $a->subOrder->order->order_number ?? $a->subOrder->order->id }}</span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-xs text-gray-600">{{ $a->delivered_at?->format('M d, Y') ?? '—' }}</td>
                                <td class="px-4 py-2 text-right font-medium">{{ number_format($a->cod_amount_collected_cents / 100, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr>
                            <td colspan="2" class="px-4 py-2 text-xs font-semibold text-gray-600 uppercase">Total</td>
                            <td class="px-4 py-2 text-right font-bold">{{ number_format($settlement->total_cod_collected_cents / 100, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            @endif
        </div>

        {{-- Earnings --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-semibold text-gray-800 text-sm">Agent Earnings (Deducted)</h2>
                <span class="text-xs text-gray-500">{{ $earnings->count() }} entries</span>
            </div>
            @if($earnings->isEmpty())
                <p class="px-4 py-6 text-sm text-gray-400 text-center">No earnings in this period.</p>
            @else
                <table class="min-w-full divide-y divide-gray-100 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($earnings as $e)
                            <tr>
                                <td class="px-4 py-2 capitalize text-xs">{{ str_replace('_', ' ', $e->earning_type) }}</td>
                                <td class="px-4 py-2">
                                    <span class="badge badge-gray text-xs capitalize">{{ $e->status }}</span>
                                </td>
                                <td class="px-4 py-2 text-right">{{ number_format($e->amount_cents / 100, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr>
                            <td colspan="2" class="px-4 py-2 text-xs font-semibold text-gray-600 uppercase">Total</td>
                            <td class="px-4 py-2 text-right font-bold">{{ number_format($settlement->total_earnings_owed_cents / 100, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            @endif
        </div>

        {{-- Open (unsettled) assignments --}}
        @if($openAssignments->isNotEmpty())
            <div class="bg-white rounded-xl border border-amber-200 overflow-hidden">
                <div class="px-4 py-3 border-b border-amber-100 bg-amber-50">
                    <h2 class="font-semibold text-amber-800 text-sm">Unsettled Deliveries (not in this settlement)</h2>
                </div>
                <table class="min-w-full divide-y divide-gray-100 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Order</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Delivered</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">COD Collected</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($openAssignments as $a)
                            <tr>
                                <td class="px-4 py-2">
                                    @if($a->subOrder?->order)
                                        <span class="font-mono text-xs">{{ $a->subOrder->order->order_number ?? $a->subOrder->order->id }}</span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-xs text-gray-600">{{ $a->delivered_at?->format('M d, Y') ?? '—' }}</td>
                                <td class="px-4 py-2 text-right font-medium">{{ number_format($a->cod_amount_collected_cents / 100, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

    </div>

    {{-- ─── Right: History + Settle Form ──────────────────────────────────────── --}}
    <div class="space-y-6">

        {{-- Mark as Settled Form --}}
        @if($settlement->status === 'pending')
            <div class="bg-white rounded-xl border border-gray-200 p-4" id="settle-form-panel">
                <h2 class="font-semibold text-gray-800 text-sm mb-3">Mark as Settled</h2>
                <form id="settle-form" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Payment Reference <span class="text-red-500">*</span></label>
                        <input type="text" name="payment_reference" required
                            placeholder="Bank transfer ID, receipt no…"
                            class="form-input w-full text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">
                            Actual Amount Remitted <span class="text-red-500">*</span>
                            <span class="text-gray-400 font-normal">(in cents)</span>
                        </label>
                        <input type="number" name="actual_amount_remitted" required
                            value="{{ $settlement->net_to_remit_cents }}"
                            class="form-input w-full text-sm">
                        <p class="text-xs text-gray-400 mt-0.5">Expected: {{ number_format($settlement->net_to_remit_cents / 100, 2) }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Notes</label>
                        <textarea name="notes" rows="2" class="form-input w-full text-sm" placeholder="Optional — required if amount differs"></textarea>
                    </div>
                    <button type="submit" class="btn btn-success btn-sm w-full">Confirm Settlement</button>
                </form>
            </div>
        @endif

        {{-- Settlement History --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100">
                <h2 class="font-semibold text-gray-800 text-sm">Settlement History</h2>
            </div>
            <ul class="divide-y divide-gray-50">
                @forelse($history as $h)
                    <li class="px-4 py-3 @if($h->id === $settlement->id) bg-blue-50 @endif">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <p class="text-xs font-medium text-gray-800">
                                    {{ $h->period_start->format('M d') }} – {{ $h->period_end->format('M d, Y') }}
                                </p>
                                <p class="text-xs text-gray-500">Net: {{ number_format($h->net_to_remit_cents / 100, 2) }}</p>
                                @if($h->settled_at)
                                    <p class="text-xs text-gray-400">{{ $h->settled_at->format('M d, Y') }}</p>
                                @endif
                            </div>
                            <div class="flex flex-col items-end gap-1">
                                @php
                                    $bc = match($h->status) { 'settled' => 'badge-success', 'disputed' => 'badge-warning', default => 'badge-gray' };
                                @endphp
                                <span class="badge {{ $bc }} text-xs capitalize">{{ $h->status }}</span>
                                @if($h->id !== $settlement->id)
                                    <a href="{{ route('admin.delivery.cod-settlements.show', $h) }}" class="text-xs text-primary-600 hover:underline">View</a>
                                @endif
                            </div>
                        </div>
                    </li>
                @empty
                    <li class="px-4 py-6 text-sm text-gray-400 text-center">No history.</li>
                @endforelse
            </ul>
        </div>

    </div>
</div>

{{-- ─── Dispute Modal ───────────────────────────────────────────────────────── --}}
<div id="dispute-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Flag as Disputed</h2>
        <form id="dispute-form" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Reason <span class="text-red-500">*</span></label>
                <textarea name="reason" required rows="3" class="form-input w-full" placeholder="Describe the discrepancy or issue…"></textarea>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" id="dispute-cancel" class="btn btn-secondary btn-sm">Cancel</button>
                <button type="submit" class="btn btn-warning btn-sm">Flag Dispute</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    const SETTLE_URL  = @json(route('admin.delivery.cod-settlements.settle', $settlement));
    const DISPUTE_URL = @json(route('admin.delivery.cod-settlements.dispute', $settlement));

    function token() { return document.querySelector('meta[name="csrf-token"]').content; }

    // ── Settle ────────────────────────────────────────────────────────────────
    document.getElementById('settle-btn')?.addEventListener('click', () => {
        document.getElementById('settle-form-panel')?.scrollIntoView({ behavior: 'smooth' });
    });

    document.getElementById('settle-form')?.addEventListener('submit', function (e) {
        e.preventDefault();
        const data = new FormData(this);
        data.set('_token', token());

        fetch(SETTLE_URL, { method: 'POST', body: data })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    window.Toast?.success(res.message);
                    setTimeout(() => window.location.reload(), 800);
                } else {
                    window.Toast?.error(res.message ?? 'Failed.');
                    // Show validation errors if present
                    if (res.errors) {
                        Object.values(res.errors).flat().forEach(m => window.Toast?.error(m));
                    }
                }
            })
            .catch(() => window.Toast?.error('Request failed.'));
    });

    // ── Dispute ───────────────────────────────────────────────────────────────
    document.getElementById('dispute-btn')?.addEventListener('click', () => {
        document.getElementById('dispute-modal').classList.remove('hidden');
    });

    document.getElementById('dispute-cancel')?.addEventListener('click', () => {
        document.getElementById('dispute-modal').classList.add('hidden');
    });

    document.getElementById('dispute-form')?.addEventListener('submit', function (e) {
        e.preventDefault();
        const data = new FormData(this);
        data.set('_token', token());

        fetch(DISPUTE_URL, { method: 'POST', body: data })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    window.Toast?.success(res.message);
                    document.getElementById('dispute-modal').classList.add('hidden');
                    setTimeout(() => window.location.reload(), 800);
                } else {
                    window.Toast?.error(res.message ?? 'Failed.');
                }
            })
            .catch(() => window.Toast?.error('Request failed.'));
    });
})();
</script>
@endpush
