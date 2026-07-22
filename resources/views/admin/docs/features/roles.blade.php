@component('admin.docs._layout', ['title' => 'Roles & Permissions', 'icon' => '🛡️', 'breadcrumb' => 'Features'])

    <div class="prose prose-sm max-w-none space-y-10">

        {{-- What it is --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">What It Is</h2>
            <p class="text-gray-600">Role-based access control built on Spatie Laravel Permission, applied independently per guard so admin, vendor, travel agency, marketer, delivery agent, and shipping company supervisor accounts each get their own role/permission universe.</p>
        </section>

        {{-- Architecture --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">1. Architecture</h2>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li>Guards: <code>admin</code>, <code>vendor</code>, <code>travel_agency</code>, <code>marketer</code>, <code>delivery_agent</code>, <code>shipping_company_supervisor</code></li>
                <li>Each guard has its own roles and permissions &mdash; <code>guard_name</code> must match</li>
                <li><code>model_has_roles</code> uses <code>model_uuid</code> (char 36), not the package default <code>model_id</code></li>
            </ul>
        </section>

        {{-- Admin roles --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">2. Admin Roles</h2>
            <p class="text-gray-600">Managed at <a href="{{ route('admin.roles.index') }}" class="text-primary-600 hover:underline">admin/roles</a>.</p>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li>System roles cannot be deleted (<code>super_admin</code>, <code>operations_admin</code>, etc.)</li>
                <li>Custom roles are created by an admin with any combination of permissions</li>
                <li>Permission format: <code>resource.action</code> (e.g. <code>orders.view</code>, <code>vendors.approve</code>)</li>
            </ul>
        </section>

        {{-- Vendor roles --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">3. Vendor Roles</h2>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li>Scoped per vendor using the prefix <code>"{vendor_id}::role-name"</code></li>
                <li>System roles: <code>vendor_owner</code>, <code>vendor_manager</code>, <code>vendor_staff</code></li>
                <li>Custom roles are created from the partner panel (<code>/roles</code>)</li>
                <li>Applied via the <code>vendor.can</code> middleware on partner panel routes</li>
            </ul>
        </section>

        {{-- Travel agency roles --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">4. Travel Agency Roles</h2>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li>Scoped per agency using the prefix <code>"{travel_agency_id}::role-name"</code></li>
                <li>System roles: <code>agency_owner</code>, <code>agency_manager</code>, <code>agency_staff</code></li>
                <li>The Owner (<code>TravelAgency</code> model) bypasses all permission checks</li>
                <li>Members (<code>TravelAgencyMember</code>) are checked via Spatie</li>
            </ul>
        </section>

        {{-- Restriction permission --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">5. Admin Permission: <code>vendors.assigned_only</code></h2>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li>A restriction permission &mdash; limits an admin to only see their assigned vendor(s)</li>
                <li>Assigned manually to specific admin accounts</li>
                <li>Hides financial data (bank accounts, total sales, payout) from a restricted view</li>
            </ul>
            <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 mt-2 text-amber-800 text-sm">
                Never granted to <code>super_admin</code> &mdash; it is a restriction, not a privilege.
            </div>
        </section>

        {{-- Who uses it / rules --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">Who Uses It & Key Rules</h2>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li>Every panel (admin, partner, travel agency, marketer, delivery, carrier) enforces its own guard-scoped roles</li>
                <li>System roles are protected from deletion across all guards, only custom roles can be removed</li>
                <li>Vendor- and agency-scoped role names must never collide across tenants &mdash; the prefix guarantees isolation</li>
            </ul>
        </section>

    </div>

@endcomponent
