@component('admin.docs._layout', ['title' => 'Classifieds Marketplace', 'icon' => '🏘️', 'breadcrumb' => 'Features'])

    <div class="prose prose-sm max-w-none space-y-10">

        {{-- What it is --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">1. What It Is</h2>
            <p class="text-gray-600">A classified listings marketplace (like Property Finder or Dubizzle) embedded in the platform. Customers and vendors can list items &mdash; real estate, vehicles, services, etc. &mdash; for sale or rent.</p>
        </section>

        {{-- How it works --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">2. How It Works</h2>
            <ol class="list-decimal list-inside text-gray-600 space-y-1">
                <li>Customer creates a listing &rarr; signs a digital contract &rarr; listing submitted for review</li>
                <li>Admin reviews &rarr; approve | reject</li>
                <li>Approved listing visible at <code>{country}.domain/classifieds/{listingNumber}</code></li>
            </ol>
        </section>

        {{-- Admin management --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">3. Admin Management</h2>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li><a href="{{ route('admin.classifieds.categories.index') }}" class="text-primary-600 hover:underline">admin/classifieds/categories</a>: classified listing categories (rentals, sales, services)</li>
                <li><a href="{{ route('admin.classifieds.contract-templates.index') }}" class="text-primary-600 hover:underline">admin/classifieds/contract-templates</a>: legal contracts per category</li>
                <li><a href="{{ route('admin.classifieds.listings.index') }}" class="text-primary-600 hover:underline">admin/classifieds/listings</a>: review queue with approve/reject + attachment verification</li>
            </ul>
        </section>

        {{-- Map view --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">4. Map View</h2>
            <p class="text-gray-600">Classifieds have lat/lng; the storefront shows a map view of all active listings. The <code>/classifieds/map-data</code> endpoint serves GeoJSON for the map.</p>
        </section>

        {{-- Inquiries --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">5. Inquiries</h2>
            <p class="text-gray-600">Customers can inquire on a listing without logging in (throttled: 5/min). The seller is notified and responds privately.</p>
        </section>

        {{-- Who uses it / rules --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">Who Uses It & Key Rules</h2>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li><strong>Customers and vendors</strong> both create listings; <strong>admin</strong> is the sole approver</li>
                <li>A listing is never publicly visible until it is admin-approved</li>
                <li>Digital contract signing happens before submission, not after approval</li>
            </ul>
        </section>

    </div>

@endcomponent
