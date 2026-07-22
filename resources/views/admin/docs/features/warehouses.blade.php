@component('admin.docs._layout', ['title' => 'Warehouses & FBN', 'icon' => '🏭', 'breadcrumb' => 'Features'])

    <div class="prose prose-sm max-w-none space-y-10">

        {{-- 1. Warehouse Types --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">1. Warehouse Types</h2>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li><code>platform_fbn</code>: Platform fulfillment center</li>
                <li><code>vendor_owned</code>: Vendor's registered warehouse (FBM/FBP)</li>
                <li><code>cross_dock</code>: Transit hub</li>
            </ul>
        </section>

        {{-- 2. Inventory Columns --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">2. Inventory Columns</h2>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li><code>quantity_on_hand</code>: physical stock (writable)</li>
                <li><code>quantity_reserved</code>: in carts / pending orders (writable)</li>
                <li><code>quantity_available</code>: VIRTUAL = <code>on_hand - reserved</code></li>
            </ul>
            <div class="bg-red-50 border border-red-200 rounded-lg p-3 mt-2 text-red-800 text-sm font-medium">
                NEVER WRITE TO <code>quantity_available</code>.
            </div>
        </section>

        {{-- 3. inventory_movements --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">3. inventory_movements &mdash; APPEND ONLY</h2>
            <p class="text-gray-600">Every stock change creates a new row. No updates or deletes.</p>
            <p class="text-gray-600">Types: <code>inbound</code> | <code>outbound</code> | <code>adjustment</code> | <code>transfer</code> | <code>return</code></p>
            <p class="text-gray-600"><code>received_at</code> on inbound movement: starts the free storage clock.</p>
        </section>

        {{-- 4. FBN Inbound Request Flow --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">4. FBN Inbound Request Flow</h2>
            <ol class="list-decimal list-inside text-gray-600 space-y-1">
                <li>Vendor submits &rarr; status: <code>draft</code> &rarr; <code>submitted</code></li>
                <li>Admin approves &rarr; <code>approved</code></li>
                <li>Vendor ships &rarr; adds tracking number</li>
                <li>Warehouse receives &rarr; status: <code>received</code></li>
                <li><code>inventory_movement</code>: type=<code>inbound</code> created, <code>received_at</code>=NOW</li>
                <li><code>quantity_on_hand</code> incremented</li>
                <li>Free storage period begins</li>
                <li>Listing becomes orderable when <code>quantity_available &gt; 0</code></li>
            </ol>
        </section>

        {{-- 5. Free Storage Period --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">5. Free Storage Period</h2>
            <p class="text-gray-600"><code>warehouses.free_storage_days</code> (default: 30)</p>
            <p class="text-gray-600 font-mono text-sm bg-gray-50 border border-gray-200 rounded-lg p-3">
                free_period_ends_at = received_at + free_storage_days
            </p>
        </section>

        {{-- 6. Daily Overage Fees --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">6. Daily Overage Fees</h2>
            <p class="text-gray-600">After <code>free_period_ends_at</code>:</p>
            <p class="text-gray-600 font-mono text-sm bg-gray-50 border border-gray-200 rounded-lg p-3">
                daily_fee = quantity_on_hand &times; daily_fee_per_unit
            </p>
            <p class="text-gray-600">Job runs at 01:00 daily &rarr; inserts <code>fbn_daily_overage_fees</code> row (idempotent)</p>
            <p class="text-gray-600">Monthly: <code>GenerateFbnStorageFeesJob</code> aggregates &rarr; creates invoice &rarr; deducted from payout</p>
        </section>

        {{-- 7. Inventory Transfers --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">7. Inventory Transfers</h2>
            <p class="text-gray-600">Move stock between warehouses.</p>
            <div class="flex flex-wrap items-center gap-2">
                @foreach (['pending', 'in_transit', 'received'] as $status)
                    <span class="px-3 py-1.5 rounded-full bg-primary-50 text-primary-700 text-xs font-medium border border-primary-200">{{ $status }}</span>
                    @if (!$loop->last)
                        <span class="text-gray-400">&rarr;</span>
                    @endif
                @endforeach
                <span class="text-gray-300 mx-1">|</span>
                <span class="px-3 py-1.5 rounded-full bg-red-50 text-red-700 text-xs font-medium border border-red-200">cancelled</span>
            </div>
        </section>

    </div>

@endcomponent
