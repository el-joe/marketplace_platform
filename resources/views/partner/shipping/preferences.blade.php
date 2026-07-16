@extends('layouts.partner')

@section('title', 'Shipping Preferences')
@section('page-title', 'Shipping Preferences')

@section('content')
<div class="p-6 space-y-6" x-data="shippingPreferences()">

    <div>
        <h1 class="text-xl font-bold text-gray-900">Shipping Preferences</h1>
        <p class="text-sm text-gray-500 mt-0.5">إعدادات الشحن الخاصة بحسابك — Manage your FBP delivery settings.</p>
    </div>

    @if(!$hasFbpListings)
        <div class="bg-blue-50 border border-blue-100 rounded-xl p-5 text-sm text-blue-800">
            This section is for FBP listings only. You have no active FBP listings yet.
        </div>
    @else
        {{-- Info banner --}}
        <div class="bg-blue-50 border border-blue-100 rounded-xl p-5 space-y-1 text-sm text-blue-800">
            <p>المناطق الاستثنائية: عند تفعيلها يحصل العميل في تلك المنطقة على توصيل مجاني. يُخصم نصيبك من أرباحك في كل طلب.</p>
            <p class="text-blue-700/80">Exceptional Zones: when enabled, customers in that zone get free delivery. Your share is deducted from your earnings per order.</p>
        </div>

        @forelse($countries as $countryName => $zones)
            @php
                $allZeroNoConfig = $zones->every(fn($z) => !$z['has_subsidy_config']);
            @endphp
            <div class="space-y-3">
                <h2 class="text-sm font-semibold text-gray-700">{{ $countryName }}</h2>

                @if($allZeroNoConfig)
                    <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 text-sm text-yellow-800">
                        Admin has not configured your delivery subsidy split yet. Contact support.
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($zones as $zone)
                        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3" x-data="{ zone: {{ json_encode($zone) }} }">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-semibold text-gray-900 text-sm">{{ $zone['name'] }}</p>
                                    @if($zone['description'])
                                        <p class="text-xs text-gray-500 mt-0.5">{{ $zone['description'] }}</p>
                                    @endif
                                </div>
                                <span
                                    class="shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                    :class="zone.is_exceptional ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'"
                                    x-text="zone.is_exceptional ? 'Active' : 'Inactive'"
                                ></span>
                            </div>

                            <p class="text-xs text-gray-600">
                                <template x-if="zone.has_subsidy_config">
                                    <span>Your share per delivery: {{ $zone['currency'] }} <span x-text="(zone.vendor_share / 100).toFixed(2)"></span></span>
                                </template>
                                <template x-if="!zone.has_subsidy_config">
                                    <span class="text-yellow-700">Contact admin — split not configured</span>
                                </template>
                            </p>

                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" class="sr-only peer" :checked="zone.is_exceptional"
                                    @change="toggleZone(zone)" :disabled="loadingZoneId === zone.id">
                                <span class="w-9 h-5 bg-gray-200 peer-checked:bg-green-500 rounded-full relative transition-colors">
                                    <span class="absolute top-0.5 start-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform peer-checked:translate-x-4 rtl:peer-checked:-translate-x-4"></span>
                                </span>
                                <span class="text-xs text-gray-500" x-text="loadingZoneId === zone.id ? 'Saving…' : 'Toggle exceptional zone'"></span>
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <p class="text-sm text-gray-400">No shipping zones found for your FBP listing countries.</p>
        @endforelse
    @endif

</div>

@push('scripts')
<script>
function shippingPreferences() {
    return {
        loadingZoneId: null,

        toggleZone(zone) {
            this.loadingZoneId = zone.id;

            fetch('{{ route("partner.shipping.preferences.toggle-zone") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ zone_id: zone.id }),
            })
            .then(async (r) => {
                const data = await r.json();
                if (!r.ok || !data.success) throw new Error(data.message || 'Failed to update zone.');
                return data;
            })
            .then((data) => {
                zone.is_exceptional = data.is_now_exceptional;
                zone.vendor_share = data.vendor_share;
            })
            .catch((e) => {
                alert(e.message);
            })
            .finally(() => {
                this.loadingZoneId = null;
            });
        },
    };
}
</script>
@endpush
@endsection
