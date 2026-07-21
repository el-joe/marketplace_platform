@extends('layouts.admin')

@section('title', $campaign->name)

@section('content')

@php
    use App\Models\ClassifiedListing;
    use App\Models\TravelPackage;
    use App\Models\Vendor;

    $campaignable = $campaign->campaignable;
    $targetType   = match ($campaign->campaignable_type) {
        ClassifiedListing::class => 'classified',
        TravelPackage::class     => 'travel',
        Vendor::class            => 'vendor',
        default                  => null,
    };
@endphp

{{-- ─── Page Header ─────────────────────────────────────────────────────────── --}}
<div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ $campaign->name }}</h1>
        <p class="text-sm text-gray-500 mt-0.5">
            {{ $campaign->campaign_type->label() }}
            &nbsp;·&nbsp;
            <a href="{{ route('admin.marketers.all.show', $campaign->marketer_id) }}" class="text-primary-600 hover:underline">
                {{ $campaign->marketer->name }}
            </a>
        </p>
    </div>

    @if (in_array($campaign->status, [\App\Enums\MarketerCampaignStatus::Draft, \App\Enums\MarketerCampaignStatus::PendingReview], true))
        <div class="flex gap-2">
            <button type="button"
                class="btn btn-success btn-sm btn-approve-campaign"
                data-id="{{ $campaign->id }}">
                {{ __('admin.marketers.approve') }}
            </button>
            <button type="button"
                class="btn btn-danger btn-sm btn-reject-campaign"
                data-id="{{ $campaign->id }}">
                {{ __('admin.marketers.reject') }}
            </button>
        </div>
    @else
        <span class="badge badge-{{ $campaign->status_color }} text-sm px-3 py-1">
            {{ $campaign->status->label() }}
        </span>
    @endif
</div>

@if ($campaign->pause_requested_at)
<div class="mb-6 flex items-center justify-between gap-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3">
    <p class="text-sm text-amber-800">
        {{ __('admin.marketers.pause_requested_on', ['date' => $campaign->pause_requested_at->format('d M Y, H:i')]) }}
    </p>
    <div class="flex gap-2 flex-shrink-0">
        <button type="button" class="btn btn-warning btn-sm btn-approve-pause"
            data-url="{{ route('admin.marketers.campaigns.pause-request.approve', $campaign) }}">
            {{ __('admin.marketers.approve_pause') }}
        </button>
        <button type="button" class="btn btn-ghost btn-sm btn-dismiss-pause"
            data-url="{{ route('admin.marketers.campaigns.pause-request.dismiss', $campaign) }}">
            {{ __('admin.marketers.dismiss') }}
        </button>
    </div>
</div>
@endif

@if ($campaign->status === \App\Enums\MarketerCampaignStatus::Rejected && $campaign->rejection_reason)
<div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3">
    <p class="text-sm text-red-700">
        <span class="font-semibold">{{ __('admin.marketers.rejection_reason') }}:</span> {{ $campaign->rejection_reason }}
    </p>
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- ─── Left: Campaign Details ───────────────────────────────────────────── --}}
    <div class="lg:col-span-2 space-y-6">

        {{-- Campaign Target Block ------------------------------------------------}}
        @if ($campaignable)
        <x-card>
            <h2 class="text-base font-semibold text-gray-800 mb-4">{{ __('admin.marketers.campaign_target') }}</h2>

            @if ($targetType === 'classified')
            {{-- Classified Listing --}}
            <div class="flex items-start gap-3 p-4 rounded-lg bg-gray-50 border border-gray-200">
                <div class="flex-shrink-0">
                    <span class="badge badge-secondary">{{ __('admin.marketers.classified') }}</span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-gray-900 truncate">{{ $campaignable->title_en }}</p>
                    @if ($campaignable->title_ar)
                        <p class="text-sm text-gray-500 truncate">{{ $campaignable->title_ar }}</p>
                    @endif
                    <div class="mt-2 flex flex-wrap gap-4 text-sm text-gray-600">
                        @if ($campaignable->price ?? null)
                            <span><span class="text-gray-400">{{ __('admin.marketers.price') }}:</span> {{ number_format($campaignable->price, 2) }} {{ $campaignable->currency ?? '' }}</span>
                        @endif
                        <span><span class="text-gray-400">{{ __('admin.status') }}:</span>
                            <span class="font-medium {{ $campaignable->status === \App\Enums\ClassifiedListingStatus::Active ? 'text-green-600' : 'text-gray-500' }}">
                                {{ $campaignable->status->label() }}
                            </span>
                        </span>
                        @if ($campaignable->seller)
                            <span><span class="text-gray-400">{{ __('admin.marketers.seller') }}:</span> {{ $campaignable->seller->name ?? $campaignable->seller->store_name ?? '—' }}</span>
                        @endif
                    </div>
                </div>
            </div>

            @elseif ($targetType === 'travel')
            {{-- Travel Package --}}
            <div class="flex items-start gap-3 p-4 rounded-lg bg-gray-50 border border-gray-200">
                <div class="flex-shrink-0">
                    <span class="badge badge-primary">{{ __('admin.marketers.travel') }}</span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-gray-900 truncate">{{ $campaignable->title_en }}</p>
                    @if ($campaignable->title_ar)
                        <p class="text-sm text-gray-500 truncate">{{ $campaignable->title_ar }}</p>
                    @endif
                    <div class="mt-2 flex flex-wrap gap-4 text-sm text-gray-600">
                        <span><span class="text-gray-400">{{ __('admin.marketers.price') }}:</span> {{ $campaignable->priceFormatted() }}</span>
                        @if ($campaignable->destination_city || $campaignable->destination_country)
                            <span><span class="text-gray-400">{{ __('admin.marketers.destination') }}:</span> {{ implode(', ', array_filter([$campaignable->destination_city, $campaignable->destination_country])) }}</span>
                        @endif
                        @if ($campaignable->duration_days)
                            <span><span class="text-gray-400">{{ __('admin.duration') }}:</span> {{ __('admin.marketers.duration_days', ['n' => $campaignable->duration_days]) }}</span>
                        @endif
                        <span><span class="text-gray-400">{{ __('admin.status') }}:</span>
                            <span class="font-medium {{ $campaignable->status === \App\Enums\TravelPackageStatus::Active ? 'text-green-600' : 'text-gray-500' }}">
                                {{ ucfirst($campaignable->status->value) }}
                            </span>
                        </span>
                        @if ($campaignable->agency)
                            <span><span class="text-gray-400">{{ __('admin.marketers.agency') }}:</span> {{ $campaignable->agency->name }}</span>
                        @endif
                    </div>
                </div>
            </div>

            @elseif ($targetType === 'vendor')
            {{-- Vendor --}}
            <div class="flex items-start gap-3 p-4 rounded-lg bg-gray-50 border border-gray-200">
                <div class="flex-shrink-0">
                    <span class="badge badge-info">{{ __('admin.marketers.vendor') }}</span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-gray-900">{{ $campaignable->store_name ?? $campaignable->name }}</p>
                    @if ($campaignable->business_name && $campaignable->business_name !== $campaignable->store_name)
                        <p class="text-sm text-gray-500">{{ $campaignable->business_name }}</p>
                    @endif
                </div>
            </div>
            @endif

        </x-card>
        @endif

        {{-- Campaign Info --------------------------------------------------------}}
        <x-card>
            <h2 class="text-base font-semibold text-gray-800 mb-4">{{ __('admin.marketers.campaign_details') }}</h2>
            <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                <div>
                    <dt class="text-gray-400">{{ __('admin.commission') }}</dt>
                    <dd class="font-medium text-gray-800">
                        {{ $campaign->commission_rate }}%
                        ({{ $campaign->commission_type?->label() ?? 'Standard' }})
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-400">{{ __('admin.marketers.budget') }}</dt>
                    <dd class="font-medium text-gray-800">
                        @if ($campaign->budget)
                            {{ number_format($campaign->budget, 2) }} {{ $campaign->vendor?->country?->currency_code ?? '' }}
                        @else
                            {{ __('admin.marketers.unlimited') }}
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-400">{{ __('admin.marketers.starts') }}</dt>
                    <dd class="font-medium text-gray-800">{{ $campaign->starts_at?->format('d M Y') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-400">{{ __('admin.marketers.ends') }}</dt>
                    <dd class="font-medium text-gray-800">{{ $campaign->ends_at?->format('d M Y') ?? __('admin.marketers.no_end_date') }}</dd>
                </div>
                <div>
                    <dt class="text-gray-400">{{ __('admin.marketers.attribution') }}</dt>
                    <dd class="font-medium text-gray-800">{{ ucfirst($campaign->attribution_model?->value ?? 'last_click') }}</dd>
                </div>
                <div>
                    <dt class="text-gray-400">{{ __('admin.marketers.whatsapp_sharing') }}</dt>
                    <dd class="font-medium text-gray-800">{{ $campaign->whatsapp_sharing_enabled ? __('admin.marketers.enabled') : __('admin.marketers.disabled') }}</dd>
                </div>
                @if ($campaign->description)
                <div class="col-span-2">
                    <dt class="text-gray-400">{{ __('admin.classifieds.description') }}</dt>
                    <dd class="font-medium text-gray-800 mt-0.5">{{ $campaign->description }}</dd>
                </div>
                @endif
            </dl>
        </x-card>

        {{-- Performance ---------------------------------------------------------}}
        <x-card>
            <h2 class="text-base font-semibold text-gray-800 mb-4">{{ __('admin.performance') }}</h2>
            <div class="grid grid-cols-3 gap-4 text-center">
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($campaign->total_clicks) }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ __('admin.marketers.clicks') }}</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($campaign->total_conversions) }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ __('admin.marketers.conversions') }}</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($campaign->total_revenue, 2) }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ __('admin.marketers.revenue') }} ({{ $campaign->vendor?->country?->currency_code ?? '' }})</p>
                </div>
            </div>
        </x-card>

    </div>

    {{-- ─── Right: Meta ─────────────────────────────────────────────────────── --}}
    <div class="space-y-6">
        <x-card>
            <h2 class="text-base font-semibold text-gray-800 mb-4">{{ __('admin.status') }}</h2>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <dt class="text-gray-400">{{ __('admin.marketers.current_status') }}</dt>
                    <dd><span class="badge badge-{{ $campaign->status_color }}">{{ $campaign->status->label() }}</span></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-400">{{ __('admin.marketers.created') }}</dt>
                    <dd class="font-medium text-gray-700">{{ $campaign->created_at->format('d M Y') }}</dd>
                </div>
                @if ($campaign->approved_at)
                <div class="flex justify-between">
                    <dt class="text-gray-400">{{ __('admin.marketers.approved') }}</dt>
                    <dd class="font-medium text-gray-700">{{ $campaign->approved_at->format('d M Y') }}</dd>
                </div>
                @endif
                @if ($campaign->tracking_url_slug)
                <div>
                    <dt class="text-gray-400 mb-1">{{ __('admin.marketers.tracking_slug') }}</dt>
                    <dd class="font-mono text-xs bg-gray-50 border border-gray-200 rounded px-2 py-1 break-all">{{ $campaign->tracking_url_slug }}</dd>
                </div>
                @endif
            </dl>
        </x-card>

        {{-- Samples Required (admin-controlled) --------------------------------}}
        <x-card>
            <h2 class="text-base font-semibold text-gray-800 mb-3">{{ __('admin.marketers.required_samples') }}</h2>
            <p class="text-xs text-gray-500 mb-3">{{ __('admin.marketers.required_samples_desc') }}</p>
            <div class="flex items-center gap-3">
                <input type="number" id="samples-required-input"
                    value="{{ $campaign->samples_required }}" min="0" max="10"
                    class="form-input w-24 text-sm py-1.5"
                    {{ !in_array($campaign->status, [\App\Enums\MarketerCampaignStatus::Draft, \App\Enums\MarketerCampaignStatus::PendingReview, \App\Enums\MarketerCampaignStatus::Active], true) ? 'disabled' : '' }}>
                <button type="button" id="samples-required-btn"
                    onclick="saveSamplesRequired()"
                    class="btn btn-sm btn-primary"
                    {{ !in_array($campaign->status, [\App\Enums\MarketerCampaignStatus::Draft, \App\Enums\MarketerCampaignStatus::PendingReview, \App\Enums\MarketerCampaignStatus::Active], true) ? 'disabled' : '' }}>
                    {{ __('admin.save') }}
                </button>
            </div>
            <p id="samples-required-msg" class="text-xs mt-2 hidden"></p>
            @if (!in_array($campaign->status, [\App\Enums\MarketerCampaignStatus::Draft, \App\Enums\MarketerCampaignStatus::PendingReview, \App\Enums\MarketerCampaignStatus::Active], true))
                <p class="text-xs text-gray-400 mt-2">{{ __('admin.marketers.editing_locked', ['status' => $campaign->status->label()]) }}</p>
            @endif
        </x-card>

        {{-- Conversion note for non-vendor campaigns -------------------------}}
        @if (in_array($targetType, ['classified', 'travel']))
        <x-card>
            <div class="flex gap-2">
                <svg class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 110 20A10 10 0 0112 2z"/>
                </svg>
                <div class="text-xs text-gray-600">
                    <p class="font-semibold text-gray-800 mb-1">{{ __('admin.marketers.conversion_tracking_pending') }}</p>
                    <p>
                        @if ($targetType === 'classified')
                            {{ __('admin.marketers.classified_conversion_note') }}
                        @else
                            {{ __('admin.marketers.travel_conversion_note') }}
                        @endif
                        {!! __('admin.marketers.commission_tracked_via_note', ['field' => '<code class="bg-gray-100 px-1 rounded">commission_rate</code>']) !!}
                    </p>
                </div>
            </div>
        </x-card>
        @endif

    </div>
</div>

{{-- ─── Reject Modal ────────────────────────────────────────────────────────── --}}
<div id="reject-campaign-modal" class="modal" style="display:none;">
    <div class="modal-box">
        <h3 class="font-bold text-lg mb-4">{{ __('admin.marketers.reject_campaign_title') }}</h3>
        <input type="hidden" id="reject-campaign-id">
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.marketers.reason') }}</label>
            <textarea id="reject-campaign-reason" rows="3" class="form-input w-full text-sm"
                placeholder="{{ __('admin.marketers.reject_campaign_reason_placeholder') }}"></textarea>
        </div>
        <div class="flex gap-3 justify-end">
            <button type="button" id="cancel-reject-campaign" class="btn btn-ghost btn-sm">{{ __('common.cancel') }}</button>
            <button type="button" id="confirm-reject-campaign" class="btn btn-danger btn-sm">{{ __('admin.marketers.reject') }}</button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    window.TRANSLATIONS = window.TRANSLATIONS || {};
    Object.assign(window.TRANSLATIONS, {
        approveCampaignConfirm: @json(__('admin.marketers.approve_campaign_confirm')),
        approve: @json(__('admin.marketers.approve')),
        pleaseEnterReason: @json(__('admin.marketers.please_enter_reason')),
        saving: @json(__('admin.marketers.saving')),
        save: @json(__('admin.save')),
        savedSamplesRequired: @json(__('admin.marketers.saved_samples_required')),
        networkError: @json(__('admin.marketers.network_error')),
    });
</script>
<script type="module">
document.addEventListener('DOMContentLoaded', function () {
    const tok = '{{ csrf_token() }}';

    $('.btn-approve-campaign').on('click', function () {
        const id = $(this).data('id');
        window.confirmDialog({ title: window.TRANSLATIONS.approveCampaignConfirm, confirmText: window.TRANSLATIONS.approve, onConfirm: () => {
            fetch('/marketers/campaigns/' + id + '/approve', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': tok, 'Content-Type': 'application/json' },
                body: '{}'
            }).then(r => r.json()).then(data => {
                if (data.success) { window.Toast.success(data.message); setTimeout(() => location.reload(), 800); }
                else { window.Toast.error(data.message); }
            });
        }});
    });

    $('.btn-approve-pause, .btn-dismiss-pause').on('click', function () {
        fetch($(this).data('url'), {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': tok, 'Content-Type': 'application/json' },
            body: '{}'
        }).then(r => r.json()).then(data => {
            if (data.success) { window.Toast.success(data.message); setTimeout(() => location.reload(), 800); }
            else { window.Toast.error(data.message); }
        });
    });

    $('.btn-reject-campaign').on('click', function () {
        $('#reject-campaign-id').val($(this).data('id'));
        $('#reject-campaign-reason').val('');
        $('#reject-campaign-modal').show();
    });
    $('#cancel-reject-campaign').on('click', () => $('#reject-campaign-modal').hide());
    $('#confirm-reject-campaign').on('click', function () {
        const id     = $('#reject-campaign-id').val();
        const reason = $('#reject-campaign-reason').val().trim();
        if (!reason) { window.Toast.warning(window.TRANSLATIONS.pleaseEnterReason); return; }
        fetch('/marketers/campaigns/' + id + '/reject', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': tok, 'Content-Type': 'application/json' },
            body: JSON.stringify({ reason }),
        }).then(r => r.json()).then(data => {
            if (data.success) {
                window.Toast.success(data.message);
                $('#reject-campaign-modal').hide();
                setTimeout(() => location.reload(), 800);
            } else { window.Toast.error(data.message); }
        });
    });
}, { once: true });

async function saveSamplesRequired() {
    const input = document.getElementById('samples-required-input');
    const btn   = document.getElementById('samples-required-btn');
    const msg   = document.getElementById('samples-required-msg');
    btn.disabled = true;
    btn.textContent = window.TRANSLATIONS.saving;
    try {
        const res = await fetch('{{ route('admin.marketers.campaigns.samples-required', $campaign) }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            body: JSON.stringify({ samples_required: parseInt(input.value, 10) }),
        });
        const data = await res.json();
        msg.classList.remove('hidden', 'text-red-500', 'text-green-600');
        if (data.success) {
            msg.textContent = window.TRANSLATIONS.savedSamplesRequired.replace(':n', data.samples_required);
            msg.classList.add('text-green-600');
        } else {
            msg.textContent = data.message || window.TRANSLATIONS.networkError;
            msg.classList.add('text-red-500');
        }
        msg.classList.remove('hidden');
    } catch (e) {
        msg.textContent = window.TRANSLATIONS.networkError;
        msg.classList.remove('hidden');
        msg.classList.add('text-red-500');
    } finally {
        btn.disabled = false;
        btn.textContent = window.TRANSLATIONS.save;
    }
}
</script>
@endpush
