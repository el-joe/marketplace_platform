{{--
Shared City form partial.
@include('admin.cities._form', ['mode' => 'create'])
@include('admin.cities._form', ['mode' => 'edit', 'city' => $city])
--}}
@php
    $city = $city ?? null;
    $isEdit = $city !== null;

    $val = function (string $field, $default = '') use ($isEdit, $city) {
        return old($field, $isEdit ? ($city->{$field} ?? $default) : $default);
    };

    $bool = function (string $field, bool $default = false) use ($isEdit, $city): bool {
        $raw = old($field, $isEdit ? ($city->{$field} ?? $default) : $default);
        return (bool) $raw;
    };
@endphp

<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <x-breadcrumbs :items="$breadcrumbs" />
        <div class="flex gap-2">
            @if ($isEdit)
                <button type="button" id="btn-delete-city" data-city-id="{{ $city->id }}"
                    data-city-name="{{ $city->name_en }}" data-url="{{ route('admin.cities.destroy', $city->id) }}"
                    class="btn btn-danger-ghost">
                    <x-heroicon name="trash" class="w-4 h-4" />
                </button>
            @endif
            <button type="submit" class="btn btn-primary">
                <x-heroicon name="check" class="w-4 h-4" />
                Save City
            </button>
        </div>
    </div>

    @if (session('success'))
        <x-alert type="success">{{ session('success') }}</x-alert>
    @endif
    @if ($errors->any())
        <x-alert type="danger">
            <ul class="list-disc list-inside text-sm">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </x-alert>
    @endif

    {{-- Missing shipping zone warning --}}
    @if ($isEdit && !$city->shipping_zone_id)
        <x-alert type="warning">
            This city has no shipping zone assigned — it <strong>cannot receive deliveries</strong> until a zone is set.
        </x-alert>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm space-y-5">

        <div class="grid grid-cols-2 gap-4">
            <x-form.input name="name_en" label="Name (English)" :value="$val('name_en')" required dir="ltr" />
            <x-form.input name="name_ar" label="Name (Arabic)" :value="$val('name_ar')" required dir="rtl" />
        </div>

        <div class="grid grid-cols-2 gap-4">
            <x-form.select name="country_id" label="Country" :options="$countries" :value="$val('country_id')"
                :empty-option="'— Select Country —'" required />
            <x-form.select name="shipping_zone_id" label="Shipping Zone" :options="$shippingZones"
                :value="$val('shipping_zone_id')" :empty-option="'— No zone (cannot deliver) —'" />
        </div>

        <div class="grid grid-cols-2 gap-4">
            <x-form.input name="latitude" label="Latitude" type="number" step="any" min="-90" max="90"
                :value="$val('latitude')" />
            <x-form.input name="longitude" label="Longitude" type="number" step="any" min="-180" max="180"
                :value="$val('longitude')" />
        </div>

        <div class="flex items-center gap-6 pt-2">
            <x-form.toggle name="is_active" label="Active" :checked="$bool('is_active', true)" />
            <x-form.toggle name="cod_available" label="Cash on Delivery Available" :checked="$bool('cod_available')" />
        </div>
    </div>
</div>