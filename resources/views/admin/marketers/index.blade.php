@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/column-renderers.js'])
@endpush

@section('title', __('admin.marketers.title'))

@section('content')

{{-- ─── Page Header ─────────────────────────────────────────────────────────── --}}
<div class="mb-6 flex items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ __('admin.marketers.marketers_influencers') }}</h1>
        <p class="text-sm text-gray-500 mt-0.5">{{ __('admin.marketers.manage_desc') }}</p>
    </div>
    <div class="flex items-center gap-2">
        <x-export-dropdown />
        <button type="button" id="create-marketer-btn" class="btn btn-primary btn-sm">
            {{ __('admin.marketers.add_new_marketer') }}
        </button>
    </div>
</div>

{{-- ─── Stats Row ───────────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    @foreach([
        ['label' => __('admin.marketers.total_marketers'),    'value' => $stats['total'],             'color' => 'gray'],
        ['label' => __('admin.marketers.active'),             'value' => $stats['active'],            'color' => 'success'],
        ['label' => __('admin.marketers.pending_approval'),   'value' => $stats['pending'],           'color' => 'warning'],
        ['label' => __('admin.marketers.commissions_mtd'),  'value' => $stats['commissions_month_by_currency']->map(fn($v, $k) => number_format($v / 100, 2) . ' ' . $k)->join(' / ') ?: '—', 'color' => 'primary'],
    ] as $stat)
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ $stat['label'] }}</p>
            <p class="mt-1 text-2xl font-bold text-{{ $stat['color'] }}-600">{{ $stat['value'] }}</p>
        </div>
    @endforeach
</div>

{{-- ─── Type Tabs ───────────────────────────────────────────────────────────── --}}
<div class="flex gap-1 bg-gray-100 rounded-xl p-1 mb-5 w-fit" id="type-tabs">
    @foreach(['' => __('admin.marketers.all'), 'influencer' => __('admin.marketers.influencer'), 'celebrity' => __('admin.marketers.celebrity'), 'affiliate' => __('admin.marketers.affiliate'), 'brand_ambassador' => __('admin.marketers.brand_ambassador')] as $type => $typeLabel)
        <button type="button" data-type="{{ $type }}"
                class="type-tab px-3 py-1.5 rounded-lg text-sm font-semibold transition-colors {{ $type === '' ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
            {{ $typeLabel }}
        </button>
    @endforeach
</div>

{{-- ─── Filters ─────────────────────────────────────────────────────────────── --}}
<x-card class="mb-5">
    <form id="filter-form" class="flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[180px]">
            <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.marketers.search') }}</label>
            <input type="text" id="search-input" class="form-input w-full text-sm" placeholder="{{ __('admin.marketers.search_placeholder') }}">
        </div>
        <div class="w-36">
            <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.status') }}</label>
            <select id="filter-status" class="form-input w-full text-sm">
                <option value="">{{ __('admin.marketers.all_statuses') }}</option>
                <option value="pending">{{ __('admin.marketers.pending') }}</option>
                <option value="active">{{ __('admin.marketers.active') }}</option>
                <option value="suspended">{{ __('admin.marketers.suspended') }}</option>
                <option value="rejected">{{ __('admin.marketers.rejected') }}</option>
            </select>
        </div>
        <div class="w-44">
            <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.marketers.country') }}</label>
            <select id="filter-country" class="form-input w-full text-sm">
                <option value="">{{ __('admin.marketers.all_countries') }}</option>
                @foreach($countries as $country)
                    <option value="{{ $country->id }}">{{ $country->name_en }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-36">
            <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('common.from') }}</label>
            <input type="date" id="filter-date-from" class="form-input w-full text-sm">
        </div>
        <div class="w-36">
            <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('common.to') }}</label>
            <input type="date" id="filter-date-to" class="form-input w-full text-sm">
        </div>
        <button type="button" id="clear-filters" class="btn btn-ghost btn-sm self-end">{{ __('admin.marketers.reset') }}</button>
    </form>
</x-card>

{{-- ─── DataTable ───────────────────────────────────────────────────────────── --}}
<x-card>
    <div class="overflow-x-auto">
        <table id="marketers-table" class="w-full text-sm" style="width:100%">
            <thead id="marketers-table-head">
                <tr>
                    <th>{{ __('admin.marketers.photo') }}</th>
                    <th>{{ __('admin.marketers.name_email') }}</th>
                    <th>{{ __('admin.marketers.country') }}</th>
                    <th>{{ __('admin.marketers.type') }}</th>
                    <th>{{ __('admin.marketers.clicks') }} / {{ __('admin.marketers.deals') }}</th>
                    <th>{{ __('admin.marketers.earnings') }}</th>
                    <th>{{ __('admin.status') }}</th>
                    <th>{{ __('admin.marketers.actions') }}</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</x-card>

{{-- ─── Create Marketer Modal ───────────────────────────────────────────────── --}}
<div id="create-marketer-modal" class="modal-backdrop hidden">
    <div class="modal-box max-w-2xl">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">{{ __('admin.marketers.add_marketer_title') }}</h3>
                <p class="text-xs text-gray-500 mt-0.5">{{ __('admin.marketers.add_marketer_desc') }}</p>
            </div>
            <button type="button" data-modal-close class="text-gray-400 hover:text-gray-600 text-2xl leading-none p-1">&times;</button>
        </div>
        <form id="create-marketer-form">
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2 grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">{{ __('admin.marketers.full_name') }} <span class="text-red-500">*</span></label>
                        <input type="text" name="name" class="form-input w-full" placeholder="{{ __('admin.marketers.name_placeholder') }}" required autocomplete="off">
                    </div>
                    <div>
                        <label class="form-label">{{ __('admin.marketers.email_label') }} <span class="text-red-500">*</span></label>
                        <input type="email" name="email" class="form-input w-full" placeholder="{{ __('admin.marketers.email_placeholder') }}" required autocomplete="off">
                    </div>
                </div>
                <div>
                    <label class="form-label">{{ __('admin.marketers.password') }} <span class="text-red-500">*</span></label>
                    <input type="password" name="password" class="form-input w-full" placeholder="{{ __('admin.marketers.password_placeholder') }}" required autocomplete="new-password" minlength="8">
                </div>
                <div>
                    <label class="form-label">{{ __('admin.marketers.phone_label') }}</label>
                    <input type="text" name="phone" class="form-input w-full" placeholder="{{ __('admin.marketers.phone_placeholder') }}">
                </div>
                <div>
                    <label class="form-label">{{ __('admin.marketers.type') }} <span class="text-red-500">*</span></label>
                    <select name="type" class="form-input w-full" required>
                        <option value="">{{ __('admin.marketers.select_type') }}</option>
                        <option value="influencer">{{ __('admin.marketers.influencer') }}</option>
                        <option value="celebrity">{{ __('admin.marketers.celebrity') }}</option>
                        <option value="affiliate">{{ __('admin.marketers.affiliate') }}</option>
                        <option value="brand_ambassador">{{ __('admin.marketers.brand_ambassador') }}</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">{{ __('admin.marketers.country_label') }}</label>
                    <select name="country_id" class="form-input w-full">
                        <option value="">{{ __('admin.marketers.select_country') }}</option>
                        @foreach($countries as $country)
                            <option value="{{ $country->id }}">{{ $country->name_en }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">{{ __('admin.marketers.niche_label') }}</label>
                    <input type="text" name="niche" class="form-input w-full" placeholder="{{ __('admin.marketers.niche_placeholder') }}">
                </div>
                <div>
                    <label class="form-label">{{ __('admin.marketers.followers_count') }}</label>
                    <input type="number" name="followers_count" class="form-input w-full" min="0" placeholder="0">
                </div>
                <div>
                    <label class="form-label">{{ __('admin.marketers.commission_rate') }}</label>
                    <input type="number" name="commission_rate" class="form-input w-full" step="0.01" min="0" max="100" placeholder="{{ __('admin.marketers.commission_rate_placeholder') }}">
                </div>
                <div>
                    <label class="form-label">{{ __('admin.status') }}</label>
                    <select name="status" class="form-input w-full">
                        <option value="active" selected>{{ __('admin.marketers.status_active_approved') }}</option>
                        <option value="pending">{{ __('admin.marketers.status_pending_approval') }}</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">{{ __('admin.marketers.whatsapp_number') }}</label>
                    <input type="text" name="whatsapp_number" class="form-input w-full" placeholder="{{ __('admin.marketers.phone_placeholder') }}">
                </div>
                <div>
                    <label class="form-label">{{ __('admin.marketers.instagram_url') }}</label>
                    <input type="url" name="social_instagram" class="form-input w-full" placeholder="https://instagram.com/…">
                </div>
                <div>
                    <label class="form-label">{{ __('admin.marketers.tiktok_url') }}</label>
                    <input type="url" name="social_tiktok" class="form-input w-full" placeholder="https://tiktok.com/@…">
                </div>
                <div class="col-span-2">
                    <label class="form-label">{{ __('admin.marketers.bio') }}</label>
                    <textarea name="bio" rows="2" class="form-input w-full" placeholder="{{ __('admin.marketers.bio_placeholder') }}"></textarea>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-5 pt-4 border-t border-gray-100">
                <button type="button" data-modal-close class="btn btn-ghost btn-sm">{{ __('common.cancel') }}</button>
                <button type="submit" id="create-marketer-submit" class="btn btn-primary btn-sm">{{ __('admin.marketers.create_marketer') }}</button>
            </div>
        </form>
    </div>
</div>

{{-- ─── Reject Modal ────────────────────────────────────────────────────────── --}}
<div id="reject-marketer-modal" class="modal" style="display:none;">
    <div class="modal-box">
        <h3 class="font-bold text-lg mb-4">{{ __('admin.marketers.reject_marketer_title') }}</h3>
        <input type="hidden" id="reject-marketer-id">
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.marketers.reason') }}</label>
            <textarea id="reject-reason" rows="3" class="form-input w-full text-sm"
                placeholder="{{ __('admin.marketers.reject_reason_placeholder') }}"></textarea>
        </div>
        <div class="flex gap-3 justify-end">
            <button type="button" id="cancel-reject-marketer" class="btn btn-ghost btn-sm">{{ __('common.cancel') }}</button>
            <button type="button" id="confirm-reject-marketer" class="btn btn-danger btn-sm">{{ __('admin.marketers.reject') }}</button>
        </div>
    </div>
</div>

@endsection

@push('scripts')

<script>
    window.TRANSLATIONS = window.TRANSLATIONS || {};
    Object.assign(window.TRANSLATIONS, {
        approveMarketerTitle: @json(__('admin.marketers.approve_marketer_confirm_title')),
        approveMarketerText: @json(__('admin.marketers.approve_marketer_confirm_text')),
        approve: @json(__('admin.marketers.approve')),
        suspendMarketerTitle: @json(__('admin.marketers.suspend_marketer_confirm_title')),
        suspend: @json(__('admin.marketers.suspend')),
        activateMarketerTitle: @json(__('admin.marketers.activate_marketer_confirm_title')),
        activate: @json(__('admin.marketers.activate')),
        pleaseEnterReason: @json(__('admin.marketers.please_enter_reason')),
        errorGeneric: @json(__('admin.marketers.error_generic')),
        creating: @json(__('admin.marketers.creating')),
        createMarketer: @json(__('admin.marketers.create_marketer')),
        failedCreateMarketer: @json(__('admin.marketers.failed_create_marketer')),
    });
</script>
<script type="module">
document.addEventListener('DOMContentLoaded', function () {
    let currentType = '';
    let table = null;

    // The metric columns (indices 3-5, 0-based) differ by type-view; the rest
    // of the table (photo, name/email, country, status, actions) is constant.
    const HEAD_METRIC_COLS = {
        affiliate:   ['{{ __('admin.marketers.clicks') }}', '{{ __('admin.marketers.conv') }}', '{{ __('admin.marketers.conv_rate') }}', '{{ __('admin.marketers.earnings') }}'],
        influencer:  ['{{ __('admin.marketers.active_deals') }}', '{{ __('admin.marketers.pending_deliverables') }}', '{{ __('admin.marketers.total_paid_out') }}', '{{ __('admin.marketers.earnings') }}'],
        all:         ['{{ __('admin.marketers.type') }}', '{{ __('admin.marketers.clicks') }} / {{ __('admin.marketers.deals') }}', '{{ __('admin.marketers.earnings') }}'],
    };

    function viewModeFor(type) {
        if (type === 'affiliate') return 'affiliate';
        if (['influencer', 'celebrity', 'brand_ambassador'].includes(type)) return 'influencer';
        return 'all';
    }

    function buildTable(viewMode) {
        if (table) { table.destroy(); }

        const metricHeads = HEAD_METRIC_COLS[viewMode];
        const $head = $('<tr>')
            .append('<th>{{ __('admin.marketers.photo') }}</th>')
            .append('<th>{{ __('admin.marketers.name_email') }}</th>')
            .append('<th>{{ __('admin.marketers.country') }}</th>');
        metricHeads.forEach(label => $head.append('<th>' + label + '</th>'));
        $head.append('<th>{{ __('admin.status') }}</th>')
             .append('<th>{{ __('admin.marketers.actions') }}</th>');
        $('#marketers-table-head').empty().append($head);

        const metricColumnDefs = metricHeads.map(() => ({}));

        table = $('#marketers-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url:  '{{ route('admin.marketers.all.datatable') }}',
                type: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                data: function (d) {
                    d.type           = currentType;
                    d.filter_type    = currentType;
                    d.filter_status  = $('#filter-status').val();
                    d.filter_country = $('#filter-country').val();
                    d.date_from      = $('#filter-date-from').val();
                    d.date_to        = $('#filter-date-to').val();
                    d.search         = { value: $('#search-input').val() };
                }
            },
            columns: [
                { orderable: false, width: '40px' },
                {},
                {},
                ...metricColumnDefs,
                {},
                { orderable: false, width: '140px' },
            ],
            pageLength: 25,
            order: [[1, 'asc']],
        });
    }

    buildTable('all');

    // Type tab switching
    $(document).on('click', '.type-tab', function () {
        $('.type-tab').removeClass('bg-white text-gray-800 shadow-sm').addClass('text-gray-500');
        $(this).addClass('bg-white text-gray-800 shadow-sm').removeClass('text-gray-500');
        currentType = $(this).data('type');
        buildTable(viewModeFor(currentType));
    });

    // Filters
    $('#search-input').on('keyup', debounce(() => table.ajax.reload(), 350));
    $('#filter-status, #filter-country, #filter-date-from, #filter-date-to').on('change', () => table.ajax.reload());
    $('#clear-filters').on('click', function () {
        $('#search-input').val('');
        $('#filter-status, #filter-country').val('');
        $('#filter-date-from, #filter-date-to').val('');
        currentType = '';
        $('.type-tab[data-type=""]').click();
        buildTable('all');
    });

    // Approve
    $(document).on('click', '.btn-approve-marketer', function () {
        const id = $(this).data('id');
        window.confirmDialog({
            title: window.TRANSLATIONS.approveMarketerTitle,
            text: window.TRANSLATIONS.approveMarketerText,
            confirmButtonText: window.TRANSLATIONS.approve,
        }).then(confirmed => {
            if (!confirmed) return;
            $.post('{{ url('/marketers') }}/' + id + '/approve', { _token: '{{ csrf_token() }}' })
                .done(r => { window.Toast.success(r.message); table.ajax.reload(); })
                .fail(xhr => window.Toast.error(xhr.responseJSON?.message || window.TRANSLATIONS.errorGeneric));
        });
    });

    // Reject
    $(document).on('click', '.btn-reject-marketer', function () {
        $('#reject-marketer-id').val($(this).data('id'));
        $('#reject-reason').val('');
        $('#reject-marketer-modal').show();
    });
    $('#cancel-reject-marketer').on('click', () => $('#reject-marketer-modal').hide());
    $('#confirm-reject-marketer').on('click', function () {
        const id = $('#reject-marketer-id').val();
        const reason = $('#reject-reason').val().trim();
        if (!reason) { window.Toast.warning(window.TRANSLATIONS.pleaseEnterReason); return; }
        $.post('{{ url('/marketers') }}/' + id + '/reject', { _token: '{{ csrf_token() }}', reason })
            .done(r => { window.Toast.success(r.message); $('#reject-marketer-modal').hide(); table.ajax.reload(); })
            .fail(xhr => window.Toast.error(xhr.responseJSON?.message || window.TRANSLATIONS.errorGeneric));
    });

    // Suspend
    $(document).on('click', '.btn-suspend-marketer', function () {
        const id = $(this).data('id');
        window.confirmDialog({
            title: window.TRANSLATIONS.suspendMarketerTitle,
            confirmButtonText: window.TRANSLATIONS.suspend,
        }).then(confirmed => {
            if (!confirmed) return;
            $.post('{{ url('/marketers') }}/' + id + '/suspend', { _token: '{{ csrf_token() }}' })
                .done(r => { window.Toast.success(r.message); table.ajax.reload(); })
                .fail(xhr => window.Toast.error(xhr.responseJSON?.message || window.TRANSLATIONS.errorGeneric));
        });
    });

    // Activate
    $(document).on('click', '.btn-activate-marketer', function () {
        const id = $(this).data('id');
        window.confirmDialog({
            title: window.TRANSLATIONS.activateMarketerTitle,
            confirmButtonText: window.TRANSLATIONS.activate,
        }).then(confirmed => {
            if (!confirmed) return;
            $.post('{{ url('/marketers') }}/' + id + '/activate', { _token: '{{ csrf_token() }}' })
                .done(r => { window.Toast.success(r.message); table.ajax.reload(); })
                .fail(xhr => window.Toast.error(xhr.responseJSON?.message || window.TRANSLATIONS.errorGeneric));
        });
    });

    function debounce(fn, ms) {
        let t;
        return function (...args) { clearTimeout(t); t = setTimeout(() => fn.apply(this, args), ms); };
    }

    // ── Create Marketer ──────────────────────────────────────────────────────
    $('#create-marketer-btn').on('click', function () {
        $('#create-marketer-form')[0].reset();
        $('#create-marketer-modal').modal('open');
    });

    $('#create-marketer-form').on('submit', function (e) {
        e.preventDefault();
        const $btn = $('#create-marketer-submit');
        $btn.prop('disabled', true).text(window.TRANSLATIONS.creating);

        $.ajax({
            url:  '{{ route('admin.marketers.all.store') }}',
            type: 'POST',
            data: $(this).serialize() + '&_token={{ csrf_token() }}',
        })
        .done(function (res) {
            window.Toast.success(res.message);
            $('#create-marketer-modal').modal('close');
            if (res.redirect) {
                setTimeout(() => window.location.href = res.redirect, 800);
            } else {
                table.ajax.reload();
            }
        })
        .fail(function (xhr) {
            if (xhr.status === 422) {
                const errors = xhr.responseJSON?.errors ?? {};
                Object.values(errors).flat().forEach(m => window.Toast.error(m));
            } else {
                window.Toast.error(xhr.responseJSON?.message ?? window.TRANSLATIONS.failedCreateMarketer);
            }
        })
        .always(() => $btn.prop('disabled', false).text(window.TRANSLATIONS.createMarketer));
    });
}, { once: true });
</script>
@endpush
