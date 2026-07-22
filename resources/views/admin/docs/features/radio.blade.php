@component('admin.docs._layout', ['title' => 'Radio Channels', 'icon' => '📻', 'breadcrumb' => 'Features'])

    <div class="prose prose-sm max-w-none space-y-10">

        {{-- What it is --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">1. What It Is</h2>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li>Online radio streams embedded in the storefront, creating an ambient shopping experience</li>
                <li>Each channel has a live audio stream URL</li>
                <li>Admin schedules programming slots</li>
            </ul>
        </section>

        {{-- How it works --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">2. How It Works</h2>
            <ol class="list-decimal list-inside text-gray-600 space-y-1">
                <li>Admin creates a channel &rarr; adds stream URL + cover image + description</li>
                <li>Admin schedules slots (time-based programming schedule)</li>
                <li>Customers listen at <code>{country}.domain/radio/{channel}</code></li>
                <li>Session tracking: JS reports listen duration per session</li>
            </ol>
        </section>

        {{-- Admin management --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">3. Admin Management</h2>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li><a href="{{ route('admin.radio.channels.index') }}" class="text-primary-600 hover:underline">admin/radio/channels</a>: channel CRUD</li>
                <li>admin/radio/channels/{id}/schedule: view weekly programming schedule</li>
                <li>admin/radio/channels/{id}/slots: add/edit/delete time slots</li>
                <li>Schedule events returned as calendar-compatible JSON for frontend display</li>
            </ul>
        </section>

        {{-- Who uses it / rules --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">Who Uses It & Key Rules</h2>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li><strong>Admin</strong> is the only one who manages channels and slots; customers only listen</li>
                <li>A channel's storefront route is per-country (<code>{country}.domain</code>), not global</li>
                <li>Slot scheduling is time-based per channel, so overlapping slots on the same channel should be avoided in the schedule UI</li>
            </ul>
        </section>

    </div>

@endcomponent
