@extends('layouts.admin')

@push('scripts')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/column-renderers.js'])
@endpush

@section('title', $marketer->name)

@section('content')

{{-- ─── Breadcrumb ──────────────────────────────────────────────────────────── --}}

{{-- ─── Profile Card ────────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-2xl border border-gray-200 p-6">
        <div class="flex items-center gap-4 mb-5">
            @if($marketer->profile_photo_path)
                <img src="{{ asset('storage/' . $marketer->profile_photo_path) }}"
                     class="w-16 h-16 rounded-full object-cover">
            @else
                <div class="w-16 h-16 rounded-full bg-purple-100 text-purple-700 flex items-center justify-center text-2xl font-bold">
                    {{ strtoupper(substr($marketer->name, 0, 1)) }}
                </div>
            @endif
            <div>
                <h2 class="text-xl font-bold text-gray-900">{{ $marketer->name }}</h2>
                <span class="badge badge-{{ $marketer->status_color }}">{{ $marketer->status->label() }}</span>
                <span class="badge badge-secondary ml-1">{{ $marketer->type_label }}</span>
            </div>
        </div>

        <div class="space-y-2 text-sm">
            @foreach([
                __('admin.marketers.email_label')   => $marketer->email,
                __('admin.marketers.phone_label')   => $marketer->phone ?? '—',
                __('admin.marketers.country_label') => $marketer->country?->name_en ?? '—',
                __('admin.marketers.niche_label')   => $marketer->niche ?? '—',
                __('admin.marketers.ref_code')       => $marketer->referral_code,
                __('admin.marketers.joined')         => $marketer->created_at->format('d M Y'),
            ] as $label => $value)
                <div class="flex justify-between gap-2">
                    <span class="text-gray-400">{{ $label }}</span>
                    <span class="font-medium text-gray-700 text-end">{{ $value }}</span>
                </div>
            @endforeach
        </div>

        {{-- Social links --}}
        @php
            $socials = [
                'instagram' => $marketer->social_instagram,
                'tiktok'    => $marketer->social_tiktok,
                'youtube'   => $marketer->social_youtube,
                'twitter'   => $marketer->social_twitter,
                'facebook'  => $marketer->social_facebook,
            ];
        @endphp
        @if(array_filter($socials))
            <div class="flex gap-2 mt-4 pt-4 border-t border-gray-100">
                @foreach($socials as $platform => $handle)
                    @if($handle)
                        <a href="{{ $handle }}" target="_blank"
                           class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg px-2 py-1 transition-colors capitalize">
                            {{ $platform }}
                        </a>
                    @endif
                @endforeach
            </div>
        @endif

        {{-- Action buttons --}}
        <div class="flex gap-2 mt-5 pt-4 border-t border-gray-100">
            @if($marketer->status === \App\Enums\MarketerStatus::Pending)
                <button type="button" class="btn btn-success btn-sm flex-1" id="btn-approve">{{ __('admin.marketers.approve') }}</button>
                <button type="button" class="btn btn-danger btn-sm flex-1" id="btn-reject">{{ __('admin.marketers.reject') }}</button>
            @elseif($marketer->status === \App\Enums\MarketerStatus::Active)
                <button type="button" class="btn btn-warning btn-sm flex-1" id="btn-suspend">{{ __('admin.marketers.suspend') }}</button>
            @elseif($marketer->status === \App\Enums\MarketerStatus::Suspended)
                <button type="button" class="btn btn-success btn-sm flex-1" id="btn-activate">{{ __('admin.marketers.activate') }}</button>
            @endif
        </div>
    </div>

    {{-- Stats Column --}}
    <div class="lg:col-span-2 grid grid-cols-2 sm:grid-cols-3 gap-4 content-start">
        @foreach([
            [__('admin.marketers.campaigns'),        $stats['total_campaigns'],    'total'],
            [__('admin.marketers.active_campaigns'), $stats['active_campaigns'],   'active'],
            [__('admin.marketers.conversions'),      $stats['total_conversions'],  'conversions'],
            [__('admin.marketers.followers'),        number_format($marketer->followers_count), 'followers'],
            [__('admin.marketers.total_clicks'),     number_format($marketer->total_clicks),    'clicks'],
            [__('admin.marketers.comm_rate'),        $marketer->commission_rate . '%',          'rate'],
        ] as [$label, $value, $key])
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">{{ $label }}</p>
                <p class="mt-1 text-2xl font-bold text-gray-800">{{ $value }}</p>
            </div>
        @endforeach

        <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4">
            <p class="text-xs font-medium text-yellow-600 uppercase tracking-wide">{{ __('admin.marketers.pending_earnings') }}</p>
            @forelse($stats['pending_by_currency'] as $currency => $cents)
                <p class="mt-1 text-xl font-bold text-yellow-700">{{ number_format($cents / 100, 2) }} {{ $currency }}</p>
            @empty
                <p class="mt-1 text-2xl font-bold text-yellow-700">—</p>
            @endforelse
        </div>
        <div class="bg-green-50 border border-green-200 rounded-xl p-4">
            <p class="text-xs font-medium text-green-600 uppercase tracking-wide">{{ __('admin.marketers.paid_earnings') }}</p>
            @forelse($stats['paid_by_currency'] as $currency => $cents)
                <p class="mt-1 text-xl font-bold text-green-700">{{ number_format($cents / 100, 2) }} {{ $currency }}</p>
            @empty
                <p class="mt-1 text-2xl font-bold text-green-700">—</p>
            @endforelse
        </div>
    </div>
</div>

{{-- ─── Tabs ────────────────────────────────────────────────────────────────── --}}
<div x-data="{ tab: 'campaigns' }"
     x-effect="document.dispatchEvent(new CustomEvent('marketer-tab-change', { detail: tab }))">

    <div class="flex gap-1 bg-gray-100 rounded-xl p-1 mb-5 flex-wrap">
        @foreach([
            'campaigns'     => __('admin.marketers.tab_campaigns'),
            'conversions'   => __('admin.marketers.tab_conversions'),
            'tiers'         => '📈 ' . __('admin.marketers.tab_tiers'),
            'samples'       => '📦 ' . __('admin.marketers.tab_samples'),
            'secret_promos' => '🔒 ' . __('admin.marketers.tab_secret_promos'),
        ] as $key => $label)
            <button type="button" @click="tab = '{{ $key }}'"
                    class="px-4 py-1.5 rounded-lg text-sm font-semibold transition-colors"
                    :class="tab === '{{ $key }}' ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-500 hover:text-gray-700'">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Campaigns DataTable --}}
    <div x-show="tab === 'campaigns'">
        <x-card>
            <div class="overflow-x-auto">
            <table id="marketer-campaigns-table" class="w-full text-sm" style="width:100%">
                <thead>
                    <tr>
                        <th>{{ __('admin.marketers.campaign') }}</th>
                        <th>{{ __('admin.marketers.type') }}</th>
                        <th>{{ __('admin.status') }}</th>
                        <th>{{ __('admin.marketers.clicks') }}</th>
                        <th>{{ __('admin.marketers.conv') }}</th>
                        <th>{{ __('admin.marketers.revenue') }}</th>
                        <th>{{ __('admin.marketers.created') }}</th>
                        <th>{{ __('admin.marketers.actions') }}</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
            </div>
        </x-card>
    </div>

    {{-- Conversions DataTable --}}
    <div x-show="tab === 'conversions'">
        <x-card>
            <div class="overflow-x-auto">
            <table id="marketer-conversions-table" class="w-full text-sm" style="width:100%">
                <thead>
                    <tr>
                        <th>{{ __('admin.marketers.marketer') }}</th>
                        <th>{{ __('admin.marketers.campaign') }}</th>
                        <th>{{ __('admin.marketers.order_value') }}</th>
                        <th>{{ __('admin.marketers.commission') }}</th>
                        <th>{{ __('admin.status') }}</th>
                        <th>{{ __('admin.marketers.date') }}</th>
                        <th>{{ __('admin.marketers.bulk') }}</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
            </div>
        </x-card>
    </div>

    {{-- Commission Tiers --}}
    <div x-show="tab === 'tiers'">
        <div class="bg-white rounded-2xl border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h3 class="text-lg font-bold text-gray-800">{{ __('admin.marketers.commission_tiers_title') }}</h3>
                    <p class="text-sm text-gray-500 mt-1">{{ __('admin.marketers.current_sales') }}: <strong>{{ $stats['total_conversions'] }}</strong></p>
                </div>
                <a href="{{ route('admin.marketers.all.tiers.show', $marketer) }}" class="btn btn-primary btn-sm">{{ __('admin.marketers.edit_tiers') }}</a>
            </div>
            @php $tiers = $marketer->commissionTiers()->whereNull('campaign_id')->orderBy('tier_order')->get(); @endphp
            @if($tiers->isEmpty())
                <p class="text-sm text-gray-400 italic">{{ __('admin.marketers.no_tiers_configured', ['rate' => $marketer->commission_rate]) }}</p>
            @else
                <div class="space-y-3">
                    @foreach($tiers as $tier)
                        @php $isCurrent = $stats['total_conversions'] >= $tier->min_sales_count && ($tier->max_sales_count === null || $stats['total_conversions'] <= $tier->max_sales_count); @endphp
                        <div class="flex items-center gap-4 p-4 rounded-xl {{ $isCurrent ? 'bg-primary-50 border-2 border-primary-300' : 'bg-gray-50 border border-gray-200' }}">
                            <div class="w-8 h-8 rounded-full {{ $isCurrent ? 'bg-primary-500 text-white' : 'bg-gray-200 text-gray-600' }} flex items-center justify-center font-bold text-sm">{{ $tier->tier_order }}</div>
                            <div class="flex-1">
                                <p class="text-sm font-semibold">
                                    {{ __('admin.marketers.sales_range', ['min' => number_format($tier->min_sales_count), 'max' => $tier->max_sales_count ? number_format($tier->max_sales_count) : __('admin.marketers.unlimited_sales')]) }}
                                </p>
                                <p class="text-xs text-gray-500">{{ __('admin.marketers.commission') }}: <strong>{{ $tier->commission_rate }}%</strong></p>
                            </div>
                            @if($isCurrent)
                                <span class="badge badge-primary">{{ __('admin.marketers.current_tier') }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Sample Requests DataTable --}}
    <div x-show="tab === 'samples'">
        <x-card>
            <div class="overflow-x-auto">
            <table id="marketer-samples-table" class="w-full text-sm" style="width:100%">
                <thead>
                    <tr>
                        <th>{{ __('admin.marketers.sample_vendor') }}</th>
                        <th>{{ __('admin.status') }}</th>
                        <th>{{ __('admin.marketers.sample_date') }}</th>
                        <th>{{ __('admin.marketers.sample_actions') }}</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
            </div>
        </x-card>
    </div>

    {{-- Secret Promotions DataTable --}}
    <div x-show="tab === 'secret_promos'">
        <x-card>
            <div class="overflow-x-auto">
            <table id="marketer-secret-promos-table" class="w-full text-sm" style="width:100%">
                <thead>
                    <tr>
                        <th>{{ __('admin.marketers.vendor') }} / {{ __('admin.marketers.product') }}</th>
                        <th>{{ __('admin.marketers.total_pct') }}</th>
                        <th>{{ __('admin.status') }}</th>
                        <th>{{ __('admin.marketers.valid_until') }}</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
            </div>
        </x-card>
    </div>

</div>

@endsection

@push('scripts')
<script>
    window.TRANSLATIONS = window.TRANSLATIONS || {};
    Object.assign(window.TRANSLATIONS, {
        approveMarketerConfirm: @json(__('admin.marketers.approve_campaign_confirm')),
        approve: @json(__('admin.marketers.approve')),
        rejectionReasonPrompt: @json(__('admin.marketers.rejection_reason_prompt')),
        suspendMarketerConfirm: @json(__('admin.marketers.suspend_marketer_confirm_title')),
        errorGeneric: @json(__('admin.marketers.error_generic')),
    });
</script>
<script type="module">
$(function () {
    const markId = '{{ $marketer->id }}';
    const tok    = '{{ csrf_token() }}';

    const dtConfig = (url, columns, order) => ({
        processing: true,
        serverSide: true,
        ajax: { url, type: 'POST', headers: { 'X-CSRF-TOKEN': tok } },
        columns,
        order,
        pageLength: 10,
    });

    const inited = {};

    function initTab(tab) {
        if (inited[tab]) return;
        inited[tab] = true;

        if (tab === 'campaigns') {
            $('#marketer-campaigns-table').DataTable(dtConfig(
                '{{ route('admin.marketers.all.marketer-campaigns.datatable', $marketer->id) }}',
                [{}, {}, {}, {}, {}, {}, {}, { orderable: false }],
                [[6, 'desc']]
            ));
        } else if (tab === 'conversions') {
            $('#marketer-conversions-table').DataTable(dtConfig(
                '{{ route('admin.marketers.all.marketer-conversions.datatable', $marketer->id) }}',
                [{}, {}, {}, {}, {}, {}, { orderable: false }],
                [[5, 'desc']]
            ));
        } else if (tab === 'samples') {
            $('#marketer-samples-table').DataTable(dtConfig(
                '{{ route('admin.marketers.all.marketer-samples.datatable', $marketer->id) }}',
                [{}, {}, {}, { orderable: false }],
                [[2, 'desc']]
            ));
        } else if (tab === 'secret_promos') {
            $('#marketer-secret-promos-table').DataTable(dtConfig(
                '{{ route('admin.marketers.all.marketer-secret-promotions.datatable', $marketer->id) }}',
                [{}, {}, {}, {}],
                [[3, 'desc']]
            ));
        }
    }

    // Initialize the default tab on load, then lazily init others on switch.
    initTab('campaigns');

    document.addEventListener('marketer-tab-change', function (e) {
        initTab(e.detail);
    });

    // ── Approve / Reject / Suspend / Activate ─────────────────────────────────
    $('#btn-approve').on('click', function () {
        window.confirmDialog({ title: window.TRANSLATIONS.approveMarketerConfirm, confirmText: window.TRANSLATIONS.approve, onConfirm: () => {
            $.post('/marketers/' + markId + '/approve', { _token: tok })
                .done(r => { window.Toast.success(r.message); setTimeout(() => location.reload(), 1200); })
                .fail(xhr => window.Toast.error(xhr.responseJSON?.message || window.TRANSLATIONS.errorGeneric));
        }});
    });
    $('#btn-reject').on('click', function () {
        const reason = prompt(window.TRANSLATIONS.rejectionReasonPrompt);
        if (!reason) return;
        $.post('/marketers/' + markId + '/reject', { _token: tok, reason })
            .done(r => { window.Toast.success(r.message); setTimeout(() => location.reload(), 1200); })
            .fail(xhr => window.Toast.error(xhr.responseJSON?.message || window.TRANSLATIONS.errorGeneric));
    });
    $('#btn-suspend').on('click', function () {
        window.confirmDialog({ title: window.TRANSLATIONS.suspendMarketerConfirm, onConfirm: () => {
            $.post('/marketers/' + markId + '/suspend', { _token: tok })
                .done(r => { window.Toast.success(r.message); setTimeout(() => location.reload(), 1200); })
                .fail(xhr => window.Toast.error(xhr.responseJSON?.message || window.TRANSLATIONS.errorGeneric));
        }});
    });
    $('#btn-activate').on('click', function () {
        $.post('/marketers/' + markId + '/activate', { _token: tok })
            .done(r => { window.Toast.success(r.message); setTimeout(() => location.reload(), 1200); })
            .fail(xhr => window.Toast.error(xhr.responseJSON?.message || window.TRANSLATIONS.errorGeneric));
    });

    // ── Sample datatable actions (delegated) ──────────────────────────────────
    $(document).on('click', '.btn-approve-sample', function () {
        const id = $(this).data('id');
        $.post('{{ url('marketer-samples') }}/' + id + '/approve', { _token: tok })
            .done(r => { window.Toast.success(r.message); $('#marketer-samples-table').DataTable().ajax.reload(); })
            .fail(xhr => window.Toast.error(xhr.responseJSON?.message || window.TRANSLATIONS.errorGeneric));
    });
    $(document).on('click', '.btn-dispatch-sample', function () {
        const id = $(this).data('id');
        $.post('{{ url('marketer-samples') }}/' + id + '/dispatch', { _token: tok })
            .done(r => { window.Toast.success(r.message); $('#marketer-samples-table').DataTable().ajax.reload(); })
            .fail(xhr => window.Toast.error(xhr.responseJSON?.message || window.TRANSLATIONS.errorGeneric));
    });
});
</script>
@endpush
