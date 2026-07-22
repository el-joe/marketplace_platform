@extends('layouts.admin')

@section('title', 'Delivery Agent Panel')

@section('content')
    <div class="mb-6">
        <a href="{{ route('admin.docs.index') }}" class="text-sm text-primary-600 hover:underline">&larr; {{ __('admin.nav.documentation') }}</a>
        <div class="flex items-center gap-3 mt-2">
            <span class="text-3xl">🚴</span>
            <h1 class="text-2xl font-bold text-gray-900">Delivery Agent Panel</h1>
        </div>
        <p class="text-sm text-gray-500 mt-2">
            URL: <code>delivery.{domain}</code> &middot; Guard: <code>delivery_agents</code>
        </p>
    </div>

    <div class="space-y-8">

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Dashboard</h2>
            <p class="text-sm text-gray-700">Today's assignments, earnings today, active status toggle</p>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Assignments</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/assignments</code> — Delivery tasks assigned to this agent</li>
                <li>Actions in order: accept &rarr; picked-up &rarr; deliver | fail</li>
                <li>OTP shown to agent for visual verification with customer (agent does NOT enter it)</li>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Location &amp; Availability</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/location</code> — Update live GPS coordinates</li>
                <li><code>/availability</code> — Toggle available/unavailable for new assignments</li>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Earnings</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/earnings</code> — Per-assignment earnings history</li>
                <li><code>/earnings/summary</code> — Weekly/monthly summary</li>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Wallet</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/wallet</code> — Agent wallet balance</li>
                <li>Request withdrawal to bank</li>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">COD Settlements</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/cod-settlements</code> — Cash collected from COD deliveries; settlement history</li>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Profile</h2>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
                <li><code>/profile</code> — Agent info and documents</li>
            </ul>
        </section>

    </div>
@endsection
