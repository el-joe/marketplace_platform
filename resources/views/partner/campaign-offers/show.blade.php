@extends('layouts.partner')

@section('title', $offer->name)
@section('page-title', __('partner.campaign_offers.title'))

@push('scripts')
    @vite('resources/js/partner/campaign-offers.js')
    <script>
        window.CAMPAIGN_OFFER_CONFIG = {
            mode:               'show',
            offerId:            "{{ $offer->id }}",
            submitUrl:          "{{ route('partner.campaign-offers.submit', $offer->id) }}",
            pauseUrl:           "{{ route('partner.campaign-offers.pause', $offer->id) }}",
            resumeUrl:          "{{ route('partner.campaign-offers.resume', $offer->id) }}",
            inviteUrl:          "{{ route('partner.campaign-offers.invite', $offer->id) }}",
            revokeBaseUrl:      "{{ url('partner/campaign-offers/invitations') }}",
            marketerSearchUrl:  "{{ route('partner.campaign-offers.marketers.search') }}",
            offerStatus:        "{{ $offer->status }}",
        };
        window.PARTNER_TRANSLATIONS = window.PARTNER_TRANSLATIONS || {};
        Object.assign(window.PARTNER_TRANSLATIONS, {
            enterCampaignName: @json(__('partner.campaign_offers.enter_campaign_name')),
            selectCampaignType: @json(__('partner.campaign_offers.select_campaign_type')),
            selectCampaignDates: @json(__('partner.campaign_offers.select_campaign_dates')),
            selectAttributionModel: @json(__('partner.campaign_offers.select_attribution_model')),
            enterValidCommissionRate: @json(__('partner.campaign_offers.enter_valid_commission_rate')),
            selectCommissionType: @json(__('partner.campaign_offers.select_commission_type')),
            confirmWithdrawInvitation: @json(__('partner.campaign_offers.confirm_withdraw_invitation')),
        });
    </script>
@endpush

@section('content')
@php
    $statusMap = [
        'draft'         => ['label' => __('partner.campaign_offers.status.draft'),            'cls' => 'bg-gray-100 text-gray-600'],
        'pending_admin' => ['label' => __('partner.campaign_offers.status.pending_admin'), 'cls' => 'bg-amber-100 text-amber-700'],
        'active'        => ['label' => __('partner.campaign_offers.status.active'),              'cls' => 'bg-emerald-100 text-emerald-700'],
        'paused'        => ['label' => __('partner.campaign_offers.status.paused'),            'cls' => 'bg-blue-100 text-blue-700'],
        'rejected'      => ['label' => __('partner.campaign_offers.status.rejected'),            'cls' => 'bg-red-100 text-red-700'],
        'ended'         => ['label' => __('partner.campaign_offers.status.ended'),            'cls' => 'bg-gray-100 text-gray-400'],
    ];
    $st = $statusMap[$offer->status] ?? ['label' => $offer->status, 'cls' => 'bg-gray-100 text-gray-600'];

    $typeMap = [
        'product_promotion' => __('partner.campaign_offers.types.product_promotion'),
        'store_promotion'   => __('partner.campaign_offers.types.store_promotion'),
        'brand_deal'        => __('partner.campaign_offers.types.brand_deal'),
        'product_specific'  => __('partner.campaign_offers.types.product_specific'),
        'flash_sale'        => __('partner.campaign_offers.types.flash_sale'),
    ];
    $commissionTypeMap = [
        'percentage'     => __('partner.campaign_offers.commission_types.percentage'),
        'flat_per_order' => __('partner.campaign_offers.commission_types.flat_per_order'),
        'flat_per_click' => __('partner.campaign_offers.commission_types.flat_per_click'),
    ];
    $invStatusMap = [
        'pending'  => ['label' => __('partner.campaign_offers.invitation_status.pending'), 'cls' => 'bg-amber-100 text-amber-700'],
        'accepted' => ['label' => __('partner.campaign_offers.invitation_status.accepted'),         'cls' => 'bg-emerald-100 text-emerald-700'],
        'declined' => ['label' => __('partner.campaign_offers.invitation_status.declined'),          'cls' => 'bg-red-100 text-red-700'],
        'expired'  => ['label' => __('partner.campaign_offers.invitation_status.expired'),       'cls' => 'bg-gray-100 text-gray-500'],
        'revoked'  => ['label' => __('partner.campaign_offers.invitation_status.revoked'),       'cls' => 'bg-gray-100 text-gray-500'],
    ];
@endphp

<div class="px-4 py-6 sm:px-6 lg:px-8 space-y-6">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('partner.campaign-offers.index') }}" class="hover:text-primary-600">{{ __('partner.campaign_offers.title') }}</a>
        <svg class="h-4 w-4 rotate-180" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/>
        </svg>
        <span class="text-gray-800 font-medium truncate max-w-xs">{{ $offer->name }}</span>
    </nav>

    {{-- Header --}}
    <div class="flex items-start justify-between gap-4 flex-wrap">
        <div class="flex items-center gap-3 flex-wrap">
            <h1 class="text-2xl font-bold text-gray-900">{{ $offer->name }}</h1>
            <span id="status-badge" class="inline-flex items-center rounded-full px-3 py-1 text-sm font-semibold {{ $st['cls'] }}">
                {{ $st['label'] }}
            </span>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            @if ($offer->status === 'draft')
                <button id="btn-submit-review"
                        class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700 transition-colors">
                    {{ __('partner.campaign_offers.submit_review') }}
                </button>
            @endif
            @if ($offer->status === 'active')
                <button id="btn-pause"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors">
                    {{ __('partner.campaign_offers.pause') }}
                </button>
            @endif
            @if ($offer->status === 'paused')
                <button id="btn-resume"
                        class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 transition-colors">
                    {{ __('partner.campaign_offers.resume') }}
                </button>
            @endif
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    {{-- Toast for AJAX actions --}}
    <div id="action-toast" class="hidden fixed top-5 end-5 z-50 max-w-sm rounded-xl border px-4 py-3 text-sm shadow-lg transition-all"></div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- ═══ LEFT: Offer details ═══ --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Details card --}}
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm divide-y divide-gray-100">
                <div class="px-6 py-4">
                    <h2 class="text-base font-semibold text-gray-900">{{ __('partner.campaign_offers.details_title') }}</h2>
                </div>
                <div class="px-6 py-5 grid grid-cols-2 gap-x-8 gap-y-4 text-sm">
                    <div>
                        <p class="text-gray-500 text-xs uppercase tracking-wide mb-1">{{ __('partner.campaign_offers.campaign_type') }}</p>
                        <p class="font-medium text-gray-900">{{ $typeMap[$offer->campaign_type] ?? $offer->campaign_type }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 text-xs uppercase tracking-wide mb-1">{{ __('partner.campaign_offers.offered_commission') }}</p>
                        <p class="font-medium text-gray-900">
                            {{ $offer->offered_commission_rate }}%
                            <span class="text-xs text-gray-500 font-normal">{{ $commissionTypeMap[$offer->commission_type] ?? '' }}</span>
                        </p>
                    </div>
                    <div>
                        <p class="text-gray-500 text-xs uppercase tracking-wide mb-1">{{ __('partner.campaign_offers.campaign_period') }}</p>
                        <p class="font-medium text-gray-900">
                            {{ $offer->starts_at->format('d M Y') }} — {{ $offer->ends_at->format('d M Y') }}
                        </p>
                    </div>
                    @if ($offer->invitation_deadline)
                        <div>
                            <p class="text-gray-500 text-xs uppercase tracking-wide mb-1">{{ __('partner.campaign_offers.invite_deadline_label') }}</p>
                            <p class="font-medium text-gray-900">{{ $offer->invitation_deadline->format('d M Y') }}</p>
                        </div>
                    @endif
                    @if ($offer->budget_per_marketer_cents)
                        <div>
                            <p class="text-gray-500 text-xs uppercase tracking-wide mb-1">{{ __('partner.campaign_offers.budget_per_marketer_label') }}</p>
                            <p class="font-medium text-gray-900">{{ number_format($offer->budget_per_marketer_cents / 100, 2) }} {{ __('common.sar') }}</p>
                        </div>
                    @endif
                    @if ($offer->total_budget_cents)
                        <div>
                            <p class="text-gray-500 text-xs uppercase tracking-wide mb-1">{{ __('partner.campaign_offers.total_budget_label') }}</p>
                            <p class="font-medium text-gray-900">{{ number_format($offer->total_budget_cents / 100, 2) }} {{ __('common.sar') }}</p>
                        </div>
                    @endif
                    <div>
                        <p class="text-gray-500 text-xs uppercase tracking-wide mb-1">{{ __('partner.campaign_offers.attribution_model_label') }}</p>
                        <p class="font-medium text-gray-900">{{ ucfirst(str_replace('_', ' ', $offer->attribution_model)) }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 text-xs uppercase tracking-wide mb-1">{{ __('partner.campaign_offers.whatsapp_sharing') }}</p>
                        <p class="font-medium text-gray-900">{{ $offer->whatsapp_sharing_enabled ? __('partner.campaign_offers.enabled') : __('partner.campaign_offers.disabled') }}</p>
                    </div>
                </div>
                @if ($offer->description)
                    <div class="px-6 py-4">
                        <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">{{ __('partner.campaign_offers.description') }}</p>
                        <p class="text-sm text-gray-700 whitespace-pre-line">{{ $offer->description }}</p>
                    </div>
                @endif
                @if ($offer->requirements)
                    <div class="px-6 py-4">
                        <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">{{ __('partner.campaign_offers.requirements') }}</p>
                        <p class="text-sm text-gray-700 whitespace-pre-line">{{ $offer->requirements }}</p>
                    </div>
                @endif
            </div>

            {{-- Status timeline --}}
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm px-6 py-5">
                <h2 class="text-base font-semibold text-gray-900 mb-4">{{ __('partner.campaign_offers.status_path_title') }}</h2>
                <div class="flex items-center gap-0">
                    @foreach ([
                        ['status' => 'draft',         'label' => __('partner.campaign_offers.status_path.draft')],
                        ['status' => 'pending_admin', 'label' => __('partner.campaign_offers.status_path.pending_admin')],
                        ['status' => 'active',        'label' => __('partner.campaign_offers.status_path.active')],
                    ] as $i => $step)
                        @php
                            $statuses = ['draft', 'pending_admin', 'active', 'paused', 'ended'];
                            $currentIndex = array_search($offer->status, $statuses);
                            $stepIndex = array_search($step['status'], $statuses);
                            $isDone = $currentIndex !== false && $stepIndex <= $currentIndex;
                        @endphp
                        <div class="flex items-center {{ !$loop->last ? 'flex-1' : '' }}">
                            <div class="flex-shrink-0 flex flex-col items-center">
                                <div class="h-8 w-8 rounded-full flex items-center justify-center text-xs font-bold
                                            {{ $isDone ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-400' }}">
                                    @if ($isDone)
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                                        </svg>
                                    @else
                                        {{ $i + 1 }}
                                    @endif
                                </div>
                                <span class="mt-1 text-xs {{ $isDone ? 'text-primary-600 font-medium' : 'text-gray-400' }}">{{ $step['label'] }}</span>
                            </div>
                            @if (!$loop->last)
                                <div class="flex-1 mx-2 h-0.5 {{ $isDone ? 'bg-primary-500' : 'bg-gray-200' }}"></div>
                            @endif
                        </div>
                    @endforeach
                </div>
                @if ($offer->status === 'rejected' && $offer->rejection_reason)
                    <div class="mt-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                        <strong>{{ __('partner.campaign_offers.rejection_reason') }}</strong> {{ $offer->rejection_reason }}
                    </div>
                @endif
            </div>

            {{-- Products grid --}}
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm divide-y divide-gray-100">
                <div class="px-6 py-4 flex items-center justify-between">
                    <h2 class="text-base font-semibold text-gray-900">{{ __('partner.campaign_offers.campaign_products') }}</h2>
                    <span class="text-xs text-gray-500">{{ __('partner.campaign_offers.products_count', ['count' => $offer->products->count()]) }}</span>
                </div>
                <div class="px-6 py-4">
                    @if ($offer->products->isEmpty())
                        <p class="text-sm text-gray-400">{{ __('partner.ads.no_products') }}</p>
                    @else
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            @foreach ($offer->products->sortBy('position') as $product)
                                @php
                                    $listing = $product->listing;
                                    $variant = $listing?->productVariant;
                                    $prod    = $variant?->product;
                                @endphp
                                <div class="flex items-center gap-3 rounded-lg border border-gray-100 p-3">
                                    <div class="h-10 w-10 flex-shrink-0 rounded bg-gray-100 flex items-center justify-center text-gray-400 text-xs">
                                        {{ $product->position }}
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium text-gray-900 truncate">{{ $prod?->name_ar ?? $prod?->name_en ?? '—' }}</p>
                                        @if ($product->commission_override !== null)
                                            <p class="text-xs text-indigo-600 mt-0.5">{{ __('partner.campaign_offers.custom_commission', ['rate' => $product->commission_override]) }}</p>
                                        @else
                                            <p class="text-xs text-gray-400 mt-0.5">{{ __('partner.campaign_offers.campaign_commission', ['rate' => $offer->offered_commission_rate]) }}</p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

        </div>

        {{-- ═══ RIGHT: Invite Marketers ═══ --}}
        <div class="space-y-5">

            {{-- Invitation stats --}}
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm px-6 py-4">
                <p class="text-sm text-gray-600 font-medium mb-3">{{ __('partner.campaign_offers.invitation_stats_title') }}</p>
                <div class="flex items-center gap-4 text-sm">
                    <span class="font-bold text-gray-900">{{ $invitationStats['invited'] }}</span><span class="text-gray-500">{{ __('partner.campaign_offers.invited_stat') }}</span>
                    <span class="text-gray-300">·</span>
                    <span class="font-bold text-emerald-600">{{ $invitationStats['accepted'] }}</span><span class="text-gray-500">{{ __('partner.campaign_offers.accepted_stat') }}</span>
                    <span class="text-gray-300">·</span>
                    <span class="font-bold text-red-500">{{ $invitationStats['declined'] }}</span><span class="text-gray-500">{{ __('partner.campaign_offers.declined_stat') }}</span>
                </div>
            </div>

            {{-- Search marketer panel --}}
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm divide-y divide-gray-100">
                <div class="px-5 py-4">
                    <h2 class="text-base font-semibold text-gray-900">{{ __('partner.campaign_offers.invite_marketers_title') }}</h2>
                </div>
                <div class="px-5 py-4 space-y-3">

                    {{-- Filters --}}
                    <div class="flex gap-2">
                        <select id="marketer-type-filter"
                                class="flex-1 rounded-lg border border-gray-300 px-2 py-2 text-xs focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                            <option value="">{{ __('partner.campaign_offers.all_types') }}</option>
                            <option value="influencer">{{ __('partner.campaign_offers.marketer_types.influencer') }}</option>
                            <option value="celebrity">{{ __('partner.campaign_offers.marketer_types.celebrity') }}</option>
                            <option value="affiliate">{{ __('partner.campaign_offers.marketer_types.affiliate') }}</option>
                            <option value="brand_ambassador">{{ __('partner.campaign_offers.marketer_types.brand_ambassador') }}</option>
                        </select>
                        <input type="text" id="marketer-niche-filter" placeholder="{{ __('partner.campaign_offers.niche_placeholder') }}"
                               class="flex-1 rounded-lg border border-gray-300 px-2 py-2 text-xs focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                    </div>

                    {{-- Search input --}}
                    <div class="relative">
                        <input type="text" id="marketer-search"
                               placeholder="{{ __('partner.campaign_offers.search_marketer_placeholder') }}"
                               class="w-full rounded-lg border border-gray-300 px-3 py-2.5 ps-9 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                        <svg class="absolute start-2.5 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                        </svg>
                    </div>

                    {{-- Search results --}}
                    <div id="marketer-results" class="hidden space-y-2 max-h-72 overflow-y-auto rounded-lg border border-gray-100 p-1">
                        {{-- Populated by JS --}}
                    </div>

                    {{-- Pending invite queue --}}
                    <div id="invite-queue" class="hidden">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-xs font-medium text-gray-600">{{ __('partner.campaign_offers.invite_list_label') }} (<span id="invite-queue-count">0</span>)</p>
                            <button id="btn-clear-queue" type="button" class="text-xs text-red-500 hover:underline">{{ __('partner.campaign_offers.clear_all') }}</button>
                        </div>
                        <div id="invite-queue-list" class="space-y-1 max-h-40 overflow-y-auto"></div>
                        <div class="mt-3">
                            <textarea id="vendor-note" rows="2" placeholder="{{ __('partner.campaign_offers.note_placeholder') }}"
                                      class="w-full rounded-lg border border-gray-300 px-3 py-2 text-xs focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 resize-none"></textarea>
                        </div>
                        <button id="btn-send-invites" type="button"
                                class="mt-2 w-full inline-flex items-center justify-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700 transition-colors">
                            {{ __('partner.campaign_offers.send_invites') }}
                        </button>
                    </div>

                </div>
            </div>

            {{-- Invited marketers list --}}
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm divide-y divide-gray-100">
                <div class="px-5 py-4">
                    <h2 class="text-sm font-semibold text-gray-900">{{ __('partner.campaign_offers.invited_marketers') }}</h2>
                </div>
                <div class="divide-y divide-gray-50" id="invitations-list">
                    @forelse ($offer->invitations as $inv)
                        @php
                            $is = $invStatusMap[$inv->status] ?? ['label' => $inv->status, 'cls' => 'bg-gray-100 text-gray-500'];
                        @endphp
                        <div class="px-5 py-3 flex items-start gap-3" data-invitation-id="{{ $inv->id }}" data-status="{{ $inv->status }}">
                            {{-- Avatar --}}
                            <div class="h-9 w-9 flex-shrink-0 rounded-full bg-indigo-100 flex items-center justify-center text-sm font-bold text-indigo-600">
                                {{ mb_substr($inv->marketer->name ?? '?', 0, 1) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-sm font-medium text-gray-900">{{ $inv->marketer->name ?? '—' }}</span>
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $is['cls'] }}">
                                        {{ $is['label'] }}
                                    </span>
                                </div>
                                @if ($inv->marketer_note)
                                    <p class="text-xs text-gray-500 mt-0.5 italic">{{ $inv->marketer_note }}</p>
                                @endif
                                <div class="mt-1 flex items-center gap-3 flex-wrap">
                                    @if ($inv->status === 'accepted' && $inv->resulting_campaign_id)
                                        <a href="{{ route('partner.marketer-campaigns.show', $inv->resulting_campaign_id) }}"
                                           class="text-xs text-primary-600 hover:underline">{{ __('partner.campaign_offers.view_campaign') }} ←</a>
                                    @endif
                                    @if ($inv->status === 'pending')
                                        <button type="button"
                                                class="btn-revoke text-xs text-red-500 hover:underline"
                                                data-invitation-id="{{ $inv->id }}">
                                            {{ __('partner.campaign_offers.revoke_invite') }}
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="px-5 py-6 text-center text-sm text-gray-400">
                            {{ __('partner.campaign_offers.no_marketer_invited') }}
                        </div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
