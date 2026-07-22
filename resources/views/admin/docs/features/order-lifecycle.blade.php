@component('admin.docs._layout', ['title' => 'Order Lifecycle', 'icon' => '📦', 'breadcrumb' => 'Features'])

    <div class="prose prose-sm max-w-none space-y-10">

        {{-- 1. Order Structure --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">1. Order Structure</h2>
            <p class="text-gray-600">One customer checkout creates:</p>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li><strong>1 order row</strong> (master)</li>
                <li><strong>1 sub_order</strong> per vendor in the cart</li>
                <li><strong>N order_items</strong> (one per listing &times; quantity)</li>
            </ul> 
            <p class="text-gray-600">All financial settlement happens at <strong>sub_order</strong> level.</p>
        </section>

        {{-- 2. Status Flow --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">2. Status Flow</h2>

            <p class="text-gray-700 font-medium mb-2">Master Order</p>
            <div class="flex flex-wrap items-center gap-2 mb-6">
                @foreach (['pending', 'confirmed', 'partially_shipped', 'shipped', 'delivered', 'completed'] as $status)
                    <span class="px-3 py-1.5 rounded-full bg-primary-50 text-primary-700 text-xs font-medium border border-primary-200">{{ $status }}</span>
                    @if (!$loop->last)
                        <span class="text-gray-400">&rarr;</span>
                    @endif
                @endforeach
            </div>
            <div class="flex flex-wrap items-center gap-2 mb-6">
                <span class="text-xs text-gray-400 mr-1">branches to:</span>
                @foreach (['cancelled', 'partially_refunded', 'refunded'] as $status)
                    <span class="px-3 py-1.5 rounded-full bg-red-50 text-red-700 text-xs font-medium border border-red-200">{{ $status }}</span>
                    @if (!$loop->last)
                        <span class="text-gray-300">|</span>
                    @endif
                @endforeach
            </div>

            <p class="text-gray-700 font-medium mb-2">Sub Order</p>
            <div class="flex flex-wrap items-center gap-2 mb-4">
                @foreach (['pending', 'processing', 'ready_for_pickup', 'shipped', 'out_for_delivery', 'delivered', 'completed'] as $status)
                    <span class="px-3 py-1.5 rounded-full bg-green-50 text-green-700 text-xs font-medium border border-green-200">{{ $status }}</span>
                    @if (!$loop->last)
                        <span class="text-gray-400">&rarr;</span>
                    @endif
                @endforeach
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs text-gray-400 mr-1">branches to:</span>
                @foreach (['cancelled', 'return_requested', 'returned', 'refunded'] as $status)
                    <span class="px-3 py-1.5 rounded-full bg-red-50 text-red-700 text-xs font-medium border border-red-200">{{ $status }}</span>
                    @if (!$loop->last)
                        <span class="text-gray-300">|</span>
                    @endif
                @endforeach
            </div>
        </section>

        {{-- 3. Cart Phase --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">3. Cart Phase</h2>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li>Inventory soft-locked via <code>cart_inventory_locks</code> (TTL 15 minutes)</li>
                <li>Guest carts use <code>X-Cart-Token</code> header; merged on login</li>
                <li>Lock prevents oversell during concurrent checkout sessions</li>
            </ul>
        </section>

        {{-- 4. Checkout Calculation --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">4. Checkout Calculation</h2>
            <ol class="list-decimal list-inside text-gray-600 space-y-1">
                <li>Subtotal per currency (never sum across currencies)</li>
                <li>Platform commission = <code>FLOOR(price &times; category_rate / 100)</code> &mdash; deducted from vendor</li>
                <li>Shipping fee via <code>ShippingCalculationService</code></li>
                <li>Exceptional zone subsidy applied if eligible</li>
                <li>Coupon / discount applied (max 2: 1 platform + 1 vendor)</li>
                <li>COD fee added if <code>payment_method = 'cod'</code></li>
                <li>Tax / VAT per country rules</li>
                <li>Warranty plan cost if selected</li>
                <li>Gateway fee = <code>ROUND(total &times; gateway_rate)</code> &mdash; split across sub_orders</li>
            </ol>
        </section>

        {{-- 5. Payment --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">5. Payment</h2>
            <p class="text-gray-600">Supported methods: <code>card</code>, <code>wallet</code>, <code>COD</code>, <code>BNPL</code> (Tabby/Tamara), <code>gift_card</code></p>
            <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 mt-2 text-amber-800 text-sm">
                <strong>Thawani note:</strong> amounts sent in baisa (OMR subunit) &mdash; ONLY inside <code>ThawaniGateway</code> class.
            </div>
        </section>

        {{-- 6. Fulfillment Flows --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">6. Fulfillment Flows (FBM vs FBN)</h2>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li><strong>FBM:</strong> Vendor prepares &rarr; Agent picks up from vendor &rarr; Delivers to customer</li>
                <li><strong>FBN:</strong> Platform warehouse picks/packs &rarr; Agent picks up &rarr; Delivers</li>
            </ul>
        </section>

        {{-- 7. Delivery OTP --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">7. Delivery OTP</h2>
            <p class="text-gray-600">4-digit code shown to the <strong>AGENT</strong> on their screen. Customer reads it aloud &mdash; agent visually matches. Agent does <strong>NOT</strong> type it &mdash; it is display-only verification.</p>
        </section>

        {{-- 8. Auto-Completion --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">8. Auto-Completion</h2>
            <p class="text-gray-600">Daily job: if <code>sub_order.status = 'delivered'</code> and <code>now &gt; delivered_at + return_window_days</code>:</p>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li>&rarr; <code>status = 'completed'</code></li>
                <li>&rarr; vendor_payout unlocked</li>
                <li>&rarr; marketer conversion approved</li>
            </ul>
        </section>

        {{-- 9. COD Gate --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">9. COD Gate</h2>
            <p class="text-gray-600">Vendor payout for COD sub_orders is <strong>BLOCKED</strong> until delivery agent has remitted cash. <code>cod_remittance_confirmed</code> must be <code>true</code> before vendor receives their payout.</p>
        </section>

        {{-- 10. Cancellation --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">10. Cancellation</h2>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li><strong>Customer:</strong> before <code>'shipped'</code> status only</li>
                <li><strong>Vendor:</strong> can cancel own sub_order (penalty on score)</li>
                <li><strong>Admin:</strong> override cancel at any stage</li>
            </ul>
            <p class="text-gray-600">On cancel: inventory released, refund initiated, marketer conversion reversed.</p>
        </section>

        {{-- 11. Return Flow --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">11. Return Flow</h2>
            <p class="text-gray-600">Customer submits &rarr; Admin/vendor reviews &rarr; Agent pickup &rarr; Warehouse inspection</p>
            <p class="text-gray-700 font-medium mt-2">Inspection outcomes:</p>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li><strong>Good:</strong> full refund</li>
                <li><strong>Damaged by customer:</strong> partial/no refund</li>
                <li><strong>Damaged in transit:</strong> full refund, carrier investigated</li>
            </ul>
        </section>

        {{-- 12. Dispute --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">12. Dispute</h2>
            <p class="text-gray-600">Customer opens &rarr; Admin assigns &rarr; Vendor responds &rarr; Admin mediates &rarr; Resolution</p>
            <p class="text-gray-700 font-medium mt-2">Resolution types:</p>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li><code>full_refund</code></li>
                <li><code>partial_refund</code></li>
                <li><code>no_refund</code></li>
                <li><code>platform_absorbs_loss</code></li>
            </ul>
        </section>

        {{-- 13. Warranty Claim --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">13. Warranty Claim</h2>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li><strong>Standard:</strong> vendor covers, filed within <code>warranty_expires_at</code></li>
                <li><strong>Extended (plan):</strong> platform covers, customer purchased plan at checkout</li>
            </ul>
            <p class="text-gray-600">Vendor panel is hard-scoped: cannot see admin-listing warranty claims (403).</p>
        </section>

    </div>

@endcomponent
