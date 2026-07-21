@extends('layouts.marketer')

@section('title', $invitation->title)
@section('page-title', __('marketer.admin_offers.offer_details'))

@push('styles')
<style>
.section-card {
    background: #fff;
    border: 1px solid #f1f5f9;
    border-radius: 1rem;
    padding: 1.25rem;
    margin-bottom: 1.25rem;
    box-shadow: 0 1px 4px rgba(0,0,0,0.04);
}
.section-card h3 {
    font-size: 0.8rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: #94a3b8;
    margin-bottom: 0.75rem;
}

.commission-summary {
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
    border: 1px solid #bbf7d0;
    border-radius: 1rem;
    padding: 1.25rem;
    margin-bottom: 1.25rem;
    text-align: center;
}
.commission-summary .big-rate {
    font-size: 2.5rem;
    font-weight: 900;
    color: #15803d;
    line-height: 1;
}
.commission-summary .sub {
    font-size: 0.8rem;
    color: #16a34a;
    margin-top: 4px;
}

.action-bar {
    background: #fff;
    border: 1px solid #f1f5f9;
    border-radius: 1rem;
    padding: 1.25rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    margin-bottom: 1.25rem;
}

.btn-accept {
    padding: 0.65rem 1.5rem;
    background: #7c3aed; color: #fff;
    border: none; border-radius: 0.75rem;
    font-size: 0.875rem; font-weight: 700;
    cursor: pointer;
}
.btn-decline {
    padding: 0.65rem 1.25rem;
    background: #fff; color: #ef4444;
    border: 1px solid #fecaca; border-radius: 0.75rem;
    font-size: 0.875rem; font-weight: 600;
    cursor: pointer;
}

.status-banner {
    border-radius: 1rem;
    padding: 1rem 1.25rem;
    margin-bottom: 1.25rem;
    font-weight: 600;
    font-size: 0.875rem;
}
.status-banner.accepted { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
.status-banner.declined { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
.status-banner.expired  { background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }
.status-banner.revoked  { background: #fae8ff; color: #9333ea; border: 1px solid #e9d5ff; }

textarea.note-input {
    width: 100%; padding: 0.6rem 0.75rem;
    border: 1px solid #e2e8f0; border-radius: 0.6rem;
    font-size: 0.82rem; color: #1e293b;
    resize: vertical; min-height: 72px;
    font-family: inherit;
}
</style>
@endpush

@section('content')

@if(session('success'))
    <div class="mb-4 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm px-4 py-3">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3">{{ session('error') }}</div>
@endif
@if($errors->any())
    <div class="mb-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3">
        @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
    </div>
@endif

<div class="mb-4">
    <a href="{{ route('marketer.admin-offers.index') }}" class="text-sm text-gray-400 hover:text-gray-600">{{ __('marketer.admin_offers.back_to_offers') }}</a>
</div>

@php
    $isPending = $invitation->status === \App\Enums\AdminMarketerInvitationStatus::Pending && !$invitation->isExpired();
@endphp

{{-- Status banner for non-pending offers --}}
@if($invitation->status === \App\Enums\AdminMarketerInvitationStatus::Accepted)
    <div class="status-banner accepted">
        {{ __('marketer.admin_offers.accepted_banner') }}
        @if($invitation->resultingCampaign)
            <a href="{{ route('marketer.campaigns.show', $invitation->resultingCampaign) }}"
               class="underline ml-2">{{ __('marketer.admin_offers.view_your_campaign') }}</a>
        @endif
    </div>
@elseif($invitation->status === \App\Enums\AdminMarketerInvitationStatus::Declined)
    <div class="status-banner declined">{{ __('marketer.admin_offers.declined_banner') }}</div>
@elseif($invitation->status === \App\Enums\AdminMarketerInvitationStatus::Expired || $invitation->isExpired())
    <div class="status-banner expired">{{ __('marketer.admin_offers.expired_banner') }}</div>
@elseif($invitation->status === \App\Enums\AdminMarketerInvitationStatus::Revoked)
    <div class="status-banner revoked">{{ __('marketer.admin_offers.revoked_banner') }}</div>
@endif

{{-- Offer header --}}
<div class="section-card">
    <div class="flex items-start gap-4">
        <div style="width:52px;height:52px;border-radius:0.875rem;background:#ede9fe;color:#7c3aed;
                    display:flex;align-items:center;justify-content:center;font-weight:800;font-size:1.2rem;flex-shrink:0;">
            🛡️
        </div>
        <div class="flex-1 min-w-0">
            <h1 class="text-lg font-bold text-gray-800">{{ $invitation->title }}</h1>
            <p class="text-sm text-gray-500 mt-0.5">{{ __('marketer.admin_offers.admin_team') }}</p>
            <div class="flex flex-wrap gap-2 mt-2 items-center">
                <span class="text-xs font-600 bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">{{ $invitation->campaign_type->label() }}</span>
                @if($invitation->starts_at && $invitation->ends_at)
                    <span class="text-xs text-gray-400">
                        {{ $invitation->starts_at->format('d M Y') }} – {{ $invitation->ends_at->format('d M Y') }}
                    </span>
                @endif
                @if($invitation->expires_at)
                    <span class="text-xs text-amber-600">{{ __('marketer.admin_offers.offer_expires', ['date' => $invitation->expires_at->format('d M Y')]) }}</span>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Commission summary --}}
<div class="commission-summary">
    <div class="big-rate">{{ number_format($invitation->commission_rate_percent, 2) }}%</div>
    <div class="sub">{{ __('marketer.admin_offers.you_will_earn', ['rate' => number_format($invitation->commission_rate_percent, 2)]) }}</div>
</div>

{{-- Description --}}
@if($invitation->description)
    <div class="section-card">
        <h3>{{ __('marketer.admin_offers.offer_brief') }}</h3>
        <p class="text-sm text-gray-700">{{ $invitation->description }}</p>
    </div>
@endif

{{-- Budget --}}
@if($invitation->budget)
    <div class="section-card">
        <h3>{{ __('marketer.admin_offers.budget') }}</h3>
        <p class="text-sm text-gray-700">
            <strong>{{ number_format($invitation->budget / 100, 2) }} {{ $invitation->budget_currency }}</strong>
        </p>
    </div>
@endif

{{-- Action bar for pending offers --}}
@if($isPending)
    <div class="action-bar">
        <p class="text-sm text-gray-600 mb-3">{{ __('marketer.admin_offers.ready_to_respond') }}</p>

        <form method="POST" action="{{ route('marketer.admin-offers.accept', $invitation) }}" class="mb-3">
            @csrf
            <textarea name="marketer_note" class="note-input mb-2" maxlength="1000" placeholder="{{ __('marketer.admin_offers.your_note') }}"></textarea>
            <button type="submit" class="btn-accept">{{ __('marketer.admin_offers.accept_offer') }}</button>
        </form>

        <details>
            <summary class="text-sm text-red-500 cursor-pointer">{{ __('marketer.admin_offers.decline') }}</summary>
            <form method="POST" action="{{ route('marketer.admin-offers.decline', $invitation) }}" class="mt-3">
                @csrf
                <p class="text-xs text-gray-500 mb-2">{{ __('marketer.admin_offers.decline_note_hint') }}</p>
                <textarea name="marketer_note" class="note-input mb-2" maxlength="1000" required></textarea>
                <button type="submit" class="btn-decline">{{ __('marketer.admin_offers.decline') }}</button>
            </form>
        </details>
    </div>
@endif

{{-- Marketer note (if any) --}}
@if($invitation->marketer_note)
    <div class="section-card">
        <h3>{{ __('marketer.admin_offers.your_note') }}</h3>
        <p class="text-sm text-gray-700">{{ $invitation->marketer_note }}</p>
    </div>
@endif

@endsection
