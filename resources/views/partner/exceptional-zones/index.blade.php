@extends('layouts.partner')

@section('title', __('partner.exceptional_zones.title'))

@section('content')

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">{{ __('partner.exceptional_zones.heading') }}</h1>
        <p class="text-sm text-gray-500 mt-0.5">{{ __('partner.exceptional_zones.subtitle') }}</p>
    </div>

    <div class="mb-6 rounded-lg border border-primary-200 bg-primary-50 px-4 py-3 text-sm text-primary-800">
        {{ __('partner.exceptional_zones.info_banner') }}
    </div>

    @if($zones->isEmpty())
        <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-600">
            {{ __('partner.exceptional_zones.no_zones') }}
        </div>
    @else
        @php
            $zonesByCountry = $zones->groupBy(fn($zone) => $zone->country?->name ?? __('partner.exceptional_zones.title'));
        @endphp

        <div class="space-y-6">
            @foreach($zonesByCountry as $countryName => $countryZones)
                <x-card padding="none">
                    <div class="px-5 py-3 border-b border-gray-100">
                        <h2 class="text-sm font-semibold text-gray-900">{{ $countryName }}</h2>
                    </div>

                    <div class="divide-y divide-gray-100">
                        @foreach($countryZones as $zone)
                            @php
                                $optIn = $zone->optedInByVendor->first();
                                $isActive = (bool) ($optIn?->is_active ?? false);
                            @endphp

                            <div class="px-5 py-4 flex items-center justify-between gap-4"
                                 x-data="{
                                     active: {{ $isActive ? 'true' : 'false' }},
                                     loading: false,
                                     toggle() {
                                         if (this.loading) return;
                                         this.loading = true;
                                         fetch('{{ route('partner.exceptional-zones.toggle', ['zone' => $zone->id]) }}', {
                                             method: 'POST',
                                             headers: {
                                                 'Content-Type': 'application/json',
                                                 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                                 'Accept': 'application/json',
                                             },
                                         })
                                             .then(r => r.json())
                                             .then(data => { this.active = data.is_active; })
                                             .finally(() => { this.loading = false; });
                                     }
                                 }">
                                <div class="min-w-0">
                                    <div class="text-sm font-medium text-gray-900">{{ $zone->name }}</div>
                                    <div class="text-xs text-gray-500 mt-1">
                                        @if($subsidySetting)
                                            {{ __('partner.exceptional_zones.subsidy_split', [
                                                'admin_amount' => number_format($subsidySetting->admin_subsidy_cents / 100, 2),
                                                'threshold_amount' => number_format($subsidySetting->full_coverage_threshold_cents / 100, 2),
                                                'currency' => 'SAR',
                                            ]) }}
                                        @else
                                            {{ __('partner.exceptional_zones.subsidy_unavailable') }}
                                        @endif
                                    </div>
                                </div>

                                <div class="flex items-center gap-3 shrink-0">
                                    <span
                                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                        :class="active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'"
                                        x-text="active ? '{{ __('partner.exceptional_zones.status.active') }}' : '{{ __('partner.exceptional_zones.status.inactive') }}'">
                                    </span>

                                    <button type="button" @click="toggle()" :disabled="loading"
                                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-primary-200 disabled:opacity-50"
                                            :class="active ? 'bg-primary-600' : 'bg-gray-300'">
                                        <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                                              :class="active ? 'translate-x-6' : 'translate-x-1'"></span>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-card>
            @endforeach
        </div>
    @endif

@endsection
