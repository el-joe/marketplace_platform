@extends('layouts.admin')

@section('title', __('admin.categories.title'))

@push('styles')
    @vite(['resources/js/admin/categories.js'])
@endpush

@section('content')
    <div class="space-y-4">

        {{-- Toolbar --}}
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <button type="button" id="bulk-commission-btn" class="btn btn-ghost btn-sm hidden">
                    <x-heroicon name="percent-badge" class="w-4 h-4 mr-1" />
                    {{ __('admin.categories.bulk_commission_short') }}
                </button>
            </div>
            <a href="{{ route('admin.categories.create') }}" class="btn btn-primary btn-sm">
                <x-heroicon name="plus" class="w-4 h-4 mr-1" />
                {{ __('admin.categories.new_category') }}
            </a>
        </div>

        {{-- Tree Table --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm" id="categories-table">
                    <thead>
                        <tr
                            class="bg-gray-50 border-b border-gray-200 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            <th class="px-2 py-3 w-8"></th>
                            <th class="px-4 py-3">{{ __('admin.category') }}</th>
                            <th class="px-4 py-3 text-end">{{ __('admin.categories.products_count') }}</th>
                            <th class="px-4 py-3 text-end">{{ __('admin.commission') }}</th>
                            <th class="px-4 py-3">{{ __('admin.categories.default_delivery') }}</th>
                            <th class="px-4 py-3">{{ __('admin.status') }}</th>
                            <th class="px-4 py-3">{{ __('admin.categories.featured') }}</th>
                            <th class="px-4 py-3 text-end"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($roots as $root)
                            @include('admin.categories._tree_row', ['category' => $root, 'depth' => 0, 'defaultShippingByCategory' => $defaultShippingByCategory])
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-12 text-center text-gray-400">
                                    {{ __('admin.categories.no_categories_yet') }}
                                    <a href="{{ route('admin.categories.create') }}" class="text-primary-600 underline ml-1">{{ __('admin.categories.add_the_first_one') }}</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Bulk Commission Modal --}}
        <div id="bulk-commission-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/50" id="bulk-commission-backdrop"></div>
            <div class="relative bg-white rounded-xl shadow-2xl p-6 max-w-sm w-full space-y-4">
                <h3 class="font-semibold text-gray-900">{{ __('admin.categories.bulk_commission_rate') }}</h3>
                <p class="text-sm text-gray-500">{{ __('admin.categories.apply_rate_to_selected') }} <span id="bulk-count">0</span> {{ __('admin.categories.title') }}.</p>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('admin.categories.commission_rate_pct') }}</label>
                    <input type="number" id="bulk-rate-input" min="0" max="100" step="0.01" class="input w-full"
                        placeholder="{{ __('admin.categories.commission_rate_placeholder') }}" />
                </div>
                <div class="flex gap-2">
                    <button type="button" id="bulk-commission-cancel" class="btn btn-ghost flex-1">{{ __('common.cancel') }}</button>
                    <button type="button" id="bulk-commission-apply" class="btn btn-primary flex-1">{{ __('common.apply') }}</button>
                </div>
            </div>
        </div>

    </div>

    <script>
        window.TRANSLATIONS = window.TRANSLATIONS || {};
        Object.assign(window.TRANSLATIONS, {
            commissionUpdated: @json(__('admin.categories.commission_updated')),
            commissionUpdateFailed: @json(__('admin.categories.commission_update_failed')),
            featuredBadge: @json(__('admin.categories.featured_badge')),
            addBadge: @json(__('admin.categories.add_badge')),
            markedFeatured: @json(__('admin.categories.marked_featured')),
            removedFeatured: @json(__('admin.categories.removed_featured')),
            toggleFeaturedFailed: @json(__('admin.categories.toggle_featured_failed')),
            deleteConfirmTitle: @json(__('admin.categories.delete_confirm_title')),
            deleteConfirmMessage: @json(__('admin.categories.delete_confirm_message')),
            categoryDeleted: @json(__('admin.categories.category_deleted')),
            deleteFailed: @json(__('admin.categories.delete_failed')),
        });

        window.ROUTES_CAT = {
            bulkCommission: '{{ route('admin.categories.bulk-commission') }}',
            toggleFeatured: '{{ url('categories') }}/:id/toggle-featured',
            destroy: '{{ url('categories') }}/:id',
        };
    </script>
@endsection