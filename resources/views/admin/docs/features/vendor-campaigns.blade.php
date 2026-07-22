@component('admin.docs._layout', ['title' => 'Vendor Campaigns (Marketer Invitations)', 'icon' => '🤝', 'breadcrumb' => 'Features'])

    <div class="prose prose-sm max-w-none space-y-10">

        {{-- 1. What It Is --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">1. What It Is</h2>
            <p class="text-gray-600">Vendors invite marketers to promote their specific products in exchange for a commission per conversion.</p>
            <p class="text-gray-600">This is distinct from self-serve ads &mdash; it's a performance-based partnership between vendor and marketer.</p>
        </section>

        {{-- 2. How It Works --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">2. How It Works</h2>
            <ol class="list-decimal list-inside text-gray-600 space-y-1">
                <li>Vendor creates campaign offer (partner panel &rarr; Campaigns)</li>
                <li>Vendor invites specific marketers or all eligible marketers</li>
                <li>Marketer receives invitation &rarr; accepts or declines</li>
                <li>Accepted marketer promotes the vendor's products using tracking links/codes</li>
                <li>When a customer converts via the marketer's link &rarr; conversion recorded</li>
                <li>Marketer earns commission; deducted from vendor payout at settlement</li>
            </ol>
        </section>

        {{-- 3. Admin Oversight --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">3. Admin Oversight</h2>
            <p class="text-gray-600"><code>/admin/vendor-campaign-offers</code> &mdash; All vendor campaigns.</p>
            <p class="text-gray-600">Admin can: approve (if review required), reject with reason. Prevent fraudulent or misleading campaign offers.</p>
        </section>

        {{-- 4. Commission Resolution Priority --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">4. Commission Resolution Priority</h2>
            <div class="flex flex-wrap items-center gap-2">
                @foreach (['Per-product override rate', 'Campaign rate', 'Marketer tier rate', 'Platform default'] as $tier)
                    <span class="px-3 py-1.5 rounded-full bg-primary-50 text-primary-700 text-xs font-medium border border-primary-200">{{ $tier }}</span>
                    @if (!$loop->last)
                        <span class="text-gray-400">&rarr;</span>
                    @endif
                @endforeach
            </div>
        </section>

    </div>

@endcomponent
