@component('admin.docs._layout', ['title' => 'Shipping Subsidy', 'icon' => '🎯', 'breadcrumb' => 'Features'])

    <div class="prose prose-sm max-w-none space-y-10">

        {{-- What it is --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">What It Is</h2>
            <p class="text-gray-600">A cost-sharing mechanism that lets vendors offer free delivery in far-flung "exceptional" shipping zones, with the platform absorbing part of the cost. See the <a href="{{ route('admin.docs.features.shipping') }}" class="text-primary-600 hover:underline">Shipping &amp; Zones</a> doc for the underlying zone/rate mechanism.</p>
        </section>

        {{-- How it works --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">How It Works</h2>
            <ol class="list-decimal list-inside text-gray-600 space-y-1">
                <li>Admin defines exceptional zones at <a href="{{ route('admin.shipping-zones.index') }}" class="text-primary-600 hover:underline">admin/shipping-zones</a></li>
                <li>Admin sets subsidy rules at <a href="{{ route('admin.shipping-subsidies.index') }}" class="text-primary-600 hover:underline">admin/shipping-subsidies</a>: <code>vendor_share</code> + <code>admin_support</code> per zone + country</li>
                <li>Vendor opts in at <code>partner.domain/exceptional-zones</code></li>
                <li>At checkout, the customer sees free delivery</li>
                <li><code>vendor_share</code> is deducted from the vendor's payout; <code>admin_support</code> is shown as a cost in the platform P&amp;L</li>
            </ol>
        </section>

        {{-- Who uses it --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">Who Uses It</h2>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li><strong>Admin</strong> defines zones and sets the vendor/platform cost split</li>
                <li><strong>Vendor</strong> opts in per zone from the partner panel &mdash; opt-in is voluntary, never forced</li>
                <li><strong>Customer</strong> only ever sees "free delivery," never the underlying split</li>
            </ul>
        </section>

        {{-- Key rules --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">Key Rules & Invariants</h2>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li>Subsidy rules are always scoped to a zone + country pair &mdash; there is no global default split</li>
                <li>A vendor not opted in to a zone pays the normal shipping fee for orders shipping there (no subsidy applied)</li>
                <li><code>admin_support</code> is a real platform cost and reflected in financial reports, not just a display trick</li>
            </ul>
        </section>

    </div>

@endcomponent
