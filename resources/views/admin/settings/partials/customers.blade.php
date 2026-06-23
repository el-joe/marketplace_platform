{{-- Customers Settings Partial --}}
<x-card class="mb-6">
    <div class="mb-4">
        <h2 class="text-base font-semibold text-gray-900">Customers &amp; Loyalty</h2>
        <p class="text-sm text-gray-500">Loyalty points, referrals, tier thresholds, and account limits.</p>
    </div>
    <div class="divide-y divide-gray-100">

        @foreach($settings->whereIn('key', [
            'loyalty_points_per_100_egp',
            'loyalty_referral_bonus_points',
            'loyalty_new_customer_bonus_points',
            'max_addresses_per_customer',
            'customer_otp_expiry_minutes',
        ]) as $setting)
            @include('admin.settings.partials._field', ['setting' => $setting])
        @endforeach

    </div>
</x-card>

{{-- Tier Thresholds --}}
<x-card>
    <div class="mb-4">
        <h2 class="text-base font-semibold text-gray-900">Loyalty Tier Thresholds</h2>
        <p class="text-sm text-gray-500">Points required to reach each loyalty tier.</p>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

        @php
        $tiers = [
            'loyalty_tier_silver_points'   => ['label' => '🥈 Silver',   'key' => 'loyalty_tier_silver_points'],
            'loyalty_tier_gold_points'     => ['label' => '🥇 Gold',     'key' => 'loyalty_tier_gold_points'],
            'loyalty_tier_platinum_points' => ['label' => '💎 Platinum', 'key' => 'loyalty_tier_platinum_points'],
        ];
        @endphp

        @foreach($tiers as $tierKey => $tier)
            @php $setting = $settings->firstWhere('key', $tierKey); @endphp
            @if($setting)
            <div class="rounded-lg border border-gray-200 p-4 bg-gray-50">
                <label for="setting-{{ $tierKey }}" class="block text-sm font-semibold text-gray-800 mb-2">
                    {{ $tier['label'] }}
                </label>
                <input
                    type="number"
                    id="setting-{{ $tierKey }}"
                    name="settings[{{ $tierKey }}]"
                    value="{{ $setting->value }}"
                    min="0"
                    step="1"
                    class="form-input w-full text-sm"
                >
                @if($setting->description)
                    <p class="mt-1 text-xs text-gray-500">{{ $setting->description }}</p>
                @endif
            </div>
            @endif
        @endforeach

    </div>
</x-card>
