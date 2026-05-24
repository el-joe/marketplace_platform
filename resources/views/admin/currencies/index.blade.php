@extends('layouts.admin')

@push('head')
    @vite('resources/js/admin/currencies.js')
@endpush

@section('title', 'Currencies & Exchange Rates')

@section('content')
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <x-breadcrumbs :items="$breadcrumbs" />
            <button type="button" id="btn-dispatch-rate-update" data-url="{{ route('admin.currencies.dispatch-update') }}"
                class="btn btn-secondary">
                <x-heroicon name="arrow-path" class="w-4 h-4" />
                Sync Exchange Rates
            </button>
        </div>

        <x-card>
            <x-slot name="header">
                <h2 class="text-base font-semibold text-gray-800">All Currencies</h2>
            </x-slot>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr
                            class="border-b border-gray-100 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            <th class="px-4 py-3">Code</th>
                            <th class="px-4 py-3">Name</th>
                            <th class="px-4 py-3">Symbol</th>
                            <th class="px-4 py-3 text-right">Rate to Base</th>
                            <th class="px-4 py-3">Base</th>
                            <th class="px-4 py-3 text-center">Decimals</th>
                            <th class="px-4 py-3 text-center">Override</th>
                            <th class="px-4 py-3">Last Updated</th>
                            <th class="px-4 py-3 text-center">Status</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse ($currencies as $currency)
                            <tr class="hover:bg-gray-50 transition-colors {{ !$currency->is_active ? 'opacity-60' : '' }}">
                                <td class="px-4 py-3">
                                    <span class="font-mono font-bold text-gray-900">{{ $currency->code }}</span>
                                </td>
                                <td class="px-4 py-3 text-gray-700">{{ $currency->name }}</td>
                                <td class="px-4 py-3 text-gray-500 font-mono">{{ $currency->symbol }}</td>
                                <td class="px-4 py-3 text-right font-mono text-gray-800">
                                    {{ number_format($currency->exchange_rate_to_base, 6) }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="font-mono text-xs text-gray-500">{{ $currency->base_currency_code }}</span>
                                </td>
                                <td class="px-4 py-3 text-center text-gray-500">{{ $currency->decimal_places }}</td>
                                <td class="px-4 py-3 text-center">
                                    @if ($currency->is_manually_overridden)
                                        <x-badge color="warning" size="xs">Manual</x-badge>
                                    @else
                                        <span class="text-gray-300 text-xs">Auto</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-500 text-xs">
                                    {{ $currency->rate_updated_at?->diffForHumans() ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if ($currency->is_active)
                                        <x-badge color="success" size="xs">Active</x-badge>
                                    @else
                                        <x-badge color="gray" size="xs">Inactive</x-badge>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('admin.currencies.edit', $currency->code) }}"
                                        class="btn btn-ghost btn-sm">Edit</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-4 py-8 text-center text-gray-400">No currencies found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>
@endsection