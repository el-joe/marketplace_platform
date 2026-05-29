{{-- Review modal: shown when admin clicks "Review" on a submission row --}}
<x-modal id="review-modal" title="Review Submission" size="xl">
    <form id="review-form">
        <input type="hidden" name="submission_id">
        @csrf

        <div class="flex gap-6">

            {{-- ── Left: product + vendor info ───────────────────────────── --}}
            <div class="w-64 flex-shrink-0 space-y-4">

                <div class="text-center">
                    <img id="review-product-img" src="" alt="" class="w-48 h-48 object-cover rounded-lg mx-auto mb-2 bg-gray-100">
                    <p id="review-product-name" class="font-semibold text-gray-900 text-sm"></p>
                    <p id="review-variant-name" class="text-xs text-gray-500"></p>
                </div>

                <div class="bg-gray-50 rounded-lg p-3 text-sm space-y-1.5">
                    <p class="font-semibold text-xs text-gray-500 uppercase">Vendor</p>
                    <p id="review-vendor-name" class="font-medium text-gray-900"></p>
                    <p id="review-vendor-country" class="text-gray-500 text-xs"></p>
                    <div class="flex items-center gap-1 text-xs">
                        <span class="text-warning-500">★</span>
                        <span id="review-vendor-rating">—</span>
                    </div>
                </div>

                <div class="bg-gray-50 rounded-lg p-3 text-sm">
                    <p class="font-semibold text-xs text-gray-500 uppercase mb-2">Stock Levels</p>
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="text-gray-400 border-b">
                                <th class="pb-1 text-left">WH</th>
                                <th class="pb-1 text-right">On hand</th>
                                <th class="pb-1 text-right">Avail</th>
                            </tr>
                        </thead>
                        <tbody id="review-stock-tbody">
                            <tr><td colspan="3" class="text-center text-gray-400 py-2">Loading…</td></tr>
                        </tbody>
                    </table>
                </div>

            </div>

            {{-- ── Right: price analysis + decision ───────────────────────── --}}
            <div class="flex-1 min-w-0 space-y-4">

                {{-- Price comparison --}}
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-gray-50 rounded-lg p-3 text-center">
                        <p class="text-xs text-gray-500 mb-1">Original Price</p>
                        <p id="review-original-price" class="text-xl font-bold text-gray-900"></p>
                    </div>
                    <div class="bg-primary-50 rounded-lg p-3 text-center">
                        <p class="text-xs text-primary-600 mb-1">Flash Price</p>
                        <p id="review-flash-price" class="text-xl font-bold text-primary-700"></p>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-500">Discount</p>
                        <p id="review-discount-pct" class="text-3xl font-bold"></p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-gray-500 mb-1">Required min: {{ $sale->min_discount_pct }}%</p>
                        <span id="review-discount-check"></span>
                    </div>
                </div>

                <div id="review-30d-row" class="hidden text-sm text-gray-600 bg-gray-50 rounded p-2">
                    30-day avg price: <strong id="review-30d-price"></strong>
                    — Flash price is <strong id="review-30d-diff"></strong>
                </div>

                {{-- Price history chart --}}
                <div>
                    <p class="text-xs text-gray-500 mb-1">30-day price history</p>
                    <div style="height:150px"><canvas id="price-history-chart"></canvas></div>
                </div>

                {{-- Fake discount warnings --}}
                <div id="fake-discount-warnings" class="hidden space-y-1"></div>

                {{-- Decision form --}}
                <div x-data="{ decision: '' }" class="space-y-3 border-t pt-3">
                    <x-form-radio-group name="decision" label="Decision"
                        :options="['approved' => 'Approve', 'rejected' => 'Reject']"
                        x-model="decision" />

                    <div x-show="decision === 'rejected'" id="rejection-fields" class="space-y-3">
                        <x-form-select name="rejection_code" label="Rejection reason" required
                            :options="[
                                'discount_too_low'        => 'Discount too low',
                                'fake_discount_suspected' => 'Fake discount detected',
                                'insufficient_stock'      => 'Insufficient stock',
                                'not_eligible_category'   => 'Category not eligible',
                                'slot_limit_reached'      => 'Slot limit reached',
                                'policy_violation'        => 'Policy violation',
                                'vendor_not_eligible'     => 'Vendor not eligible',
                            ]" />
                        <x-form-textarea name="rejection_reason" label="Message to vendor" rows="2" />
                    </div>

                    <x-form-textarea name="admin_notes" label="Internal admin notes (not shown to vendor)" rows="2" />

                    <div class="flex justify-end gap-2">
                        <button type="button" onclick="closeModal('review-modal')" class="btn btn-ghost">Cancel</button>
                        <button type="submit" id="review-submit-btn" class="btn btn-primary">Save Decision</button>
                    </div>
                </div>

            </div>
        </div>
    </form>
</x-modal>
