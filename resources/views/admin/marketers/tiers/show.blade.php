@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/admin/marketer-tiers.js'])
@endpush

@section('title', 'Commission Tiers — ' . $marketer->name)

@section('content')

    <x-breadcrumbs :items="$breadcrumbs" class="mb-4" />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Tier editor --}}
        <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h2 class="text-lg font-bold text-gray-800">Commission Tiers</h2>
                    <p class="text-sm text-gray-500 mt-0.5">
                        Define sales thresholds that automatically increase the commission rate.
                    </p>
                </div>
                <button type="button" id="save-tiers-btn" data-url="{{ route('admin.marketers.tiers.store', $marketer) }}"
                    class="btn btn-primary">Save Tiers</button>
            </div>

            <div id="tiers-container"></div>
        </div>

        {{-- Info sidebar --}}
        <div class="space-y-4">
            <div class="bg-white rounded-2xl border border-gray-200 p-5">
                <h3 class="font-semibold text-gray-700 mb-3">Marketer Stats</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-400">Name</span>
                        <span class="font-medium">{{ $marketer->name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Default Rate</span>
                        <span class="font-medium">{{ $marketer->commission_rate }}%</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Total Conversions</span>
                        <span class="font-bold">{{ number_format($salesCount) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Current Tier</span>
                        @php
                            $currentTier = $tiers->first(
                                fn($t) =>
                                $salesCount >= $t->min_sales_count &&
                                ($t->max_sales_count === null || $salesCount <= $t->max_sales_count)
                            );
                        @endphp
                        <span class="font-bold {{ $currentTier ? 'text-primary-600' : 'text-gray-400' }}">
                            {{ $currentTier ? 'Tier ' . $currentTier->tier_order . ' (' . $currentTier->commission_rate . '%)' : 'Default' }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm text-blue-700">
                <p class="font-semibold mb-1">How tiers work</p>
                <p>The highest tier the marketer qualifies for (by sales count) is used. Tiers apply globally unless
                    overridden per campaign.</p>
            </div>
        </div>

    </div>

@endsection

@push('scripts')
    <script>
        window.existingTiers = @json($tiers->map(fn($t) => [
            'min_sales_count' => $t->min_sales_count,
            'max_sales_count' => $t->max_sales_count,
            'commission_rate' => (float) $t->commission_rate,
        ])->values());
    </script>
@endpush
