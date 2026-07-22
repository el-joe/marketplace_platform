@component('admin.docs._layout', ['title' => 'Secret Promotions', 'icon' => '🔒', 'breadcrumb' => 'Features'])

    <div class="prose prose-sm max-w-none space-y-10">

        {{-- 1. What It Is --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">1. What It Is</h2>
            <p class="text-gray-600">Exclusive, limited-availability promotions that are hidden from the general public. Only accessible via a marketer's special tracking link or a specific promo code.</p>
            <p class="text-gray-600">Purpose: reward loyal customers or influencer audiences with prices not shown on the listing.</p>
        </section>

        {{-- 2. Key Security Rule --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">2. Key Security Rule</h2>
            <div class="bg-red-50 border border-red-200 rounded-lg p-3 text-red-800 text-sm">
                <code>admin_share_pct</code> and <code>product_value</code> are <strong>NEVER</strong> exposed in any customer-facing or non-admin API response or Blade view. These are admin-only fields.
            </div>
        </section>

        {{-- 3. How It Works --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">3. How It Works</h2>
            <ol class="list-decimal list-inside text-gray-600 space-y-1">
                <li>Admin creates secret promotion at <code>/admin/marketers-secret-promotions</code>: select vendor + listing, set discounted price, assign marketer(s), set start/end dates, set max redemptions</li>
                <li>Linked to specific marketer(s) &mdash; only their audience can see the price</li>
                <li>Marketer shares their tracking link &rarr; customer lands on listing &rarr; sees secret price</li>
                <li>Purchase tracked back to marketer as conversion</li>
            </ol>
        </section>

        {{-- 4. Statuses --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">4. Statuses</h2>
            <div class="flex flex-wrap items-center gap-2 mb-4">
                @foreach (['draft', 'pending', 'approved', 'active', 'expired'] as $status)
                    <span class="px-3 py-1.5 rounded-full bg-primary-50 text-primary-700 text-xs font-medium border border-primary-200">{{ $status }}</span>
                    @if (!$loop->last)
                        <span class="text-gray-400">&rarr;</span>
                    @endif
                @endforeach
                <span class="text-gray-300">|</span>
                <span class="px-3 py-1.5 rounded-full bg-red-50 text-red-700 text-xs font-medium border border-red-200">rejected</span>
            </div>
            <p class="text-gray-600">Admin can: approve, reject (with reason), expire manually, duplicate.</p>
        </section>

        {{-- 5. Marketer View --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">5. Marketer View</h2>
            <p class="text-gray-600">Marketer sees their secret promotions at <code>/secret-promotions</code>. They can use their tracking link to share the promotion.</p>
            <p class="text-gray-600">They earn commission on each redemption (same as campaign conversions).</p>
        </section>

    </div>

@endcomponent
