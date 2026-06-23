@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js'])
@endpush

@section('title', $agent->name . ' — Delivery Agent')

@section('content')

@php
    $statusColors = [
        'active'    => 'success',
        'inactive'  => 'gray',
        'on_shift'  => 'primary',
        'suspended' => 'danger',
    ];
    $docStatusColors = [
        'pending'  => 'warning',
        'verified' => 'success',
        'rejected' => 'danger',
        'expired'  => 'danger',
    ];
@endphp

{{-- ─── Page Header ─────────────────────────────────────────────────────────── --}}
<div class="mb-6 flex items-start justify-between gap-4">
    <div class="flex items-center gap-4 min-w-0">
        <div class="w-14 h-14 rounded-xl bg-primary-100 text-primary-700 flex items-center justify-center text-xl font-bold flex-shrink-0">
            {{ strtoupper(substr($agent->name, 0, 1)) }}
        </div>
        <div class="min-w-0">
            <h1 class="text-2xl font-bold text-gray-900 truncate">{{ $agent->name }}</h1>
            <div class="flex items-center gap-2 mt-1 flex-wrap">
                <x-badge :color="$statusColors[$agent->status] ?? 'gray'">
                    {{ ucwords(str_replace('_', ' ', $agent->status)) }}
                </x-badge>
                <x-badge color="gray">{{ ucfirst(str_replace('_', ' ', $agent->agent_type)) }}</x-badge>
                <x-badge color="gray">{{ ucfirst($agent->vehicle_type) }}</x-badge>
                @if($agent->is_available)
                    <x-badge color="success">Available</x-badge>
                @endif
            </div>
        </div>
    </div>
    <div class="flex items-center gap-2 flex-shrink-0">
        @if($agent->status === 'active' || $agent->status === 'on_shift')
            <button type="button" id="suspend-btn" class="btn btn-warning btn-sm">Suspend</button>
        @else
            <button type="button" id="activate-btn" class="btn btn-success btn-sm">Activate</button>
        @endif
        <button type="button" id="reset-pwd-btn" class="btn btn-secondary btn-sm">Reset Password</button>
        <a href="{{ route('admin.delivery.agents.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Back</a>
    </div>
</div>

{{-- ─── Stats Strip ─────────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    @foreach([
        ['label' => 'Total Deliveries', 'value' => number_format($agent->total_deliveries)],
        ['label' => 'Rating',           'value' => number_format($agent->rating_avg, 1) . ' ★'],
        ['label' => 'Delivered',        'value' => number_format($assignmentStats->delivered ?? 0)],
        ['label' => 'Failed',           'value' => number_format($assignmentStats->failed ?? 0)],
    ] as $stat)
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ $stat['label'] }}</p>
            <p class="mt-1 text-2xl font-bold text-gray-800">{{ $stat['value'] }}</p>
        </div>
    @endforeach
</div>

{{-- ─── Tabs Layout ─────────────────────────────────────────────────────────── --}}
<div x-data="{ tab: 'profile' }">
    <div class="flex gap-1 border-b border-gray-200 overflow-x-auto pb-px mb-6">
        @foreach([
            'profile'     => 'Profile',
            'assignments' => 'Assignments',
            'earnings'    => 'Earnings',
            'documents'   => 'Documents',
            'shifts'      => 'Shifts',
        ] as $key => $label)
            <button type="button"
                    @click="tab = '{{ $key }}'"
                    :class="tab === '{{ $key }}'
                        ? 'border-b-2 border-primary-600 text-primary-700 font-semibold'
                        : 'text-gray-500 hover:text-gray-700'"
                    class="px-4 py-2 text-sm whitespace-nowrap transition-colors focus:outline-none">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- ── Profile Tab ──────────────────────────────────────────────────────── --}}
    <div x-show="tab === 'profile'">
        <div class="grid grid-cols-12 gap-6">

            {{-- Main profile info --}}
            <div class="col-span-12 lg:col-span-8">
                <x-card title="Agent Profile">
                    <x-slot name="actions">
                        <button type="button" id="edit-profile-btn" class="btn btn-secondary btn-sm text-xs">Edit</button>
                    </x-slot>

                    <div id="profile-view" class="grid grid-cols-2 gap-x-6 gap-y-4 text-sm">
                        @foreach([
                            'Name'                    => $agent->name,
                            'Email'                   => $agent->email,
                            'Phone'                   => $agent->phone,
                            'Country'                 => $agent->country?->name_en ?? '—',
                            'Zone'                    => $agent->zone?->name ?? '—',
                            'Agent Type'              => ucfirst(str_replace('_', ' ', $agent->agent_type)),
                            'Vehicle Type'            => ucfirst($agent->vehicle_type),
                            'National ID'             => $agent->national_id ?? '—',
                            'Vehicle Plate'           => $agent->vehicle_plate ?? '—',
                            'Emergency Contact'       => $agent->emergency_contact_name ?? '—',
                            'Emergency Phone'         => $agent->emergency_contact_phone ?? '—',
                            'Base Salary'             => $agent->base_salary_cents ? number_format($agent->base_salary_cents / 100, 2) : '—',
                            'Per Delivery Fee'        => number_format($agent->per_delivery_fee_cents / 100, 2),
                            'Last Login'              => $agent->last_login_at?->format('d M Y H:i') ?? 'Never',
                            'Last Login IP'           => $agent->last_login_ip ?? '—',
                            'Joined'                  => $agent->created_at->format('d M Y'),
                        ] as $label => $value)
                            <div>
                                <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">{{ $label }}</dt>
                                <dd class="mt-0.5 text-gray-800 font-medium">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </div>

                    {{-- Edit form (hidden by default) --}}
                    <form id="profile-edit-form" class="hidden mt-4 border-t pt-4">
                        @csrf @method('PUT')
                        <div class="grid grid-cols-2 gap-4">
                            @foreach([
                                ['name' => 'name',                    'label' => 'Name',              'type' => 'text',   'value' => $agent->name],
                                ['name' => 'phone',                   'label' => 'Phone',             'type' => 'text',   'value' => $agent->phone],
                                ['name' => 'national_id',             'label' => 'National ID',       'type' => 'text',   'value' => $agent->national_id],
                                ['name' => 'vehicle_plate',           'label' => 'Vehicle Plate',     'type' => 'text',   'value' => $agent->vehicle_plate],
                                ['name' => 'emergency_contact_name',  'label' => 'Emergency Name',    'type' => 'text',   'value' => $agent->emergency_contact_name],
                                ['name' => 'emergency_contact_phone', 'label' => 'Emergency Phone',   'type' => 'text',   'value' => $agent->emergency_contact_phone],
                                ['name' => 'base_salary_cents',       'label' => 'Base Salary (cents)','type' => 'number','value' => $agent->base_salary_cents],
                                ['name' => 'per_delivery_fee_cents',  'label' => 'Per Delivery Fee (cents)','type' => 'number','value' => $agent->per_delivery_fee_cents],
                            ] as $f)
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">{{ $f['label'] }}</label>
                                    <input type="{{ $f['type'] }}" name="{{ $f['name'] }}" value="{{ $f['value'] }}" class="form-input w-full text-sm">
                                </div>
                            @endforeach
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Vehicle Type</label>
                                <select name="vehicle_type" class="form-input w-full text-sm">
                                    @foreach(['motorcycle','car','van','bicycle'] as $vt)
                                        <option value="{{ $vt }}" {{ $agent->vehicle_type === $vt ? 'selected' : '' }}>{{ ucfirst($vt) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <input type="hidden" name="country_id" value="{{ $agent->country_id }}">
                            <input type="hidden" name="agent_type" value="{{ $agent->agent_type }}">
                        </div>
                        <div class="mt-4 flex gap-3">
                            <button type="submit" class="btn btn-primary btn-sm">Save</button>
                            <button type="button" id="cancel-edit-btn" class="btn btn-ghost btn-sm">Cancel</button>
                        </div>
                    </form>
                </x-card>
            </div>

            {{-- Zone assignment sidebar --}}
            <div class="col-span-12 lg:col-span-4 space-y-4">
                <x-card title="Zone Assignment">
                    <form id="zone-form">
                        @csrf
                        <label class="block text-xs font-medium text-gray-600 mb-1">Assign to Zone</label>
                        <select name="zone_id" id="zone-select" class="form-input w-full text-sm mb-3">
                            <option value="">No zone</option>
                            @foreach($zones as $zone)
                                <option value="{{ $zone->id }}" {{ $agent->zone_id === $zone->id ? 'selected' : '' }}>
                                    {{ $zone->name }}
                                </option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-secondary btn-sm w-full">Update Zone</button>
                    </form>
                </x-card>

                @if($agent->current_latitude && $agent->current_longitude)
                    <x-card title="Last Known Location">
                        <p class="text-xs text-gray-500 mb-2">Updated {{ $agent->last_location_at?->diffForHumans() ?? '—' }}</p>
                        <div id="agent-mini-map" class="h-48 rounded-lg bg-gray-100 flex items-center justify-center">
                            <span class="text-gray-400 text-sm">
                                {{ $agent->current_latitude }}, {{ $agent->current_longitude }}
                            </span>
                        </div>
                    </x-card>
                @endif
            </div>

        </div>
    </div>

    {{-- ── Assignments Tab ──────────────────────────────────────────────────── --}}
    <div x-show="tab === 'assignments'" x-cloak>
        <x-card>
            <div class="mb-4 flex gap-3">
                <select id="assignment-status-filter" class="form-input text-sm w-40">
                    <option value="">All statuses</option>
                    <option value="assigned">Assigned</option>
                    <option value="accepted">Accepted</option>
                    <option value="picked_up">Picked Up</option>
                    <option value="delivered">Delivered</option>
                    <option value="failed">Failed</option>
                </select>
            </div>
            <table id="assignments-table" class="w-full text-sm" style="width:100%">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Status</th>
                        <th>Assigned At</th>
                        <th>Delivered At</th>
                        <th>Duration</th>
                        <th>Rating</th>
                    </tr>
                </thead>
            </table>
        </x-card>
    </div>

    {{-- ── Earnings Tab ─────────────────────────────────────────────────────── --}}
    <div x-show="tab === 'earnings'" x-cloak>
        <div class="grid grid-cols-3 gap-4 mb-6" id="earnings-summary-row">
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">This Month</p>
                <p class="mt-1 text-2xl font-bold text-gray-800" id="earnings-this-month">—</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Last Month</p>
                <p class="mt-1 text-2xl font-bold text-gray-800" id="earnings-last-month">—</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Year to Date</p>
                <p class="mt-1 text-2xl font-bold text-gray-800" id="earnings-ytd">—</p>
            </div>
        </div>
        <x-card title="Monthly Earnings">
            <canvas id="earnings-chart" height="80"></canvas>
        </x-card>
    </div>

    {{-- ── Documents Tab ────────────────────────────────────────────────────── --}}
    <div x-show="tab === 'documents'" x-cloak>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($agent->documents as $doc)
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <div class="flex items-start justify-between mb-3">
                        <div>
                            <p class="font-medium text-gray-800 text-sm">{{ $doc->label }}</p>
                            @if($doc->expires_at)
                                <p class="text-xs text-gray-400 mt-0.5">
                                    Expires: {{ $doc->expires_at->format('d M Y') }}
                                    @if($doc->isExpired())
                                        <span class="text-red-500">(Expired)</span>
                                    @endif
                                </p>
                            @endif
                        </div>
                        <x-badge :color="$docStatusColors[$doc->status] ?? 'gray'">
                            {{ ucfirst($doc->status) }}
                        </x-badge>
                    </div>

                    <a href="{{ Storage::url($doc->file_path) }}" target="_blank"
                       class="block w-full text-center text-xs text-primary-600 hover:underline mb-3">
                        View Document
                    </a>

                    @if($doc->rejection_reason)
                        <p class="text-xs text-red-600 bg-red-50 rounded p-2 mb-3">{{ $doc->rejection_reason }}</p>
                    @endif

                    @if($doc->status === 'pending')
                        <div class="flex gap-2">
                            <button type="button" data-doc-id="{{ $doc->id }}" data-action="verify"
                                class="doc-action-btn btn btn-xs btn-success flex-1">Verify</button>
                            <button type="button" data-doc-id="{{ $doc->id }}" data-action="reject"
                                class="doc-action-btn btn btn-xs btn-danger flex-1">Reject</button>
                        </div>
                    @endif

                    @if($doc->verified_at)
                        <p class="text-xs text-gray-400 mt-2">
                            Verified {{ $doc->verified_at->format('d M Y') }}
                        </p>
                    @endif
                </div>
            @empty
                <div class="col-span-3 text-center py-12 text-gray-400">
                    No documents uploaded yet.
                </div>
            @endforelse
        </div>
    </div>

    {{-- ── Shifts Tab ───────────────────────────────────────────────────────── --}}
    <div x-show="tab === 'shifts'" x-cloak>
        <x-card>
            <table class="w-full text-sm">
                <thead class="text-xs text-gray-500 uppercase border-b">
                    <tr>
                        <th class="pb-2 text-left">Date</th>
                        <th class="pb-2 text-left">Zone</th>
                        <th class="pb-2 text-left">Scheduled</th>
                        <th class="pb-2 text-left">Actual</th>
                        <th class="pb-2 text-left">Status</th>
                        <th class="pb-2 text-right">Deliveries</th>
                        <th class="pb-2 text-right">Earnings</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($agent->shifts as $shift)
                        @php
                            $shiftColors = ['scheduled'=>'gray','active'=>'primary','completed'=>'success','no_show'=>'warning','cancelled'=>'danger'];
                        @endphp
                        <tr>
                            <td class="py-2">{{ $shift->shift_date->format('d M Y') }}</td>
                            <td class="py-2">{{ $shift->zone?->name ?? '—' }}</td>
                            <td class="py-2 text-xs">{{ $shift->scheduled_start }} – {{ $shift->scheduled_end }}</td>
                            <td class="py-2 text-xs">
                                @if($shift->actual_start)
                                    {{ \Carbon\Carbon::parse($shift->actual_start)->format('H:i') }} –
                                    {{ $shift->actual_end ? \Carbon\Carbon::parse($shift->actual_end)->format('H:i') : '…' }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="py-2">
                                <x-badge :color="$shiftColors[$shift->status] ?? 'gray'" class="text-xs">
                                    {{ ucfirst(str_replace('_', ' ', $shift->status)) }}
                                </x-badge>
                            </td>
                            <td class="py-2 text-right">{{ $shift->total_deliveries }}</td>
                            <td class="py-2 text-right">{{ number_format($shift->total_earnings_cents / 100, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-gray-400">No shifts recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-card>
    </div>

</div>

{{-- ─── Reject Document Modal ───────────────────────────────────────────────── --}}
<div id="reject-doc-modal" class="modal-backdrop hidden">
    <div class="modal-box max-w-sm">
        <h3 class="text-lg font-semibold mb-4">Reject Document</h3>
        <form id="reject-doc-form">
            @csrf
            <input type="hidden" id="reject-doc-id">
            <label class="block text-sm font-medium text-gray-700 mb-1">Rejection Reason <span class="text-red-500">*</span></label>
            <textarea name="reason" rows="3" class="form-input w-full" required placeholder="Explain why the document is rejected…"></textarea>
            <div class="mt-4 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('reject-doc-modal').classList.add('hidden')"
                    class="btn btn-ghost btn-sm">Cancel</button>
                <button type="submit" class="btn btn-danger btn-sm">Reject</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
(function () {
    const agentId        = @json($agent->id);
    const UPDATE_URL     = @json(route('admin.delivery.agents.update', $agent->id));
    const SUSPEND_URL    = @json(route('admin.delivery.agents.suspend', $agent->id));
    const ACTIVATE_URL   = @json(route('admin.delivery.agents.activate', $agent->id));
    const RESET_PWD_URL  = @json(route('admin.delivery.agents.reset-password', $agent->id));
    const ZONE_URL       = @json(route('admin.delivery.agents.assign-zone', $agent->id));
    const ASSIGN_DT_URL  = @json(route('admin.delivery.agents.assignments.datatable', $agent->id));
    const EARNINGS_URL   = @json(route('admin.delivery.agents.earnings-summary', $agent->id));
    const VERIFY_BASE    = @json(url('admin/delivery/documents'));
    const token          = () => $('meta[name=csrf-token]').attr('content');

    // ── Assignments DataTable ────────────────────────────────────────────────
    const assignTable = $('#assignments-table').DataTable({
        processing : true,
        serverSide : true,
        ajax: {
            url    : ASSIGN_DT_URL,
            type   : 'POST',
            data   : d => Object.assign(d, { _token: token(), status: $('#assignment-status-filter').val() }),
        },
        columns: [
            { data: 'sub_order_number' },
            {
                data: 'status',
                render: v => {
                    const c = { assigned:'gray', accepted:'primary', picked_up:'warning', delivered:'success', failed:'danger' }[v] ?? 'gray';
                    return `<span class="badge badge-${c} text-xs capitalize">${v.replace('_',' ')}</span>`;
                }
            },
            { data: 'assigned_at' },
            { data: 'delivered_at' },
            { data: 'duration_minutes', render: v => v ? `${v} min` : '—' },
            { data: 'customer_rating', render: v => v ? `${'★'.repeat(v)}` : '—' },
        ],
        order: [[2, 'desc']],
    });
    $('#assignment-status-filter').on('change', () => assignTable.ajax.reload());

    // ── Earnings ─────────────────────────────────────────────────────────────
    let earningsChartInstance = null;
    $('[\\@click="tab = \'earnings\'"]').on('click', loadEarnings);
    function loadEarnings () {
        if (earningsChartInstance) return;
        $.getJSON(EARNINGS_URL, data => {
            const fmt = cents => (cents / 100).toFixed(2);
            $('#earnings-this-month').text(fmt(data.summary.this_month_cents));
            $('#earnings-last-month').text(fmt(data.summary.last_month_cents));
            $('#earnings-ytd').text(fmt(data.summary.ytd_cents));

            const labels = data.monthly.map(r => `${r.year}-${String(r.month).padStart(2,'0')}`);
            const values = data.monthly.map(r => r.total_cents / 100);

            earningsChartInstance = new Chart(document.getElementById('earnings-chart'), {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                        label: 'Earnings',
                        data: values,
                        backgroundColor: 'rgba(79,70,229,0.7)',
                        borderRadius: 4,
                    }],
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true } },
                },
            });
        });
    }

    // ── Profile Edit ─────────────────────────────────────────────────────────
    $('#edit-profile-btn').on('click', () => {
        $('#profile-view').addClass('hidden');
        $('#profile-edit-form').removeClass('hidden');
    });
    $('#cancel-edit-btn').on('click', () => {
        $('#profile-view').removeClass('hidden');
        $('#profile-edit-form').addClass('hidden');
    });
    $('#profile-edit-form').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url    : UPDATE_URL,
            method : 'POST',
            data   : $(this).serialize() + '&_method=PUT&_token=' + token(),
            success: res => { if (res.success) { window.Toast?.success(res.message); location.reload(); } },
            error  : xhr => window.Toast?.error(xhr.responseJSON?.message ?? 'Update failed.'),
        });
    });

    // ── Zone Form ─────────────────────────────────────────────────────────────
    $('#zone-form').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url    : ZONE_URL,
            method : 'POST',
            data   : { _token: token(), zone_id: $('#zone-select').val() },
            success: res => { if (res.success) window.Toast?.success(res.message); },
            error  : xhr => window.Toast?.error(xhr.responseJSON?.message ?? 'Failed.'),
        });
    });

    // ── Suspend / Activate ────────────────────────────────────────────────────
    $('#suspend-btn').on('click', () => {
        const reason = prompt('Suspension reason (required):');
        if (!reason) return;
        $.ajax({
            url    : SUSPEND_URL,
            method : 'POST',
            data   : { _token: token(), reason },
            success: res => { if (res.success) { window.Toast?.success(res.message); location.reload(); } },
        });
    });
    $('#activate-btn').on('click', () => {
        $.ajax({
            url    : ACTIVATE_URL,
            method : 'POST',
            data   : { _token: token() },
            success: res => { if (res.success) { window.Toast?.success(res.message); location.reload(); } },
        });
    });

    // ── Reset Password ────────────────────────────────────────────────────────
    $('#reset-pwd-btn').on('click', () => {
        if (!confirm('Generate a new temporary password and send to agent?')) return;
        $.ajax({
            url    : RESET_PWD_URL,
            method : 'POST',
            data   : { _token: token() },
            success: res => {
                if (res.success) {
                    alert(`New temp password: ${res.temp_password}\n\nPlease relay this to the agent securely.`);
                }
            },
        });
    });

    // ── Document Actions ──────────────────────────────────────────────────────
    $(document).on('click', '.doc-action-btn', function () {
        const docId  = $(this).data('doc-id');
        const action = $(this).data('action');

        if (action === 'verify') {
            $.ajax({
                url    : `${VERIFY_BASE}/${docId}/verify`,
                method : 'POST',
                data   : { _token: token() },
                success: res => { if (res.success) { window.Toast?.success(res.message); location.reload(); } },
            });
        } else {
            $('#reject-doc-id').val(docId);
            document.getElementById('reject-doc-modal').classList.remove('hidden');
        }
    });

    $('#reject-doc-form').on('submit', function (e) {
        e.preventDefault();
        const docId = $('#reject-doc-id').val();
        $.ajax({
            url    : `${VERIFY_BASE}/${docId}/reject`,
            method : 'POST',
            data   : $(this).serialize() + '&_token=' + token(),
            success: res => {
                if (res.success) {
                    window.Toast?.success(res.message);
                    document.getElementById('reject-doc-modal').classList.add('hidden');
                    location.reload();
                }
            },
        });
    });
})();
</script>
@endpush
