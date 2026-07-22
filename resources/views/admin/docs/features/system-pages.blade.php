@component('admin.docs._layout', ['title' => 'System Pages', 'icon' => '⚙️', 'breadcrumb' => 'Features'])

    <div class="prose prose-sm max-w-none space-y-10">

        {{-- What it is --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">What It Is</h2>
            <p class="text-gray-600">Everything in the admin sidebar's <strong>System</strong> group: platform-wide geography, currencies, document requirements, payments infrastructure, warehouse operations, and the audit trail.</p>
        </section>

        {{-- Countries --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">Countries</h2>
            <p class="text-gray-600"><a href="{{ route('admin.countries.index') }}" class="text-primary-600 hover:underline">admin/countries</a></p>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li>Launching a country enables it on the platform for customers and vendors</li>
                <li>Per-country: payment methods, shipping settings, category overrides, VAT rate</li>
                <li>Deactivating a country hides it from the storefront; existing orders are unaffected</li>
            </ul>
        </section>

        {{-- Cities --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">Cities</h2>
            <p class="text-gray-600"><a href="{{ route('admin.cities.index') }}" class="text-primary-600 hover:underline">admin/cities</a>: manage cities within each country. Bulk import via CSV. Cities are assigned to shipping zones for rate calculation.</p>
        </section>

        {{-- Currencies --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">Currencies</h2>
            <p class="text-gray-600"><a href="{{ route('admin.currencies.index') }}" class="text-primary-600 hover:underline">admin/currencies</a>: exchange rates for all supported currencies (SAR, AED, OMR, KWD, QAR, BHD, EGP, JOD).</p>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li>Manual rate override or auto-refresh from an exchange rate API</li>
            </ul>
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 mt-2 text-gray-700 text-sm">
                All monetary values are stored as BIGINT base units &mdash; the displayed rate is for display conversion only.
            </div>
        </section>

        {{-- Shipping Zones / Methods --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">Shipping Zones & Shipping Methods</h2>
            <p class="text-gray-600">See the <a href="{{ route('admin.docs.features.shipping') }}" class="text-primary-600 hover:underline">Shipping</a> documentation.</p>
        </section>

        {{-- Document Types --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">Document Types</h2>
            <p class="text-gray-600"><a href="{{ route('admin.vendor-document-types.index') }}" class="text-primary-600 hover:underline">admin/vendor-document-types</a>: configures which documents vendors must upload during onboarding, per country, with a required/optional toggle per document type.</p>
        </section>

        {{-- Payment Methods --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">Payment Methods</h2>
            <p class="text-gray-600"><a href="{{ route('admin.payment-methods.index') }}" class="text-primary-600 hover:underline">admin/payment-methods</a>: enable/disable payment methods per country. Sort order affects checkout display.</p>
        </section>

        {{-- Payment Gateways --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">Payment Gateways</h2>
            <p class="text-gray-600"><a href="{{ route('admin.payment-gateways.index') }}" class="text-primary-600 hover:underline">admin/payment-gateways</a>: configure gateway credentials (Thawani, Stripe, etc.) using the Strategy Pattern.</p>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li>Test connection before going live</li>
                <li>Webhook logs per gateway for debugging</li>
            </ul>
        </section>

        {{-- Weight Slabs --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">Weight Slabs</h2>
            <p class="text-gray-600">Shipping surcharge tiers by weight &mdash; see the <a href="{{ route('admin.docs.features.shipping') }}" class="text-primary-600 hover:underline">Shipping</a> docs.</p>
        </section>

        {{-- Shipping Subsidies --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">Shipping Subsidies</h2>
            <p class="text-gray-600">Exceptional zone cost split rules &mdash; see the <a href="{{ route('admin.docs.features.subsidy') }}" class="text-primary-600 hover:underline">Subsidy</a> docs.</p>
        </section>

        {{-- Warehouses --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">Warehouses</h2>
            <p class="text-gray-600">See the <a href="{{ route('admin.docs.features.warehouses') }}" class="text-primary-600 hover:underline">Warehouses</a> documentation.</p>
        </section>

        {{-- Inventory Transfers --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">Inventory Transfers</h2>
            <p class="text-gray-600"><a href="{{ route('admin.warehouses.transfers.index') }}" class="text-primary-600 hover:underline">admin/warehouses/transfers</a>: move stock between warehouses.</p>
            <p class="text-gray-600 font-mono text-sm bg-gray-50 border border-gray-200 rounded-lg p-3">pending &rarr; in_transit &rarr; received | cancelled</p>
        </section>

        {{-- Shipping Surcharges --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">Shipping Surcharges</h2>
            <p class="text-gray-600"><a href="{{ route('admin.warehouses.shipping-surcharges.index') }}" class="text-primary-600 hover:underline">admin/warehouses/shipping-surcharges</a>: extra fees on FBN outbound shipments.</p>
        </section>

        {{-- Activity Log --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">Activity Log</h2>
            <p class="text-gray-600"><a href="{{ route('admin.activity-log.index') }}" class="text-primary-600 hover:underline">admin/activity-log</a>: immutable audit trail of all admin actions.</p>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li>Filter by: causer (which admin), event type, date range</li>
                <li>Shows: what changed, old value, new value, timestamp</li>
            </ul>
        </section>

        {{-- Who uses it / rules --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">Who Uses It & Key Rules</h2>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li><strong>Admin only</strong> &mdash; none of these pages are exposed to vendors, customers, or other panels</li>
                <li>Deactivating a country or currency never retroactively changes historical orders</li>
                <li>The activity log is immutable &mdash; entries cannot be edited or deleted, only read and filtered</li>
            </ul>
        </section>

    </div>

@endcomponent
