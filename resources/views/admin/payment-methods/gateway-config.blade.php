@extends('layouts.admin')

@section('title', __('admin.payment_section.gateway_config'))

@section('content')

    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('admin.payment_section.gateway_config') }}</h1>
            <p class="text-sm text-gray-500 mt-0.5">{{ __('admin.payment_section.gateway_config_desc') }}</p>
        </div>
        <a href="{{ route('admin.payment-methods.index') }}"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
            <x-heroicon name="arrow-left" class="w-4 h-4" />
            {{ __('admin.payment_section.back_to_methods') }}
        </a>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        @foreach($gateways as $code => $gateway)
            @php
                $envKeys = match ($code) {
                    'paytabs' => ['PAYTABS_PROFILE_ID', 'PAYTABS_SERVER_KEY', 'PAYTABS_REGION', 'PAYTABS_BASE_URL'],
                    'tabby' => ['TABBY_SECRET_KEY', 'TABBY_PUBLIC_KEY', 'TABBY_MERCHANT_CODE'],
                    'noon_pay' => ['NOON_PAY_APP_KEY', 'NOON_PAY_APP_SECRET', 'NOON_PAY_BUSINESS_ID', 'NOON_PAY_ENV'],
                    'stripe' => ['STRIPE_KEY', 'STRIPE_SECRET', 'STRIPE_WEBHOOK_SECRET'],
                    'cod' => [],
                    'bank_transfer' => [],
                    default => [],
                };
            @endphp

            <x-card>
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between gap-4">
                    <h3 class="text-base font-semibold text-gray-900">{{ $gateway->getName() }}</h3>
                    <div class="flex items-center gap-2">
                        <button type="button"
                            class="btn-test-gateway inline-flex items-center gap-1 rounded-lg bg-primary-50 px-3 py-1.5 text-xs font-medium text-primary-700 hover:bg-primary-100 border border-primary-200"
                            data-provider="{{ $code }}">
                            <x-heroicon name="signal" class="w-3.5 h-3.5" />
                            {{ __('admin.payment_section.test_connection') }}
                        </button>
                    </div>
                </div>

                <div class="p-4 space-y-3">
                    <div class="flex items-center gap-2 text-sm">
                        <span class="font-medium text-gray-600">{{ __('admin.payment_section.code_label') }}</span>
                        <code class="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-xs text-gray-700">{{ $code }}</code>
                    </div>

                    <div class="flex items-center gap-2 text-sm">
                        <span class="font-medium text-gray-600">{{ __('admin.payment_section.supported_types') }}</span>
                        <div class="flex flex-wrap gap-1">
                            @foreach($gateway->getSupportedMethodTypes() as $type)
                                <span
                                    class="rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700">{{ $type }}</span>
                            @endforeach
                        </div>
                    </div>

                    @if(count($envKeys) > 0)
                        <div class="mt-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-2">{{ __('admin.payment_section.required_env_vars') }}</p>
                            <ul class="space-y-1">
                                @foreach($envKeys as $envKey)
                                    @php $isSet = !empty(env($envKey)); @endphp
                                    <li class="flex items-center gap-2 text-xs">
                                        @if($isSet)
                                            <x-heroicon name="check-circle" class="w-3.5 h-3.5 text-success-500 flex-shrink-0" />
                                        @else
                                            <x-heroicon name="x-circle" class="w-3.5 h-3.5 text-danger-500 flex-shrink-0" />
                                        @endif
                                        <code class="font-mono text-gray-600">{{ $envKey }}</code>
                                        <span class="{{ $isSet ? 'text-success-600' : 'text-danger-600' }}">
                                            {{ $isSet ? __('admin.payment_section.set_status') : __('admin.payment_section.missing_status') }}
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @else
                        <p class="text-xs text-gray-400 italic">{{ __('admin.payment_section.no_api_credentials') }}</p>
                    @endif

                    {{-- Test result area --}}
                    <div class="gateway-test-result hidden rounded-lg border p-3 text-sm" id="test-result-{{ $code }}"></div>
                </div>
            </x-card>
        @endforeach
    </div>

@endsection

@push('scripts')
    <script>
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
