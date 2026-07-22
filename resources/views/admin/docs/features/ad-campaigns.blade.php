@component('admin.docs._layout', ['title' => 'Ad Campaigns (Self-Serve)', 'icon' => '📊', 'breadcrumb' => 'Features'])

    <div class="prose prose-sm max-w-none space-y-10">

        {{-- 1. What It Is --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">1. What It Is</h2>
            <p class="text-gray-600">Vendors create their own advertising campaigns to promote their listings within the platform's native ad network. Admin reviews and approves each campaign.</p>
        </section>

        {{-- 2. How It Works --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">2. How It Works</h2>
            <ol class="list-decimal list-inside text-gray-600 space-y-1">
                <li>Vendor creates campaign (from partner panel) with: listing, budget, bid, dates</li>
                <li>Campaign submitted &rarr; status: <code>pending</code></li>
                <li>Admin reviews at <code>/admin/ad-campaigns</code> &rarr; approve or reject</li>
                <li>Live: ad shown in ad slots across storefront</li>
                <li>Admin can pause/resume live campaigns</li>
                <li>Budget exhausted &rarr; campaign auto-pauses</li>
            </ol>
        </section>

        {{-- 3. Ad Slots --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">3. Ad Slots</h2>
            <p class="text-gray-600"><code>/admin/ad-slots</code> &mdash; Named positions where ads can appear. Each slot: name, placement context, dimensions, max concurrent bookings.</p>
            <p class="text-gray-600">Slots link to Paid Ad Bookings for reserved (non-auction) placements.</p>
        </section>

        {{-- 4. Paid Ad Bookings --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">4. Paid Ad Bookings</h2>
            <p class="text-gray-600">Vendors can reserve specific ad slots for specific date ranges at a fixed price.</p>
            <p class="text-gray-600"><code>/admin/paid-ad-bookings</code> &mdash; Review queue. Admin reviews creative (image/video) before approving.</p>
            <p class="text-gray-600">Status: <code>pending</code> &rarr; <code>approved</code> | <code>rejected</code></p>
        </section>

        {{-- 5. Fraud Alerts --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">5. Fraud Alerts</h2>
            <p class="text-gray-600"><code>/admin/ad-campaigns/fraud</code> &mdash; Suspicious click patterns detected by the system. Admin can block fraud patterns to protect advertiser budgets.</p>
        </section>

    </div>

@endcomponent
