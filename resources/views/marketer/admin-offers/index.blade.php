@extends('layouts.marketer')

@section('title', __('marketer.admin_offers.title'))
@section('page-title', __('marketer.admin_offers.title'))

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
}
.inv-body { flex:1; min-width:0; }
.inv-title { font-size:0.9rem; font-weight:700; color:#1e293b; }
.inv-admin { font-size:0.75rem; color:#64748b; margin-top:1px; }
.inv-meta { display:flex; flex-wrap:wrap; gap:0.5rem; margin-top:0.5rem; align-items:center; }
.type-chip {
    font-size:0.68rem; font-weight:600; padding:2px 8px;
    border-radius:999px; background:#f1f5f9; color:#475569;
}
.commission-val { font-size:0.82rem; font-weight:700; color:#16a34a; }
.date-range { font-size:0.72rem; color:#94a3b8; }

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

@if(session('success'))
    <div class="mb-4 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm px-4 py-3">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3">{{ session('error') }}</div>
@endif

<div class="flex justify-end mb-4">
    <x-export-dropdown />
</div>

<form method="GET" action="{{ route('marketer.admin-offers.index') }}" class="flex flex-wrap items-end gap-3 mb-4 bg-white rounded-2xl border border-gray-100 p-4">
    <div>
        <label class="text-xs text-gray-400 font-semibold">{{ __('common.status') }}</label>
        <select name="status" class="w-full rounded-lg border-gray-200 text-sm mt-1">
            <option value="">{{ __('common.all') }}</option>
            @foreach(\App\Enums\AdminMarketerInvitationStatus::cases() as $status)
                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="text-xs text-gray-400 font-semibold">{{ __('common.date_from') }}</label>
        <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full rounded-lg border-gray-200 text-sm mt-1">
    </div>
    <div>
        <label class="text-xs text-gray-400 font-semibold">{{ __('common.date_to') }}</label>
        <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full rounded-lg border-gray-200 text-sm mt-1">
    </div>
    <button type="submit" class="bg-slate-800 text-white text-sm font-semibold rounded-lg px-4 py-2">{{ __('common.filter') }}</button>
</form>

<div class="inv-stats">
    <div class="inv-stat-card">
        <div class="label">{{ __('marketer.admin_offers.pending') }}</div>
        <div class="value">{{ $pendingCount }}</div>
    </div>
    <div class="inv-stat-card">
        <div class="label">{{ __('marketer.admin_offers.accepted') }}</div>
        <div class="value">{{ $acceptedCount }}</div>
    </div>
    <div class="inv-stat-card">
        <div class="label">{{ __('marketer.admin_offers.total_earned') }}</div>
        <div class="value">{{ number_format($totalEarnedFromAdminCampaigns / 100, 2) }}</div>
    </div>
</div>

@if($invitations->isEmpty())
    <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <p class="text-sm font-medium">{{ __('marketer.admin_offers.no_offers') }}</p>
    </div>
@else
    @foreach($invitations as $inv)
        @php
            $statusClass = match($inv->status) {
                \App\Enums\AdminMarketerInvitationStatus::Pending  => 'status-pending',
                \App\Enums\AdminMarketerInvitationStatus::Accepted => 'status-accepted',
                \App\Enums\AdminMarketerInvitationStatus::Declined => 'status-declined',
                \App\Enums\AdminMarketerInvitationStatus::Expired  => 'status-expired',
                \App\Enums\AdminMarketerInvitationStatus::Revoked  => 'status-revoked',
                default => 'status-expired',
            };
            $statusIcon = match($inv->status) {
                \App\Enums\AdminMarketerInvitationStatus::Pending  => '🕐',
                \App\Enums\AdminMarketerInvitationStatus::Accepted => '✓',
                \App\Enums\AdminMarketerInvitationStatus::Declined => '✕',
                \App\Enums\AdminMarketerInvitationStatus::Expired  => '⏱',
                \App\Enums\AdminMarketerInvitationStatus::Revoked  => '↩',
                default => '',
            };
        @endphp
        <div class="inv-card">
            <div class="inv-avatar">🛡️</div>
            <div class="inv-body">
                <div class="inv-title">{{ $inv->title }}</div>
                <div class="inv-admin">{{ __('marketer.admin_offers.admin_team') }}</div>
                <div class="inv-meta">
                    <span class="type-chip">{{ $inv->campaign_type->label() }}</span>
                    <span class="commission-val">{{ number_format($inv->commission_rate_percent, 2) }}%</span>
                    @if($inv->starts_at && $inv->ends_at)
                        <span class="date-range">{{ $inv->starts_at->format('d M') }} – {{ $inv->ends_at->format('d M Y') }}</span>
                    @endif
                    <span class="status-pill {{ $statusClass }}">{{ $statusIcon }} {{ $inv->status->label() }}</span>
                </div>
            </div>
            <div class="inv-action">
                <a href="{{ route('marketer.admin-offers.show', $inv) }}" class="btn-view">{{ __('marketer.admin_offers.view_details') }}</a>
            </div>
        </div>
    @endforeach
@endif

{{ $invitations->links() }}
@endsection
