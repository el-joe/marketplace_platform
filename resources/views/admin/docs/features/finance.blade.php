@component('admin.docs._layout', ['title' => 'Finance & Payouts', 'icon' => '💰', 'breadcrumb' => 'Features'])

    <div class="prose prose-sm max-w-none space-y-10">

        {{-- What it is --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">What It Is</h2>
            <p class="text-gray-600">The financial backbone of the platform: vendor/agent/marketer payouts, a double-entry ledger, raw transaction records, and vendor subscription billing.</p>
        </section>

        {{-- Vendor Payouts --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">Vendor Payouts</h2>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li>Eligibility: <code>sub_order.status = 'completed'</code> + COD settlement gate passed</li>
                <li><code>PayoutCalculationService</code> groups by vendor + currency &mdash; <strong>never sums across currencies</strong></li>
                <li>Admin flow: <a href="{{ route('admin.payouts.index') }}" class="text-primary-600 hover:underline">approve &rarr; process</a> (process triggers the bank transfer)</li>
                <li>Admin can also: <strong>hold</strong> (pending investigation) or <strong>recalculate</strong></li>
            </ul>
        </section>

        {{-- COD Gate --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">COD Settlement Gate</h2>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li><code>sub_order.cod_remittance_confirmed</code> must be <code>true</code> before a COD sub-order can be paid out</li>
                <li>Set automatically when an admin marks a COD settlement as settled</li>
            </ul>
            <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 mt-2 text-amber-800 text-sm">
                Vendors see an amber notice in the partner panel while COD funds have not yet been remitted.
            </div>
        </section>

        {{-- Delivery Agent Payouts --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">Delivery Agent Payouts</h2>
            <p class="text-gray-600"><a href="{{ route('admin.delivery.payouts.index') }}" class="text-primary-600 hover:underline">admin/delivery/payouts</a>: <strong>generate &rarr; approve &rarr; process</strong>, based on per-delivery earning rates plus bonuses.</p>
        </section>

        {{-- Marketer Payouts --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">Marketer Payouts</h2>
            <p class="text-gray-600"><a href="{{ route('admin.marketers.payouts.index') }}" class="text-primary-600 hover:underline">admin/marketer-payouts</a>: <strong>generate &rarr; approve &rarr; process</strong>, based on approved <code>marketer_conversions</code> in the period.</p>
        </section>

        {{-- Ledger --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">Ledger</h2>
            <p class="text-gray-600">Double-entry ledger at <a href="{{ route('admin.ledger.index') }}" class="text-primary-600 hover:underline">admin/ledger</a>. Every financial event writes a debit row and a credit row; transaction groups link related ledger rows together.</p>
        </section>

        {{-- Transactions --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">Transactions</h2>
            <p class="text-gray-600">Raw transaction log at <a href="{{ route('admin.transactions.index') }}" class="text-primary-600 hover:underline">admin/transactions</a>, one level above the ledger's debit/credit rows.</p>
        </section>

        {{-- Subscriptions --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">Subscriptions (Vendor Plans)</h2>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li><a href="{{ route('admin.subscriptions.index') }}" class="text-primary-600 hover:underline">admin/subscriptions</a>: view active vendor subscriptions</li>
                <li>Plans: create monthly/annual plans, priced per country</li>
                <li>Invoices: monthly invoices generated per vendor</li>
                <li>Admin can manually subscribe a vendor or cancel their subscription</li>
            </ul>
        </section>

        {{-- Who / rules --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">Who Uses It & Key Rules</h2>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li><strong>Admins</strong> run and approve every payout flow; no payout is auto-processed without an approval step</li>
                <li><strong>Vendors</strong> only see payout status and COD notices, never raw ledger entries</li>
                <li>Currency mixing across a single payout is a hard invariant &mdash; the calculation service enforces per-currency grouping</li>
            </ul>
        </section>

    </div>

@endcomponent
