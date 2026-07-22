@component('admin.docs._layout', ['title' => 'Influencer Deals', 'icon' => '🌟', 'breadcrumb' => 'Features'])

    <div class="prose prose-sm max-w-none space-y-10">

        {{-- 1. What It Is --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">1. What It Is</h2>
            <p class="text-gray-600">Flat-fee content deals between the platform (admin) and influencer marketers.</p>
            <p class="text-gray-600">Unlike affiliate campaigns (performance-based), deals pay a fixed amount for specific content deliverables (Instagram post, TikTok video, YouTube review, etc.).</p>
        </section>

        {{-- 2. How It Works --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">2. How It Works</h2>
            <ol class="list-decimal list-inside text-gray-600 space-y-1">
                <li>Admin proposes deal at <code>/admin/influencer-deals/propose</code>: influencer, deliverables list, flat_fee, currency, deadline</li>
                <li>Influencer sees deal in their panel &rarr; accepts or rejects</li>
                <li>Admin approves deal &rarr; payment initiated</li>
                <li>Influencer submits deliverables (content links, files)</li>
                <li>Admin reviews each deliverable &rarr; approve or reject with feedback</li>
                <li>All deliverables approved &rarr; deal marked complete</li>
            </ol>
        </section>

        {{-- 3. Deliverables --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">3. Deliverables</h2>
            <p class="text-gray-600">Each deal has multiple deliverables (e.g. "1 Instagram story + 1 feed post + 1 TikTok"). Influencer submits each one separately.</p>
            <p class="text-gray-600">Admin can reject with feedback &rarr; influencer resubmits.</p>
        </section>

        {{-- 4. Payment --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">4. Payment</h2>
            <p class="text-gray-600">Flat fee paid to influencer's wallet upon deal completion (all deliverables approved).</p>
            <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 mt-2 text-amber-800 text-sm">
                Sourced from platform budget &mdash; <strong>NOT</strong> from vendor payout.
            </div>
        </section>

    </div>

@endcomponent
