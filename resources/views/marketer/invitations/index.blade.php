@extends('layouts.marketer')

@section('title', __('marketer.invitations.title'))
@section('page-title', __('marketer.invitations.title'))

@push('styles')
<style>
.inv-stats { display:flex; gap:1rem; margin-bottom:1.5rem; flex-wrap:wrap; }
.inv-stat-card {
    flex:1; min-width:160px;
    background:#fff; border:1px solid #f1f5f9;
    border-radius:1rem; padding:1.1rem 1.25rem;
    box-shadow:0 1px 4px rgba(0,0,0,0.05);
}
.inv-stat-card .label { font-size:0.72rem; color:#94a3b8; font-weight:600; text-transform:uppercase; letter-spacing:.04em; }
.inv-stat-card .value { font-size:1.6rem; font-weight:800; color:#1e293b; line-height:1.1; margin-top:2px; }

.tab-bar { display:flex; gap:0.25rem; background:#f8fafc; border:1px solid #e2e8f0; border-radius:0.75rem; padding:4px; margin-bottom:1.5rem; flex-wrap:wrap; }
.tab-bar a {
    flex:1; text-align:center; padding:0.45rem 0.75rem;
    border-radius:0.6rem; font-size:0.8rem; font-weight:600;
    color:#64748b; text-decoration:none; white-space:nowrap;
    transition:background 0.15s,color 0.15s;
}
.tab-bar a.active { background:#fff; color:#7c3aed; box-shadow:0 1px 3px rgba(0,0,0,0.08); }
.tab-bar .badge {
    display:inline-flex; align-items:center; justify-content:center;
    background:#f59e0b; color:#fff; font-size:0.6rem; font-weight:700;
    min-width:1rem; height:1rem; border-radius:999px; padding:0 3px;
    margin-left:4px; vertical-align:middle; line-height:1;
}

.inv-card {
    background:#fff; border:1px solid #f1f5f9;
    border-radius:1rem; padding:1.1rem 1.25rem;
    box-shadow:0 1px 4px rgba(0,0,0,0.04);
    display:flex; gap:1rem; align-items:flex-start;
    margin-bottom:0.75rem; transition:box-shadow 0.15s;
}
.inv-card:hover { box-shadow:0 4px 16px rgba(0,0,0,0.08); }
.inv-avatar {
    width:44px; height:44px; border-radius:0.75rem;
    background:#ede9fe; color:#7c3aed;
    display:flex; align-items:center; justify-content:center;
    font-weight:800; font-size:1.1rem; flex-shrink:0;
    overflow:hidden;
}
.inv-avatar img { width:100%; height:100%; object-fit:cover; }
.inv-body { flex:1; min-width:0; }
.inv-title { font-size:0.9rem; font-weight:700; color:#1e293b; }
.inv-vendor { font-size:0.75rem; color:#64748b; margin-top:1px; }
.inv-meta { display:flex; flex-wrap:wrap; gap:0.5rem; margin-top:0.5rem; align-items:center; }
.type-chip {
    font-size:0.68rem; font-weight:600; padding:2px 8px;
    border-radius:999px; background:#f1f5f9; color:#475569;
}
.commission-val { font-size:0.82rem; font-weight:700; color:#16a34a; }
.date-range { font-size:0.72rem; color:#94a3b8; }
.products-count { font-size:0.72rem; color:#64748b; }

.status-pill {
    display:inline-flex; align-items:center; gap:4px;
    font-size:0.68rem; font-weight:700; padding:3px 9px;
    border-radius:999px;
}
.status-pending  { background:#fef3c7; color:#b45309; }
.status-accepted { background:#dcfce7; color:#15803d; }
.status-declined { background:#fee2e2; color:#b91c1c; }
.status-expired  { background:#f1f5f9; color:#94a3b8; }
.status-revoked  { background:#fae8ff; color:#9333ea; }

.inv-action { display:flex; align-items:center; flex-shrink:0; margin-top:0.25rem; }
.btn-view {
    padding:0.4rem 0.9rem; border-radius:0.6rem;
    background:#7c3aed; color:#fff; font-size:0.75rem; font-weight:600;
    text-decoration:none; border:none; cursor:pointer;
    transition:background 0.15s;
}
.btn-view:hover { background:#6d28d9; }

.empty-state { text-align:center; padding:3rem 1rem; color:#94a3b8; }
.empty-state svg { width:3rem; height:3rem; margin:0 auto 0.75rem; stroke:#d1d5db; }
</style>
@endpush

@section('content')
<div class="inv-stats">
    <div class="inv-stat-card">
        <div class="label">{{ __('marketer.invitations.pending') }}</div>
        <div class="value">{{ $pendingCount }}</div>
    </div>
    <div class="inv-stat-card">
        <div class="label">{{ __('marketer.invitations.accepted_this_month') }}</div>
        <div class="value">{{ $acceptedThisMonth }}</div>
    </div>
</div>

@php
    $activeTab = request('tab', 'all');
    $tabs = [
        'all'      => ['label' => __('marketer.invitations.tab_all'), 'badge' => null],
        'pending'  => ['label' => __('marketer.invitations.tab_pending'), 'badge' => $pendingCount > 0 ? $pendingCount : null],
        'accepted' => ['label' => __('marketer.invitations.tab_accepted'), 'badge' => null],
        'declined' => ['label' => __('marketer.invitations.tab_declined'), 'badge' => null],
    ];
@endphp

<div class="tab-bar">
    @foreach($tabs as $key => $tab)
        <a href="{{ route('marketer.invitations.index', ['tab' => $key]) }}"
           class="{{ $activeTab === $key ? 'active' : '' }}">
            {{ $tab['label'] }}
            @if($tab['badge'])
                <span class="badge">{{ $tab['badge'] }}</span>
            @endif
        </a>
    @endforeach
</div>

@php
    $filtered = $invitations->getCollection()->filter(function ($inv) use ($activeTab) {
        if ($activeTab === 'all') return true;
        return $inv->status->value === $activeTab;
    });
@endphp

@if($filtered->isEmpty())
    <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
        </svg>
        <p class="text-sm font-medium">{{ __('marketer.invitations.no_invitations') }}</p>
    </div>
@else
    @foreach($filtered as $inv)
        @php
            $offer = $inv->offer;
            $vendor = $offer?->vendor;
            $productsCount = $offer?->products?->count() ?? 0;
            $statusClass = match($inv->status) {
                \App\Enums\VendorCampaignInvitationStatus::Pending  => 'status-pending',
                \App\Enums\VendorCampaignInvitationStatus::Accepted => 'status-accepted',
                \App\Enums\VendorCampaignInvitationStatus::Declined => 'status-declined',
                \App\Enums\VendorCampaignInvitationStatus::Expired  => 'status-expired',
                \App\Enums\VendorCampaignInvitationStatus::Revoked  => 'status-revoked',
                default    => 'status-expired',
            };
            $statusIcon = match($inv->status) {
                \App\Enums\VendorCampaignInvitationStatus::Pending  => '🕐',
                \App\Enums\VendorCampaignInvitationStatus::Accepted => '✓',
                \App\Enums\VendorCampaignInvitationStatus::Declined => '✕',
                \App\Enums\VendorCampaignInvitationStatus::Expired  => '⏱',
                \App\Enums\VendorCampaignInvitationStatus::Revoked  => '↩',
                default    => '',
            };
            $typeLabel = ucwords(str_replace('_', ' ', $offer?->campaign_type ?? ''));
        @endphp
        <div class="inv-card">
            <div class="inv-avatar">
                @if($vendor?->logo_path)
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($vendor->logo_path) }}"
                         alt="{{ $vendor->store_name }}">
                @else
                    {{ strtoupper(substr($vendor?->store_name ?? 'V', 0, 1)) }}
                @endif
            </div>
            <div class="inv-body">
                <div class="inv-title">{{ $offer?->name ?? '—' }}</div>
                <div class="inv-vendor">{{ $vendor?->store_name ?? '—' }}</div>
                <div class="inv-meta">
                    @if($typeLabel)
                        <span class="type-chip">{{ $typeLabel }}</span>
                    @endif
                    <span class="commission-val">
                        {{ $offer?->offered_commission_rate }}%
                        {{ $offer?->commission_type === 'revenue_share' ? __('marketer.invitations.rev_share') : __('marketer.invitations.commission') }}
                    </span>
                    @if($offer?->starts_at && $offer?->ends_at)
                        <span class="date-range">
                            {{ $offer->starts_at->format('d M') }} – {{ $offer->ends_at->format('d M Y') }}
                        </span>
                    @endif
                    @if($productsCount > 0)
                        <span class="products-count">{{ __('marketer.invitations.products_count', ['count' => $productsCount, 'product' => __('marketer.campaigns.product')]) }}</span>
                    @endif
                    <span class="status-pill {{ $statusClass }}">{{ $statusIcon }} {{ ucfirst($inv->status->value) }}</span>
                </div>
            </div>
            <div class="inv-action">
                <a href="{{ route('marketer.invitations.show', $inv) }}" class="btn-view">{{ __('marketer.invitations.view_details') }}</a>
            </div>
        </div>
    @endforeach
@endif

{{ $invitations->appends(['tab' => $activeTab])->links() }}
@endsection
