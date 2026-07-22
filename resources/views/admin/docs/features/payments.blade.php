@component('admin.docs._layout', ['title' => 'Payments & Wallets', 'icon' => '💳', 'breadcrumb' => 'Features'])

    <div class="prose prose-sm max-w-none space-y-10">

        {{-- 1. Payment Methods --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">1. Payment Methods</h2>
            <p class="text-gray-600"><code>card</code>, <code>wallet</code>, <code>cod</code>, <code>bnpl</code> (Tabby/Tamara), <code>gift_card</code></p>
            <p class="text-gray-600">Per-country availability configured in <code>country_payment_methods</code></p>
        </section>

        {{-- 2. Payment Gateways --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">2. Payment Gateways</h2>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li>Configured via <code>/admin/payment-gateways</code> with credential Strategy Pattern</li>
                <li>Test connection available per gateway</li>
                <li>Webhook logs viewable per method</li>
            </ul>
        </section>

        {{-- 3. Customer Wallet --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">3. Customer Wallet</h2>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li>One wallet per currency (polymorphic &mdash; <code>owner_type='customer'</code>)</li>
                <li><code>wallet_transactions</code>: append-only ledger with <code>balance_after</code> for audit trail</li>
                <li>Wallet can be frozen by admin (suspicion/fraud)</li>
                <li>Withdrawal requests &rarr; admin approves &rarr; bank transfer processed</li>
            </ul>
        </section>

        {{-- 4. COD Flow --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">4. COD Flow</h2>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li>Agent collects cash at delivery</li>
                <li><code>delivery_agent_cod_settlements</code>: tracks what each agent owes</li>
                <li>Admin generates settlement &rarr; marks settled</li>
            </ul>
            <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 mt-2 text-amber-800 text-sm">
                Only then: <code>sub_order.cod_remittance_confirmed = true</code> &rarr; vendor payout unlocked
            </div>
        </section>

        {{-- 5. Refunds --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">5. Refunds</h2>
            <p class="text-gray-600 font-mono text-sm bg-gray-50 border border-gray-200 rounded-lg p-3">
                refunds.net_refund = amount - gateway_fee_deducted - tax_deducted
            </p>
            <p class="text-gray-500 text-xs">(VIRTUAL GENERATED &mdash; never write)</p>
            <ul class="list-disc list-inside text-gray-600 space-y-1 mt-2">
                <li><strong>Card:</strong> reversed to card (5&ndash;10 business days)</li>
                <li><strong>Wallet:</strong> instant</li>
                <li><strong>COD:</strong> added to customer wallet (no cash back)</li>
            </ul>
        </section>

        {{-- 6. Gift Cards --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">6. Gift Cards</h2>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li>Admin creates with value + currency + expiry</li>
                <li>Customer redeems at checkout (full or partial use)</li>
                <li>Admin can cancel, extend expiry, adjust balance</li>
            </ul>
        </section>

        {{-- 7. Money Rules --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">7. Money Rules (Critical)</h2>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li>All values: BIGINT base currency (SAR, AED, OMR, KWD, QAR, BHD, EGP, JOD)</li>
                <li>NO <code>/100</code> or <code>&times;100</code> anywhere (except Thawani baisa conversion, only inside <code>ThawaniGateway</code>)</li>
                <li>NEVER sum across currencies &mdash; always <code>GROUP BY</code> currency first</li>
                <li><code>FLOOR()</code> for percentage calcs, <code>ROUND()</code> for flat rates</li>
            </ul>
        </section>

    </div>

@endcomponent
