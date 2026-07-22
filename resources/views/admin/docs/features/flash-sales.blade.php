@component('admin.docs._layout', ['title' => 'Flash Sales', 'icon' => '⚡', 'breadcrumb' => 'Features'])

    <div class="prose prose-sm max-w-none space-y-10">

        {{-- 1. What It Is --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">1. What It Is</h2>
            <p class="text-gray-600">Time-limited sale events where vendors submit discounted prices for their listings. Platform controls the event; vendors participate by submitting prices below a threshold.</p>
        </section>

        {{-- 2. Lifecycle --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">2. Lifecycle</h2>
            <div class="flex flex-wrap items-center gap-2 mb-4">
                @foreach (['draft', 'scheduled', 'active'] as $status)
                    <span class="px-3 py-1.5 rounded-full bg-primary-50 text-primary-700 text-xs font-medium border border-primary-200">{{ $status }}</span>
                    @if (!$loop->last)
                        <span class="text-gray-400">&rarr;</span>
                    @endif
                @endforeach
                <span class="text-gray-400">&rarr;</span>
                <span class="px-3 py-1.5 rounded-full bg-green-50 text-green-700 text-xs font-medium border border-green-200">ended</span>
                <span class="text-gray-300">|</span>
                <span class="px-3 py-1.5 rounded-full bg-red-50 text-red-700 text-xs font-medium border border-red-200">cancelled</span>
            </div>
            <p class="text-gray-600">Admin: create event &rarr; set start/end times &rarr; invite vendors &rarr; review submissions &rarr; publish.</p>
        </section>

        {{-- 3. Vendor Participation Flow --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">3. Vendor Participation Flow</h2>
            <ol class="list-decimal list-inside text-gray-600 space-y-1">
                <li>Vendor receives invitation (<code>flash_sale_vendor_invititions</code> &mdash; note double-ti intentional)</li>
                <li>Vendor views event in partner panel (<code>/flash-sales</code>)</li>
                <li>Vendor submits listing price for the event (must be below original price)</li>
                <li>Admin reviews submission &rarr; approve or reject</li>
                <li>At event start: approved listings shown at discounted price</li>
                <li>At event end: prices revert automatically</li>
            </ol>
        </section>

        {{-- 4. Admin Tools --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">4. Admin Tools</h2>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li><strong>Submission stats:</strong> how many submitted vs total invited</li>
                <li><strong>Live monitor:</strong> real-time sales data during active event (<code>/live-data</code>)</li>
                <li><strong>Analytics:</strong> post-event performance (<code>/analytics-data</code>)</li>
                <li><strong>Bulk review:</strong> approve/reject multiple submissions at once</li>
                <li><strong>Price history:</strong> track price changes per listing across events</li>
            </ul>
        </section>

    </div>

@endcomponent
