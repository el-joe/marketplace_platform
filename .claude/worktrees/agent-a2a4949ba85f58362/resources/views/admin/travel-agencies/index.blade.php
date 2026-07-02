@extends('layouts.admin')

@section('title', 'Travel Agencies')

@section('content')
    <div class="p-6 space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900">Travel Agencies</h1>
                @if($pendingCount > 0)
                    <p class="text-sm text-amber-600 font-medium mt-0.5">
                        {{ $pendingCount }} agency(ies) pending approval
                    </p>
                @endif
            </div>
        </div>

        {{-- Filters --}}
        <form method="GET" class="flex flex-wrap gap-3">
            <input name="q" value="{{ request('q') }}" placeholder="Search by name / email..."
                class="rounded-lg border border-gray-300 px-3 py-2 text-sm w-64">
            <select name="status" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
                <option value="">All Statuses</option>
                @foreach(['pending', 'active', 'suspended'] as $s)
                    <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <select name="country_id" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
                <option value="">All Countries</option>
                @foreach($countries as $c)
                    <option value="{{ $c->id }}" {{ request('country_id') == $c->id ? 'selected' : '' }}>{{ $c->name_en }}
                    </option>
                @endforeach
            </select>
            <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm">Filter</button>
            <a href="{{ route('admin.travel.agencies.index') }}"
                class="px-4 py-2 border border-gray-300 rounded-lg text-sm">Reset</a>
        </form>

        <div id="agencies-table-wrap" class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-start font-semibold text-gray-700">Agency</th>
                        <th class="px-4 py-3 text-start font-semibold text-gray-700">Email</th>
                        <th class="px-4 py-3 text-start font-semibold text-gray-700">Country</th>
                        <th class="px-4 py-3 text-start font-semibold text-gray-700">Packages</th>
                        <th class="px-4 py-3 text-start font-semibold text-gray-700">Status</th>
                        <th class="px-4 py-3 text-start font-semibold text-gray-700">Registered</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody id="agencies-tbody" class="divide-y divide-gray-100">
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-400 text-sm">Loading…</td>
                    </tr>
                </tbody>
            </table>
            <div id="agencies-pagination"
                class="px-4 py-3 border-t border-gray-100 flex items-center gap-2 text-sm text-gray-500"></div>
        </div>
    </div>

    @push('scripts')
        <script>
            const statusColors = {
                pending: 'bg-amber-100 text-amber-700',
                active: 'bg-emerald-100 text-emerald-700',
                suspended: 'bg-red-100 text-red-700',
            };

            let currentPage = 1;

            async function loadAgencies(page = 1) {
                currentPage = page;
                const params = new URLSearchParams({
                    page,
                    q: document.querySelector('[name=q]').value,
                    status: document.querySelector('[name=status]').value,
                    country_id: document.querySelector('[name=country_id]').value,
                });

                const res = await fetch(`{{ route('admin.travel.agencies.datatable') }}?${params}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const json = await res.json();

                const tbody = document.getElementById('agencies-tbody');
                tbody.innerHTML = json.data.length === 0
                    ? '<tr><td colspan="7" class="px-4 py-8 text-center text-gray-400 text-sm">No agencies found.</td></tr>'
                    : json.data.map(a => `
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-900">${a.name}</td>
                        <td class="px-4 py-3 text-gray-500">${a.email}</td>
                        <td class="px-4 py-3 text-gray-500">${a.country ?? '—'}</td>
                        <td class="px-4 py-3 text-gray-500">${a.packages}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium ${statusColors[a.status] ?? ''}">
                                ${a.status}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-500">${a.created_at}</td>
                        <td class="px-4 py-3">
                            <a href="/travel/agencies/${a.id}" class="text-primary-600 text-xs font-medium hover:underline">View</a>
                        </td>
                    </tr>`).join('');

                // Pagination
                const pg = json.meta;
                const div = document.getElementById('agencies-pagination');
                div.innerHTML = `Page ${pg.current_page} of ${pg.last_page} &nbsp;·&nbsp; ${pg.total} agencies`;
            }

            document.addEventListener('DOMContentLoaded', () => loadAgencies());
        </script>
    @endpush
@endsection