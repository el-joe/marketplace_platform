@extends('layouts.marketer')

@section('title', $campaign->name)
@section('page-title', $campaign->name)

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
<div class="bg-slate-800 rounded-2xl p-5 mb-6" x-data="{ copied: false, loading: false, url: '{{ $trackingUrl }}' }">
    <p class="text-xs font-bold text-slate-400 uppercase tracking-wide mb-3">Tracking Link</p>
    <div class="flex gap-2">
        <input type="text" :value="url" readonly
               class="flex-1 bg-slate-700 text-slate-200 font-mono text-sm rounded-xl px-3 py-2.5 border border-slate-600 focus:outline-none focus:border-yellow-400">
        <button @click="navigator.clipboard.writeText(url).then(() => { copied = true; setTimeout(() => copied = false, 2000); })"
                class="flex-shrink-0 bg-yellow-400 hover:bg-yellow-300 text-slate-900 font-bold text-sm rounded-xl px-4 py-2.5 transition-colors">
            <span x-text="copied ? '✓ Copied!' : '📋 Copy'"></span>
        </button>
    </div>

    {{-- Social share --}}
    <div class="flex gap-2 mt-3">
        <a :href="'https://wa.me/?text=' + encodeURIComponent('Check this out: ' + url)" target="_blank"
           class="flex-1 text-center bg-green-600 hover:bg-green-500 text-white text-xs font-semibold rounded-xl py-2 transition-colors">
            📱 WhatsApp
        </a>
        <button @click="navigator.clipboard.writeText(url).then(() => alert('Copied for Instagram bio!'))"
                class="flex-1 text-center bg-gradient-to-r from-purple-500 to-pink-500 hover:from-purple-400 hover:to-pink-400 text-white text-xs font-semibold rounded-xl py-2 transition-colors">
            📸 Instagram Bio
        </button>
        <a :href="'https://twitter.com/intent/tweet?url=' + encodeURIComponent(url)" target="_blank"
           class="flex-1 text-center bg-sky-500 hover:bg-sky-400 text-white text-xs font-semibold rounded-xl py-2 transition-colors">
            🐦 Twitter
        </a>
    </div>
</div>
@endif

{{-- ── Stats Grid ───────────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    @foreach([
        ['Clicks', number_format($campaign->total_clicks), 'text-blue-600'],
        ['Conversions', number_format($campaign->total_conversions), 'text-purple-600'],
        ['Conv. Rate', $campaign->total_clicks > 0 ? round($campaign->total_conversions / $campaign->total_clicks * 100, 2) . '%' : '0%', 'text-yellow-600'],
        ['Revenue', number_format($campaign->total_revenue_cents / 100, 2) . ' SAR', 'text-green-600'],
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

{{-- ── Products Grid (vendor campaigns) ───────────────────────────────────── --}}
@if($campaign->campaignable_type === \App\Models\Vendor::class && $campaign->products->isNotEmpty())
<div class="bg-white rounded-2xl border border-gray-100 p-5 mb-6">
    <h3 class="font-bold text-gray-800 mb-4">Campaign Products ({{ $campaign->products->count() }})</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        @foreach($campaign->products as $cp)
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
                        <p class="text-xs text-gray-500 mt-0.5">{{ number_format($listing->sale_price / 100, 2) }} SAR</p>
                    @endif
                    <div class="link-row" x-data="{ copied: false }">
                        <span class="link-text">{{ $productLink }}</span>
                        <button @click="navigator.clipboard.writeText('{{ $productLink }}').then(() => { copied = true; setTimeout(() => copied = false, 1500) })"
                                class="flex-shrink-0 text-xs bg-yellow-400 hover:bg-yellow-300 text-slate-900 font-semibold rounded-lg px-2 py-1 transition-colors">
                            <span x-text="copied ? '✓' : 'Copy'"></span>
                        </button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@elseif($campaign->campaignable)
{{-- ── Single-target card (classified listing or travel package) ───────────── --}}
@php
    $target = $campaign->campaignable;
    $isClassified = $campaign->campaignable_type === \App\Models\ClassifiedListing::class;
    $isTravel = $campaign->campaignable_type === \App\Models\TravelPackage::class;
    $targetTitle = $target->title ?? $target->name ?? '—';
    $targetPrice = match(true) {
        $isClassified && $target->price => number_format($target->price / 100, 2) . ' SAR',
        $isTravel && $target->price_cents => number_format($target->price_cents / 100, 2) . ' SAR',
        default => null,
    };
    $targetUrl = match(true) {
        $isClassified => url(env('DEFAULT_COUNTRY_SLUG', 'sa') . '/classifieds/' . $target->listing_number),
        $isTravel => url(env('DEFAULT_COUNTRY_SLUG', 'sa') . '/travel/' . $target->id),
        default => null,
    };
@endphp
<div class="bg-white rounded-2xl border border-gray-100 p-5 mb-6">
    <h3 class="font-bold text-gray-800 mb-4">{{ $isClassified ? 'Classified Listing' : 'Travel Package' }}</h3>
    <div class="product-card">
        @if($target->thumbnail_path ?? null)
            <img src="{{ \Illuminate\Support\Facades\Storage::url($target->thumbnail_path) }}"
                 alt="{{ $targetTitle }}" class="w-16 h-16 rounded-xl object-cover shrink-0">
        @endif
        <div class="flex-1 min-w-0">
            <p class="font-semibold text-sm text-gray-800 truncate">{{ $targetTitle }}</p>
            @if($targetPrice)
                <p class="text-xs text-gray-500 mt-0.5">{{ $targetPrice }}</p>
            @endif
            @if($targetUrl)
                <a href="{{ $targetUrl }}" target="_blank"
                   class="text-xs text-blue-600 hover:underline mt-1 inline-block">View Listing →</a>
            @endif
        </div>
    </div>
</div>
@endif

{{-- ── WhatsApp Incentive Links ────────────────────────────────────────────── --}}
@if($isActive && $campaign->whatsapp_sharing_enabled)
<div class="bg-white rounded-2xl border border-gray-100 p-5 mb-6" x-data="whatsappPanel()">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h3 class="font-bold text-gray-800">📱 WhatsApp Links</h3>
            <p class="text-xs text-gray-400 mt-0.5">Generate incentive links with discount coupons for WhatsApp sharing</p>
        </div>
        <button type="button" @click="open = !open"
            class="btn btn-sm bg-green-500 hover:bg-green-400 text-white font-semibold">
            <span x-text="open ? 'Cancel' : 'Generate'"></span>
        </button>
    </div>

    <div x-show="open" x-cloak class="border-t border-gray-100 pt-4 mt-2">
        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <label class="form-label text-xs">Link Type</label>
                <select x-model="form.link_type" class="form-input text-sm py-1.5">
                    <option value="discount">Discount %</option>
                    <option value="free_shipping">Free Shipping</option>
                    <option value="both">Discount + Free Shipping</option>
                </select>
            </div>
            <div x-show="form.link_type !== 'free_shipping'">
                <label class="form-label text-xs">Discount %</label>
                <input type="number" x-model="form.discount_pct" class="form-input text-sm py-1.5"
                    min="1" max="100" step="0.5" placeholder="e.g. 10">
            </div>
        </div>
        <button type="button" @click="generate()" :disabled="loading"
            class="btn btn-success w-full" x-text="loading ? 'Generating...' : 'Generate WhatsApp Link'"></button>

        <div x-show="result" x-cloak class="mt-4 bg-green-50 border border-green-200 rounded-xl p-4 space-y-2">
            <p class="text-xs text-gray-500">Coupon: <strong x-text="result?.coupon_code"></strong></p>
            <div class="flex gap-2">
                <input type="text" :value="result?.tracking_url" readonly class="form-input flex-1 text-xs py-1.5 font-mono">
                <button type="button" @click="navigator.clipboard.writeText(result?.tracking_url)"
                    class="btn btn-xs btn-secondary">Copy</button>
            </div>
            <a :href="result?.whatsapp_url" target="_blank"
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
                    <a :href="`https://wa.me/?text=${encodeURIComponent('{{ $wl->tracking_url }}')}`" target="_blank"
                        class="text-xs text-green-600 hover:underline">Share</a>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endif

{{-- ── QR Code Generator ────────────────────────────────────────────────────── --}}
@if($isActive)
<div class="bg-white rounded-2xl border border-gray-100 p-5 mb-6" x-data="qrPanel()">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h3 class="font-bold text-gray-800">🔲 QR Code</h3>
            <p class="text-xs text-gray-400 mt-0.5">Generate a scannable QR code for offline promotions</p>
        </div>
        <button type="button" @click="generate()" :disabled="loading"
            class="btn btn-sm btn-primary" x-text="loading ? 'Generating...' : 'Generate QR'"></button>
    </div>

    <div x-show="qrUrl" x-cloak class="text-center">
        <img :src="qrUrl" alt="QR Code" class="w-40 h-40 mx-auto rounded-xl border border-gray-200 mb-3">
        <a :href="downloadUrl" download class="btn btn-sm btn-secondary">⬇ Download PNG</a>
        <p class="text-xs text-gray-400 mt-2">Scan with any camera → opens tracking link</p>
    </div>
</div>
@endif

{{-- ── Sample Requests ───────────────────────────────────────────────────────── --}}
@if($isActive && $campaign->samples_required)
<div class="bg-white rounded-2xl border border-gray-100 p-5 mb-6" x-data="samplesPanel()">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h3 class="font-bold text-gray-800">📦 Sample Request</h3>
            <p class="text-xs text-gray-400 mt-0.5">Request product samples to review and promote</p>
        </div>
        <button type="button" @click="open = !open"
            class="btn btn-sm btn-secondary" x-text="open ? 'Cancel' : 'Request Samples'"></button>
    </div>

    <div x-show="open" x-cloak class="border-t border-gray-100 pt-4">
        <template x-for="(item, i) in items" :key="i">
            <div class="flex gap-2 mb-2">
                <input type="text" x-model="item.listing_id" placeholder="Listing UUID"
                    class="form-input flex-1 text-sm py-1.5">
                <input type="number" x-model="item.quantity" placeholder="Qty" min="1" max="10"
                    class="form-input w-20 text-sm py-1.5">
                <button type="button" @click="items.splice(i, 1)"
                    class="text-red-400 hover:text-red-600 text-sm px-2">✕</button>
            </div>
        </template>
        <button type="button" @click="items.push({ listing_id: '', quantity: 1 })"
            class="text-xs text-blue-600 hover:underline mb-3">+ Add item</button>
        <button type="button" @click="submit()" :disabled="loading"
            class="btn btn-primary w-full text-sm" x-text="loading ? 'Submitting...' : 'Submit Request'"></button>
        <p x-show="success" x-cloak class="text-sm text-green-600 mt-3 text-center font-medium">
            ✓ Request submitted! You'll be notified when approved.
        </p>
    </div>
</div>
@endif

{{-- ── Campaign Details ─────────────────────────────────────────────────────── --}}
<div class="bg-white rounded-2xl border border-gray-100 p-5 mb-6">
    <h3 class="font-bold text-gray-800 mb-4">Details</h3>
    <div class="space-y-3 text-sm">
        @foreach([
            'Commission Rate' => $campaign->commission_rate . '% (' . ucfirst(str_replace('_', ' ', $campaign->commission_type)) . ')',
            'Start Date'      => $campaign->starts_at?->format('d M Y') ?? '—',
            'End Date'        => $campaign->ends_at?->format('d M Y') ?? 'No end date',
            'Slug'            => $campaign->tracking_url_slug,
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

// ── WhatsApp Panel ────────────────────────────────────────────────────────────
function whatsappPanel() {
    return {
        open: false, loading: false, result: null,
        form: { link_type: 'discount', discount_pct: 10, free_shipping: false },
        async generate() {
            this.loading = true; this.result = null;
            try {
                const res = await fetch('{{ route('marketer.campaigns.whatsapp-link', $campaign) }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                    body: JSON.stringify(this.form),
                });
                const data = await res.json();
                if (data.success) this.result = data;
                else alert(data.message || 'Error generating link');
            } catch (e) { alert('Network error'); }
            finally { this.loading = false; }
        }
    };
}

// ── QR Panel ─────────────────────────────────────────────────────────────────
function qrPanel() {
    return {
        loading: false, qrUrl: null, downloadUrl: null,
        async generate() {
            this.loading = true;
            try {
                const res = await fetch('{{ route('marketer.campaigns.qr-code', $campaign) }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                    body: JSON.stringify({
                        code_type: 'campaign',
                        target_url: '{{ $trackingUrl }}',
                        custom_label: '{{ $campaign->name }}',
                    }),
                });
                const data = await res.json();
                if (data.success) { this.qrUrl = data.data.qr_url; this.downloadUrl = data.data.download_url; }
                else alert(data.message || 'Error');
            } catch (e) { alert('Network error'); }
            finally { this.loading = false; }
        }
    };
}

// ── Samples Panel ─────────────────────────────────────────────────────────────
function samplesPanel() {
    return {
        open: false, loading: false, success: false,
        items: [{ listing_id: '', quantity: 1 }],
        async submit() {
            this.loading = true;
            try {
                const res = await fetch('{{ route('marketer.campaigns.samples', $campaign) }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                    body: JSON.stringify({ items: this.items }),
                });
                const data = await res.json();
                if (data.success) { this.success = true; this.open = false; }
                else alert(data.message || 'Error');
            } catch (e) { alert('Network error'); }
            finally { this.loading = false; }
        }
    };
}
</script>
@endpush
