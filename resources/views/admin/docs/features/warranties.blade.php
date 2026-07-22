@component('admin.docs._layout', ['title' => 'Warranties', 'icon' => '🛡️', 'breadcrumb' => 'Features'])

    <div class="prose prose-sm max-w-none space-y-10">

        {{-- What it is --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">What It Is</h2>
            <p class="text-gray-600">Two separate warranty systems: the vendor's standard warranty bundled with a product, and platform-sold extended warranty plans purchased at checkout.</p>
        </section>

        {{-- Standard warranty --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">1. Standard Warranty</h2>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li>Provided by the vendor as part of the product listing; duration is <code>warranty_months</code> on the product</li>
                <li>Claims are filed by the <strong>customer</strong> &mdash; never by the vendor or admin</li>
                <li>Vendors <strong>cannot</strong> see claims on admin-listing items (403 guard)</li>
            </ul>
        </section>

        {{-- Extended warranty plans --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">2. Extended Warranty Plans</h2>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li>Admin creates plans at <a href="{{ route('admin.warranty-plans.index') }}" class="text-primary-600 hover:underline">admin/warranty-plans</a>, scoped to parent product categories</li>
                <li>Displayed at listing detail, purchasable at checkout</li>
                <li>Payment goes to the <strong>platform</strong>, not the vendor</li>
                <li>After delivery: <code>warranty_purchases.status = 'active'</code></li>
            </ul>
        </section>

        {{-- Claim flow --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">3. Warranty Claim Flow</h2>
            <p class="text-gray-600 font-mono text-sm bg-gray-50 border border-gray-200 rounded-lg p-3">
                submitted &rarr; under_review &rarr; approved | rejected &rarr; resolved
            </p>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li>Resolution types: <strong>repair</strong>, <strong>replace</strong>, <strong>refund</strong></li>
                <li>Message thread between customer, vendor, and admin</li>
            </ul>
        </section>

        {{-- Admin view --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">4. Admin View</h2>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li><a href="{{ route('admin.warranty-claims.index') }}" class="text-primary-600 hover:underline">admin/warranty-claims</a>: all claims, filterable by status/vendor</li>
                <li><a href="{{ route('admin.warranty-purchases.index') }}" class="text-primary-600 hover:underline">admin/warranty-purchases</a>: all extended warranty sales (revenue report)</li>
            </ul>
        </section>

        {{-- Who uses it / rules --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">Who Uses It & Key Rules</h2>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li><strong>Customers</strong> file claims and purchase extended plans</li>
                <li><strong>Vendors</strong> only see claims for their own listings, never admin-managed listings</li>
                <li><strong>Admin</strong> arbitrates claims, manages plans, and tracks extended-warranty revenue</li>
            </ul>
        </section>

    </div>

@endcomponent
