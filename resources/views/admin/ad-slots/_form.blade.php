<x-card class="mb-5">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

        {{-- Name --}}
        <div class="sm:col-span-2">
            <x-form-input
                name="name"
                label="Slot Name"
                :value="old('name', $adSlot?->name)"
                placeholder="e.g. Homepage Hero Banner"
                required />
        </div>

        {{-- Slot Code --}}
        <div>
            <x-form-input
                name="slot_code"
                label="Slot Code"
                :value="old('slot_code', $adSlot?->slot_code)"
                placeholder="e.g. homepage_hero_001"
                required
                :readonly="(bool)$adSlot" />
            @if($adSlot)
                <p class="text-xs text-gray-400 mt-1">Slot code cannot be changed after creation.</p>
            @endif
        </div>

        {{-- Placement --}}
        <div>
            <x-form-select
                name="banner_placement_definition_id"
                label="Placement"
                :value="old('banner_placement_definition_id', $adSlot?->banner_placement_definition_id)">
                <option value="">— Select placement —</option>
                @foreach($placements as $p)
                    <option value="{{ $p->id }}" {{ old('banner_placement_definition_id', $adSlot?->banner_placement_definition_id) == $p->id ? 'selected' : '' }}>
                        {{ $p->name }} ({{ $p->placement_key }})
                    </option>
                @endforeach
            </x-form-select>
        </div>

        {{-- Country --}}
        <div>
            <x-form-select
                name="country_id"
                label="Country"
                :value="old('country_id', $adSlot?->country_id)"
                required>
                <option value="">— Select country —</option>
                @foreach($countries as $c)
                    <option value="{{ $c->id }}" {{ old('country_id', $adSlot?->country_id) == $c->id ? 'selected' : '' }}>
                        {{ $c->flag_emoji ? $c->flag_emoji . ' ' : '' }}{{ $c->name_en }}
                    </option>
                @endforeach
            </x-form-select>
        </div>

        {{-- Pricing Model --}}
        <div>
            <x-form-select
                name="pricing_model"
                label="Pricing Model"
                :value="old('pricing_model', $adSlot?->pricing_model)"
                required>
                <option value="">— Select model —</option>
                <option value="flat" {{ old('pricing_model', $adSlot?->pricing_model) === 'flat' ? 'selected' : '' }}>Flat Rate</option>
                <option value="daily" {{ old('pricing_model', $adSlot?->pricing_model) === 'daily' ? 'selected' : '' }}>Daily Rate</option>
                <option value="weekly" {{ old('pricing_model', $adSlot?->pricing_model) === 'weekly' ? 'selected' : '' }}>Weekly Rate</option>
            </x-form-select>
        </div>

        {{-- Base Rate (in cents) --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Base Rate ($) <span class="text-red-500">*</span></label>
            @php
                $baseRateDisplay = $adSlot?->base_rate_cents ? number_format($adSlot->base_rate_cents / 100, 2) : old('base_rate_display');
            @endphp
            <input
                type="number"
                name="base_rate_display"
                value="{{ old('base_rate_display', $baseRateDisplay) }}"
                step="0.01"
                min="0"
                class="form-input w-full @error('base_rate_display') border-red-400 @enderror"
                placeholder="0.00"
                required>
            @error('base_rate_display')
                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
            @enderror
            <p class="text-xs text-gray-400 mt-1">Enter in dollars. Stored in cents.</p>
        </div>

        {{-- Currency --}}
        <div>
            <x-form-input
                name="currency"
                label="Currency"
                :value="old('currency', $adSlot?->currency ?? 'USD')"
                placeholder="USD"
                required />
        </div>

        {{-- Min Booking Days --}}
        <div>
            <x-form-input
                type="number"
                name="min_booking_days"
                label="Min Booking Days"
                :value="old('min_booking_days', $adSlot?->min_booking_days ?? 1)"
                min="1"
                placeholder="1" />
        </div>

        {{-- Max Booking Days --}}
        <div>
            <x-form-input
                type="number"
                name="max_booking_days"
                label="Max Booking Days"
                :value="old('max_booking_days', $adSlot?->max_booking_days)"
                min="1"
                placeholder="e.g. 90" />
        </div>

        {{-- Min Seller Tier --}}
        <div>
            <x-form-input
                name="min_seller_tier"
                label="Min Seller Tier"
                :value="old('min_seller_tier', $adSlot?->min_seller_tier)"
                placeholder="e.g. gold" />
        </div>

        {{-- Notes for Vendors --}}
        <div class="sm:col-span-2">
            <x-form-textarea
                name="notes_for_vendors"
                label="Notes for Vendors"
                :value="old('notes_for_vendors', $adSlot?->notes_for_vendors)"
                rows="3"
                placeholder="Any important information vendors should know about this slot…" />
        </div>

        {{-- Toggles --}}
        <div class="flex items-center gap-2">
            <input
                type="checkbox"
                id="is_available"
                name="is_available"
                value="1"
                class="form-checkbox"
                {{ old('is_available', $adSlot?->is_available ?? true) ? 'checked' : '' }}>
            <label for="is_available" class="text-sm font-medium text-gray-700">Available for booking</label>
        </div>
        <div class="flex items-center gap-2">
            <input
                type="checkbox"
                id="requires_approval"
                name="requires_approval"
                value="1"
                class="form-checkbox"
                {{ old('requires_approval', $adSlot?->requires_approval ?? true) ? 'checked' : '' }}>
            <label for="requires_approval" class="text-sm font-medium text-gray-700">Requires admin approval</label>
        </div>

    </div>
</x-card>

<div class="flex items-center justify-end gap-3">
    <a href="{{ route('admin.ad-slots.index') }}" class="btn btn-secondary">Cancel</a>
    <button type="submit" class="btn btn-primary">
        {{ $adSlot ? 'Save Changes' : 'Create Slot' }}
    </button>
</div>
