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
            {{ ucfirst(str_replace('_', ' ', $campaign->campaign_type)) }}
            &nbsp;·&nbsp;
            <a href="{{ route('admin.marketers.all.show', $campaign->marketer_id) }}" class="text-primary-600 hover:underline">
                {{ $campaign->marketer->name }}
            </a>
        </p>
    </div>

    @if ($campaign->status === 'draft')
        <div class="flex gap-2">
            <button type="button"
                class="btn btn-success btn-sm btn-approve-campaign"
                data-id="{{ $campaign->id }}">
                Approve
            </button>
            <button type="button"
                class="btn btn-danger btn-sm btn-reject-campaign"
                data-id="{{ $campaign->id }}">
                Reject
            </button>
        </div>
    @else
        <span class="badge badge-{{ $campaign->status_color }} text-sm px-3 py-1">
            {{ ucfirst($campaign->status) }}
        </span>
    @endif
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- ─── Left: Campaign Details ───────────────────────────────────────────── --}}
    <div class="lg:col-span-2 space-y-6">

        {{-- Campaign Target Block ------------------------------------------------}}
        @if ($campaignable)
        <x-card>
            <h2 class="text-base font-semibold text-gray-800 mb-4">Campaign Target</h2>

            @if ($targetType === 'classified')
            {{-- Classified Listing --}}
            <div class="flex items-start gap-3 p-4 rounded-lg bg-gray-50 border border-gray-200">
                <div class="flex-shrink-0">
                    <span class="badge badge-secondary">Classified</span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-gray-900 truncate">{{ $campaignable->title_en }}</p>
                    @if ($campaignable->title_ar)
                        <p class="text-sm text-gray-500 truncate">{{ $campaignable->title_ar }}</p>
                    @endif
                    <div class="mt-2 flex flex-wrap gap-4 text-sm text-gray-600">
                        @if ($campaignable->price ?? null)
                            <span><span class="text-gray-400">Price:</span> {{ number_format($campaignable->price, 2) }} SAR</span>
                        @endif
                        <span><span class="text-gray-400">Status:</span>
                            <span class="font-medium {{ $campaignable->status === 'active' ? 'text-green-600' : 'text-gray-500' }}">
                                {{ ucfirst($campaignable->status) }}
                            </span>
                        </span>
                        @if ($campaignable->seller)
                            <span><span class="text-gray-400">Seller:</span> {{ $campaignable->seller->name ?? $campaignable->seller->store_name ?? '—' }}</span>
                        @endif
                    </div>
                </div>
            </div>

            @elseif ($targetType === 'travel')
            {{-- Travel Package --}}
            <div class="flex items-start gap-3 p-4 rounded-lg bg-gray-50 border border-gray-200">
                <div class="flex-shrink-0">
                    <span class="badge badge-primary">Travel</span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-gray-900 truncate">{{ $campaignable->title_en }}</p>
                    @if ($campaignable->title_ar)
                        <p class="text-sm text-gray-500 truncate">{{ $campaignable->title_ar }}</p>
                    @endif
                    <div class="mt-2 flex flex-wrap gap-4 text-sm text-gray-600">
                        <span><span class="text-gray-400">Price:</span> {{ $campaignable->priceFormatted() }}</span>
                        @if ($campaignable->destination_city || $campaignable->destination_country)
                            <span><span class="text-gray-400">Destination:</span> {{ implode(', ', array_filter([$campaignable->destination_city, $campaignable->destination_country])) }}</span>
                        @endif
                        @if ($campaignable->duration_days)
                            <span><span class="text-gray-400">Duration:</span> {{ $campaignable->duration_days }} days</span>
                        @endif
                        <span><span class="text-gray-400">Status:</span>
                            <span class="font-medium {{ $campaignable->status === 'active' ? 'text-green-600' : 'text-gray-500' }}">
                                {{ ucfirst($campaignable->status) }}
                            </span>
                        </span>
                        @if ($campaignable->agency)
                            <span><span class="text-gray-400">Agency:</span> {{ $campaignable->agency->name }}</span>
                        @endif
                    </div>
                </div>
            </div>

            @elseif ($targetType === 'vendor')
            {{-- Vendor --}}
            <div class="flex items-start gap-3 p-4 rounded-lg bg-gray-50 border border-gray-200">
                <div class="flex-shrink-0">
                    <span class="badge badge-info">Vendor</span>
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
            <h2 class="text-base font-semibold text-gray-800 mb-4">Campaign Details</h2>
            <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                <div>
                    <dt class="text-gray-400">Commission</dt>
                    <dd class="font-medium text-gray-800">
                        {{ $campaign->commission_rate }}%
                        ({{ ucfirst($campaign->commission_type ?? 'standard') }})
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-400">Budget</dt>
                    <dd class="font-medium text-gray-800">
                        @if ($campaign->budget_cents)
                            {{ number_format($campaign->budget_cents / 100, 2) }} SAR
                        @else
                            Unlimited
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-400">Starts</dt>
                    <dd class="font-medium text-gray-800">{{ $campaign->starts_at?->format('d M Y') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-400">Ends</dt>
                    <dd class="font-medium text-gray-800">{{ $campaign->ends_at?->format('d M Y') ?? 'No end date' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-400">Attribution</dt>
                    <dd class="font-medium text-gray-800">{{ ucfirst($campaign->attribution_model ?? 'last_click') }}</dd>
                </div>
                <div>
                    <dt class="text-gray-400">WhatsApp Sharing</dt>
                    <dd class="font-medium text-gray-800">{{ $campaign->whatsapp_sharing_enabled ? 'Enabled' : 'Disabled' }}</dd>
                </div>
                @if ($campaign->description)
                <div class="col-span-2">
                    <dt class="text-gray-400">Description</dt>
                    <dd class="font-medium text-gray-800 mt-0.5">{{ $campaign->description }}</dd>
                </div>
                @endif
            </dl>
        </x-card>

        {{-- Performance ---------------------------------------------------------}}
        <x-card>
            <h2 class="text-base font-semibold text-gray-800 mb-4">Performance</h2>
            <div class="grid grid-cols-3 gap-4 text-center">
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($campaign->total_clicks) }}</p>
                    <p class="text-xs text-gray-400 mt-1">Clicks</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($campaign->total_conversions) }}</p>
                    <p class="text-xs text-gray-400 mt-1">Conversions</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($campaign->total_revenue_cents / 100, 2) }}</p>
                    <p class="text-xs text-gray-400 mt-1">Revenue (SAR)</p>
                </div>
            </div>
        </x-card>

    </div>

    {{-- ─── Right: Meta ─────────────────────────────────────────────────────── --}}
    <div class="space-y-6">
        <x-card>
            <h2 class="text-base font-semibold text-gray-800 mb-4">Status</h2>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <dt class="text-gray-400">Current status</dt>
                    <dd><span class="badge badge-{{ $campaign->status_color }}">{{ ucfirst($campaign->status) }}</span></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-400">Created</dt>
                    <dd class="font-medium text-gray-700">{{ $campaign->created_at->format('d M Y') }}</dd>
                </div>
                @if ($campaign->approved_at)
                <div class="flex justify-between">
                    <dt class="text-gray-400">Approved</dt>
                    <dd class="font-medium text-gray-700">{{ $campaign->approved_at->format('d M Y') }}</dd>
                </div>
                @endif
                @if ($campaign->tracking_url_slug)
                <div>
                    <dt class="text-gray-400 mb-1">Tracking slug</dt>
                    <dd class="font-mono text-xs bg-gray-50 border border-gray-200 rounded px-2 py-1 break-all">{{ $campaign->tracking_url_slug }}</dd>
                </div>
                @endif
            </dl>
        </x-card>

        {{-- Conversion note for non-vendor campaigns -------------------------}}
        @if (in_array($targetType, ['classified', 'travel']))
        <x-card>
            <div class="flex gap-2">
                <svg class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 110 20A10 10 0 0112 2z"/>
                </svg>
                <div class="text-xs text-gray-600">
                    <p class="font-semibold text-gray-800 mb-1">Conversion tracking pending design</p>
                    <p>
                        @if ($targetType === 'classified')
                            Classified campaigns do not yet have a defined conversion event (e.g. mark-sold, inquiry-to-sale).
                        @else
                            Travel campaigns do not yet have a defined conversion event (e.g. booking confirmation).
                        @endif
                        Commission is tracked via the campaign's <code class="bg-gray-100 px-1 rounded">commission_rate</code> field once conversion recording is designed for this campaign type.
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
        <h3 class="font-bold text-lg mb-4">Reject Campaign</h3>
        <input type="hidden" id="reject-campaign-id">
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Reason</label>
            <textarea id="reject-campaign-reason" rows="3" class="form-input w-full text-sm"
                placeholder="Explain why this campaign is rejected…"></textarea>
        </div>
        <div class="flex gap-3 justify-end">
            <button type="button" id="cancel-reject-campaign" class="btn btn-ghost btn-sm">Cancel</button>
            <button type="button" id="confirm-reject-campaign" class="btn btn-danger btn-sm">Reject</button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script type="module">
$(function () {
    const tok = '{{ csrf_token() }}';

    $('.btn-approve-campaign').on('click', function () {
        const id = $(this).data('id');
        window.confirmDialog({ title: 'Approve campaign?', confirmText: 'Approve', onConfirm: () => {
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

    $('.btn-reject-campaign').on('click', function () {
        $('#reject-campaign-id').val($(this).data('id'));
        $('#reject-campaign-reason').val('');
        $('#reject-campaign-modal').show();
    });
    $('#cancel-reject-campaign').on('click', () => $('#reject-campaign-modal').hide());
    $('#confirm-reject-campaign').on('click', function () {
        const id     = $('#reject-campaign-id').val();
        const reason = $('#reject-campaign-reason').val().trim();
        if (!reason) { window.Toast.warning('Please enter a reason.'); return; }
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
});
</script>
@endpush
