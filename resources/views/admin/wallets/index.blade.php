@extends('layouts.admin')

@section('title', __('admin.wallets.overview_title'))

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('admin.wallets.title') }}</h1>
            <p class="text-sm text-gray-500 mt-0.5">{{ __('admin.wallets.subtitle') }}</p>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" class="flex flex-wrap gap-3 bg-white rounded-xl border border-gray-200 p-4">
        <select name="owner_type" class="form-select text-sm rounded-lg border-gray-300">
            <option value="">{{ __('admin.wallets.all_types') }}</option>
            @foreach(['customer','vendor','marketer','delivery_agent'] as $t)
                <option value="{{ $t }}" @selected(request('owner_type') === $t)>{{ __('admin.wallets.' . $t) }}</option>
            @endforeach
        </select>
        <select name="currency" class="form-select text-sm rounded-lg border-gray-300">
            <option value="">{{ __('admin.wallets.all_currencies') }}</option>
            <option value="EGP" @selected(request('currency') === 'EGP')>EGP</option>
            <option value="USD" @selected(request('currency') === 'USD')>USD</option>
        </select>
        <label class="flex items-center gap-2 text-sm text-gray-600">
            <input type="checkbox" name="frozen" value="1" @checked(request('frozen')) class="rounded border-gray-300">
            {{ __('admin.wallets.frozen_only') }}
        </label>
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700">{{ __('admin.wallets.filter') }}</button>
        <a href="{{ route('admin.wallets.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm rounded-lg hover:bg-gray-200">{{ __('admin.wallets.reset') }}</a>
    </form>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 text-start font-medium text-gray-600">{{ __('admin.wallets.owner') }}</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-600">{{ __('admin.wallets.type') }}</th>
                    <th class="px-4 py-3 text-end font-medium text-gray-600">{{ __('admin.wallets.balance') }}</th>
                    <th class="px-4 py-3 text-end font-medium text-gray-600">{{ __('admin.wallets.pending') }}</th>
                    <th class="px-4 py-3 text-center font-medium text-gray-600">{{ __('admin.wallets.currency') }}</th>
                    <th class="px-4 py-3 text-center font-medium text-gray-600">{{ __('admin.wallets.status') }}</th>
                    <th class="px-4 py-3 text-end font-medium text-gray-600">{{ __('admin.wallets.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($wallets as $wallet)
                    @php $owner = $wallet->owner; @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $owner?->name ?? $wallet->owner_id }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ __('admin.wallets.' . $wallet->owner_type) }}</td>
                        <td class="px-4 py-3 text-end font-semibold text-gray-900">{{ number_format($wallet->balance_cents / 100, 2) }}</td>
                        <td class="px-4 py-3 text-end text-amber-600">{{ number_format($wallet->pending_balance_cents / 100, 2) }}</td>
                        <td class="px-4 py-3 text-center text-gray-500">{{ $wallet->currency }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($wallet->is_frozen)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">{{ __('admin.wallets.frozen') }}</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">{{ __('admin.wallets.active') }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-end">
                            <a href="{{ route('admin.wallets.show', $wallet) }}" class="text-blue-600 hover:underline text-xs font-medium">{{ __('admin.wallets.view') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-gray-400">{{ __('admin.wallets.no_wallets_found') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
        <div class="px-4 py-3 border-t border-gray-100">{{ $wallets->withQueryString()->links() }}</div>
    </div>

</div>
@endsection
