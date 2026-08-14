@extends('layouts.admin')

@section('title', __('admin.payment_section.title'))

@section('content')

    {{-- ─── Header ─────────────────────────────────────────────────────────── --}}
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('admin.payment_section.title') }}</h1>
            <p class="text-sm text-gray-500 mt-0.5">{{ __('admin.payment_section.methods_desc') }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.payment-methods.gateway-config') }}"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                <x-heroicon name="cog-6-tooth" class="w-4 h-4" />
                {{ __('admin.payment_section.gateway_config') }}
            </a>
            <button type="button" id="btn-add-method"
                class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700">
                <x-heroicon name="plus" class="w-4 h-4" />
                {{ __('admin.payment_section.add_method') }}
            </button>
        </div>
    </div>

    {{-- ─── Gateway Status Bar ─────────────────────────────────────────────── --}}
    <x-card title="{{ __('admin.payment_section.gateway_status') }}" class="mb-6">
        <div class="flex flex-wrap gap-3">
            @foreach($gateways as $code => $gateway)
                <div class="flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm">
                    <span class="font-medium text-gray-700">{{ $gateway->getName() }}</span>
                    <button type="button"
                        class="btn-test-gateway rounded px-2 py-0.5 text-xs font-medium text-primary-700 bg-primary-50 hover:bg-primary-100 border border-primary-200"
                        data-provider="{{ $code }}">
                        {{ __('admin.payment_section.test') }}
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
                    {{ __('admin.payment_section.add') }}
                </button>
            </x-slot:actions>

            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between gap-4">
                <div>
                    <h3 class="text-base font-semibold text-gray-900">
                        {{ $country->name_en }}
                        <span class="text-sm font-normal text-gray-400 ml-1">({{ $country->currency_code }})</span>
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">{{ __('admin.payment_section.method_count', ['count' => $country->countryPaymentMethods->count()]) }}</p>
                </div>
                <button type="button"
                    class="btn-add-country-method inline-flex items-center gap-1 rounded-lg bg-primary-50 px-3 py-1.5 text-xs font-medium text-primary-700 hover:bg-primary-100"
                    data-country-id="{{ $country->id }}" data-country-name="{{ $country->name_en }}">
                    <x-heroicon name="plus" class="w-3.5 h-3.5" />
                    {{ __('admin.payment_section.add') }}
                </button>
            </div>

            <div class="p-4">
                @if($country->countryPaymentMethods->isEmpty())
                    <p class="text-sm text-gray-400 italic">{{ __('admin.payment_section.no_methods_for_country') }}</p>
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
                                    @if($method->gateway_code)
                                        @if($method->is_configured)
                                            <span class="inline-flex items-center gap-0.5 rounded px-1.5 py-0.5 text-xs font-medium bg-success-50 text-success-700 border border-success-200">
                                                <x-heroicon name="check-circle" class="w-3 h-3" /> {{ __('admin.payment_section.configured') }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-0.5 rounded px-1.5 py-0.5 text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200">
                                                <x-heroicon name="exclamation-triangle" class="w-3 h-3" /> {{ __('admin.payment_section.no_credentials') }}
                                            </span>
                                        @endif
                                    @endif
                                </span>
                                {{-- Fee --}}
                                <span class="text-xs text-gray-500">
                                    @if($method->fee_pct)
                                        {{ number_format($method->fee_pct, 2) }}%
                                    @endif
                                    @if($method->fee_fixed)
                                        + {{ number_format($method->fee_fixed, 2) }}
                                    @endif
                                </span>
                                {{-- Logo thumbnail --}}
                                @if($method->provider_logo_path)
                                    <img src="{{ $method->provider_logo_path }}" alt="" class="w-6 h-6 object-contain rounded border border-gray-100" />
                                @endif
                                {{-- Installments --}}
                                @if(in_array($method->method_type, ['bnpl', 'bank_transfer']))
                                    <span
                                        class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $method->is_installment_enabled ? 'bg-purple-50 text-purple-700' : 'bg-gray-100 text-gray-400' }}">
                                        {{ $method->is_installment_enabled ? "x{$method->installments_count}" : __('admin.payment_section.installments_disabled') }}
                                    </span>
                                @endif
                                {{-- Toggle --}}
                                <button type="button"
                                    class="btn-toggle-method rounded-full px-2 py-0.5 text-xs font-semibold transition
                                                                                               {{ $method->is_active ? 'bg-success-50 text-success-700 hover:bg-success-100' : 'bg-gray-100 text-gray-500 hover:bg-gray-200' }}"
                                    data-id="{{ $method->id }}" data-active="{{ $method->is_active ? '1' : '0' }}">
                                    {{ $method->is_active ? __('common.active') : __('common.inactive') }}
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
        <x-empty-state title="{{ __('admin.payment_section.no_active_countries_title') }}" description="{{ __('admin.payment_section.no_active_countries_desc') }}" />
    @endforelse

    {{-- ─── Add / Edit Modal ───────────────────────────────────────────────── --}}
    <x-modal id="method-modal" title="{{ __('admin.payment_section.method_modal_title') }}" size="lg">
        <form id="method-form" novalidate x-data="{ methodType: '' }">
            @csrf
            <input type="hidden" id="method-id">
            <input type="hidden" id="method-http" value="POST">
            <input type="hidden" id="method-country-id">

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-form-select name="method_type" label="{{ __('admin.payment_section.method_type') }}" required
                        x-on:change="methodType = $event.target.value">
                        <option value="">{{ __('admin.payment_section.select_type_placeholder') }}</option>
                        @foreach($methodTypes as $key => $info)
                            <option value="{{ $key }}">{{ $info['label'] }}</option>
                        @endforeach
                    </x-form-select>
                </div>
                <div>
                    <x-form-select name="provider" label="{{ __('admin.payment_section.provider_gateway') }}">
                        <option value="">{{ __('admin.payment_section.none_manual') }}</option>
                        @foreach($gateways as $code => $gateway)
                            <option value="{{ $code }}">{{ $gateway->getName() }}</option>
                        @endforeach
                    </x-form-select>
                </div>
                <div>
                    <x-form-input name="display_name_en" label="{{ __('admin.payment_section.display_name_en') }}" placeholder="{{ __('admin.payment_section.display_name_en_placeholder') }}"
                        required />
                </div>
                <div>
                    <x-form-input name="display_name_ar" label="{{ __('admin.payment_section.display_name_ar') }}" placeholder="{{ __('admin.payment_section.display_name_ar_placeholder') }}" dir="rtl" />
                </div>
                <div>
                    <x-form-input name="fee_pct" label="{{ __('admin.payment_section.fee_pct') }}" type="number" placeholder="0.00" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.payment_section.fee_fixed') }}</label>
                    <input type="number" name="fee_fixed_display" id="fee_fixed_display" placeholder="0.00" min="0"
                        step="0.01"
                        class="block w-full rounded-lg border border-gray-300 py-2 px-3 text-sm text-gray-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-200 focus:border-primary-500" />
                    <input type="hidden" name="fee_fixed" id="fee_fixed" />
                    <p class="text-xs text-gray-400 mt-1">{{ __('admin.payment_section.fee_fixed_hint') }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.payment_section.min_order_amount') }}</label>
                    <input type="number" name="min_order_display" id="min_order_display" placeholder="0.00" min="0"
                        step="0.01"
                        class="block w-full rounded-lg border border-gray-300 py-2 px-3 text-sm text-gray-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-200 focus:border-primary-500" />
                    <input type="hidden" name="min_order" id="min_order" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.payment_section.max_order_amount') }}</label>
                    <input type="number" name="max_order_display" id="max_order_display" placeholder="0.00" min="0"
                        step="0.01"
                        class="block w-full rounded-lg border border-gray-300 py-2 px-3 text-sm text-gray-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-200 focus:border-primary-500" />
                    <input type="hidden" name="max_order" id="max_order" />
                </div>
            </div>

            {{-- ─── Gateway Configuration ──────────────────────────────────────── --}}
            <div class="mt-4 pt-4 border-t border-gray-200">
                <h4 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-1">
                    <x-heroicon name="key" class="w-4 h-4 text-gray-400" />
                    {{ __('admin.payment_section.gateway_configuration') }}
                </h4>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <x-form-select name="gateway_code" label="{{ __('admin.payment_section.gateway_driver') }}">
                            <option value="">{{ __('admin.payment_section.none_manual') }}</option>
                            @foreach($availableGateways as $code)
                                <option value="{{ $code }}">{{ ucwords(str_replace('_', ' ', $code)) }}</option>
                            @endforeach
                        </x-form-select>
                        <p class="text-xs text-gray-400 mt-1">{{ __('admin.payment_section.gateway_driver_hint') }}</p>
                    </div>
                    <div>
                        <x-form-select name="environment" label="{{ __('admin.payment_section.environment') }}">
                            <option value="sandbox">{{ __('admin.payment_section.sandbox') }}</option>
                            <option value="production">{{ __('admin.payment_section.production') }}</option>
                        </x-form-select>
                    </div>
                </div>

                {{-- ─── Credentials (key-value, per gateway) ──────────────────── --}}
                <div class="mt-4" id="credentials-section">
                    <label class="block text-xs font-medium text-gray-600 mb-2">{{ __('admin.payment_section.api_credentials') }}</label>
                    <div id="credentials-fields" class="space-y-2"></div>
                    <p class="text-xs text-gray-400 mt-1">{{ __('admin.payment_section.credentials_hint') }}</p>
                </div>

                {{-- ─── Webhook Secret ─────────────────────────────────────────── --}}
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.payment_section.webhook_secret') }}</label>
                    <input type="password" name="webhook_secret" id="webhook_secret" autocomplete="off"
                        placeholder="{{ __('admin.payment_section.leave_blank_to_keep') }}"
                        class="block w-full rounded-lg border border-gray-300 py-2 px-3 text-sm text-gray-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-200 focus:border-primary-500">
                    <p class="text-xs text-gray-400 mt-1">{{ __('admin.payment_section.webhook_secret_hint') }}</p>
                </div>

                {{-- ─── Test Connection ────────────────────────────────────────── --}}
                <div class="mt-4 flex items-center gap-3">
                    <button type="button" id="btn-test-connection"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50"
                        disabled>
                        <x-heroicon name="signal" class="w-3.5 h-3.5" />
                        {{ __('admin.payment_section.test_connection') }}
                    </button>
                    <span id="test-connection-result" class="text-xs text-gray-500"></span>
                </div>
            </div>

            <div class="mt-4 flex items-center gap-6">
                <x-form-toggle name="is_active" label="{{ __('common.active') }}" :checked="true" />
            </div>

            {{-- ─── Installment & Display Settings (BNPL / bank_transfer only) ──── --}}
            <div x-show="methodType === 'bnpl' || methodType === 'bank_transfer'" x-cloak
                class="mt-4 pt-4 border-t border-gray-200">
                <h4 class="text-sm font-semibold text-gray-700 mb-3">
                    {{ __('admin.payment_section.installment_settings') }}
                </h4>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <x-form-input name="installments_count" label="{{ __('admin.payment_section.installments_count') }}"
                            type="number" min="2" max="60" placeholder="4" />
                        <p class="text-xs text-gray-400 mt-1">{{ __('admin.payment_section.installments_count_hint') }}</p>
                    </div>
                    <div>
                        <x-form-input name="provider_logo_path" label="{{ __('admin.payment_section.provider_logo_path') }}"
                            placeholder="/assets/payment-logos/tabby.svg" />
                    </div>
                    <div class="sm:col-span-2">
                        <x-form-input name="installment_label_en" label="{{ __('admin.payment_section.installment_label_en') }}"
                            placeholder="Pay in {n} interest-free installments of {amount}" />
                        <p class="text-xs text-gray-400 mt-1">{{ __('admin.payment_section.installment_label_hint') }}</p>
                    </div>
                    <div class="sm:col-span-2">
                        <x-form-input name="installment_label_ar" label="{{ __('admin.payment_section.installment_label_ar') }}" dir="rtl" />
                    </div>
                    <div class="sm:col-span-2">
                        <x-form-input name="learn_more_url" label="{{ __('admin.payment_section.learn_more_url') }}" type="url"
                            placeholder="https://..." />
                    </div>
                </div>
            </div>

            <x-slot:footer>
                <button type="button" data-modal-close class="btn-secondary">{{ __('common.cancel') }}</button>
                <button type="submit" form="method-form" class="btn-primary">{{ __('admin.payment_section.save_method') }}</button>
            </x-slot:footer>
        </form>
    </x-modal>

    {{-- ─── Confirm Delete Modal ───────────────────────────────────────────── --}}
    <x-modal id="delete-method-modal" title="{{ __('admin.payment_section.remove_method_title') }}" size="sm">
        <p class="text-sm text-gray-600" id="delete-method-message">
            {{ __('admin.payment_section.remove_method_confirm') }}
        </p>
        <input type="hidden" id="delete-method-id" />
        <x-slot:footer>
            <button type="button" data-modal-close class="btn-secondary">{{ __('common.cancel') }}</button>
            <button type="button" id="btn-confirm-delete-method" class="btn-danger">{{ __('admin.payment_section.remove') }}</button>
        </x-slot:footer>
    </x-modal>

@endsection

@push('scripts')
    <script type="module">
        window.TRANSLATIONS = window.TRANSLATIONS || {};
        Object.assign(window.TRANSLATIONS, {
            active: @json(__('common.active')),
            inactive: @json(__('common.inactive')),
            testing: @json(__('admin.payment_section.testing')),
            connected: @json(__('admin.payment_section.connected')),
            failed: @json(__('admin.payment_section.failed')),
            requestFailed: @json(__('admin.payment_section.request_failed')),
            test: @json(__('admin.payment_section.test')),
            failedToUpdateStatus: @json(__('admin.payment_section.failed_to_update_status')),
            addPaymentMethod: @json(__('admin.payment_section.add_payment_method')),
            editPaymentMethod: @json(__('admin.payment_section.edit_payment_method')),
            paymentMethodUpdated: @json(__('admin.payment_section.payment_method_updated')),
            paymentMethodCreated: @json(__('admin.payment_section.payment_method_created')),
            saveFailed: @json(__('admin.payment_section.save_failed')),
            removeConfirm: @json(__('admin.payment_section.remove_confirm')),
            paymentMethodRemoved: @json(__('admin.payment_section.payment_method_removed')),
            deleteFailed: @json(__('admin.payment_section.delete_failed')),
        });
    </script>
    @vite(['resources/js/admin/payment-methods.js'])
@endpush
