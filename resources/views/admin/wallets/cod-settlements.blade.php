@extends('layouts.admin')

@section('title', 'COD Settlements')

@section('content')
<div class="space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">COD Settlements</h1>
            <p class="text-sm text-gray-500 mt-0.5">Cash-on-delivery reconciliation for delivery agents</p>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-green-800 text-sm">{{ session('success') }}</div>
    @endif

    {{-- Run Settlement Form --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h2 class="font-semibold text-gray-800 mb-4">Run New Settlement</h2>
        <form method="POST" action="{{ route('admin.wallets.cod-settlements.run') }}" class="flex flex-wrap gap-3 items-end">
            @csrf
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Agent</label>
                <select name="agent_id" required class="form-select text-sm rounded-lg border-gray-300 min-w-48">
                    <option value="">Select agent...</option>
                    @foreach($agents as $agent)
                        <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Period Start</label>
                <input type="date" name="period_start" required class="form-input text-sm rounded-lg border-gray-300">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Period End</label>
                <input type="date" name="period_end" required class="form-input text-sm rounded-lg border-gray-300">
            </div>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700">Run Settlement</button>
        </form>
    </div>

    {{-- Status Filter --}}
    <div class="flex gap-2">
        @foreach(['', 'pending', 'settled', 'disputed'] as $s)
            <a href="{{ route('admin.wallets.cod-settlements', ['status' => $s]) }}"
               class="px-3 py-1.5 text-sm rounded-lg {{ request('status', '') === $s ? 'bg-blue-600 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50' }}">
                {{ $s ? ucfirst($s) : 'All' }}
            </a>
        @endforeach
    </div>

    {{-- Settlements Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Agent</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Period</th>
                    <th class="px-4 py-3 text-right font-medium text-gray-600">COD Collected</th>
                    <th class="px-4 py-3 text-right font-medium text-gray-600">Earnings Owed</th>
                    <th class="px-4 py-3 text-right font-medium text-gray-600">Net to Remit</th>
                    <th class="px-4 py-3 text-center font-medium text-gray-600">Status</th>
                    <th class="px-4 py-3 text-right font-medium text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($settlements as $s)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $s->agent?->name }}</td>
                        <td class="px-4 py-3 text-gray-600 text-xs">{{ $s->period_start->format('d M') }} – {{ $s->period_end->format('d M Y') }}</td>
                        <td class="px-4 py-3 text-right text-gray-700">{{ number_format($s->total_cod_collected_cents / 100, 2) }}</td>
                        <td class="px-4 py-3 text-right text-gray-700">{{ number_format($s->total_earnings_owed_cents / 100, 2) }}</td>
                        <td class="px-4 py-3 text-right font-semibold {{ $s->net_to_remit_cents >= 0 ? 'text-red-600' : 'text-green-700' }}">
                            {{ $s->net_to_remit_cents >= 0 ? '+' : '' }}{{ number_format($s->net_to_remit_cents / 100, 2) }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            @php $colors = ['pending'=>'bg-yellow-100 text-yellow-700','settled'=>'bg-green-100 text-green-700','disputed'=>'bg-red-100 text-red-700']; @endphp
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $colors[$s->status] ?? '' }}">{{ ucfirst($s->status) }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if($s->status === 'pending')
                                <form method="POST" action="{{ route('admin.wallets.cod-settlements.settle', $s) }}">
                                    @csrf @method('PATCH')
                                    <button class="text-xs px-3 py-1.5 bg-green-600 text-white rounded-lg hover:bg-green-700">Mark Settled</button>
                                </form>
                            @else
                                <span class="text-xs text-gray-400">{{ $s->settled_at?->format('d M Y') ?? '—' }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-gray-400">No settlements found.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3 border-t border-gray-100">{{ $settlements->withQueryString()->links() }}</div>
    </div>

</div>
@endsection
