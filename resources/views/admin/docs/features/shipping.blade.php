@component('admin.docs._layout', ['title' => 'Shipping & Zones', 'icon' => '🚛', 'breadcrumb' => 'Features'])

    <div class="prose prose-sm max-w-none space-y-10">

        {{-- 1. Architecture --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">1. Architecture</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm border border-gray-200 rounded-lg overflow-hidden">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left px-4 py-2 font-medium text-gray-700">Layer</th>
                            <th class="text-left px-4 py-2 font-medium text-gray-700">Purpose</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr>
                            <td class="px-4 py-2 font-mono text-xs text-gray-800">shipping_zones</td>
                            <td class="px-4 py-2 text-gray-600">Geographic groupings of cities within a country</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-2 font-mono text-xs text-gray-800">shipping_methods</td>
                            <td class="px-4 py-2 text-gray-600">Carrier/service options available per zone</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-2 font-mono text-xs text-gray-800">shipping_rates</td>
                            <td class="px-4 py-2 text-gray-600">Base fee + per-kg rate for a zone/method pair</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-2 font-mono text-xs text-gray-800">shipping_weight_slabs</td>
                            <td class="px-4 py-2 text-gray-600">Extra fee tiers layered on top of the rate for heavy items</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p class="text-gray-500 text-xs mt-2">shipping_zones &rarr; shipping_methods &rarr; shipping_rates &rarr; shipping_weight_slabs</p>
        </section>

        {{-- 2. Weight Calculation --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">2. Weight Calculation</h2>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li><code>actual_weight = product.weight_grams &times; quantity</code></li>
                <li><code>volumetric_weight = (L &times; W &times; H cm) &divide; volumetric_divisor &times; 1000</code></li>
                <li><code>billable_weight = MAX(actual, volumetric)</code></li>
            </ul>
            <p class="text-gray-600">Standard divisor: <strong>5000</strong> (configurable per zone/method)</p>
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 mt-2 text-gray-700 text-sm">
                <strong>Example:</strong> 0.5kg actual, 40&times;30&times;20cm &rarr; volumetric = 4.8kg &rarr; billed at 4.8kg
            </div>
        </section>

        {{-- 3. Rate Calculation --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">3. Rate Calculation</h2>
            <p class="text-gray-600 font-mono text-sm bg-gray-50 border border-gray-200 rounded-lg p-3">
                base_fee + (billable_weight_kg &times; rate_per_kg) + slab_extra_fee
            </p>
        </section>

        {{-- 4. Shipping Zones --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">4. Shipping Zones</h2>
            <p class="text-gray-600">Geographic groupings within each country. Cities assigned to zones in admin. Customer address &rarr; zone lookup &rarr; rate lookup.</p>
        </section>

        {{-- 5. Exceptional Zones --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">5. Exceptional Zones</h2>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li><code>vendor_exceptional_zones</code>: vendor opts in to serve a specific zone</li>
                <li><code>vendor_fbp_subsidy_settings</code>: admin sets <code>vendor_share</code> + <code>admin_support</code> split</li>
            </ul>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mt-2 text-blue-800 text-sm">
                At checkout: customer sees <strong>FREE DELIVERY</strong>. Behind the scenes: <code>vendor_share</code> deducted from vendor payout, platform absorbs <code>admin_support</code>.
            </div>
        </section>

        {{-- 6. Free Shipping Threshold --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">6. Free Shipping Threshold</h2>
            <p class="text-gray-600">Settings key: <code>'free_shipping_threshold_{country_code}'</code></p>
            <p class="text-gray-600">If order subtotal &ge; threshold: <code>shipping_fee = 0</code> (platform absorbs)</p>
        </section>

        {{-- 7. Weight Slabs --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">7. Weight Slabs</h2>
            <p class="text-gray-600">Extra fee tiers for heavy items:</p>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li>0&ndash;1kg: +0</li>
                <li>1&ndash;5kg: +10 AED</li>
                <li>5&ndash;20kg: +25 AED</li>
                <li>20kg+: special handling</li>
            </ul>
        </section>

        {{-- 8. Surcharges --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">8. Surcharges (FBN)</h2>
            <p class="text-gray-600">Warehouse-level outbound surcharges on FBN shipments (<code>warehouse/shipping-surcharges</code>)</p>
        </section>

        {{-- 9. Estimated Delivery Date --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">9. Estimated Delivery Date</h2>
            <p class="text-gray-600 font-mono text-sm bg-gray-50 border border-gray-200 rounded-lg p-3">
                order_confirmed_at + (missed cutoff ? +1 day : 0) + delivery_days range
            </p>
            <p class="text-gray-600">Excludes Fridays and country public holidays.</p>
        </section>

    </div>

@endcomponent
