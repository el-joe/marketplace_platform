@extends('layouts.admin')

@section('title', 'Travel Agency Panel')

@section('content')
    <div class="mb-6">
        <a href="{{ route('admin.docs.index') }}" class="text-sm text-primary-600 hover:underline">&larr; {{ __('admin.nav.documentation') }}</a>
        <div class="flex items-center gap-3 mt-2">
            <span class="text-3xl">✈️</span>
            <h1 class="text-2xl font-bold text-gray-900">Travel Agency Panel</h1>
        </div>
        <p class="text-sm text-gray-500 mt-2">
            URL: <code>travel-agency.{domain}</code> &middot; Guard: <code>travel_agency</code> (single-model — the TravelAgency row itself is the owner)
        </p>
        <p class="text-sm text-gray-500 mt-1">Team members: <code>TravelAgencyMember</code> (separate table, same guard via custom UserProvider)</p>
    </div>

    <div class="space-y-8">

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Dashboard</h2>
            <p class="text-sm text-gray-700">Bookings today, revenue this month, pending inquiries, packages in review</p>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Travel Packages</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/packages</code> — CRUD for travel packages</li>
                <li>Create: destination, dates, price, inclusions, media gallery, itinerary</li>
                <li>Submit for review &rarr; Admin approves &rarr; Package goes live on storefront</li>
                <li>Withdraw: pause a live package</li>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Bookings</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/bookings</code> — All bookings for this agency's packages</li>
                <li>Update status: pending_documents &rarr; confirmed &rarr; completed | cancelled</li>
                <li>Create bookings manually on behalf of customers</li>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Inquiries</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/inquiries</code> — Customer inquiries from the storefront travel pages</li>
                <li>Mark as contacted, convert to booking, close</li>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Marketer Campaigns</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/campaigns</code> — Create and manage campaigns to promote packages via marketer network</li>
                <li>Invite specific marketers to campaigns; revoke invitations</li>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Reports</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/reports/revenue</code> — Revenue by period, grouped by currency</li>
                <li><code>/reports/bookings</code> — Booking counts, status breakdown, filters</li>
                <li><code>/reports/packages</code> — Package-level conversion and revenue stats</li>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Team</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/team</code> — Manage staff members with role-based access</li>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Roles</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/roles</code> — Create custom roles scoped to this agency</li>
                <li>System roles: <code>agency_owner</code> (all permissions), <code>agency_manager</code>, <code>agency_staff</code></li>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Bank Accounts</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/bank-accounts</code> — Feature-flagged (hidden until enabled in .env)</li>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Profile</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/profile</code> — Agency name, logo, contact info, password</li>
            </ul>
        </section>

    </div>
@endsection
