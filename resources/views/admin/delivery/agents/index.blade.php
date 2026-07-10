@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/column-renderers.js'])
@endpush

@section('title', __('admin.delivery_section.agents'))

@section('content')

{{-- ─── Page Header ─────────────────────────────────────────────────────────── --}}
<div class="mb-6 flex items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ __('admin.delivery_section.agents') }}</h1>
        <p class="text-sm text-gray-500 mt-0.5">{{ __('admin.delivery_section.agents_desc') }}</p>
    </div>
    <button type="button" id="add-agent-btn" class="btn btn-primary btn-sm">
        {{ __('admin.delivery_section.add_agent') }}
    </button>
</div>

{{-- ─── Stats Row ───────────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    @foreach([
        ['label' => __('admin.delivery_section.total_agents_stat'), 'value' => $stats['total'],     'color' => 'gray'],
        ['label' => __('common.active'),      'value' => $stats['active'],    'color' => 'success'],
        ['label' => __('admin.delivery_section.on_shift'),       'value' => $stats['on_shift'],  'color' => 'primary'],
        ['label' => __('admin.delivery_section.suspended'),      'value' => $stats['suspended'], 'color' => 'danger'],
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
            <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.delivery_section.search_label') }}</label>
            <input type="text" id="search-input" class="form-input w-full text-sm" placeholder="{{ __('admin.delivery_section.search_agent_name_phone') }}">
        </div>
        <div class="w-44">
            <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('common.country') }}</label>
            <select id="filter-country" class="form-input w-full text-sm">
                <option value="">{{ __('admin.delivery_section.all_countries') }}</option>
                @foreach($countries as $country)
                    <option value="{{ $country->id }}">{{ $country->name_en }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-44">
            <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.delivery_section.zone_col') }}</label>
            <select id="filter-zone" class="form-input w-full text-sm">
                <option value="">{{ __('admin.delivery_section.all_zones') }}</option>
                @foreach($zones as $zone)
                    <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-36">
            <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('common.status') }}</label>
            <select id="filter-status" class="form-input w-full text-sm">
                <option value="">{{ __('admin.delivery_section.all_statuses') }}</option>
                <option value="active">{{ __('admin.delivery_section.active') }}</option>
                <option value="inactive">{{ __('common.inactive') }}</option>
                <option value="on_shift">{{ __('admin.delivery_section.on_shift') }}</option>
                <option value="suspended">{{ __('admin.delivery_section.suspended') }}</option>
            </select>
        </div>
        <div class="w-36">
            <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('common.type') }}</label>
            <select id="filter-type" class="form-input w-full text-sm">
                <option value="">{{ __('common.all') }}</option>
                <option value="platform">{{ __('admin.delivery_section.platform_type') }}</option>
                <option value="third_party">{{ __('admin.delivery_section.third_party') }}</option>
            </select>
        </div>
        <button type="button" id="clear-filters" class="btn btn-ghost btn-sm self-end">{{ __('admin.delivery_section.reset') }}</button>
    </form>
</x-card>

{{-- ─── DataTable ───────────────────────────────────────────────────────────── --}}
<x-card>
    <div class="overflow-x-auto">
        <table id="agents-table" class="w-full text-sm" style="width:100%">
            <thead>
                <tr>
                    <th>{{ __('common.name') }}</th>
                    <th>{{ __('admin.delivery_section.phone_col') }}</th>
                    <th>{{ __('common.country') }}</th>
                    <th>{{ __('admin.delivery_section.zone_col') }}</th>
                    <th>{{ __('common.type') }}</th>
                    <th>{{ __('common.status') }}</th>
                    <th>{{ __('admin.delivery_section.rating_col') }}</th>
                    <th>{{ __('admin.delivery_section.deliveries_col') }}</th>
                    <th>{{ __('admin.delivery_section.available_col') }}</th>
                    <th>{{ __('admin.delivery_section.last_login_col') }}</th>
                    <th>{{ __('admin.delivery_section.actions_col') }}</th>
                </tr>
            </thead>
        </table>
    </div>
</x-card>

{{-- ─── Add Agent Modal ─────────────────────────────────────────────────────── --}}
<div id="add-agent-modal" class="modal-backdrop hidden" x-data>
    <div class="modal-box w-full max-w-xl">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">{{ __('admin.delivery_section.add_delivery_agent') }}</h3>
            <button type="button" onclick="document.getElementById('add-agent-modal').classList.add('hidden')"
                class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <form id="add-agent-form" class="space-y-4">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.delivery_section.full_name') }} <span class="text-red-500">*</span></label>
                    <input type="text" name="name" class="form-input w-full" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.delivery_section.email_required') }} <span class="text-red-500">*</span></label>
                    <input type="email" name="email" class="form-input w-full" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.delivery_section.phone_required') }} <span class="text-red-500">*</span></label>
                    <input type="text" name="phone" class="form-input w-full" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.delivery_section.password_required') }} <span class="text-red-500">*</span></label>
                    <input type="password" name="password" class="form-input w-full" required minlength="8">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('common.country') }} <span class="text-red-500">*</span></label>
                    <select name="country_id" class="form-input w-full" required>
                        <option value="">{{ __('admin.delivery_section.select_country') }}</option>
                        @foreach($countries as $country)
                            <option value="{{ $country->id }}">{{ $country->name_en }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.delivery_section.zone_col') }}</label>
                    <select name="zone_id" class="form-input w-full">
                        <option value="">{{ __('admin.delivery_section.no_zone') }}</option>
                        @foreach($zones as $zone)
                            <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.delivery_section.agent_type_required') }} <span class="text-red-500">*</span></label>
                    <select name="agent_type" class="form-input w-full" required>
                        <option value="platform">{{ __('admin.delivery_section.platform_type') }}</option>
                        <option value="third_party">{{ __('admin.delivery_section.third_party') }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.delivery_section.vehicle_required') }} <span class="text-red-500">*</span></label>
                    <select name="vehicle_type" class="form-input w-full" required>
                        <option value="motorcycle">{{ __('admin.delivery_section.motorcycle') }}</option>
                        <option value="car">{{ __('admin.delivery_section.car') }}</option>
                        <option value="van">{{ __('admin.delivery_section.van') }}</option>
                        <option value="bicycle">{{ __('admin.delivery_section.bicycle') }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.delivery_section.national_id') }}</label>
                    <input type="text" name="national_id" class="form-input w-full">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.delivery_section.vehicle_plate') }}</label>
                    <input type="text" name="vehicle_plate" class="form-input w-full">
                </div>
            </div>
            <div class="pt-4 border-t border-gray-100 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('add-agent-modal').classList.add('hidden')"
                    class="btn btn-ghost btn-sm">{{ __('common.cancel') }}</button>
                <button type="submit" class="btn btn-primary btn-sm">{{ __('admin.delivery_section.create_agent') }}</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script type="module">
window.TRANSLATIONS = window.TRANSLATIONS || {};
Object.assign(window.TRANSLATIONS, {
    platform: @json(__('admin.delivery_section.platform_type')),
    thirdPartyShort: @json(__('admin.delivery_section.third_party_short')),
    yes: @json(__('admin.delivery_section.yes')),
    no: @json(__('admin.delivery_section.no')),
    view: @json(__('admin.delivery_section.view')),
    quickSearchPlaceholder: @json(__('admin.delivery_section.quick_search_placeholder')),
    createAgentFailed: @json(__('admin.delivery_section.create_agent_failed')),
});

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
                    ? `<span class="badge badge-primary text-xs">${window.TRANSLATIONS.platform}</span>`
                    : `<span class="badge badge-gray text-xs">${window.TRANSLATIONS.thirdPartyShort}</span>`
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
                    ? `<span class="inline-block w-2 h-2 rounded-full bg-green-500"></span> ${window.TRANSLATIONS.yes}`
                    : `<span class="inline-block w-2 h-2 rounded-full bg-gray-400"></span> ${window.TRANSLATIONS.no}`
            },
            { data: 'last_login_at' },
            {
                data: null, orderable: false,
                render: r =>
                    `<a href="${r.show_url}" class="btn btn-xs btn-secondary">${window.TRANSLATIONS.view}</a>`
            },
        ],
        order: [[9, 'desc']],
        pageLength: 25,
        language: { search: '', searchPlaceholder: window.TRANSLATIONS.quickSearchPlaceholder },
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
                    window.Toast?.error(window.TRANSLATIONS.createAgentFailed);
                }
            }
        });
    });
})();
</script>
@endpush
