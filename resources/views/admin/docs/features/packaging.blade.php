@component('admin.docs._layout', ['title' => 'Packaging Supplies', 'icon' => '📦', 'breadcrumb' => 'Features'])

    <div class="prose prose-sm max-w-none space-y-10">

        {{-- What it is --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">What It Is</h2>
            <p class="text-gray-600">The platform sells its own branded packaging materials to vendors &mdash; boxes, bags, tape, labels, etc. Vendors order supplies from the platform and bear the delivery fee themselves; it is never subsidized.</p>
        </section>

        {{-- How it works: Admin --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">Admin Side</h2>
            <p class="text-gray-600"><a href="{{ route('admin.packaging.catalog') }}" class="text-primary-600 hover:underline">admin/packaging</a></p>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li><strong>Catalog:</strong> add/edit/toggle supply items (name, type, size, unit price, stock)</li>
                <li><strong>Orders:</strong> review pending vendor packaging orders &rarr; approve/reject/ship/deliver</li>
            </ul>
        </section>

        {{-- How it works: Vendor --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">Vendor Side</h2>
            <p class="text-gray-600"><code>partner.domain/packaging</code></p>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li>Catalog: browse available items with the delivery fee shown for their country</li>
                <li>Alpine.js cart: add items with quantities, see running total + delivery fee + grand total</li>
                <li>Place order &rarr; platform notified &rarr; status: <code>pending &rarr; approved &rarr; shipped &rarr; delivered</code></li>
            </ul>
        </section>

        {{-- Delivery fee --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">Delivery Fee</h2>
            <p class="text-gray-600">Comes from settings key <code>'packaging_delivery_fee_{country_code}'</code> (BIGINT, base currency). Set per country in <a href="{{ route('admin.content-settings.index') }}" class="text-primary-600 hover:underline">admin/content-settings</a>.</p>
        </section>

        {{-- Stock snapshot --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">Stock Snapshot</h2>
            <p class="text-gray-600"><code>unit_cost</code> is snapshotted at order time and stored on <code>request_items.unit_cost</code>. Price changes made after an order is placed do not affect that order.</p>
        </section>

        {{-- Who uses it / rules --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">Who Uses It & Key Rules</h2>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li><strong>Admin</strong> owns the catalog and fulfills orders</li>
                <li><strong>Vendors</strong> browse, order, and pay the delivery fee &mdash; the fee is never waived or subsidized</li>
                <li>Historical orders are immune to later price changes because of the unit-cost snapshot</li>
            </ul>
        </section>

    </div>

@endcomponent
