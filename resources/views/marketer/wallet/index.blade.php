@extends('layouts.marketer')

@section('title', __('marketer.wallet.title'))
@section('page-title', __('marketer.wallet.title'))

@section('content')
<div class="max-w-3xl mx-auto space-y-6 py-6 px-4">

    @if(session('success'))
        <div class="rounded-xl bg-green-50 border border-green-200 px-4 py-3 text-green-800 text-sm">{{ session('success') }}</div>
    @endif

    {{-- Balance Card --}}
    <div class="bg-gradient-to-br from-purple-600 to-purple-900 rounded-2xl p-6 text-white shadow-lg">
        <p class="text-sm font-medium text-purple-200 mb-1">{{ __('marketer.wallet.available_balance') }}</p>
        <p class="text-4xl font-extrabold tracking-tight">{{ number_format($wallet->balance_cents / 100, 2) }} <span class="text-xl font-semibold text-purple-300">{{ $wallet->currency }}</span></p>
        @if($wallet->pending_balance_cents > 0)
            <p class="text-sm text-purple-300 mt-2">+ {{ number_format($wallet->pending_balance_cents / 100, 2) }} {{ $wallet->currency }} {{ __('marketer.wallet.pending_balance') }}</p>
        @endif
        @if($wallet->is_frozen)
            <div class="mt-3 inline-flex items-center gap-1.5 bg-red-500/30 text-red-100 text-xs font-medium px-3 py-1 rounded-full">{{ __('marketer.wallet.wallet_frozen') }}</div>
        @endif
    </div>

    {{-- Withdraw --}}
    @unless($wallet->is_frozen)
    <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm">
        <h2 class="font-semibold text-gray-800 mb-4">{{ __('marketer.wallet.request_withdrawal') }}</h2>
        <form method="POST" action="{{ route('marketer.wallet.withdraw') }}" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('marketer.wallet.amount', ['currency' => $wallet->currency]) }}</label>
                    <input type="number" name="amount" min="1" step="0.01" required max="{{ $wallet->balance_cents / 100 }}"
                           class="w-full form-input rounded-lg border-gray-300 text-sm" placeholder="0.00">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('marketer.wallet.bank_name') }}</label>
                    <input type="text" name="bank_name" required maxlength="150"
                           class="w-full form-input rounded-lg border-gray-300 text-sm">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('marketer.wallet.bank_iban') }}</label>
                    <input type="text" name="bank_iban" required maxlength="50"
                           class="w-full form-input rounded-lg border-gray-300 text-sm font-mono">
                </div>
            </div>
            <button type="submit" class="w-full sm:w-auto px-6 py-2.5 bg-purple-600 text-white text-sm font-semibold rounded-xl hover:bg-purple-700 transition">
                {{ __('marketer.wallet.request_withdrawal') }}
            </button>
        </form>
    </div>
    @endunless

    {{-- Withdrawal History --}}
    @if($withdrawalRequests->isNotEmpty())
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800">{{ __('marketer.wallet.recent_withdrawals') }}</h2>
        </div>
        <div class="divide-y divide-gray-100">
            @foreach($withdrawalRequests as $wr)
                <div class="px-5 py-3 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ number_format($wr->amount_cents / 100, 2) }} {{ $wr->currency }}</p>
                        <p class="text-xs text-gray-500">{{ $wr->bank_name }} · {{ $wr->created_at->format('d M Y') }}</p>
                    </div>
                    @php $colors = ['pending'=>'bg-yellow-100 text-yellow-700','approved'=>'bg-blue-100 text-blue-700','processed'=>'bg-green-100 text-green-700','rejected'=>'bg-red-100 text-red-700']; @endphp
                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold {{ $colors[$wr->status] ?? '' }}">{{ ucfirst($wr->status) }}</span>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Transaction History --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800">{{ __('marketer.wallet.transaction_history') }}</h2>
        </div>
        @forelse($transactions as $tx)
            <div class="px-5 py-3 flex items-center justify-between border-b border-gray-50 last:border-0">
                <div>
                    <p class="text-sm font-medium text-gray-900">{{ $tx->description }}</p>
                    <p class="text-xs text-gray-400">{{ str_replace('_',' ', $tx->source_type) }} · {{ $tx->created_at->format('d M Y H:i') }}</p>
                </div>
                <p class="text-sm font-bold {{ $tx->type === 'credit' ? 'text-green-600' : 'text-red-500' }}">
                    {{ $tx->type === 'credit' ? '+' : '−' }}{{ number_format($tx->amount_cents / 100, 2) }}
                </p>
            </div>
        @empty
            <div class="px-5 py-8 text-center text-sm text-gray-400">{{ __('marketer.wallet.no_transactions') }}</div>
        @endforelse
        @if($transactions->hasPages())
            <div class="px-5 py-3 border-t border-gray-100">{{ $transactions->links() }}</div>
        @endif
    </div>

</div>
@endsection
