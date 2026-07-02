@extends('layouts.admin')

@section('title', 'COD Settlements')

@section('content')

{{-- ─── Header ──────────────────────────────────────────────────────────────── --}}
<div class="mb-6 flex items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">COD Settlements</h1>
        <p class="text-sm text-gray-500 mt-0.5">Reconcile cash-on-delivery collections and release vendor payouts.</p>
    </div>
    <button type="button" id="generate-btn" class="btn btn-primary btn-sm">+ Generate Settlement</button>
</div>

{{-- ─── Stats ───────────────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Cash in Agents' Custody</p>
        <p class="mt-1 text-2xl font-bold text-red-600">{{ number_format($pendingCashCents / 100, 2) }}</p>
        <p class="text-xs text-gray-400 mt-0.5">Platform money not yet remitted</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Settled This Month</p>
        <p class="mt-1 text-2xl font-bold text-green-600">{{ number_format($settledThisMonthCents / 100, 2) }}</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Disputed</p>
        <p class="mt-1 text-2xl font-bold text-yellow-600">{{ $disputedCount }}</p>
    </div>
</div>

{{-- ─── Agent Table ─────────────────────────────────────────────────────────── --}}
<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider text-xs">Agent</th>
                <th class="px-4 py-3 text-right font-medium text-gray-500 uppercase tracking-wider text-xs">Pending Cash</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider text-xs">Last Settlement</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider text-xs">Status</th>
                <th class="px-4 py-3 text-right font-medium text-gray-500 uppercase tracking-wider text-xs">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($agents as $agent)
                @php
                    $lastSettlement = $agent->codSettlements->first();
                    $pendingCod = $agentPendingCod[$agent->id] ?? 0;
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <div class="font-medium text-gray-900">{{ $agent->name }}</div>
                        <div class="text-xs text-gray-500">{{ $agent->phone ?? $agent->email }}</div>
                    </td>
                    <td class="px-4 py-3 text-right">
                        @if($pendingCod > 0)
                            <span class="font-semibold text-red-600">{{ number_format($pendingCod / 100, 2) }}</span>
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if($lastSettlement)
                            <div class="text-xs">
                                {{ $lastSettlement->period_start->format('M d') }} – {{ $lastSettlement->period_end->format('M d, Y') }}
                            </div>
                            <div class="text-xs text-gray-400">
                                Net: {{ number_format($lastSettlement->net_to_remit_cents / 100, 2) }}
                            </div>
                        @else
                            <span class="text-gray-400 text-xs">No settlements yet</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if($lastSettlement)
                            @php
                                $badgeClass = match($lastSettlement->status) {
                                    'settled'  => 'badge-success',
                                    'disputed' => 'badge-warning',
                                    default    => 'badge-gray',
                                };
                            @endphp
                            <span class="badge {{ $badgeClass }} text-xs capitalize">{{ $lastSettlement->status }}</span>
                        @else
                            <span class="text-gray-400 text-xs">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right space-x-2">
                        @if($pendingCod > 0)
                            <button type="button"
                                class="btn btn-xs btn-outline gen-settlement-btn"
                                data-agent-id="{{ $agent->id }}"
                                data-agent-name="{{ $agent->name }}">
                                Generate Settlement
                            </button>
                        @endif
                        @if($lastSettlement)
                            <a href="{{ route('admin.delivery.cod-settlements.show', $lastSettlement) }}"
                               class="btn btn-xs btn-secondary">View History</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-gray-400">No agents with COD activity.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- ─── Generate Modal ──────────────────────────────────────────────────────── --}}
<div id="generate-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Generate COD Settlement</h2>
        <form id="generate-form" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Agent</label>
                <select name="agent_id" id="gen-agent-id" required class="form-input w-full">
                    <option value="">Select agent…</option>
                    @foreach($agents as $agent)
                        <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Period Start</label>
                    <input type="date" name="period_start" required class="form-input w-full">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Period End</label>
                    <input type="date" name="period_end" required class="form-input w-full">
                </div>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" id="gen-cancel" class="btn btn-secondary btn-sm">Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm">Generate</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    const GENERATE_URL = @json(route('admin.delivery.cod-settlements.generate'));

    function token() { return document.querySelector('meta[name="csrf-token"]').content; }

    // Open generate modal
    document.getElementById('generate-btn').addEventListener('click', () => {
        document.getElementById('generate-modal').classList.remove('hidden');
    });

    // Per-agent "Generate Settlement" button pre-fills the agent
    document.querySelectorAll('.gen-settlement-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('gen-agent-id').value = btn.dataset.agentId;
            document.getElementById('generate-modal').classList.remove('hidden');
        });
    });

    document.getElementById('gen-cancel').addEventListener('click', () => {
        document.getElementById('generate-modal').classList.add('hidden');
    });

    document.getElementById('generate-form').addEventListener('submit', function (e) {
        e.preventDefault();
        const data = new FormData(this);
        data.set('_token', token());

        fetch(GENERATE_URL, { method: 'POST', body: data })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    window.Toast?.success(res.message);
                    document.getElementById('generate-modal').classList.add('hidden');
                    if (res.settlement?.show_url) {
                        window.location.href = res.settlement.show_url;
                    } else {
                        window.location.reload();
                    }
                } else {
                    window.Toast?.error(res.message ?? 'Generation failed.');
                }
            })
            .catch(() => window.Toast?.error('Request failed.'));
    });
})();
</script>
@endpush
