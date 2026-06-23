@extends('layouts.admin')

@section('title', 'Payment Methods')

@section('content')

    {{-- ─── Header ─────────────────────────────────────────────────────────── --}}
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Payment Methods</h1>
            <p class="text-sm text-gray-500 mt-0.5">Configure payment options per country.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.payment-methods.gateway-config') }}"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                <x-heroicon name="cog-6-tooth" class="w-4 h-4" />
                Gateway Config
            </a>
            <button type="button" id="btn-add-method"
                class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700">
                <x-heroicon name="plus" class="w-4 h-4" />
                Add Method
            </button>
        </div>
    </div>

    {{-- ─── Gateway Status Bar ─────────────────────────────────────────────── --}}
    <x-card title="Gateway Status" class="mb-6">
        <div class="flex flex-wrap gap-3">
            @foreach($gateways as $code => $gateway)
                <div class="flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm">
                    <span class="font-medium text-gray-700">{{ $gateway->getName() }}</span>
                    <button type="button"
                        class="btn-test-gateway rounded px-2 py-0.5 text-xs font-medium text-primary-700 bg-primary-50 hover:bg-primary-100 border border-primary-200"
                        data-provider="{{ $code }}">
                        Test
                    </button>
                    <span class="gateway-status text-xs" id="gateway-status-{{ $code }}">—</span>
                </div>
            @endforeach
        </div>
    </x-card>

    {{-- ─── Per-Country Method Lists ────────────────────────────────────────── --}}
    @forelse($countries as $country)
        <x-card class="mb-4">
            <x-slot:actions>
                <button type="button"
                    class="btn-add-country-method inline-flex items-center gap-1 rounded-lg bg-primary-50 px-3 py-1.5 text-xs font-medium text-primary-700 hover:bg-primary-100"
                    data-country-id="{{ $country->id }}" data-country-name="{{ $country->name_en }}">
                    <x-heroicon name="plus" class="w-3.5 h-3.5" />
                    Add
                </button>
            </x-slot:actions>

            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between gap-4">
                <div>
                    <h3 class="text-base font-semibold text-gray-900">
                        {{ $country->name_en }}
                        <span class="text-sm font-normal text-gray-400 ml-1">({{ $country->currency_code }})</span>
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">{{ $country->countryPaymentMethods->count() }} method(s)</p>
                </div>
                <button type="button"
                    class="btn-add-country-method inline-flex items-center gap-1 rounded-lg bg-primary-50 px-3 py-1.5 text-xs font-medium text-primary-700 hover:bg-primary-100"
                    data-country-id="{{ $country->id }}" data-country-name="{{ $country->name_en }}">
                    <x-heroicon name="plus" class="w-3.5 h-3.5" />
                    Add
                </button>
            </div>

            <div class="p-4">
                @if($country->countryPaymentMethods->isEmpty())
                    <p class="text-sm text-gray-400 italic">No payment methods configured for this country.</p>
                @else
                    <ul class="divide-y divide-gray-100" id="sortable-{{ $country->id }}">
                        @foreach($country->countryPaymentMethods as $method)
                            <li class="flex items-center gap-3 py-2.5 px-1" data-id="{{ $method->id }}">
                                {{-- Drag handle --}}
                                <span class="cursor-grab text-gray-300 hover:text-gray-400">
                                    <x-heroicon name="bars-3" class="w-4 h-4" />
                                </span>
                                {{-- Type badge --}}
                                <span
                                    class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-blue-50 text-blue-700">
                                    {{ $methodTypes[$method->method_type]['label'] ?? $method->method_type }}
                                </span>
                                {{-- Name --}}
                                <span class="flex-1 text-sm font-medium text-gray-900">
                                    {{ $method->display_name_en }}
                                    @if($method->provider)
                                        <span class="ml-1 text-xs text-gray-400">({{ $method->provider }})</span>
                                    @endif
                                </span>
                                {{-- Fee --}}
                                <span class="text-xs text-gray-500">
                                    @if($method->fee_pct)
                                        {{ number_format($method->fee_pct, 2) }}%
                                    @endif
                                    @if($method->fee_fixed_cents)
                                        + {{ number_format($method->fee_fixed_cents / 100, 2) }}
                                    @endif
                                </span>
                                {{-- Toggle --}}
                                <button type="button"
                                    class="btn-toggle-method rounded-full px-2 py-0.5 text-xs font-semibold transition
                                                                                               {{ $method->is_active ? 'bg-success-50 text-success-700 hover:bg-success-100' : 'bg-gray-100 text-gray-500 hover:bg-gray-200' }}"
                                    data-id="{{ $method->id }}" data-active="{{ $method->is_active ? '1' : '0' }}">
                                    {{ $method->is_active ? 'Active' : 'Inactive' }}
                                </button>
                                {{-- Actions --}}
                                <button type="button" class="btn-edit-method text-gray-400 hover:text-primary-600 p-1 rounded"
                                    data-id="{{ $method->id }}" data-row="{{ json_encode($method) }}">
                                    <x-heroicon name="pencil-square" class="w-4 h-4" />
                                </button>
                                <button type="button" class="btn-delete-method text-gray-400 hover:text-danger-600 p-1 rounded"
                                    data-id="{{ $method->id }}" data-name="{{ $method->display_name_en }}">
                                    <x-heroicon name="trash" class="w-4 h-4" />
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </x-card>
    @empty
        <x-empty-state title="No active countries" description="Activate countries first to configure payment methods." />
    @endforelse

    {{-- ─── Add / Edit Modal ───────────────────────────────────────────────── --}}
    <x-modal id="method-modal" title="Payment Method" size="lg">
        <form id="method-form" novalidate>
            @csrf
            <input type="hidden" id="method-id">
            <input type="hidden" id="method-http" value="POST">
            <input type="hidden" id="method-country-id">

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-form-select name="method_type" label="Method Type" required>
                        <option value="">— Select type —</option>
                        @foreach($methodTypes as $key => $info)
                            <option value="{{ $key }}">{{ $info['label'] }}</option>
                        @endforeach
                    </x-form-select>
                </div>
                <div>
                    <x-form-select name="provider" label="Provider / Gateway">
                        <option value="">— None (manual) —</option>
                        @foreach($gateways as $code => $gateway)
                            <option value="{{ $code }}">{{ $gateway->getName() }}</option>
                        @endforeach
                    </x-form-select>
                </div>
                <div>
                    <x-form-input name="display_name_en" label="Display Name (EN)" placeholder="e.g. Credit Card"
                        required />
                </div>
                <div>
                    <x-form-input name="display_name_ar" label="Display Name (AR)" placeholder="e.g. بطاقة ائتمان" />
                </div>
                <div>
                    <x-form-input name="fee_pct" label="Fee %" type="number" placeholder="0.00" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fee Fixed (currency units)</label>
                    <input type="number" name="fee_fixed_display" id="fee_fixed_display" placeholder="0.00" min="0"
                        step="0.01"
                        class="block w-full rounded-lg border border-gray-300 py-2 px-3 text-sm text-gray-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-200 focus:border-primary-500" />
                    <input type="hidden" name="fee_fixed_cents" id="fee_fixed_cents" />
                    <p class="text-xs text-gray-400 mt-1">Stored as cents internally.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Min Order Amount</label>
                    <input type="number" name="min_order_display" id="min_order_display" placeholder="0.00" min="0"
                        step="0.01"
                        class="block w-full rounded-lg border border-gray-300 py-2 px-3 text-sm text-gray-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-200 focus:border-primary-500" />
                    <input type="hidden" name="min_order_cents" id="min_order_cents" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Max Order Amount</label>
                    <input type="number" name="max_order_display" id="max_order_display" placeholder="0.00" min="0"
                        step="0.01"
                        class="block w-full rounded-lg border border-gray-300 py-2 px-3 text-sm text-gray-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-200 focus:border-primary-500" />
                    <input type="hidden" name="max_order_cents" id="max_order_cents" />
                </div>
                <div class="sm:col-span-2 flex items-center gap-6">
                    <x-form-toggle name="is_active" label="Active" :checked="true" />
                </div>
            </div>

            <x-slot:footer>
                <button type="button" data-modal-close class="btn-secondary">Cancel</button>
                <button type="submit" form="method-form" class="btn-primary">Save Method</button>
            </x-slot:footer>
        </form>
    </x-modal>

    {{-- ─── Confirm Delete Modal ───────────────────────────────────────────── --}}
    <x-modal id="delete-method-modal" title="Remove Payment Method" size="sm">
        <p class="text-sm text-gray-600" id="delete-method-message">
            Remove this payment method? This action cannot be undone.
        </p>
        <input type="hidden" id="delete-method-id" />
        <x-slot:footer>
            <button type="button" data-modal-close class="btn-secondary">Cancel</button>
            <button type="button" id="btn-confirm-delete-method" class="btn-danger">Remove</button>
        </x-slot:footer>
    </x-modal>

@endsection

@push('scripts')
    @vite(['resources/js/admin/payment-methods.js'])
@endpush