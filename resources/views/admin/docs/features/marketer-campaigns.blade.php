@component('admin.docs._layout', ['title' => 'Marketer Campaigns', 'icon' => '📣', 'breadcrumb' => 'Features'])

    <div class="prose prose-sm max-w-none space-y-10">

        {{-- 1. What It Is --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">1. What It Is</h2>
            <p class="text-gray-600">Marketers (affiliates or influencers) create campaigns to promote platform products, classifieds, or travel packages and earn commissions on resulting sales.</p>
        </section>

        {{-- 2. Commission Types --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">2. Commission Types</h2>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li><code>percentage</code>: <code>FLOOR(order_value &times; rate / 100)</code> &mdash; rounded down</li>
                <li><code>flat_per_conversion</code>: <code>ROUND(flat_amount)</code> &mdash; per completed order</li>
                <li><code>flat_per_click</code>: paid per click (rare, usually for influencers)</li>
            </ul>
        </section>

        {{-- 3. Attribution Models --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">3. Attribution Models</h2>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li><code>last_click</code>: last marketer link clicked before purchase gets 100% credit</li>
                <li><code>first_click</code>: first marketer link clicked in session gets 100% credit</li>
                <li><code>linear</code>: credit split equally among all marketer touchpoints (remainder to last)</li>
            </ul>
        </section>

        {{-- 4. Conversion Lifecycle --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">4. Conversion Lifecycle</h2>
            <div class="flex flex-wrap items-center gap-2 mb-3">
                @foreach (['pending', 'approved'] as $status)
                    <span class="px-3 py-1.5 rounded-full bg-green-50 text-green-700 text-xs font-medium border border-green-200">{{ $status }}</span>
                    @if (!$loop->last)
                        <span class="text-gray-400">&rarr;</span>
                    @endif
                @endforeach
                <span class="text-gray-300">|</span>
                <span class="px-3 py-1.5 rounded-full bg-red-50 text-red-700 text-xs font-medium border border-red-200">reversed</span>
            </div>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li>Order placed via marketer link &rarr; <code>conversion.status = 'pending'</code></li>
                <li>Return window passes &rarr; <code>conversion.status = 'approved'</code></li>
                <li><code>sub_order</code> completed &rarr; marketer payout generated</li>
                <li>If order refunded &rarr; <code>conversion.status = 'reversed'</code>, earnings decremented</li>
            </ul>
        </section>

        {{-- 5. Budget Exhaustion Guard --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">5. Budget Exhaustion Guard</h2>
            <p class="text-gray-600"><code>marketer_campaigns.budget</code> (if set) checked with <code>lockForUpdate()</code> before each conversion.</p>
            <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 mt-2 text-amber-800 text-sm">
                If budget exhausted &rarr; campaign pauses automatically, no new conversions recorded.
            </div>
        </section>

        {{-- 6. Campaign Types Available --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">6. Campaign Types Available</h2>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li><strong>product campaigns:</strong> promote specific vendor_listings</li>
                <li><strong>classified campaigns:</strong> promote classified ad listings</li>
                <li><strong>travel campaigns:</strong> promote travel packages</li>
            </ul>
        </section>

        {{-- 7. Admin Actions --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">7. Admin Actions at /admin/marketer-campaigns</h2>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li><strong>approve:</strong> campaign goes live</li>
                <li><strong>reject:</strong> with reason (marketer can edit and resubmit)</li>
                <li><strong>approve pause request:</strong> when active marketer requests pause</li>
                <li><strong>dismiss pause request:</strong> if request is not valid</li>
            </ul>
        </section>

        {{-- 8. Payout Calculation --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">8. Payout Calculation</h2>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li><code>gross</code> = sum of approved conversions in period</li>
                <li><code>net</code> = gross - platform_share (if applicable) - tax</li>
            </ul>
            <p class="text-gray-600">Payouts grouped by currency &mdash; never summed across currencies.</p>
        </section>

    </div>

@endcomponent
