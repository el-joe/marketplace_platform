@extends('layouts.admin')

@section('title', $method->name)

@section('content')

    <div class="mb-6 flex items-center justify-between">
        <div>
            <a href="{{ route('admin.shipping-methods.index') }}" class="text-xs text-gray-500 hover:text-primary-600 inline-flex items-center gap-1 mb-1">
                <span aria-hidden="true">&larr;</span>
                {{ __('admin.shipping_section.shipping_methods_tab') }}
            </a>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                {{ $method->name }}
                <code class="text-sm font-normal text-gray-400 font-mono">{{ $method->code }}</code>
            </h1>
        </div>
        <button type="button"
                class="btn-toggle-method text-xs rounded-full px-3 py-1 font-semibold transition
                       {{ $method->is_active ? 'bg-success-50 text-success-700 hover:bg-success-100' : 'bg-gray-100 text-gray-500 hover:bg-gray-200' }}"
                data-id="{{ $method->id }}"
                data-active="{{ $method->is_active ? '1' : '0' }}">
            {{ $method->is_active ? __('common.active') : __('common.inactive') }}
        </button>
    </div>

    {{-- ─── Method Details Form ────────────────────────────────────────────────── --}}
    <x-card>
        <div class="p-4">
            <form id="method-detail-form" novalidate
                  x-data="{
                      badgeLabel: @js($method->badge_label_en ?? ''),
                      badgeColor: @js($method->badge_color_hex ?? '#e5e7eb'),
                      badgeTextColor: @js($method->badge_text_color_hex ?? '#111827'),
                  }">
                @csrf
                <input type="hidden" id="method-id" value="{{ $method->id }}">

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <x-form-input name="name" label="{{ __('admin.shipping_section.name_label') }}" :value="$method->name" required />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.shipping_section.code_label') }}</label>
                        <input type="text" value="{{ $method->code }}" readonly disabled
                               class="block w-full rounded-lg border border-gray-200 bg-gray-50 py-2 px-3 text-sm text-gray-500 cursor-not-allowed" />
                        <p class="text-xs text-gray-400 mt-1">{{ __('admin.shipping_section.code_immutable_notice') }}</p>
                    </div>
                    <div>
                        <x-form-input name="description" label="{{ __('admin.shipping_section.description_label') }}" :value="$method->description" />
                    </div>
                    <div>
                        <x-form-input name="min_delivery_days" type="number" label="{{ __('admin.shipping_section.min_days') }}" :value="$method->min_delivery_days" required />
                    </div>
                    <div>
                        <x-form-input name="max_delivery_days" type="number" label="{{ __('admin.shipping_section.max_days') }}" :value="$method->max_delivery_days" required />
                    </div>
                    <div>
                        <x-form-input name="order_cutoff_time" type="time" label="{{ __('admin.shipping_section.order_cutoff_time') }}" :value="$method->order_cutoff_time ? substr($method->order_cutoff_time, 0, 5) : ''"
                                      help-text="{{ __('admin.shipping_section.order_cutoff_time_help') }}" />
                    </div>
                    <div>
                        <x-form-input name="handling_time_hours" type="number" min="0" label="{{ __('admin.shipping_section.handling_time_hours') }}" :value="$method->handling_time_hours" />
                    </div>
                    <div>
                        <x-form-input name="display_priority" type="number" min="0" label="{{ __('admin.shipping_section.display_priority') }}" :value="$method->display_priority" help-text="{{ __('admin.shipping_section.display_priority_help') }}" />
                    </div>
                    <div class="flex items-center gap-6">
                        <x-form-toggle name="is_express_type" label="{{ __('admin.shipping_section.express_type') }}" :checked="$method->is_express_type" />
                        <x-form-toggle name="show_estimated_price" label="{{ __('admin.shipping_section.show_estimated_price') }}" :checked="$method->show_estimated_price" />
                    </div>

                    <div class="sm:col-span-2 lg:col-span-3 border-t border-gray-100 pt-4">
                        <p class="text-sm font-semibold text-gray-700 mb-3">{{ __('admin.shipping_section.badge_label') }}</p>
                    </div>
                    <div>
                        <x-form-input name="badge_label_en" x-model="badgeLabel" label="{{ __('admin.shipping_section.badge_label_en') }}" :value="$method->badge_label_en" placeholder="Express" />
                    </div>
                    <div>
                        <x-form-input name="badge_label_ar" label="{{ __('admin.shipping_section.badge_label_ar') }}" :value="$method->badge_label_ar" placeholder="سريع" dir="rtl" />
                    </div>
                    <div class="flex items-end gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.shipping_section.badge_color') }}</label>
                            <input type="color" name="badge_color_hex" x-model="badgeColor" value="{{ $method->badge_color_hex ?? '#e5e7eb' }}"
                                   class="h-9 w-16 rounded border border-gray-300 p-0.5 cursor-pointer" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.shipping_section.text_color') }}</label>
                            <input type="color" name="badge_text_color_hex" x-model="badgeTextColor" value="{{ $method->badge_text_color_hex ?? '#111827' }}"
                                   class="h-9 w-16 rounded border border-gray-300 p-0.5 cursor-pointer" />
                        </div>
                    </div>

                    {{-- Live badge preview — mirrors exactly how the badge renders on a product listing card --}}
                    <div class="sm:col-span-2 lg:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.shipping_section.preview') }}</label>
                        <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-4 flex items-center">
                            <span x-show="badgeLabel"
                                  x-text="badgeLabel"
                                  :style="`background-color: ${badgeColor}; color: ${badgeTextColor};`"
                                  class="rounded-full px-2 py-0.5 text-xs font-semibold"></span>
                            <span x-show="!badgeLabel" class="text-xs text-gray-400">{{ __('admin.shipping_section.no_badge_configured') }}</span>
                        </div>
                    </div>

                    <div>
                        <x-form-input name="delivery_label_en" label="{{ __('admin.shipping_section.delivery_panel_label_en') }}" :value="$method->delivery_label_en" placeholder="Delivered within 2-4 days" />
                    </div>
                    <div>
                        <x-form-input name="delivery_label_ar" label="{{ __('admin.shipping_section.delivery_panel_label_ar') }}" :value="$method->delivery_label_ar" placeholder="يتم التوصيل خلال 2-4 أيام" dir="rtl" />
                    </div>

                    <div class="sm:col-span-2 lg:col-span-3">
                        <x-form-toggle name="is_active" label="{{ __('common.active') }}" :checked="$method->is_active" />
                    </div>
                </div>

                <div class="mt-4 flex justify-end">
                    <button type="submit" form="method-detail-form" class="btn-primary">{{ __('common.save') }}</button>
                </div>
            </form>
        </div>
    </x-card>

    {{-- ─── Shipping Rates Sub-table ───────────────────────────────────────────── --}}
    <div class="mt-6">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">{{ __('admin.shipping_section.shipping_rates') }}</h2>
            <button type="button" id="btn-add-rate"
                    class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700">
                <x-heroicon name="plus" class="w-4 h-4" />
                {{ __('admin.shipping_section.new_rate') }}
            </button>
        </div>

        <x-card padding="none">
            <table class="table-base w-full">
                <thead>
                    <tr>
                        <th>{{ __('admin.shipping_section.origin_zone_col') }}</th>
                        <th>{{ __('admin.shipping_section.destination_zone_col') }}</th>
                        <th class="text-end">{{ __('admin.shipping_section.base_fee_col') }}</th>
                        <th class="text-end">{{ __('admin.shipping_section.per_kg_col') }}</th>
                        <th class="text-end">{{ __('admin.shipping_section.free_threshold_col') }}</th>
                        <th class="text-center">{{ __('admin.shipping_section.status_col') }}</th>
                        <th class="text-center w-20">{{ __('admin.shipping_section.actions_col') }}</th>
                    </tr>
                </thead>
                <tbody id="method-rates-tbody">
                    @forelse($method->rates as $rate)
                        <tr data-rate-id="{{ $rate->id }}">
                            <td>{{ optional($rate->originZone)->name ?? __('admin.shipping_section.any_origin') }}</td>
                            <td>{{ optional($rate->destinationZone)->name ?? '—' }}</td>
                            <td class="text-end">{{ $rate->base_fee_formatted }}</td>
                            <td class="text-end">{{ $rate->rate_per_kg_formatted }}</td>
                            <td class="text-end">{{ $rate->free_threshold_formatted ?? '—' }}</td>
                            <td class="text-center">
                                <button type="button"
                                        class="btn-toggle-rate text-xs rounded-full px-2 py-0.5 font-semibold transition
                                               {{ $rate->is_active ? 'bg-success-50 text-success-700 hover:bg-success-100' : 'bg-gray-100 text-gray-500 hover:bg-gray-200' }}"
                                        data-id="{{ $rate->id }}"
                                        data-active="{{ $rate->is_active ? '1' : '0' }}">
                                    {{ $rate->is_active ? __('common.active') : __('common.inactive') }}
                                </button>
                            </td>
                            <td class="text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <button type="button"
                                            class="btn-edit-rate p-1 rounded text-gray-400 hover:text-primary-600"
                                            data-id="{{ $rate->id }}"
                                            data-row="{{ json_encode($rate) }}">
                                        <x-heroicon name="pencil-square" class="w-4 h-4" />
                                    </button>
                                    <button type="button"
                                            class="btn-delete-rate p-1 rounded text-gray-400 hover:text-danger-600"
                                            data-id="{{ $rate->id }}">
                                        <x-heroicon name="trash" class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr id="method-rates-empty-row">
                            <td colspan="7">
                                <x-empty-state title="{{ __('admin.shipping_section.no_shipping_rates_title') }}" description="{{ __('admin.shipping_section.no_shipping_rates_desc') }}" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-card>
    </div>

    {{-- Rate Modal --}}
    <x-modal id="rate-modal" title="{{ __('admin.shipping_section.rate_modal_title') }}" size="lg">
        <form id="rate-form" novalidate>
            @csrf
            <input type="hidden" id="rate-id">
            <input type="hidden" id="rate-http" value="POST">
            <input type="hidden" name="shipping_method_id" value="{{ $method->id }}">

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-form-select name="destination_zone_id" label="{{ __('admin.shipping_section.destination_zone') }}" required>
                        <option value="">{{ __('admin.shipping_section.select_zone_placeholder') }}</option>
                        @foreach($zones as $zone)
                            <option value="{{ $zone->id }}">{{ $zone->name }} ({{ optional($zone->country)->iso_code_2 }})</option>
                        @endforeach
                    </x-form-select>
                </div>
                <div>
                    <x-form-select name="origin_zone_id" label="{{ __('admin.shipping_section.origin_zone_optional') }}">
                        <option value="">{{ __('admin.shipping_section.any_origin') }}</option>
                        @foreach($zones as $zone)
                            <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                        @endforeach
                    </x-form-select>
                </div>
                <div>
                    <x-form-select name="carrier_id" label="{{ __('admin.shipping_section.carrier_optional') }}">
                        <option value="">{{ __('admin.shipping_section.any_carrier') }}</option>
                        @foreach($carriers as $carrier)
                            <option value="{{ $carrier->id }}">{{ $carrier->name }}</option>
                        @endforeach
                    </x-form-select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.shipping_section.base_fee_required') }} <span class="text-danger-500">*</span></label>
                    <input type="number" id="rate-base-fee-display" step="0.01" min="0" placeholder="0.00"
                           class="block w-full rounded-lg border border-gray-300 py-2 px-3 text-sm text-gray-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-200 focus:border-primary-500" />
                    <input type="hidden" name="base_fee" id="rate-base-fee" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.shipping_section.rate_per_kg_required') }} <span class="text-danger-500">*</span></label>
                    <input type="number" id="rate-per-kg-display" step="0.01" min="0" placeholder="0.00"
                           class="block w-full rounded-lg border border-gray-300 py-2 px-3 text-sm text-gray-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-200 focus:border-primary-500" />
                    <input type="hidden" name="rate_per_kg" id="rate-per-kg" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.shipping_section.free_shipping_threshold_label') }}</label>
                    <input type="number" id="rate-free-threshold-display" step="0.01" min="0" placeholder="0.00"
                           class="block w-full rounded-lg border border-gray-300 py-2 px-3 text-sm text-gray-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-200 focus:border-primary-500" />
                    <input type="hidden" name="free_shipping_threshold" id="rate-free-threshold" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.shipping_section.cod_extra_fee') }}</label>
                    <input type="number" id="rate-cod-fee-display" step="0.01" min="0" placeholder="0.00"
                           class="block w-full rounded-lg border border-gray-300 py-2 px-3 text-sm text-gray-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-200 focus:border-primary-500" />
                    <input type="hidden" name="cod_extra_fee" id="rate-cod-fee" />
                </div>
                <div>
                    <x-form-input name="min_weight_grams" label="{{ __('admin.shipping_section.min_weight_grams') }}" type="number" placeholder="0" />
                </div>
                <div>
                    <x-form-input name="volumetric_divisor" label="{{ __('admin.shipping_section.volumetric_divisor') }}" type="number" placeholder="5000" />
                </div>
                <div class="sm:col-span-2">
                    <x-form-toggle name="is_active" label="{{ __('common.active') }}" :checked="true" />
                </div>
            </div>

            <x-slot:footer>
                <button type="button" data-modal-close class="btn-secondary">{{ __('common.cancel') }}</button>
                <button type="submit" form="rate-form" class="btn-primary">{{ __('admin.shipping_section.save_rate') }}</button>
            </x-slot:footer>
        </form>
    </x-modal>

    {{-- Confirm Delete Rate Modal --}}
    <x-modal id="delete-rate-modal" title="{{ __('admin.shipping_section.delete_rate_title') }}" size="sm">
        <p class="text-sm text-gray-600">{{ __('admin.shipping_section.delete_rate_confirm') }}</p>
        <input type="hidden" id="delete-rate-id" />
        <x-slot:footer>
            <button type="button" data-modal-close class="btn-secondary">{{ __('common.cancel') }}</button>
            <button type="button" id="btn-confirm-delete-rate" class="btn-danger">{{ __('common.delete') }}</button>
        </x-slot:footer>
    </x-modal>

@endsection

@push('scripts')
    @vite(['resources/js/admin/shipping-method-show.js'])
    <script type="module">
        window.TRANSLATIONS = window.TRANSLATIONS || {};
        Object.assign(window.TRANSLATIONS, {
            active: @json(__('common.active')),
            inactive: @json(__('common.inactive')),
            shippingRateUpdated: @json(__('admin.shipping_section.shipping_rate_updated')),
            shippingRateCreated: @json(__('admin.shipping_section.shipping_rate_created')),
            saveFailed: @json(__('admin.shipping_section.save_failed')),
            shippingRateDeleted: @json(__('admin.shipping_section.shipping_rate_deleted')),
            deleteFailed: @json(__('admin.shipping_section.delete_failed')),
            failedToUpdateRateStatus: @json(__('admin.shipping_section.failed_to_update_rate_status')),
            shippingMethodUpdated: @json(__('admin.shipping_section.shipping_method_updated')),
            failedToUpdateStatus: @json(__('admin.shipping_section.failed_to_update_status')),
        });
        window.METHOD_ID = @json($method->id);
    </script>
@endpush
