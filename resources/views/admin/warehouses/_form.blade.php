{{--
Warehouse form partial.
Include with: @include('admin.warehouses._form', ['mode' => 'create'])
@include('admin.warehouses._form', ['mode' => 'edit'])

Expected variables:
$countries — collection/array of [id => name_en]
$vendors — collection/array of [id => store_name]
$admins — collection/array of [id => name]
$warehouse — Warehouse model (edit mode only)
--}}
@php
    $warehouse = $warehouse ?? null;
    $isEdit = $mode === 'edit' && $warehouse !== null;

    $val = fn($field, $default = '') => old($field, $isEdit ? ($warehouse->{$field} ?? $default) : $default);

    // Storage rate is stored in cents; display as decimal
    $storageRateDisplay = old('storage_rate_per_m3_price') !== null
        ? old('storage_rate_per_m3_price')
        : ($isEdit && $warehouse->storage_rate_per_m3_price ? number_format($warehouse->storage_rate_per_m3_price / 100, 2, '.', '') : '');

    $typeOptions = [
        'platform_fbn' => 'Platform FBN (Fulfilled By Noon)',
        'seller_owned' => 'Seller-Owned',
        'third_party' => 'Third-Party Logistics',
    ];

    $countryOptions = ['' => '— Select Country —'] + $countries->toArray();
    $vendorOptions = ['' => '— None (Platform-Owned) —'] + $vendors->toArray();
    $adminOptions = ['' => '— No Manager —'] + $admins->toArray();
@endphp

<div class="space-y-6" x-data="{ type: '{{ $val('type', 'platform_fbn') }}' }">

    {{-- ── Page Header ─────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between">
        <div>
            <nav class="text-sm text-gray-500 flex items-center gap-1.5 mb-1">
                <a href="{{ route('admin.warehouses.index') }}" class="hover:text-primary-600">Warehouses</a>
                <span>/</span>
                <span class="text-gray-800 font-medium">
                    {{ $isEdit ? 'Edit: ' . e($warehouse->name) : 'New Warehouse' }}
                </span>
            </nav>
            <h1 class="text-xl font-semibold text-gray-900">
                {{ $isEdit ? 'Edit Warehouse' : 'Add Warehouse' }}
            </h1>
        </div>
        <div class="flex gap-2">
            <a href="{{ $isEdit ? route('admin.warehouses.show', $warehouse->id) : route('admin.warehouses.index') }}"
                class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">
                {{ $isEdit ? 'Save Changes' : 'Create Warehouse' }}
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="p-3 bg-green-50 border border-green-200 rounded text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="p-3 bg-red-50 border border-red-200 rounded text-sm text-red-700">
            <p class="font-medium mb-1">Please fix the following errors:</p>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ── Two-column layout ───────────────────────────────────────────── --}}
    <div class="flex gap-6 items-start">

        {{-- ═══════ LEFT: Main Details ═══════ --}}
        <div class="flex-1 min-w-0 space-y-4">

            {{-- Basic Details --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="text-sm font-semibold text-gray-900">Basic Details</h2>
                </div>
                <div class="px-5 py-5 space-y-4">

                    <x-form-input name="name" label="Warehouse Name" type="text" :value="$val('name')"
                        placeholder="e.g. Cairo Main Fulfillment Center" required />

                    <x-form-input name="code" label="Warehouse Code" type="text" :value="$val('code')"
                        placeholder="e.g. CAI-FBN-01" maxlength="20" required
                        hint="Unique identifier, max 20 characters. Cannot be changed after creation easily." />

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Type <span class="text-red-500">*</span>
                        </label>
                        <select name="type" x-model="type"
                            class="form-select w-full @error('type') border-red-400 @enderror" required>
                            @foreach($typeOptions as $value => $label)
                                <option value="{{ $value }}" @selected($val('type', 'platform_fbn') === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('type')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <x-form-select name="country_id" label="Country" :options="$countryOptions"
                        :value="$val('country_id')" required select2 />

                </div>
            </div>

            {{-- Capacity & Rates --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="text-sm font-semibold text-gray-900">Capacity & Rates</h2>
                    <p class="text-xs text-gray-500 mt-0.5">All capacity values in cubic metres (m³).</p>
                </div>
                <div class="px-5 py-5 space-y-4">

                    <div class="grid grid-cols-2 gap-4">
                        <x-form-input name="total_capacity_m3" label="Total Capacity (m³)" type="number"
                            :value="$val('total_capacity_m3')" placeholder="0.00" step="0.01" min="0" />
                        <x-form-input name="used_capacity_m3" label="Used Capacity (m³)" type="number"
                            :value="$val('used_capacity_m3')" placeholder="0.00" step="0.01" min="0" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <x-form-input name="storage_rate_per_m3_price" label="Storage Rate per m³" type="number"
                            :value="$storageRateDisplay" placeholder="0.00" step="0.01" min="0"
                            hint="Enter as decimal (e.g. 5.00). Stored in cents." />
                        <x-form-input name="storage_currency" label="Currency (ISO 4217)" type="text"
                            :value="$val('storage_currency', 'EGP')" placeholder="EGP" maxlength="3"
                            hint="3-letter ISO code, e.g. USD, EGP." />
                    </div>

                </div>
            </div>

            {{-- GPS Location --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="text-sm font-semibold text-gray-900">GPS Location</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Optional. Used for distance calculations and map display.
                    </p>
                </div>
                <div class="px-5 py-5">
                    <div class="grid grid-cols-2 gap-4">
                        <x-form-input name="latitude" label="Latitude" type="number" :value="$val('latitude')"
                            placeholder="30.0444" step="0.0000001" min="-90" max="90" />
                        <x-form-input name="longitude" label="Longitude" type="number" :value="$val('longitude')"
                            placeholder="31.2357" step="0.0000001" min="-180" max="180" />
                    </div>
                </div>
            </div>

        </div>

        {{-- ═══════ RIGHT: Sidebar ═══════ --}}
        <div class="w-72 shrink-0 space-y-4">

            {{-- Status --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="text-sm font-semibold text-gray-900">Status</h2>
                </div>
                <div class="px-5 py-4">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1"
                            class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                            @if($val('is_active', '1') == '1' || ($val('is_active', '1') === true)) checked @endif>
                        <span class="text-sm text-gray-700">Active</span>
                    </label>
                    <p class="mt-1.5 text-xs text-gray-400">Inactive warehouses are hidden from operations.</p>
                </div>
            </div>

            {{-- Ownership & Management --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="text-sm font-semibold text-gray-900">Ownership & Management</h2>
                </div>
                <div class="px-5 py-4 space-y-4">

                    {{-- Only relevant for seller_owned type, but available to all --}}
                    <div x-show="type === 'seller_owned'" x-cloak>
                        <x-form-select name="owner_vendor_id" label="Owner Vendor" :options="$vendorOptions"
                            :value="$val('owner_vendor_id')" select2 />
                    </div>
                    <div x-show="type !== 'seller_owned'" x-cloak>
                        <p class="text-xs text-gray-400">Owner vendor applies only to seller-owned warehouses.</p>
                    </div>

                    <x-form-select name="manager_admin_id" label="Manager (Admin)" :options="$adminOptions"
                        :value="$val('manager_admin_id')" select2 />

                </div>
            </div>

            @if($isEdit)
                {{-- Meta info in edit mode --}}
                <div class="bg-gray-50 rounded-xl border border-gray-200 px-5 py-4 text-xs text-gray-500 space-y-1.5">
                    <div class="flex justify-between">
                        <span>Created</span>
                        <span>{{ $warehouse->created_at->format('Y-m-d') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Updated</span>
                        <span>{{ $warehouse->updated_at->format('Y-m-d') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>ID</span>
                        <span class="font-mono truncate ml-2">{{ $warehouse->id }}</span>
                    </div>
                </div>
            @endif

        </div>
    </div>
</div>