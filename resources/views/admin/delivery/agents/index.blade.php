@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/column-renderers.js'])
@endpush

@section('title', 'Delivery Agents')

@section('content')

{{-- ─── Page Header ─────────────────────────────────────────────────────────── --}}
<div class="mb-6 flex items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Delivery Agents</h1>
        <p class="text-sm text-gray-500 mt-0.5">Manage platform and third-party delivery agents.</p>
    </div>
    <button type="button" id="add-agent-btn" class="btn btn-primary btn-sm">
        + Add Agent
    </button>
</div>

{{-- ─── Stats Row ───────────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    @foreach([
        ['label' => 'Total Agents',   'value' => $stats['total'],     'color' => 'gray'],
        ['label' => 'Active',         'value' => $stats['active'],    'color' => 'success'],
        ['label' => 'On Shift',       'value' => $stats['on_shift'],  'color' => 'primary'],
        ['label' => 'Suspended',      'value' => $stats['suspended'], 'color' => 'danger'],
    ] as $stat)
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ $stat['label'] }}</p>
            <p class="mt-1 text-2xl font-bold text-{{ $stat['color'] }}-600">{{ number_format($stat['value']) }}</p>
        </div>
    @endforeach
</div>

{{-- ─── Filters ─────────────────────────────────────────────────────────────── --}}
<x-card class="mb-5">
    <form id="filter-form" class="flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[180px]">
            <label class="block text-xs font-medium text-gray-600 mb-1">Search</label>
            <input type="text" id="search-input" class="form-input w-full text-sm" placeholder="Name, phone…">
        </div>
        <div class="w-44">
            <label class="block text-xs font-medium text-gray-600 mb-1">Country</label>
            <select id="filter-country" class="form-input w-full text-sm">
                <option value="">All countries</option>
                @foreach($countries as $country)
                    <option value="{{ $country->id }}">{{ $country->name_en }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-44">
            <label class="block text-xs font-medium text-gray-600 mb-1">Zone</label>
            <select id="filter-zone" class="form-input w-full text-sm">
                <option value="">All zones</option>
                @foreach($zones as $zone)
                    <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-36">
            <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
            <select id="filter-status" class="form-input w-full text-sm">
                <option value="">All statuses</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="on_shift">On Shift</option>
                <option value="suspended">Suspended</option>
            </select>
        </div>
        <div class="w-36">
            <label class="block text-xs font-medium text-gray-600 mb-1">Type</label>
            <select id="filter-type" class="form-input w-full text-sm">
                <option value="">All types</option>
                <option value="platform">Platform</option>
                <option value="third_party">Third Party</option>
            </select>
        </div>
        <button type="button" id="clear-filters" class="btn btn-ghost btn-sm self-end">Reset</button>
    </form>
</x-card>

{{-- ─── DataTable ───────────────────────────────────────────────────────────── --}}
<x-card>
    <table id="agents-table" class="w-full text-sm" style="width:100%">
        <thead>
            <tr>
                <th>Name</th>
                <th>Phone</th>
                <th>Country</th>
                <th>Zone</th>
                <th>Type</th>
                <th>Status</th>
                <th>Rating</th>
                <th>Deliveries</th>
                <th>Available</th>
                <th>Last Login</th>
                <th>Actions</th>
            </tr>
        </thead>
    </table>
</x-card>

{{-- ─── Add Agent Modal ─────────────────────────────────────────────────────── --}}
<div id="add-agent-modal" class="modal-backdrop hidden" x-data>
    <div class="modal-box w-full max-w-xl">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">Add Delivery Agent</h3>
            <button type="button" onclick="document.getElementById('add-agent-modal').classList.add('hidden')"
                class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <form id="add-agent-form" class="space-y-4">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" class="form-input w-full" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                    <input type="email" name="email" class="form-input w-full" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone <span class="text-red-500">*</span></label>
                    <input type="text" name="phone" class="form-input w-full" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password <span class="text-red-500">*</span></label>
                    <input type="password" name="password" class="form-input w-full" required minlength="8">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Country <span class="text-red-500">*</span></label>
                    <select name="country_id" class="form-input w-full" required>
                        <option value="">Select country</option>
                        @foreach($countries as $country)
                            <option value="{{ $country->id }}">{{ $country->name_en }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Zone</label>
                    <select name="zone_id" class="form-input w-full">
                        <option value="">No zone</option>
                        @foreach($zones as $zone)
                            <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Agent Type <span class="text-red-500">*</span></label>
                    <select name="agent_type" class="form-input w-full" required>
                        <option value="platform">Platform</option>
                        <option value="third_party">Third Party</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Vehicle <span class="text-red-500">*</span></label>
                    <select name="vehicle_type" class="form-input w-full" required>
                        <option value="motorcycle">Motorcycle</option>
                        <option value="car">Car</option>
                        <option value="van">Van</option>
                        <option value="bicycle">Bicycle</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">National ID</label>
                    <input type="text" name="national_id" class="form-input w-full">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Vehicle Plate</label>
                    <input type="text" name="vehicle_plate" class="form-input w-full">
                </div>
            </div>
            <div class="pt-4 border-t border-gray-100 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('add-agent-modal').classList.add('hidden')"
                    class="btn btn-ghost btn-sm">Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm">Create Agent</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script type="module">
(function () {
    const DATATABLE_URL = @json(route('admin.delivery.agents.datatable'));
    const STORE_URL     = @json(route('admin.delivery.agents.store'));

    // ── DataTable ─────────────────────────────────────────────────────────────
    const table = $('#agents-table').DataTable({
        processing : true,
        serverSide : true,
        ajax: {
            url    : DATATABLE_URL,
            type   : 'POST',
            data   : d => Object.assign(d, {
                _token     : $('meta[name=csrf-token]').attr('content'),
                country_id : $('#filter-country').val(),
                zone_id    : $('#filter-zone').val(),
                status     : $('#filter-status').val(),
                agent_type : $('#filter-type').val(),
            }),
        },
        columns: [
            {
                data: null,
                render: r =>
                    `<a href="${r.show_url}" class="font-medium text-primary-600 hover:underline">${r.name}</a>
                     <div class="text-xs text-gray-400">${r.email}</div>`
            },
            { data: 'phone' },
            { data: 'country' },
            { data: 'zone', defaultContent: '—' },
            {
                data: 'agent_type',
                render: v => v === 'platform'
                    ? '<span class="badge badge-primary text-xs">Platform</span>'
                    : '<span class="badge badge-gray text-xs">3rd Party</span>'
            },
            {
                data: 'status',
                render: v => {
                    const colors = { active:'success', inactive:'gray', on_shift:'primary', suspended:'danger' };
                    const c = colors[v] ?? 'gray';
                    return `<span class="badge badge-${c} text-xs capitalize">${v.replace('_',' ')}</span>`;
                }
            },
            { data: 'rating_avg' },
            { data: 'total_deliveries' },
            {
                data: 'is_available',
                render: v => v
                    ? '<span class="inline-block w-2 h-2 rounded-full bg-green-500"></span> Yes'
                    : '<span class="inline-block w-2 h-2 rounded-full bg-gray-400"></span> No'
            },
            { data: 'last_login_at' },
            {
                data: null, orderable: false,
                render: r =>
                    `<a href="${r.show_url}" class="btn btn-xs btn-secondary">View</a>`
            },
        ],
        order: [[9, 'desc']],
        pageLength: 25,
        language: { search: '', searchPlaceholder: 'Quick search…' },
    });

    // Filters
    ['#filter-country','#filter-zone','#filter-status','#filter-type'].forEach(sel =>
        $(sel).on('change', () => table.ajax.reload())
    );
    $('#search-input').on('input', function () {
        table.search(this.value).draw();
    });
    $('#clear-filters').on('click', () => {
        $('#filter-country, #filter-zone, #filter-status, #filter-type').val('');
        $('#search-input').val('');
        table.search('').ajax.reload();
    });

    // ── Add Agent Modal ───────────────────────────────────────────────────────
    $('#add-agent-btn').on('click', () =>
        document.getElementById('add-agent-modal').classList.remove('hidden')
    );

    $('#add-agent-form').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url    : STORE_URL,
            method : 'POST',
            data   : $(this).serialize() + '&_token=' + $('meta[name=csrf-token]').attr('content'),
            success: res => {
                if (res.success) {
                    window.Toast?.success(res.message);
                    window.location.href = res.redirect;
                }
            },
            error: xhr => {
                const errors = xhr.responseJSON?.errors;
                if (errors) {
                    window.Toast?.error(Object.values(errors).flat().join('\n'));
                } else {
                    window.Toast?.error('Failed to create agent.');
                }
            }
        });
    });
})();
</script>
@endpush
