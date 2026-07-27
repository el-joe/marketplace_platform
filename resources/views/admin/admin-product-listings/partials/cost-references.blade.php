{{--
    Cost References tab — admin-only, confidential.
    Guarded entirely by $canViewCost (products.cost_data.view), passed from
    AdminProductListingController::show(). Never render this partial, or leak
    any of its data, to a vendor- or customer-facing view.
--}}
@if($canViewCost)
    <div class="flex items-start gap-3 bg-red-50 border-b border-red-200 px-5 py-3 rounded-t-xl">
        <x-heroicon name="lock-closed" class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" />
        <div>
            <p class="text-sm font-semibold text-red-800">Confidential Cost References</p>
            <p class="text-xs text-red-600 mt-0.5">Manufacturer, shipping and margin data — restricted to authorized admins. Never shown to vendors or customers.</p>
        </div>
    </div>

    <div class="p-5 space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-700">Cost References</h3>
            <button type="button" id="btn-add-cost-reference" class="btn btn-primary btn-sm">Add Cost Reference</button>
        </div>

        <div class="overflow-x-auto border border-gray-200 rounded-xl">
            <table id="cost-references-table" class="table-base w-full text-sm">
                <thead>
                    <tr>
                        <th>Manufacturer</th>
                        <th class="text-end">Manufacturer Cost</th>
                        <th class="text-end">Shipping Cost</th>
                        <th class="text-end">Landed Cost</th>
                        <th class="text-end">Margin %</th>
                        <th class="text-center">Competitors</th>
                        <th>Last Checked</th>
                        <th class="text-center w-24">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <x-modal id="cost-reference-modal" title="Cost Reference" size="lg">
        <form id="cost-reference-form" novalidate>
            <input type="hidden" id="cost-reference-id">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Manufacturer Name</label>
                    <input type="text" name="manufacturer_name" class="form-input w-full text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Manufacturer SKU</label>
                    <input type="text" name="manufacturer_sku" class="form-input w-full text-sm">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Manufacturer URL</label>
                    <input type="url" name="manufacturer_url" class="form-input w-full text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Manufacturer Cost (cents)</label>
                    <input type="number" name="manufacturer_cost" min="0" step="1" class="form-input w-full text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Shipping Cost (cents)</label>
                    <input type="number" name="shipping_cost" min="0" step="1" class="form-input w-full text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Landed Cost (cents)</label>
                    <input type="number" name="landed_cost" min="0" step="1" class="form-input w-full text-sm" placeholder="Auto = manufacturer + shipping">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Platform Margin %</label>
                    <input type="number" name="platform_margin_pct" step="0.01" class="form-input w-full text-sm">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea name="notes" rows="2" class="form-input w-full text-sm"></textarea>
                </div>

                <div class="sm:col-span-2 border border-gray-200 rounded-lg p-3 space-y-2">
                    <div class="flex items-center justify-between">
                        <label class="text-sm font-medium text-gray-700">Competitor Links</label>
                        <button type="button" id="btn-add-competitor-link" class="text-xs text-primary-600 hover:underline">+ Add link</button>
                    </div>
                    <div id="competitor-links-rows" class="space-y-2"></div>
                </div>
            </div>
        </form>

        <x-slot:footer>
            <button type="button" data-modal-close class="btn-secondary">{{ __('common.cancel') }}</button>
            <button type="submit" form="cost-reference-form" class="btn-primary">{{ __('common.save') }}</button>
        </x-slot:footer>
    </x-modal>

    @push('scripts')
        @vite(['resources/js/components/datatable.js', 'resources/js/admin/cost-references.js'])
        <script type="module">
            window.COST_REFERENCE_ROUTES = {
                datatable: @json(route('admin.admin-product-listings.cost-references.datatable', $listing)),
                store: @json(route('admin.admin-product-listings.cost-references.store', $listing)),
                update: @json(route('admin.admin-product-listings.cost-references.update', [$listing, '__ID__'])),
                destroy: @json(route('admin.admin-product-listings.cost-references.destroy', [$listing, '__ID__'])),
            };
        </script>
    @endpush
@endif
