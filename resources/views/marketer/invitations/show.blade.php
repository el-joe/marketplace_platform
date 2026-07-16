@extends('layouts.marketer')

@section('title', $invitation->offer?->name ?? __('marketer.invitations.title'))
@section('page-title', __('marketer.invitations.invitation_details'))

@push('styles')
<style>
.product-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 0.875rem;
    padding: 1rem;
    display: flex;
    gap: 0.75rem;
    align-items: flex-start;
}

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
    cursor: pointer; transition: background 0.15s;
}
.btn-accept:hover { background: #6d28d9; }
.btn-accept:disabled { opacity: 0.5; cursor: not-allowed; }

.btn-decline {
    padding: 0.65rem 1.25rem;
    background: #fff; color: #ef4444;
    border: 1px solid #fecaca; border-radius: 0.75rem;
    font-size: 0.875rem; font-weight: 600;
    cursor: pointer; transition: background 0.15s, border-color 0.15s;
}
.btn-decline:hover { background: #fff1f2; border-color: #ef4444; }
.btn-decline:disabled { opacity: 0.5; cursor: not-allowed; }

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
textarea.note-input:focus { outline: none; border-color: #a78bfa; box-shadow: 0 0 0 3px rgba(167,139,250,0.15); }

#decline-modal-backdrop {
    display: none;
    position: fixed; inset: 0;
    background: rgba(15,23,42,0.45);
    backdrop-filter: blur(2px);
    z-index: 1000;
    align-items: center; justify-content: center; padding: 1rem;
}
#decline-modal-backdrop.open { display: flex; }
.decline-modal-box {
    background: #fff; border-radius: 1.25rem;
    width: 100%; max-width: 440px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.18);
    padding: 1.5rem;
    animation: modalIn 0.2s ease;
}
@keyframes modalIn {
    from { opacity:0; transform:translateY(-10px) scale(0.97); }
    to   { opacity:1; transform:translateY(0) scale(1); }
}

#toast-container {
    position:fixed; top:1.25rem; right:1.25rem;
    z-index:9999; display:flex; flex-direction:column; gap:0.5rem; pointer-events:none;
}
.toast {
    display:flex; align-items:flex-start; gap:0.75rem;
    min-width:280px; max-width:380px;
    padding:0.875rem 1rem; border-radius:0.875rem;
    box-shadow:0 8px 24px rgba(0,0,0,0.12);
    font-size:0.85rem; line-height:1.4; pointer-events:all;
    opacity:0; transform:translateX(1.5rem);
    transition:opacity 0.22s ease, transform 0.22s ease;
}
.toast.show { opacity:1; transform:translateX(0); }
.toast.hide  { opacity:0; transform:translateX(1.5rem); }
.toast-success { background:#f0fdf4; border:1px solid #bbf7d0; color:#15803d; }
.toast-error   { background:#fff1f2; border:1px solid #fecdd3; color:#be123c; }
</style>
@endpush

@section('content')

<div id="toast-container"></div>

<div class="mb-4">
    <a href="{{ route('marketer.invitations.index') }}" class="text-sm text-gray-400 hover:text-gray-600">{{ __('marketer.invitations.back_to_invitations') }}</a>
</div>

@php
    $offer      = $invitation->offer;
    $vendor     = $offer?->vendor;
    $isPending  = $invitation->status === \App\Enums\VendorCampaignInvitationStatus::Pending && !$invitation->isExpired();
    $typeLabel  = ucwords(str_replace('_', ' ', $offer?->campaign_type?->value ?? ''));
@endphp

{{-- Status banner for non-pending invitations --}}
@if($invitation->status === \App\Enums\VendorCampaignInvitationStatus::Accepted)
    <div class="status-banner accepted">
        {{ __('marketer.invitations.accepted_banner') }}
        @if($invitation->resultingCampaign)
            <a href="{{ route('marketer.campaigns.show', $invitation->resultingCampaign) }}"
               class="underline ml-2">{{ __('marketer.invitations.view_your_campaign') }}</a>
        @endif
    </div>
@elseif($invitation->status === \App\Enums\VendorCampaignInvitationStatus::Declined)
    <div class="status-banner declined">{{ __('marketer.invitations.declined_banner') }}</div>
@elseif($invitation->status === \App\Enums\VendorCampaignInvitationStatus::Expired || $invitation->isExpired())
    <div class="status-banner expired">{{ __('marketer.invitations.expired_banner') }}</div>
@elseif($invitation->status === \App\Enums\VendorCampaignInvitationStatus::Revoked)
    <div class="status-banner revoked">{{ __('marketer.invitations.revoked_banner') }}</div>
@endif

{{-- Offer header --}}
<div class="section-card">
    <div class="flex items-start gap-4">
        <div style="width:52px;height:52px;border-radius:0.875rem;background:#ede9fe;color:#7c3aed;
                    display:flex;align-items:center;justify-content:center;font-weight:800;font-size:1.2rem;
                    flex-shrink:0;overflow:hidden;">
            @if($vendor?->logo_path)
                <img src="{{ \Illuminate\Support\Facades\Storage::url($vendor->logo_path) }}"
                     alt="{{ $vendor->store_name }}"
                     style="width:100%;height:100%;object-fit:cover;">
            @else
                {{ strtoupper(substr($vendor?->store_name ?? 'V', 0, 1)) }}
            @endif
        </div>
        <div class="flex-1 min-w-0">
            <h1 class="text-lg font-bold text-gray-800">{{ $offer?->name }}</h1>
            <p class="text-sm text-gray-500 mt-0.5">{{ $vendor?->store_name }}</p>
            <div class="flex flex-wrap gap-2 mt-2 items-center">
                @if($typeLabel)
                    <span class="text-xs font-600 bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">{{ $typeLabel }}</span>
                @endif
                @if($offer?->starts_at && $offer?->ends_at)
                    <span class="text-xs text-gray-400">
                        {{ $offer->starts_at->format('d M Y') }} – {{ $offer->ends_at->format('d M Y') }}
                    </span>
                @endif
                @if($invitation->expires_at)
                    <span class="text-xs text-amber-600">{{ __('marketer.invitations.invitation_expires', ['date' => $invitation->expires_at->format('d M Y')]) }}</span>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Commission summary --}}
<div class="commission-summary">
    <div class="big-rate">{{ $offer?->offered_commission_rate }}%</div>
    <div class="sub">
        {{ __('marketer.invitations.you_will_earn', ['rate' => $offer?->offered_commission_rate]) }}
        @if($offer?->commission_type?->value === 'revenue_share') {{ __('marketer.invitations.revenue_share') }} @endif
    </div>
</div>

{{-- Offer description & requirements --}}
@if($offer?->description || $offer?->requirements)
    <div class="section-card">
        <h3>{{ __('marketer.invitations.offer_brief') }}</h3>
        @if($offer->description)
            <p class="text-sm text-gray-700 mb-3">{{ $offer->description }}</p>
        @endif
        @if($offer->requirements)
            <div>
                <p class="text-xs font-semibold text-gray-500 mb-1 uppercase tracking-wide">{{ __('marketer.invitations.requirements') }}</p>
                <p class="text-sm text-gray-700">{{ $offer->requirements }}</p>
            </div>
        @endif
    </div>
@endif

{{-- Budget info --}}
@if($offer?->budget_per_marketer)
    <div class="section-card">
        <h3>{{ __('marketer.invitations.budget') }}</h3>
        <p class="text-sm text-gray-700">
            {{ __('marketer.invitations.your_campaign_budget', ['amount' => '']) }}<strong>{{ number_format($offer->budget_per_marketer, 2) }}</strong>
        </p>
    </div>
@endif

{{-- Products being promoted --}}
@if($offer?->products?->isNotEmpty())
    <div class="section-card">
        <h3>{{ __('marketer.invitations.products_promoted', ['count' => $offer->products->count()]) }}</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            @foreach($offer->products as $op)
                @php
                    $listing = $op->vendorListing;
                    $product = $listing?->product;
                    $effectiveRate = $op->commission_override ?? $offer->offered_commission_rate;
                @endphp
                <div class="product-card">
                    @if($listing?->thumbnail_path ?? ($product?->thumbnail ?? null))
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($listing->thumbnail_path ?? $product->thumbnail) }}"
                             alt="{{ $product?->name_en }}"
                             style="width:48px;height:48px;object-fit:cover;border-radius:0.625rem;flex-shrink:0;">
                    @else
                        <div style="width:48px;height:48px;background:#e2e8f0;border-radius:0.625rem;flex-shrink:0;
                                    display:flex;align-items:center;justify-content:center;font-size:1.25rem;">📦</div>
                    @endif
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-sm text-gray-800 truncate">
                            {{ $product?->name_en ?? $listing?->id ?? '—' }}
                        </p>
                        @if($listing?->sale_price)
                            <p class="text-xs text-gray-500 mt-0.5">
                                {{ number_format($listing->sale_price / 100, 2) }}
                                {{ $listing->vendor?->country?->currency_code ?? '' }}
                            </p>
                        @endif
                        <p class="text-xs font-semibold mt-1" style="color:#16a34a;">
                            {{ $effectiveRate }}{{ __('marketer.invitations.commission_suffix') }}
                            @if($op->commission_override) <span class="text-gray-400 font-normal">{{ __('marketer.invitations.override') }}</span> @endif
                        </p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

{{-- Action bar for pending invitations --}}
@if($isPending)
    <div class="action-bar">
        <p class="text-sm text-gray-600 mb-3">{{ __('marketer.invitations.ready_to_respond') }}</p>
        <div class="flex flex-wrap gap-3">
            <button class="btn-accept" id="accept-btn" onclick="handleAccept()">{{ __('marketer.invitations.accept_invitation') }}</button>
            <button class="btn-decline" id="decline-btn" onclick="openDeclineModal()">{{ __('marketer.invitations.decline') }}</button>
        </div>
        <p class="text-xs text-gray-400 mt-2">
            {{ __('marketer.invitations.accept_hint') }}
        </p>
    </div>
@endif

{{-- Marketer note (if any) --}}
@if($invitation->marketer_note)
    <div class="section-card">
        <h3>{{ __('marketer.invitations.your_note') }}</h3>
        <p class="text-sm text-gray-700">{{ $invitation->marketer_note }}</p>
    </div>
@endif

{{-- Decline modal --}}
@if($isPending)
<div id="decline-modal-backdrop">
    <div class="decline-modal-box">
        <h3 class="font-bold text-gray-800 mb-1">{{ __('marketer.invitations.decline_invitation_title') }}</h3>
        <p class="text-sm text-gray-500 mb-4">{{ __('marketer.invitations.decline_note_hint') }}</p>
        <textarea class="note-input" id="decline-note" placeholder="{{ __('marketer.invitations.optional_note') }}" maxlength="500"></textarea>
        <div class="flex gap-2 justify-end mt-4">
            <button onclick="closeDeclineModal()"
                    style="padding:0.55rem 1rem;background:#f1f5f9;color:#64748b;border:none;border-radius:0.6rem;font-size:0.8rem;font-weight:600;cursor:pointer;">
                {{ __('marketer.campaigns.cancel') }}
            </button>
            <button onclick="handleDecline()" id="confirm-decline-btn"
                    style="padding:0.55rem 1rem;background:#ef4444;color:#fff;border:none;border-radius:0.6rem;font-size:0.8rem;font-weight:700;cursor:pointer;">
                {{ __('marketer.invitations.confirm_decline') }}
            </button>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
const ACCEPT_URL  = '{{ route('marketer.invitations.accept', $invitation) }}';
const DECLINE_URL = '{{ route('marketer.invitations.decline', $invitation) }}';
const CSRF        = '{{ csrf_token() }}';

function showToast(msg, type = 'success') {
    const c = document.getElementById('toast-container');
    const t = document.createElement('div');
    t.className = `toast toast-${type}`;
    t.innerHTML = `<span>${msg}</span>`;
    c.appendChild(t);
    requestAnimationFrame(() => { t.classList.add('show'); });
    setTimeout(() => {
        t.classList.remove('show'); t.classList.add('hide');
        setTimeout(() => t.remove(), 300);
    }, 4000);
}

async function handleAccept() {
    const btn = document.getElementById('accept-btn');
    btn.disabled = true; btn.textContent = @json(__('marketer.invitations.processing'));
    try {
        const res = await fetch(ACCEPT_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({}),
        });
        const data = await res.json();
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => { window.location.href = data.redirect; }, 1200);
        } else {
            showToast(data.message || @json(__('marketer.invitations.error_occurred')), 'error');
            btn.disabled = false; btn.textContent = @json(__('marketer.invitations.accept_invitation'));
        }
    } catch (e) {
        showToast(@json(__('marketer.invitations.network_error')), 'error');
        btn.disabled = false; btn.textContent = @json(__('marketer.invitations.accept_invitation'));
    }
}

function openDeclineModal()  { document.getElementById('decline-modal-backdrop').classList.add('open'); }
function closeDeclineModal() { document.getElementById('decline-modal-backdrop').classList.remove('open'); }

async function handleDecline() {
    const note = document.getElementById('decline-note').value.trim();
    const btn  = document.getElementById('confirm-decline-btn');
    btn.disabled = true; btn.textContent = @json(__('marketer.invitations.declining'));
    try {
        const res = await fetch(DECLINE_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ marketer_note: note || null }),
        });
        const data = await res.json();
        if (data.success) {
            closeDeclineModal();
            showToast(data.message, 'success');
            setTimeout(() => { window.location.reload(); }, 1200);
        } else {
            showToast(data.message || @json(__('marketer.invitations.error_occurred')), 'error');
            btn.disabled = false; btn.textContent = @json(__('marketer.invitations.confirm_decline'));
        }
    } catch (e) {
        showToast(@json(__('marketer.invitations.network_error')), 'error');
        btn.disabled = false; btn.textContent = @json(__('marketer.invitations.confirm_decline'));
    }
}

// Close modal on backdrop click
document.getElementById('decline-modal-backdrop')?.addEventListener('click', function(e) {
    if (e.target === this) closeDeclineModal();
});
</script>
@endpush
