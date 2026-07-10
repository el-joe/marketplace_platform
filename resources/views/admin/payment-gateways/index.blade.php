@extends('layouts.admin')

@section('title', __('admin.payment_section.gateways_title'))

@section('content')

<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ __('admin.payment_section.gateways_title') }}</h1>
        <p class="text-sm text-gray-500 mt-0.5">{{ __('admin.payment_section.gateways_desc') }}</p>
    </div>
    <button id="add-gateway-btn"
        class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700">
        <x-heroicon name="plus" class="w-4 h-4" />
        {{ __('admin.payment_section.add_payment_method') }}
    </button>
</div>

{{-- Per-country accordion --}}
<div class="max-w-6xl mx-auto pb-12" x-data="{ openCountry: '{{ $countries->first()?->id }}' }">

    @foreach($countries as $country)
    <div class="bg-white rounded-2xl border border-gray-200 mb-4 overflow-hidden">

        <button @click="openCountry = openCountry === '{{ $country->id }}' ? null : '{{ $country->id }}'"
            class="w-full flex items-center justify-between px-5 py-4 hover:bg-gray-50 transition-colors">
            <div class="flex items-center gap-3">
                <div class="text-start">
                    <p class="font-semibold text-gray-900">{{ $country->name_en }}</p>
                    <p class="text-xs text-gray-500">
                        {{ __('admin.payment_section.currency_label', ['code' => $country->currency_code]) }}
                        &middot; {{ __('admin.payment_section.method_count', ['count' => ($methods[$country->id] ?? collect())->count()]) }}
                    </p>
                </div>
            </div>
            <x-heroicon name="chevron-down"
                class="w-5 h-5 text-gray-400 transition-transform duration-200"
                ::class="openCountry === '{{ $country->id }}' ? '-rotate-180' : ''" />
        </button>

        <div x-show="openCountry === '{{ $country->id }}'" x-transition class="border-t border-gray-100">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                        <tr>
                            <th class="px-4 py-2.5 text-start">{{ __('admin.payment_section.method_col') }}</th>
                            <th class="px-4 py-2.5 text-start">{{ __('admin.payment_section.gateway_col') }}</th>
                            <th class="px-4 py-2.5 text-center">{{ __('admin.payment_section.settlement_currency_col') }}</th>
                            <th class="px-4 py-2.5 text-center">{{ __('admin.payment_section.env_col') }}</th>
                            <th class="px-4 py-2.5 text-center">{{ __('admin.payment_section.configured_col') }}</th>
                            <th class="px-4 py-2.5 text-center">{{ __('admin.payment_section.fee_col') }}</th>
                            <th class="px-4 py-2.5 text-center">{{ __('admin.payment_section.last_verified_col') }}</th>
                            <th class="px-4 py-2.5 text-center">{{ __('admin.payment_section.active_col') }}</th>
                            <th class="px-4 py-2.5"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($methods[$country->id] ?? [] as $method)
                        <tr class="hover:bg-gray-50" id="method-row-{{ $method->id }}">
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-900">{{ $method->display_name_en }}</p>
                                <p class="text-xs text-gray-400">{{ $method->method_type }}</p>
                            </td>
                            <td class="px-4 py-3">
                                @if($method->gateway_code)
                                    <span class="inline-flex items-center rounded-md bg-primary-50 px-2 py-0.5 text-xs font-medium text-primary-700 ring-1 ring-inset ring-primary-200">
                                        {{ $method->gateway_code }}
                                    </span>
                                @else
                                    <span class="text-gray-400 text-xs">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="font-mono text-xs {{ $method->settlement_currency ? 'text-amber-700 bg-amber-50 px-2 py-0.5 rounded' : 'text-gray-400' }}">
                                    {{ $method->effective_currency }}
                                    @if($method->settlement_currency)
                                        <span title="Override — differs from country default">⚠</span>
                                    @endif
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($method->environment ?? null)
                                    <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset
                                        {{ $method->environment === 'production'
                                            ? 'bg-green-50 text-green-700 ring-green-200'
                                            : 'bg-yellow-50 text-yellow-700 ring-yellow-200' }}">
                                        {{ $method->environment }}
                                    </span>
                                @else
                                    <span class="text-gray-400 text-xs">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($method->is_configured)
                                    <span class="text-green-600 font-bold" title="Credentials set">✓</span>
                                @else
                                    <span class="text-red-500" title="No credentials set">✗</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center text-xs text-gray-600">
                                {{ $method->fee_pct }}%
                                @if($method->fee_fixed_cents)
                                    + {{ number_format($method->fee_fixed_cents / 100, 2) }}
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center text-xs">
                                @if($method->last_verified_at)
                                    <span class="{{ $method->last_verification_status === 'success' ? 'text-green-600' : 'text-red-500' }}">
                                        {{ $method->last_verification_status === 'success' ? '✓' : '✗' }}
                                    </span>
                                    <span class="text-gray-400">{{ $method->last_verified_at->diffForHumans() }}</span>
                                @else
                                    <span class="text-gray-400">{{ __('admin.payment_section.never_tested') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <button type="button" class="toggle-active-btn" data-id="{{ $method->id }}">
                                    <span class="w-9 h-5 rounded-full inline-flex items-center px-0.5 transition-colors duration-200
                                        {{ $method->is_active ? 'bg-green-500 justify-end' : 'bg-gray-300 justify-start' }}">
                                        <span class="w-4 h-4 bg-white rounded-full shadow"></span>
                                    </span>
                                </button>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex gap-1 justify-end">
                                    <button type="button" class="test-connection-btn inline-flex items-center rounded px-2 py-1 text-xs font-medium text-gray-600 bg-gray-100 hover:bg-gray-200"
                                        data-id="{{ $method->id }}" title="{{ __('admin.payment_section.test_connection') }}">
                                        {{ __('admin.payment_section.test') }}
                                    </button>
                                    <button type="button" class="edit-method-btn inline-flex items-center rounded px-2 py-1 text-xs font-medium text-gray-600 bg-gray-100 hover:bg-gray-200"
                                        data-id="{{ $method->id }}"
                                        data-method="{{ json_encode([
                                            'id'                  => $method->id,
                                            'country_id'          => $method->country_id,
                                            'method_type'         => $method->method_type,
                                            'provider'            => $method->provider,
                                            'gateway_code'        => $method->gateway_code,
                                            'display_name_en'     => $method->display_name_en,
                                            'display_name_ar'     => $method->display_name_ar,
                                            'settlement_currency' => $method->settlement_currency,
                                            'environment'         => $method->environment,
                                            'fee_pct'             => $method->fee_pct,
                                            'fee_fixed_cents'     => $method->fee_fixed_cents,
                                            'min_order_cents'     => $method->min_order_cents,
                                            'max_order_cents'     => $method->max_order_cents,
                                            'sort_order'          => $method->sort_order,
                                            'is_configured'       => $method->is_configured,
                                        ]) }}">
                                        {{ __('admin.payment_section.edit') }}
                                    </button>
                                    <button type="button" class="delete-method-btn inline-flex items-center rounded px-2 py-1 text-xs font-medium text-red-600 bg-red-50 hover:bg-red-100"
                                        data-id="{{ $method->id }}">
                                        {{ __('admin.payment_section.delete') }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-gray-400 text-sm">
                                {{ __('admin.payment_section.no_methods_for_country_yet') }}
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
    @endforeach

</div>

{{-- ─── Add / Edit Modal ────────────────────────────────────────────────────── --}}
<div id="gateway-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 backdrop-blur-sm" role="dialog">
    <div class="relative w-full max-w-2xl max-h-[90vh] overflow-y-auto bg-white rounded-2xl shadow-2xl mx-4">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h2 class="text-lg font-semibold text-gray-900" id="modal-title">{{ __('admin.payment_section.method_modal_title') }}</h2>
            <button type="button" id="modal-close" class="text-gray-400 hover:text-gray-600">
                <x-heroicon name="x-mark" class="w-5 h-5" />
            </button>
        </div>

        <form id="gateway-form" novalidate>
            <input type="hidden" name="method_id" id="method-id">

            <div class="p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.payment_section.country_required') }} <span class="text-red-500">*</span></label>
                        <select name="country_id" id="field-country_id" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500" required>
                            <option value="">{{ __('admin.payment_section.select_country_placeholder') }}</option>
                            @foreach($countries as $c)
                                <option value="{{ $c->id }}">{{ $c->name_en }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.payment_section.gateway_strategy') }} <span class="text-red-500">*</span></label>
                        <select name="gateway_code" id="field-gateway_code" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500" required>
                            <option value="">{{ __('admin.payment_section.select_gateway_placeholder') }}</option>
                            @foreach($availableGateways as $code)
                                <option value="{{ $code }}">{{ ucfirst(str_replace('_', ' ', $code)) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.payment_section.display_name_en') }} <span class="text-red-500">*</span></label>
                        <input type="text" name="display_name_en" id="field-display_name_en" dir="ltr"
                            class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.payment_section.display_name_ar') }}</label>
                        <input type="text" name="display_name_ar" id="field-display_name_ar" dir="rtl"
                            class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.payment_section.method_type_required') }} <span class="text-red-500">*</span></label>
                        <select name="method_type" id="field-method_type" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500" required>
                            <option value="card">{{ __('admin.payment_section.card') }}</option>
                            <option value="wallet">{{ __('admin.payment_section.wallet') }}</option>
                            <option value="cod">{{ __('admin.payment_section.cash_on_delivery_type') }}</option>
                            <option value="bank_transfer">{{ __('admin.payment_section.bank_transfer_type') }}</option>
                            <option value="bnpl">{{ __('admin.payment_section.bnpl_type') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.payment_section.environment') }} <span class="text-red-500">*</span></label>
                        <select name="environment" id="field-environment" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            <option value="sandbox">{{ __('admin.payment_section.sandbox_test') }}</option>
                            <option value="production">{{ __('admin.payment_section.production_live') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.payment_section.settlement_currency_override') }}</label>
                        <select name="settlement_currency" id="field-settlement_currency" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            <option value="">{{ __('admin.payment_section.use_country_default') }}</option>
                            @foreach($currencies as $cur)
                                <option value="{{ $cur->code }}">{{ $cur->code }} — {{ $cur->name }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-amber-600 mt-1">⚠ {{ __('admin.payment_section.settlement_override_hint') }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.payment_section.fee_pct') }}</label>
                        <input type="number" step="0.01" min="0" max="100" name="fee_pct" id="field-fee_pct"
                            class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500" value="0">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.payment_section.fixed_fee_subunits') }}</label>
                        <input type="number" min="0" name="fee_fixed_cents" id="field-fee_fixed_cents"
                            class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500" value="0">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.payment_section.min_order') }}</label>
                        <input type="number" min="0" name="min_order_cents" id="field-min_order_cents"
                            class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500" value="0">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.payment_section.max_order_optional') }}</label>
                        <input type="number" min="0" name="max_order_cents" id="field-max_order_cents"
                            class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    </div>
                </div>

                {{-- Dynamic credentials section --}}
                <div id="credentials-section" class="bg-amber-50 border border-amber-200 rounded-xl p-4 space-y-3">
                    <p class="text-xs font-semibold text-amber-700 uppercase flex items-center gap-1.5">
                        <x-heroicon name="lock-closed" class="w-3.5 h-3.5" />
                        {{ __('admin.payment_section.gateway_credentials') }}
                    </p>

                    <div id="no-gateway-selected" class="text-xs text-gray-400 italic">
                        {{ __('admin.payment_section.select_gateway_hint') }}
                    </div>

                    {{-- Thawani fields --}}
                    <div data-gateway-fields="thawani" class="space-y-3 hidden">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('admin.payment_section.publishable_key') }}</label>
                            <input type="text" name="credentials[publishable_key]"
                                class="block w-full rounded-lg border border-gray-300 px-3 py-2 font-mono text-xs focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                placeholder="pk_…  (leave blank to keep existing when editing)">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('admin.payment_section.secret_key_field') }}</label>
                            <input type="password" name="credentials[secret_key]"
                                class="block w-full rounded-lg border border-gray-300 px-3 py-2 font-mono text-xs focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                placeholder="sk_…  (leave blank to keep existing when editing)">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('admin.payment_section.webhook_signing_secret') }}</label>
                        <input type="password" name="webhook_secret" id="field-webhook_secret"
                            class="block w-full rounded-lg border border-gray-300 px-3 py-2 font-mono text-xs focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            placeholder="{{ __('admin.payment_section.leave_blank_hint') }}">
                    </div>
                </div>

                <p id="configured-notice" class="hidden text-xs text-green-700 bg-green-50 border border-green-200 rounded-lg px-3 py-2">
                    ✓ {{ __('admin.payment_section.credentials_saved_notice') }}
                </p>
            </div>

            <div class="flex justify-end gap-3 px-6 pb-6">
                <button type="button" id="modal-cancel" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    {{ __('admin.payment_section.cancel') }}
                </button>
                <button type="submit" id="save-gateway-btn"
                    class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">
                    {{ __('admin.payment_section.save_payment_method') }}
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
window.TRANSLATIONS = window.TRANSLATIONS || {};
Object.assign(window.TRANSLATIONS, {
    editPaymentMethod: @json(__('admin.payment_section.method_modal_title')),
    addPaymentMethod: @json(__('admin.payment_section.add_payment_method')),
    deletePaymentMethodConfirm: @json(__('admin.payment_section.remove_method_confirm')),
    deleted: @json(__('common.deleted_successfully')),
    cannotDelete: @json(__('admin.payment_section.cannot_delete')),
    networkError: @json(__('admin.payment_section.network_error')),
    testLabel: @json(__('admin.payment_section.test')),
    saving: @json(__('admin.payment_section.saving')),
    savePaymentMethod: @json(__('admin.payment_section.save_payment_method')),
    saved: @json(__('common.saved_successfully')),
    saveFailed: @json(__('admin.payment_section.save_failed')),
});

(function () {
    const routes = {
        store:           '{{ route('admin.payment-methods.store') }}',
        update:          (id) => `/admin/payment-methods/${id}`,
        destroy:         (id) => `/admin/payment-methods/${id}`,
        toggle:          (id) => `/admin/payment-methods/${id}/toggle`,
        testConnection:  (id) => `/admin/payment-methods/${id}/test-connection`,
    };

    const modal   = document.getElementById('gateway-modal');
    const form    = document.getElementById('gateway-form');
    const title   = document.getElementById('modal-title');
    const methodId = document.getElementById('method-id');
    const gatewaySel = document.getElementById('field-gateway_code');

    function openModal(data = null) {
        form.reset();
        document.querySelectorAll('[data-gateway-fields]').forEach(el => el.classList.add('hidden'));
        document.getElementById('no-gateway-selected').classList.remove('hidden');
        document.getElementById('configured-notice').classList.add('hidden');

        if (data) {
            title.textContent = window.TRANSLATIONS.editPaymentMethod;
            methodId.value = data.id;
            ['country_id','method_type','gateway_code','display_name_en','display_name_ar',
             'settlement_currency','environment','fee_pct','fee_fixed_cents',
             'min_order_cents','max_order_cents','sort_order'].forEach(k => {
                const el = document.getElementById('field-' + k);
                if (el && data[k] != null) el.value = data[k];
            });
            if (data.gateway_code) showGatewayFields(data.gateway_code);
            if (data.is_configured) {
                document.getElementById('configured-notice').classList.remove('hidden');
            }
        } else {
            title.textContent = window.TRANSLATIONS.addPaymentMethod;
            methodId.value = '';
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function showGatewayFields(code) {
        document.querySelectorAll('[data-gateway-fields]').forEach(el => el.classList.add('hidden'));
        const panel = document.querySelector(`[data-gateway-fields="${code}"]`);
        if (panel) {
            panel.classList.remove('hidden');
            document.getElementById('no-gateway-selected').classList.add('hidden');
        } else {
            document.getElementById('no-gateway-selected').classList.remove('hidden');
        }
    }

    gatewaySel.addEventListener('change', () => showGatewayFields(gatewaySel.value));

    document.getElementById('add-gateway-btn').addEventListener('click', () => openModal());
    document.getElementById('modal-close').addEventListener('click', closeModal);
    document.getElementById('modal-cancel').addEventListener('click', closeModal);

    document.addEventListener('click', e => {
        if (e.target.closest('.edit-method-btn')) {
            const btn = e.target.closest('.edit-method-btn');
            openModal(JSON.parse(btn.dataset.method));
        }
        if (e.target.closest('.delete-method-btn')) {
            const id = e.target.closest('.delete-method-btn').dataset.id;
            if (!confirm(window.TRANSLATIONS.deletePaymentMethodConfirm)) return;
            fetch(routes.destroy(id), {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            })
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    document.getElementById('method-row-' + id)?.remove();
                    Toastify({ text: window.TRANSLATIONS.deleted, duration: 3000, style: { background: '#22c55e' } }).showToast();
                } else {
                    Toastify({ text: d.message || window.TRANSLATIONS.cannotDelete, duration: 5000, style: { background: '#ef4444' } }).showToast();
                }
            });
        }
        if (e.target.closest('.toggle-active-btn')) {
            const id = e.target.closest('.toggle-active-btn').dataset.id;
            fetch(routes.toggle(id), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            })
            .then(r => r.json())
            .then(d => {
                if (d.success) location.reload();
            });
        }
        if (e.target.closest('.test-connection-btn')) {
            const btn = e.target.closest('.test-connection-btn');
            const id  = btn.dataset.id;
            btn.textContent = '…';
            btn.disabled = true;
            fetch(routes.testConnection(id), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            })
            .then(r => r.json())
            .then(d => {
                const color = d.success ? '#22c55e' : '#ef4444';
                Toastify({ text: d.message, duration: 5000, style: { background: color } }).showToast();
                btn.textContent = window.TRANSLATIONS.testLabel;
                btn.disabled = false;
                // Reload to refresh last_verified_at display
                setTimeout(() => location.reload(), 2000);
            });
        }
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const id  = methodId.value;
        const url = id ? routes.update(id) : routes.store;
        const method = id ? 'PUT' : 'POST';

        const body = {};
        new FormData(form).forEach((v, k) => {
            // Support nested keys like credentials[publishable_key]
            const match = k.match(/^(\w+)\[(\w+)\]$/);
            if (match) {
                body[match[1]] = body[match[1]] || {};
                if (v) body[match[1]][match[2]] = v; // skip blank credential fields
            } else {
                if (k !== 'method_id') body[k] = v;
            }
        });

        const saveBtn = document.getElementById('save-gateway-btn');
        saveBtn.disabled = true;
        saveBtn.textContent = window.TRANSLATIONS.saving;

        fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: JSON.stringify(body),
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                Toastify({ text: d.message || window.TRANSLATIONS.saved, duration: 3000, style: { background: '#22c55e' } }).showToast();
                closeModal();
                setTimeout(() => location.reload(), 800);
            } else {
                const msg = d.message || Object.values(d.errors || {}).flat().join(', ') || window.TRANSLATIONS.saveFailed;
                Toastify({ text: msg, duration: 5000, style: { background: '#ef4444' } }).showToast();
            }
        })
        .catch(() => Toastify({ text: window.TRANSLATIONS.networkError, duration: 4000, style: { background: '#ef4444' } }).showToast())
        .finally(() => { saveBtn.disabled = false; saveBtn.textContent = window.TRANSLATIONS.savePaymentMethod; });
    });
})();
</script>
@endpush
