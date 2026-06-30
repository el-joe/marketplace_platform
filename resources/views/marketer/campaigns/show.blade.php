@extends('layouts.marketer')

@section('title', $campaign->name)
@section('page-title', $campaign->name)

@push('styles')
<style>
/* ── Toast ───────────────────────────────────────────────────────────────── */
#toast-container {
    position: fixed;
    top: 1.25rem;
    right: 1.25rem;
    z-index: 9999;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    pointer-events: none;
}
.toast {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    min-width: 280px;
    max-width: 380px;
    padding: 0.875rem 1rem;
    border-radius: 0.875rem;
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
    font-size: 0.85rem;
    line-height: 1.4;
    pointer-events: all;
    opacity: 0;
    transform: translateX(1.5rem);
    transition: opacity 0.22s ease, transform 0.22s ease;
}
.toast.show  { opacity: 1; transform: translateX(0); }
.toast.hide  { opacity: 0; transform: translateX(1.5rem); }
.toast-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #15803d; }
.toast-error   { background: #fff1f2; border: 1px solid #fecdd3; color: #be123c; }
.toast-icon { font-size: 1rem; line-height: 1; flex-shrink: 0; margin-top: 1px; }
.toast-body { flex: 1; }
.toast-title { font-weight: 700; margin-bottom: 1px; }
.toast-msg   { opacity: 0.85; }
.toast-close {
    background: none; border: none; cursor: pointer;
    opacity: 0.5; font-size: 0.9rem; line-height: 1;
    flex-shrink: 0; padding: 0; margin-top: 1px;
    color: inherit;
}
.toast-close:hover { opacity: 1; }

/* ── Samples Modal ───────────────────────────────────────────────────────── */
.samples-modal-backdrop {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15,23,42,0.45);
    backdrop-filter: blur(2px);
    z-index: 1000;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}
.samples-modal-backdrop.open { display: flex; }
.samples-modal-box {
    background: #fff;
    border-radius: 1.25rem;
    width: 100%;
    max-width: 520px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 60px rgba(0,0,0,0.18);
    animation: modalIn 0.2s ease;
}
@keyframes modalIn {
    from { opacity: 0; transform: translateY(-12px) scale(0.97); }
    to   { opacity: 1; transform: translateY(0)    scale(1); }
}
.samples-modal-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    padding: 1.25rem 1.25rem 1rem;
    border-bottom: 1px solid #f1f5f9;
    flex-shrink: 0;
}
.samples-modal-close {
    background: #f1f5f9;
    border: none;
    border-radius: 0.5rem;
    width: 2rem;
    height: 2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    cursor: pointer;
    color: #64748b;
    flex-shrink: 0;
    transition: background 0.15s;
}
.samples-modal-close:hover { background: #e2e8f0; color: #1e293b; }
.samples-modal-body {
    padding: 1.25rem;
    overflow-y: auto;
    flex: 1;
}
.samples-modal-footer {
    padding: 1rem 1.25rem;
    border-top: 1px solid #f1f5f9;
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    flex-shrink: 0;
}

.product-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 0.875rem;
    padding: 1rem;
    display: flex;
    gap: 0.75rem;
    align-items: flex-start;
}
.product-card .link-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 0.5rem;
}
.product-card .link-text {
    font-size: 0.7rem;
    font-family: monospace;
    color: #64748b;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    padding: 0.25rem 0.5rem;
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
</style>
@endpush

@section('content')

<div id="toast-container"></div>

@php
    $statusColors = [
        'active'    => 'bg-green-100 text-green-700',
        'draft'     => 'bg-gray-100 text-gray-600',
        'paused'    => 'bg-yellow-100 text-yellow-700',
        'ended'     => 'bg-blue-100 text-blue-700',
        'cancelled' => 'bg-red-100 text-red-600',
    ];
    $sc = $statusColors[$campaign->status] ?? 'bg-gray-100 text-gray-600';
    $isActive = $campaign->status === 'active';
@endphp

{{-- ── Header ───────────────────────────────────────────────────────────────── --}}
<div class="flex flex-wrap items-start justify-between gap-4 mb-6">
    <div>
        <div class="flex items-center gap-2 mb-1">
            <a href="{{ route('marketer.campaigns.index') }}" class="text-sm text-gray-400 hover:text-gray-600">← Campaigns</a>
        </div>
        <h1 class="text-xl font-bold text-gray-800">{{ $campaign->name }}</h1>
        <p class="text-sm text-gray-500 mt-0.5">{{ ucfirst(str_replace('_', ' ', $campaign->campaign_type)) }}</p>
    </div>
    <div class="flex items-center gap-3">
        <span class="text-sm font-semibold rounded-full px-3 py-1 {{ $sc }}">{{ ucfirst($campaign->status) }}</span>
    </div>
</div>

{{-- ── Tracking Link ────────────────────────────────────────────────────────── --}}
@if($isActive)
<div class="bg-slate-800 rounded-2xl p-5 mb-6">
    <p class="text-xs font-bold text-slate-400 uppercase tracking-wide mb-3">Tracking Link</p>
    <div class="flex gap-2">
        <input id="tracking-url-input" type="text" value="{{ $trackingUrl }}" readonly
               class="flex-1 bg-slate-700 text-slate-200 font-mono text-sm rounded-xl px-3 py-2.5 border border-slate-600 focus:outline-none focus:border-yellow-400">
        <button id="copy-url-btn" onclick="copyTrackingUrl()"
                class="flex-shrink-0 bg-yellow-400 hover:bg-yellow-300 text-slate-900 font-bold text-sm rounded-xl px-4 py-2.5 transition-colors">
            <span id="copy-btn-text">📋 Copy</span>
        </button>
    </div>

    {{-- Social share --}}
    <div class="flex gap-2 mt-3">
        <a id="whatsapp-share-btn" href="https://wa.me/?text={{ urlencode("Check this out: $trackingUrl") }}" target="_blank"
           class="flex-1 text-center bg-green-600 hover:bg-green-500 text-white text-xs font-semibold rounded-xl py-2 transition-colors">
            📱 WhatsApp
        </a>
        <button onclick="navigator.clipboard.writeText({{ json_encode($trackingUrl) }}).then(() => alert('Copied for Instagram bio!'))"
                class="flex-1 text-center bg-gradient-to-r from-purple-500 to-pink-500 hover:from-purple-400 hover:to-pink-400 text-white text-xs font-semibold rounded-xl py-2 transition-colors">
            📸 Instagram Bio
        </button>
        <a href="https://twitter.com/intent/tweet?url={{ urlencode($trackingUrl) }}" target="_blank"
           class="flex-1 text-center bg-sky-500 hover:bg-sky-400 text-white text-xs font-semibold rounded-xl py-2 transition-colors">
            🐦 Twitter
        </a>
    </div>
</div>
<script>
function copyTrackingUrl() {
    var url = document.getElementById('tracking-url-input').value;
    var btn = document.getElementById('copy-btn-text');
    navigator.clipboard.writeText(url).then(function() {
        btn.textContent = '✓ Copied!';
        setTimeout(function() { btn.textContent = '📋 Copy'; }, 2000);
    });
}
</script>
@endif

{{-- ── Stats Grid ───────────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    @foreach([
        ['Clicks', number_format($campaign->total_clicks), 'text-blue-600'],
        ['Conversions', number_format($campaign->total_conversions), 'text-purple-600'],
        ['Conv. Rate', $campaign->total_clicks > 0 ? round($campaign->total_conversions / $campaign->total_clicks * 100, 2) . '%' : '0%', 'text-yellow-600'],
        ['Revenue', number_format($campaign->total_revenue_cents / 100, 2) . ' ' . ($campaign->vendor?->country?->currency_code ?? ''), 'text-green-600'],
    ] as [$label, $value, $color])
        <div class="bg-white rounded-xl border border-gray-100 p-4">
            <p class="text-xs text-gray-400 font-medium uppercase tracking-wide">{{ $label }}</p>
            <p class="text-xl font-bold {{ $color }} mt-1">{{ $value }}</p>
        </div>
    @endforeach
</div>

{{-- ── Daily Analytics Chart ────────────────────────────────────────────────── --}}
<div class="bg-white rounded-2xl border border-gray-100 p-5 mb-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="font-bold text-gray-800">Daily Performance</h3>
        <span class="text-xs text-gray-400">Last 30 days</span>
    </div>
    <canvas id="campaign-chart" height="80"></canvas>
</div>

{{-- ── Campaign Target ───────────────────────────────────────────────────────── --}}
@if($campaign->campaignable_type === \App\Models\Vendor::class && $campaign->campaignable)
    {{-- Promoted Store header --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-5 mb-6">
        <h3 class="font-bold text-gray-800 mb-2">Promoted Store</h3>
        <p class="text-sm text-gray-700">{{ $campaign->campaignable->store_name }}</p>
    </div>
    {{-- Products grid (only when products are linked) --}}
    @if($campaign->products->isNotEmpty())
    <div class="bg-white rounded-2xl border border-gray-100 p-5 mb-6">
        <h3 class="font-bold text-gray-800 mb-4">Campaign Products ({{ $campaign->products->count() }})</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            @foreach($campaign->products as $cpIndex => $cp)
                @php
                    $listing = $cp->vendorListing;
                    $product = $listing?->product;
                    $productLink = $trackingUrl;
                    if ($listing?->product_id) {
                        $productLink = str_replace('?ref=', '?product_id=' . $listing->product_id . '&ref=', $trackingUrl);
                    }
                @endphp
                <div class="product-card">
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-sm text-gray-800 truncate">
                            {{ $product?->name_en ?? 'Product #' . $cp->id }}
                        </p>
                        @if($listing?->sale_price)
                            <p class="text-xs text-gray-500 mt-0.5">{{ number_format($listing->sale_price / 100, 2) }} {{ $listing->vendor?->country?->currency_code ?? '' }}</p>
                        @endif
                        <div class="link-row">
                            <span class="link-text">{{ $productLink }}</span>
                            <button onclick="copyProductLink(this, {{ json_encode($productLink) }})"
                                    class="shrink-0 text-xs bg-yellow-400 hover:bg-yellow-300 text-slate-900 font-semibold rounded-lg px-2 py-1 transition-colors">
                                Copy
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

@elseif($campaign->campaignable_type === \App\Models\ClassifiedListing::class && $campaign->campaignable)
    @php $listing = $campaign->campaignable; @endphp
    <div class="bg-white rounded-2xl border border-gray-100 p-5 mb-6">
        <h3 class="font-bold text-gray-800 mb-3">Classified Listing</h3>
        <div class="product-card">
            @if($listing->thumbnail_path ?? null)
                <img src="{{ \Illuminate\Support\Facades\Storage::url($listing->thumbnail_path) }}"
                     alt="{{ $listing->title_en }}" class="w-16 h-16 rounded-xl object-cover shrink-0">
            @endif
            <div class="flex-1 min-w-0">
                <p class="font-semibold text-sm text-gray-800">{{ $listing->title_en }}</p>
                <p class="text-xs text-gray-500 mt-0.5">
                    {{ number_format($listing->price_cents / 100, 2) }} {{ $listing->currency }}
                    · {{ ucfirst($listing->status) }}
                </p>
                <p class="text-xs text-gray-400 mt-1">#{{ $listing->listing_number }}</p>
            </div>
        </div>
    </div>

@elseif($campaign->campaignable_type === \App\Models\TravelPackage::class && $campaign->campaignable)
    @php $package = $campaign->campaignable; @endphp
    <div class="bg-white rounded-2xl border border-gray-100 p-5 mb-6">
        <h3 class="font-bold text-gray-800 mb-3">Travel Package</h3>
        <div class="product-card">
            @if($package->thumbnail_path ?? null)
                <img src="{{ \Illuminate\Support\Facades\Storage::url($package->thumbnail_path) }}"
                     alt="{{ $package->title_en }}" class="w-16 h-16 rounded-xl object-cover shrink-0">
            @endif
            <div class="flex-1 min-w-0">
                <p class="font-semibold text-sm text-gray-800">{{ $package->title_en }}</p>
                <p class="text-xs text-gray-500 mt-0.5">
                    {{ $package->destination_city ? $package->destination_city . ', ' : '' }}{{ $package->destination_country }}
                    · {{ number_format($package->price_cents / 100, 2) }} {{ $package->currency }}
                </p>
                <p class="text-xs text-gray-400 mt-1">
                    {{ \Carbon\Carbon::parse($package->departure_date)->format('d M Y') }}
                    – {{ \Carbon\Carbon::parse($package->return_date)->format('d M Y') }}
                    ({{ $package->duration_days }}d / {{ $package->duration_nights }}n)
                </p>
                <p class="text-xs text-gray-400 mt-1">By {{ $package->travelAgency->name ?? '—' }}</p>
            </div>
        </div>
    </div>
@endif

{{-- ── WhatsApp Incentive Links ────────────────────────────────────────────── --}}
@if($isActive && $campaign->whatsapp_sharing_enabled)
<div class="bg-white rounded-2xl border border-gray-100 p-5 mb-6">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h3 class="font-bold text-gray-800">📱 WhatsApp Links</h3>
            <p class="text-xs text-gray-400 mt-0.5">Generate incentive links with discount coupons for WhatsApp sharing</p>
        </div>
        <button type="button" onclick="toggleWhatsappForm()"
            class="btn btn-sm bg-green-500 hover:bg-green-400 text-white font-semibold">
            <span id="wa-toggle-text">Generate</span>
        </button>
    </div>

    <div id="wa-form" style="display:none" class="border-t border-gray-100 pt-4 mt-2">
        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <label class="form-label text-xs">Link Type</label>
                <select id="wa-link-type" onchange="toggleDiscountField()" class="form-input text-sm py-1.5">
                    <option value="discount">Discount %</option>
                    <option value="free_shipping">Free Shipping</option>
                    <option value="both">Discount + Free Shipping</option>
                </select>
            </div>
            <div id="wa-discount-field">
                <label class="form-label text-xs">Discount %</label>
                <input type="number" id="wa-discount-pct" value="10" class="form-input text-sm py-1.5"
                    min="1" max="100" step="0.5" placeholder="e.g. 10">
            </div>
        </div>
        <button type="button" id="wa-generate-btn" onclick="generateWhatsappLink()"
            class="btn btn-success w-full">Generate WhatsApp Link</button>

        <div id="wa-result" style="display:none" class="mt-4 bg-green-50 border border-green-200 rounded-xl p-4 space-y-2">
            <p class="text-xs text-gray-500">Coupon: <strong id="wa-coupon-code"></strong></p>
            <div class="flex gap-2">
                <input type="text" id="wa-result-url" readonly class="form-input flex-1 text-xs py-1.5 font-mono">
                <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('wa-result-url').value)"
                    class="btn btn-xs btn-secondary">Copy</button>
            </div>
            <a id="wa-share-link" href="#" target="_blank"
                class="block text-center bg-green-600 hover:bg-green-500 text-white text-sm font-semibold rounded-xl py-2 transition-colors">
                Share on WhatsApp 🚀
            </a>
        </div>
    </div>

    {{-- Existing WhatsApp links list --}}
    @php $waLinks = $campaign->whatsappLinks()->where('marketer_id', $marketer->id)->orderByDesc('created_at')->limit(5)->get(); @endphp
    @if($waLinks->isNotEmpty())
        <div class="mt-4 border-t border-gray-100 pt-4">
            <p class="text-xs text-gray-400 font-medium mb-2">Recent Links</p>
            @foreach($waLinks as $wl)
                <div class="flex items-center justify-between py-2 border-b border-gray-50 last:border-0">
                    <div class="text-sm">
                        <span class="font-mono text-xs bg-gray-100 rounded px-1.5 py-0.5">{{ $wl->coupon_code }}</span>
                        <span class="ml-2 text-xs text-gray-400">{{ number_format($wl->total_uses) }} uses</span>
                    </div>
                    <a href="https://wa.me/?text={{ urlencode($wl->tracking_url) }}" target="_blank"
                        class="text-xs text-green-600 hover:underline">Share</a>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endif

{{-- ── QR Code Generator ────────────────────────────────────────────────────── --}}
@if($isActive)
<div class="bg-white rounded-2xl border border-gray-100 p-5 mb-6">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h3 class="font-bold text-gray-800">🔲 QR Code</h3>
            <p class="text-xs text-gray-400 mt-0.5">Generate a scannable QR code for offline promotions</p>
        </div>
        <button type="button" id="qr-generate-btn" onclick="generateQr()"
            class="btn btn-sm btn-primary">Generate QR</button>
    </div>

    <div id="qr-result" style="display:none" class="text-center">
        <img id="qr-img" src="" alt="QR Code" class="w-40 h-40 mx-auto rounded-xl border border-gray-200 mb-3">
        <a id="qr-download" href="#" target="_blank" class="btn btn-sm btn-secondary">⬇ Download PNG</a>
        <p class="text-xs text-gray-400 mt-2">Scan with any camera → opens tracking link</p>
    </div>
</div>
@endif

{{-- ── Sample Requests ───────────────────────────────────────────────────────── --}}
@if($isActive && $campaign->campaignable_type === \App\Models\Vendor::class)
<div class="bg-white rounded-2xl border border-gray-100 p-5 mb-6">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h3 class="font-bold text-gray-800">📦 Sample Requests</h3>
            <p class="text-xs text-gray-400 mt-0.5">Request product samples from this vendor to review and promote authentically.</p>
        </div>
        @if($quota > 0)
            <button type="button" onclick="openSamplesModal()"
                class="btn btn-sm btn-secondary">+ Request Samples</button>
        @else
            <span class="text-xs text-gray-400 italic">Not available for this category</span>
        @endif
    </div>

    @if($quotaCategory)
        @if($quota > 0)
            <div class="text-xs text-blue-700 bg-blue-50 border border-blue-200 rounded-xl px-3 py-2 mb-4">
                You may request up to {{ $quota }} sample(s) for this campaign's category ({{ $quotaCategory->name_en }}).
            </div>
        @else
            <div class="text-xs text-gray-500 bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 mb-4">
                Sample requests are not available for this category ({{ $quotaCategory->name_en }}).
            </div>
        @endif
    @endif

    @if($campaign->samples_required > 0)
        <div class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-xl px-3 py-2 mb-4">
            This vendor requires you to review {{ $campaign->samples_required }} sample item(s) before promoting.
        </div>
    @endif

    @if($sampleRequests->isNotEmpty())
        <div class="space-y-3">
            @foreach($sampleRequests as $req)
                @php
                    $reqStatusColors = [
                        'requested'  => 'bg-yellow-100 text-yellow-700 border-yellow-200',
                        'approved'   => 'bg-blue-100 text-blue-700 border-blue-200',
                        'dispatched' => 'bg-purple-100 text-purple-700 border-purple-200',
                        'received'   => 'bg-green-100 text-green-700 border-green-200',
                        'rejected'   => 'bg-red-100 text-red-600 border-red-200',
                    ];
                    $rsc = $reqStatusColors[$req->status] ?? 'bg-gray-100 text-gray-600 border-gray-200';
                @endphp
                <div class="border border-gray-100 rounded-xl overflow-hidden">
                    {{-- Request header row --}}
                    <div class="flex items-center justify-between px-4 py-2.5 bg-gray-50">
                        <div class="flex items-center gap-3">
                            <span class="text-xs font-mono text-gray-400">#{{ substr($req->id, 0, 8) }}</span>
                            <span class="text-xs text-gray-500">{{ $req->created_at->format('d M Y, H:i') }}</span>
                            @if($req->notes)
                                <span class="text-xs text-gray-400 italic">{{ Str::limit($req->notes, 50) }}</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-semibold rounded-full px-2.5 py-0.5 border {{ $rsc }}">
                                {{ ucfirst($req->status) }}
                            </span>
                            @if($req->status === 'dispatched')
                                <button type="button"
                                    onclick="confirmSampleReceived('{{ $req->id }}')"
                                    class="text-xs font-semibold text-white bg-green-500 hover:bg-green-400 rounded-lg px-2.5 py-0.5 transition-colors">
                                    Mark Received
                                </button>
                            @endif
                        </div>
                    </div>

                    {{-- Items table --}}
                    @if($req->items->isNotEmpty())
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th class="text-left px-4 py-2 text-gray-400 font-medium">Product</th>
                                <th class="text-center px-4 py-2 text-gray-400 font-medium w-20">Qty</th>
                                <th class="text-right px-4 py-2 text-gray-400 font-medium w-28">Unit Price</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($req->items->where('is_mandatory', false) as $item)
                                @php
                                    $productName = $item->vendorListing?->productVariant?->product?->name_en
                                        ?? $item->vendorListing?->productVariant?->sku
                                        ?? 'Product';
                                    $unitPrice = $item->vendorListing?->price ?? $item->sample_cost_cents;
                                @endphp
                                <tr>
                                    <td class="px-4 py-2 text-gray-700 font-medium">{{ $productName }}</td>
                                    <td class="px-4 py-2 text-center text-gray-600">{{ $item->marketer_quantity ?: $item->quantity }}</td>
                                    <td class="px-4 py-2 text-right text-gray-600">
                                        @if($unitPrice)
                                            {{ number_format($unitPrice / 100, 2) }} {{ $campaign->vendor?->country?->currency_code ?? '' }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            @php $marketerItems = $req->items->where('is_mandatory', false); @endphp
                            <tr class="border-t border-gray-100 bg-gray-50">
                                <td class="px-4 py-2 text-gray-400">{{ $marketerItems->count() }} item(s)</td>
                                <td class="px-4 py-2 text-center text-gray-600 font-semibold">
                                    {{ $marketerItems->sum(fn($i) => $i->marketer_quantity ?: $i->quantity) }}
                                </td>
                                <td class="px-4 py-2 text-right text-gray-700 font-semibold"></td>
                            </tr>
                        </tfoot>
                    </table>
                    @endif

                    {{-- Timeline dots --}}
                    <div class="px-4 py-2.5 bg-gray-50 border-t border-gray-100 flex items-center gap-1.5">
                        @foreach(['requested' => 'Requested', 'approved' => 'Approved', 'dispatched' => 'Dispatched', 'received' => 'Received'] as $step => $label)
                            @php
                                $steps = ['requested', 'approved', 'dispatched', 'received'];
                                $reqStepIndex = array_search($req->status, $steps);
                                $thisStepIndex = array_search($step, $steps);
                                $done = $req->status !== 'rejected' && $reqStepIndex !== false && $thisStepIndex <= $reqStepIndex;
                            @endphp
                            <div class="flex items-center gap-1.5 @if(!$loop->last) flex-1 @endif">
                                <div class="w-2 h-2 rounded-full shrink-0 {{ $done ? 'bg-green-400' : 'bg-gray-200' }}"></div>
                                <span class="text-xs {{ $done ? 'text-gray-600 font-medium' : 'text-gray-300' }}">{{ $label }}</span>
                                @if(!$loop->last)
                                    <div class="flex-1 h-px {{ $done ? 'bg-green-200' : 'bg-gray-100' }}"></div>
                                @endif
                            </div>
                        @endforeach
                        @if($req->status === 'rejected')
                            <span class="text-xs text-red-500 ms-2">✕ Rejected</span>
                        @endif
                    </div>

                    @if($req->status === 'rejected' && $req->rejection_reason)
                        <div class="px-4 py-2 bg-red-50 border-t border-red-100">
                            <p class="text-xs text-red-600"><span class="font-semibold">Reason:</span> {{ $req->rejection_reason }}</p>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <p class="text-xs text-gray-400">No sample requests yet for this campaign.</p>
    @endif
</div>

{{-- ── Sample Request Modal ──────────────────────────────────────────────────── --}}
@if($quota > 0)
<div id="samples-modal" class="samples-modal-backdrop" onclick="closeSamplesModalBackdrop(event)">
    <div class="samples-modal-box">
        <div class="samples-modal-header">
            <div>
                <h3 class="text-base font-bold text-gray-800">📦 Request Samples</h3>
                <p class="text-xs text-gray-400 mt-0.5">Search and add products you want to receive as samples.</p>
            </div>
            <button type="button" onclick="closeSamplesModal()" class="samples-modal-close">✕</button>
        </div>

        <div class="samples-modal-body">
            @if($campaign->samples_required > 0)
                <div class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-xl px-3 py-2 mb-4">
                    This vendor requires {{ $campaign->samples_required }} sample item(s) before promoting.
                </div>
            @endif

            {{-- Product search --}}
            <div class="mb-4">
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Search Products</label>
                <div class="flex gap-2">
                    <input type="text" id="sample-product-search" class="form-input flex-1 text-sm py-1.5"
                           placeholder="Type a product name…">
                    <button type="button" onclick="searchSampleProducts()"
                            class="btn btn-ghost btn-sm">Search</button>
                </div>
                <div id="sample-search-results" style="display:none"
                     class="mt-1 border border-gray-200 rounded-xl overflow-hidden">
                    <ul id="sample-product-list"
                        class="divide-y divide-gray-100 max-h-44 overflow-y-auto text-sm"></ul>
                </div>
            </div>

            {{-- Selected items --}}
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Selected Items</label>
                <div id="samples-items" class="space-y-2">
                    <p id="samples-empty-hint" class="text-xs text-gray-400">No products added yet. Search above to add items.</p>
                </div>
            </div>
        </div>

        <div class="samples-modal-footer">
            <button type="button" onclick="closeSamplesModal()" class="btn btn-ghost btn-sm">Cancel</button>
            <button type="button" id="samples-submit-btn" onclick="submitSamples()"
                class="btn btn-primary btn-sm">Submit Request</button>
        </div>
    </div>
</div>
@endif {{-- quota > 0 --}}
@endif

{{-- ── Campaign Details ─────────────────────────────────────────────────────── --}}
<div class="bg-white rounded-2xl border border-gray-100 p-5 mb-6">
    <h3 class="font-bold text-gray-800 mb-4">Details</h3>
    @if($campaign->description)
        <p class="text-sm text-gray-600 mb-4">{{ $campaign->description }}</p>
    @endif
    <div class="space-y-3 text-sm">
        @php
            $campaignCurrency = $campaign->vendor?->country?->currency_code ?? '';
            $budgetDisplay = $campaign->budget_cents
                ? number_format($campaign->budget_cents / 100, 2) . ' / spent: ' . number_format($campaign->budget_spent_cents / 100, 2) . ' ' . $campaignCurrency
                : 'Unlimited (spent: ' . number_format($campaign->budget_spent_cents / 100, 2) . ' ' . $campaignCurrency . ')';

            $samplesDisplay = $campaign->samples_required > 0
                ? $campaign->samples_required . ' item(s) — set by platform admin'
                : 'None required';

            $autoApprovalDisplay = $campaign->auto_approved
                ? 'Auto-approved on ' . $campaign->approved_at?->format('d M Y')
                : ($campaign->approved_at
                    ? 'Approved on ' . $campaign->approved_at->format('d M Y')
                    : ($campaign->auto_approve_at
                        ? 'Pending review — auto-approves ' . $campaign->auto_approve_at->format('d M Y, H:i')
                        : '—'));
        @endphp
        @foreach([
            'Status'            => ucfirst($campaign->status),
            'Type'              => ucfirst(str_replace('_', ' ', $campaign->campaign_type)),
            'Commission Rate'   => "{$campaign->commission_rate}% (" . ucfirst(str_replace('_', ' ', $campaign->commission_type)) . ')',
            'Attribution Model' => ucfirst(str_replace('_', ' ', $campaign->attribution_model ?? 'last_click')),
            'Budget'            => $budgetDisplay,
            'Start Date'        => $campaign->starts_at?->format('d M Y') ?? '—',
            'End Date'          => $campaign->ends_at?->format('d M Y') ?? 'No end date',
            'Required Samples'  => $samplesDisplay,
            'Approval'          => $autoApprovalDisplay,
            'Slug'              => $campaign->tracking_url_slug,
        ] as $label => $value)
            <div class="flex justify-between">
                <span class="text-gray-400">{{ $label }}</span>
                <span class="font-medium text-gray-700">{{ $value }}</span>
            </div>
        @endforeach
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
const ctx = document.getElementById('campaign-chart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: @json($chartLabels),
        datasets: [
            {
                label: 'Clicks',
                data: @json($clicksData),
                backgroundColor: 'rgba(59,130,246,0.5)',
                borderRadius: 4,
                order: 2,
            },
            {
                label: 'Conversions',
                data: @json($conversionsData),
                type: 'line',
                borderColor: '#facc15',
                backgroundColor: 'transparent',
                tension: 0.4,
                pointRadius: 3,
                order: 1,
            }
        ]
    },
    options: {
        responsive: true,
        scales: {
            x: { ticks: { maxTicksLimit: 8, font: { size: 10 } }, grid: { display: false } },
            y: { ticks: { font: { size: 10 } }, grid: { color: '#f8fafc' }, beginAtZero: true }
        },
        plugins: { legend: { labels: { font: { size: 11 } } } }
    }
});

// ── Product copy ──────────────────────────────────────────────────────────────
function copyProductLink(btn, url) {
    navigator.clipboard.writeText(url).then(function() {
        btn.textContent = '✓';
        setTimeout(function() { btn.textContent = 'Copy'; }, 1500);
    });
}

// ── WhatsApp Panel ────────────────────────────────────────────────────────────
function toggleWhatsappForm() {
    var form = document.getElementById('wa-form');
    var text = document.getElementById('wa-toggle-text');
    var open = form.style.display === 'none';
    form.style.display = open ? 'block' : 'none';
    text.textContent = open ? 'Cancel' : 'Generate';
}

function toggleDiscountField() {
    var type = document.getElementById('wa-link-type').value;
    document.getElementById('wa-discount-field').style.display = type === 'free_shipping' ? 'none' : 'block';
}

async function generateWhatsappLink() {
    var btn = document.getElementById('wa-generate-btn');
    btn.disabled = true;
    btn.textContent = 'Generating...';
    try {
        var res = await fetch('{{ route('marketer.campaigns.whatsapp-link', $campaign) }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            body: JSON.stringify({
                link_type: document.getElementById('wa-link-type').value,
                discount_pct: document.getElementById('wa-discount-pct').value,
            }),
        });
        var data = await res.json();
        if (data.success) {
            document.getElementById('wa-coupon-code').textContent = data.coupon_code;
            document.getElementById('wa-result-url').value = data.tracking_url;
            document.getElementById('wa-share-link').href = data.whatsapp_url;
            document.getElementById('wa-result').style.display = 'block';
        } else {
            alert(data.message || 'Error generating link');
        }
    } catch (e) { alert('Network error'); }
    finally { btn.disabled = false; btn.textContent = 'Generate WhatsApp Link'; }
}

// ── QR Panel ─────────────────────────────────────────────────────────────────
async function generateQr() {
    var btn = document.getElementById('qr-generate-btn');
    btn.disabled = true;
    btn.textContent = 'Generating...';
    try {
        var res = await fetch('{{ route('marketer.campaigns.qr-code', $campaign) }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            body: JSON.stringify({
                code_type: 'campaign',
                target_url: @json($trackingUrl),
                custom_label: @json($campaign->name),
            }),
        });
        var data = await res.json();
        if (data.success) {

            document.getElementById('qr-img').src = data.data.qr_url;
            document.getElementById('qr-download').href = data.data.download_url;
            document.getElementById('qr-result').style.display = 'block';
        } else {
            alert(data.message || 'Error generating QR');
        }
    } catch (e) { alert('Network error'); }
    finally { btn.disabled = false; btn.textContent = 'Generate QR'; }
}

// ── Samples Modal ─────────────────────────────────────────────────────────────
var samplesItems = {};   // listing_id → { listing_id, name, price, quantity }

function openSamplesModal() {
    document.getElementById('samples-modal').classList.add('open');
    document.body.style.overflow = 'hidden';
    var inp = document.getElementById('sample-product-search');
    if (inp) { inp.value = ''; inp.focus(); }
    samplesItems = {};
    renderSampleItems();
    document.getElementById('sample-search-results').style.display = 'none';
}
function closeSamplesModal() {
    document.getElementById('samples-modal').classList.remove('open');
    document.body.style.overflow = '';
}
function closeSamplesModalBackdrop(e) {
    if (e.target === document.getElementById('samples-modal')) closeSamplesModal();
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeSamplesModal();
});

var campaignCurrency = @json($campaignCurrency ?? '');

document.addEventListener('DOMContentLoaded', function () {
    var inp = document.getElementById('sample-product-search');
    if (inp) inp.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); searchSampleProducts(); }
    });
});

async function searchSampleProducts() {
    var q = document.getElementById('sample-product-search').value.trim();
    var vendorId = @json($campaign->campaignable_id ?? null);
    if (!vendorId) return;
    var res = await fetch('{{ route('marketer.campaigns.products.search') }}?vendor_id=' + encodeURIComponent(vendorId) + '&q=' + encodeURIComponent(q));
    var products = await res.json();
    var list = document.getElementById('sample-product-list');
    list.innerHTML = '';
    if (!products.length) {
        list.innerHTML = '<li class="px-4 py-2 text-gray-400 text-xs">No products found.</li>';
    } else {
        products.forEach(function (p) {
            var li = document.createElement('li');
            li.className = 'px-4 py-2 hover:bg-gray-50 flex items-center justify-between gap-2';
            li.innerHTML = '<span class="text-sm text-gray-700 flex-1">' + p.text + ' <span class="text-xs text-gray-400">' + p.price + (campaignCurrency ? ' ' + campaignCurrency : '') + '</span></span>' +
                '<button type="button" onclick="addSampleProduct(this)"' +
                ' data-id="' + p.id + '" data-name="' + p.text.replace(/"/g, '&quot;') + '" data-price="' + p.price + '"' +
                ' class="text-xs bg-yellow-400 hover:bg-yellow-300 text-slate-900 font-semibold rounded-lg px-2 py-0.5 shrink-0">+ Add</button>';
            list.appendChild(li);
        });
    }
    document.getElementById('sample-search-results').style.display = 'block';
}

var categoryQuota = {{ $quota }};

function addSampleProduct(btn) {
    var id = btn.dataset.id;
    if (!samplesItems[id]) {
        samplesItems[id] = { listing_id: id, name: btn.dataset.name, price: btn.dataset.price, quantity: categoryQuota || 1 };
    }
    renderSampleItems();
    document.getElementById('sample-search-results').style.display = 'none';
    document.getElementById('sample-product-search').value = '';
}

function removeSampleProduct(id) {
    delete samplesItems[id];
    renderSampleItems();
}

function renderSampleItems() {
    var container = document.getElementById('samples-items');
    var ids = Object.keys(samplesItems);
    if (!ids.length) {
        container.innerHTML = '<p class="text-xs text-gray-400">No products added yet. Search above to add items.</p>';
        return;
    }
    container.innerHTML = ids.map(function (id) {
        var item = samplesItems[id];
        return '<div class="flex items-center gap-2 bg-gray-50 rounded-xl px-3 py-2">' +
            '<span class="flex-1 text-sm text-gray-700 font-medium">' + item.name + '</span>' +
            '<span class="text-xs text-gray-400 shrink-0">' + item.price + (campaignCurrency ? ' ' + campaignCurrency : '') + '</span>' +
            '<span class="inline-block w-16 text-center text-sm font-semibold text-gray-700 bg-gray-200 rounded-lg py-1">' + item.quantity + '</span>' +
            '<button type="button" onclick="removeSampleProduct(\'' + id + '\')" class="text-red-400 hover:text-red-600 text-sm font-bold px-1">✕</button>' +
            '</div>';
    }).join('');
}

async function submitSamples() {
    var ids = Object.keys(samplesItems);
    if (!ids.length) { alert('Please add at least one product.'); return; }
    var btn = document.getElementById('samples-submit-btn');
    btn.disabled = true;
    btn.textContent = 'Submitting...';
    try {
        var items = ids.map(function (id) {
            return { listing_id: samplesItems[id].listing_id, quantity: samplesItems[id].quantity };
        });
        var res = await fetch('{{ route('marketer.campaigns.samples', $campaign) }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            body: JSON.stringify({ items: items }),
        });
        var data = await res.json();
        if (data.success) {
            closeSamplesModal();
            showToast('success', 'Request submitted!', 'You will be notified once the vendor approves your sample request.', 2500);
            setTimeout(function() { location.reload(); }, 1400);
        } else {
            showToast('error', 'Submission failed', data.message || 'Something went wrong. Please try again.', 0);
        }
    } catch (e) { showToast('error', 'Network error', 'Please check your connection and try again.', 0); }
    finally { btn.disabled = false; btn.textContent = 'Submit Request'; }
}

// ── Toast ─────────────────────────────────────────────────────────────────────
function showToast(type, title, message, duration) {
    var container = document.getElementById('toast-container');
    var t = document.createElement('div');
    t.className = 'toast toast-' + type;
    t.innerHTML =
        '<span class="toast-icon">' + (type === 'success' ? '✓' : '✕') + '</span>' +
        '<div class="toast-body">' +
            '<div class="toast-title">' + title + '</div>' +
            (message ? '<div class="toast-msg">' + message + '</div>' : '') +
        '</div>' +
        '<button class="toast-close" onclick="dismissToast(this.parentElement)">✕</button>';
    container.appendChild(t);
    requestAnimationFrame(function() { requestAnimationFrame(function() { t.classList.add('show'); }); });
    if (duration !== 0) {
        setTimeout(function() { dismissToast(t); }, duration || 4000);
    }
    return t;
}
function dismissToast(t) {
    if (!t || !t.parentElement) return;
    t.classList.replace('show', 'hide');
    setTimeout(function() { t.remove(); }, 250);
}

function confirmSampleReceived(requestId) {
    if (!confirm('Confirm you have received this sample?')) return;
    fetch(`{{ url('/samples') }}/` + requestId + `/mark-received`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
    }).then(r => r.json()).then(data => {
        if (data.success) {
            showToast('success', 'Marked as received!', null, 2000);
            setTimeout(function() { location.reload(); }, 1000);
        } else {
            showToast('error', 'Could not update', data.message || 'Error updating status.', 0);
        }
    }).catch(function() { showToast('error', 'Network error', 'Please try again.', 0); });
}
</script>
@endpush
