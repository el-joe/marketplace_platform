@component('admin.docs._layout', ['title' => 'Travel Packages', 'icon' => '✈️', 'breadcrumb' => 'Features'])

    <div class="prose prose-sm max-w-none space-y-10">

        {{-- Overview --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">1. Overview</h2>
            <p class="text-gray-600">Agencies create travel packages on the travel agency panel. Admin reviews and approves packages. Customers book via <code>{country}.domain/travel</code>.</p>
        </section>

        {{-- Package flow --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">2. Package Flow</h2>
            <ol class="list-decimal list-inside text-gray-600 space-y-1">
                <li>Agency creates &rarr; submits for review</li>
                <li>Admin approves | rejects</li>
                <li>Live: customer can inquire or book</li>
                <li>Agency confirms booking &rarr; travel completed</li>
            </ol>
        </section>

        {{-- Booking statuses --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">3. Booking Statuses</h2>
            <p class="text-gray-600 font-mono text-sm bg-gray-50 border border-gray-200 rounded-lg p-3">
                pending_documents &rarr; confirmed &rarr; completed | cancelled
            </p>
        </section>

        {{-- Admin controls --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">4. Admin Controls</h2>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li><a href="{{ route('admin.travel.agencies.index') }}" class="text-primary-600 hover:underline">admin/travel/agencies</a>: approve, suspend, reactivate travel agencies</li>
                <li><a href="{{ route('admin.travel.packages.index') }}" class="text-primary-600 hover:underline">admin/travel/packages</a>: review queue &mdash; approve, reject, expire; contract download</li>
                <li><a href="{{ route('admin.travel.bookings.index') }}" class="text-primary-600 hover:underline">admin/travel/bookings</a>: all bookings platform-wide</li>
                <li>
                    <a href="{{ route('admin.travel.countries.index') }}" class="text-primary-600 hover:underline">admin/travel/countries</a>
                    + <a href="{{ route('admin.travel.cities.index') }}" class="text-primary-600 hover:underline">/cities</a>
                    + <a href="{{ route('admin.travel.inclusions.index') }}" class="text-primary-600 hover:underline">/inclusions</a>: catalog management for destinations
                </li>
                <li><a href="{{ route('admin.travel.change-requests.index') }}" class="text-primary-600 hover:underline">admin/travel/change-requests</a>: agency change requests for locked profile sections</li>
            </ul>
        </section>

        {{-- Who uses it / rules --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">Who Uses It & Key Rules</h2>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li><strong>Travel agencies</strong> create and manage packages; <strong>admin</strong> is the sole approver of both agencies and packages</li>
                <li>Locked profile sections can only change through the change-request approval flow, never edited directly by the agency</li>
                <li>A booking cannot move to <code>completed</code> without first passing through <code>confirmed</code></li>
            </ul>
        </section>

    </div>

@endcomponent
