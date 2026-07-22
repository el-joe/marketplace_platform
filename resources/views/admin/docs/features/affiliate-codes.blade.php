@component('admin.docs._layout', ['title' => 'Affiliate Promo Codes', 'icon' => '🏷️', 'breadcrumb' => 'Features'])

    <div class="prose prose-sm max-w-none space-y-10">

        {{-- 1. What It Is --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">1. What It Is</h2>
            <p class="text-gray-600">Unique discount codes assigned to affiliate-type marketers. Customer enters code at checkout &rarr; discount applied &rarr; conversion attributed to marketer.</p>
        </section>

        {{-- 2. Difference from Coupons --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">2. Difference from Coupons</h2>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li><strong>Coupons:</strong> created by admin or vendor for general use, may be public</li>
                <li><strong>Affiliate codes:</strong> tied to a specific marketer, tracked for commission</li>
            </ul>
        </section>

        {{-- 3. How It Works --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">3. How It Works</h2>
            <ol class="list-decimal list-inside text-gray-600 space-y-1">
                <li>Affiliate marketer requests a code from their panel (<code>/promo-codes</code>)</li>
                <li>Admin creates/assigns the code at <code>/admin/affiliate-promo-codes</code></li>
                <li>Marketer shares the code publicly (social media, YouTube description, etc.)</li>
                <li>Customer enters code at checkout &rarr; discount applied &rarr; code recorded on order</li>
                <li><code>marketer_conversions</code> row created &rarr; commission earned by marketer</li>
            </ol>
        </section>

        {{-- 4. Admin Controls --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">4. Admin Controls</h2>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li>Toggle code active/inactive without deleting</li>
                <li>Delete deactivated codes</li>
                <li>View all active codes with their marketer linkage</li>
            </ul>
        </section>

    </div>

@endcomponent
