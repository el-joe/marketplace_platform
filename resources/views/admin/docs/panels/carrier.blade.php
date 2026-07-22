@extends('layouts.admin')

@section('title', 'Carrier Panel')

@section('content')
    <div class="mb-6">
        <a href="{{ route('admin.docs.index') }}" class="text-sm text-primary-600 hover:underline">&larr; {{ __('admin.nav.documentation') }}</a>
        <div class="flex items-center gap-3 mt-2">
            <span class="text-3xl">🚚</span>
            <h1 class="text-2xl font-bold text-gray-900">Carrier (Shipping Supervisor) Panel</h1>
        </div>
        <p class="text-sm text-gray-500 mt-2">
            URL: <code>carrier.{domain}</code> &middot; Guard: <code>shipping_company_supervisors</code> &middot; Belongs to: <code>shipping_companies</code>
        </p>
    </div>

    <div class="space-y-8">

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Dashboard</h2>
            <p class="text-sm text-gray-700">Active deliveries, agent count, SLA metrics</p>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Agents</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/agents</code> — Create/manage delivery agents for this carrier</li>
                <li>Unlimited agents — no cap enforced</li>
                <li>Suspend/activate agents</li>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Supervisors</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/supervisors</code> — Manage supervisors within the same shipping company</li>
                <li>Only owner-level supervisor can create other supervisors (gated in controller)</li>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Assignments</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/assignments/unassigned</code> — Orders not yet assigned to an agent</li>
                <li>Assign shipments to agents; reassign if needed</li>
                <li><code>/assignments</code> — All assignments with status tracking</li>
                <li><code>/assignments/{id}</code> — Detail view per assignment</li>
            </ul>
        </section>

    </div>
@endsection
