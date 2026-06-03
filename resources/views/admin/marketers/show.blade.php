@extends('layouts.admin')

@push('styles')
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
                <span class="badge badge-{{ $marketer->status_color }}">{{ ucfirst($marketer->status) }}</span>
                <span class="badge badge-secondary ml-1">{{ $marketer->type_label }}</span>
            </div>
        </div>

        <div class="space-y-2 text-sm">
            @foreach([
                'Email'    => $marketer->email,
                'Phone'    => $marketer->phone ?? '—',
                'Country'  => $marketer->country?->name_en ?? '—',
                'Niche'    => $marketer->niche ?? '—',
                'Ref Code' => $marketer->referral_code,
                'Joined'   => $marketer->created_at->format('d M Y'),
            ] as $label => $value)
                <div class="flex justify-between gap-2">
                    <span class="text-gray-400">{{ $label }}</span>
                    <span class="font-medium text-gray-700 text-right">{{ $value }}</span>
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
            @if($marketer->status === 'pending')
                <button type="button" class="btn btn-success btn-sm flex-1" id="btn-approve">Approve</button>
                <button type="button" class="btn btn-danger btn-sm flex-1" id="btn-reject">Reject</button>
            @elseif($marketer->status === 'active')
                <button type="button" class="btn btn-warning btn-sm flex-1" id="btn-suspend">Suspend</button>
            @elseif($marketer->status === 'suspended')
                <button type="button" class="btn btn-success btn-sm flex-1" id="btn-activate">Activate</button>
            @endif
        </div>
    </div>

    {{-- Stats Column --}}
    <div class="lg:col-span-2 grid grid-cols-2 sm:grid-cols-3 gap-4 content-start">
        @foreach([
            ['Campaigns',        $stats['total_campaigns'],    'total'],
            ['Active Campaigns', $stats['active_campaigns'],   'active'],
            ['Conversions',      $stats['total_conversions'],  'conversions'],
            ['Followers',        number_format($marketer->followers_count), 'followers'],
            ['Total Clicks',     number_format($marketer->total_clicks),    'clicks'],
            ['Comm. Rate',       $marketer->commission_rate . '%',          'rate'],
        ] as [$label, $value, $key])
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">{{ $label }}</p>
                <p class="mt-1 text-2xl font-bold text-gray-800">{{ $value }}</p>
            </div>
        @endforeach

        <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4">
            <p class="text-xs font-medium text-yellow-600 uppercase tracking-wide">Pending Earnings</p>
            <p class="mt-1 text-2xl font-bold text-yellow-700">{{ number_format($stats['pending_earnings'] / 100, 2) }} SAR</p>
        </div>
        <div class="bg-green-50 border border-green-200 rounded-xl p-4">
            <p class="text-xs font-medium text-green-600 uppercase tracking-wide">Paid Earnings</p>
            <p class="mt-1 text-2xl font-bold text-green-700">{{ number_format($stats['paid_earnings'] / 100, 2) }} SAR</p>
        </div>
    </div>
</div>

{{-- ─── Tabs ────────────────────────────────────────────────────────────────── --}}
<div x-data="{ tab: 'campaigns' }">

    <div class="flex gap-1 bg-gray-100 rounded-xl p-1 mb-5 flex-wrap">
        @foreach([
            'campaigns'     => 'Campaigns',
            'conversions'   => 'Conversions',
            'tiers'         => '📈 Tiers',
            'samples'       => '📦 Samples',
            'secret_promos' => '🔒 Secret Promos',
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
            <table id="marketer-campaigns-table" class="w-full text-sm" style="width:100%">
                <thead>
                    <tr>
                        <th>Campaign</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Clicks</th>
                        <th>Conv.</th>
                        <th>Revenue (SAR)</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </x-card>
    </div>

    {{-- Conversions DataTable --}}
    <div x-show="tab === 'conversions'">
        <x-card>
            <table id="marketer-conversions-table" class="w-full text-sm" style="width:100%">
                <thead>
                    <tr>
                        <th>Marketer</th>
                        <th>Campaign</th>
                        <th>Order Value</th>
                        <th>Commission</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Bulk</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </x-card>
    </div>

    {{-- Commission Tiers --}}
    <div x-show="tab === 'tiers'">
        <div class="bg-white rounded-2xl border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h3 class="text-lg font-bold text-gray-800">Commission Tiers (العمولات المتدرجة)</h3>
                    <p class="text-sm text-gray-500 mt-1">Current sales: <strong>{{ $stats['total_conversions'] }}</strong></p>
                </div>
                <a href="{{ route('admin.marketers.tiers.show', $marketer) }}" class="btn btn-primary btn-sm">Edit Tiers</a>
            </div>
            @php $tiers = $marketer->commissionTiers()->whereNull('campaign_id')->orderBy('tier_order')->get(); @endphp
            @if($tiers->isEmpty())
                <p class="text-sm text-gray-400 italic">No tiers configured. Default rate: {{ $marketer->commission_rate }}%</p>
            @else
                <div class="space-y-3">
                    @foreach($tiers as $tier)
                        @php $isCurrent = $stats['total_conversions'] >= $tier->min_sales_count && ($tier->max_sales_count === null || $stats['total_conversions'] <= $tier->max_sales_count); @endphp
                        <div class="flex items-center gap-4 p-4 rounded-xl {{ $isCurrent ? 'bg-primary-50 border-2 border-primary-300' : 'bg-gray-50 border border-gray-200' }}">
                            <div class="w-8 h-8 rounded-full {{ $isCurrent ? 'bg-primary-500 text-white' : 'bg-gray-200 text-gray-600' }} flex items-center justify-center font-bold text-sm">{{ $tier->tier_order }}</div>
                            <div class="flex-1">
                                <p class="text-sm font-semibold">
                                    {{ number_format($tier->min_sales_count) }} – {{ $tier->max_sales_count ? number_format($tier->max_sales_count) : '∞' }} sales
                                </p>
                                <p class="text-xs text-gray-500">Commission: <strong>{{ $tier->commission_rate }}%</strong></p>
                            </div>
                            @if($isCurrent)
                                <span class="badge badge-primary">Current Tier</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Sample Requests --}}
    <div x-show="tab === 'samples'">
        <div class="bg-white rounded-2xl border border-gray-200 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Sample Requests</h3>
            @php $samples = $marketer->sampleRequests()->with(['vendor', 'items.vendorListing.product'])->orderByDesc('created_at')->limit(20)->get(); @endphp
            @if($samples->isEmpty())
                <p class="text-sm text-gray-400 italic">No sample requests yet.</p>
            @else
                <div class="space-y-3">
                    @foreach($samples as $sample)
                        <div class="border border-gray-200 rounded-xl p-4">
                            <div class="flex items-center justify-between mb-2">
                                <div>
                                    <span class="font-medium text-sm">{{ $sample->vendor->store_name ?? '—' }}</span>
                                    <span class="ml-2 text-xs text-gray-400">{{ $sample->created_at->format('d M Y') }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="badge badge-{{ $sample->status_color }}">{{ ucfirst($sample->status) }}</span>
                                    @if($sample->status === 'requested')
                                        <button type="button" data-id="{{ $sample->id }}" class="btn btn-xs btn-success btn-approve-sample-inline">Approve</button>
                                    @elseif($sample->status === 'approved')
                                        <button type="button" data-id="{{ $sample->id }}" class="btn btn-xs btn-primary btn-dispatch-sample-inline">Dispatch</button>
                                    @endif
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                @foreach($sample->items as $item)
                                    <span class="text-xs bg-gray-100 rounded px-2 py-1">
                                        {{ $item->vendorListing?->product?->name_en ?? $item->vendor_listing_id }} × {{ $item->quantity }}
                                    </span>
                                @endforeach
                            </div>
                            <p class="text-xs text-gray-400 mt-2">
                                Total cost: {{ number_format($sample->total_cost_cents / 100, 2) }} EGP
                            </p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Secret Promotions --}}
    <div x-show="tab === 'secret_promos'">
        <div class="bg-white rounded-2xl border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-800">🔒 Secret Promotions</h3>
                <button type="button" class="btn btn-primary btn-sm" @click="$dispatch('open-modal', 'add-secret-promo')">+ Add Promotion</button>
            </div>
            @php $promos = \App\Models\MarketerSecretPromotion::where('marketer_id', $marketer->id)->with('vendorListing.product')->orderByDesc('created_at')->get(); @endphp
            @if($promos->isEmpty())
                <p class="text-sm text-gray-400 italic">No secret promotions for this marketer.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-left">
                            <tr>
                                <th class="px-3 py-2 font-medium text-gray-500">Listing</th>
                                <th class="px-3 py-2 font-medium text-gray-500 text-right">Total %</th>
                                <th class="px-3 py-2 font-medium text-gray-500 text-right">Marketer %</th>
                                <th class="px-3 py-2 font-medium text-gray-500 text-right">Platform %</th>
                                <th class="px-3 py-2 font-medium text-gray-500">Status</th>
                                <th class="px-3 py-2 font-medium text-gray-500">Valid Until</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($promos as $promo)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-2">{{ $promo->vendorListing?->product?->name_en ?? '—' }}</td>
                                    <td class="px-3 py-2 text-right font-mono">{{ $promo->total_commission_pct }}%</td>
                                    <td class="px-3 py-2 text-right font-mono text-green-600">{{ $promo->marketer_share_pct }}%</td>
                                    <td class="px-3 py-2 text-right font-mono text-blue-600">{{ $promo->admin_share_pct }}%</td>
                                    <td class="px-3 py-2"><span class="badge badge-{{ $promo->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($promo->status) }}</span></td>
                                    <td class="px-3 py-2 text-gray-500">{{ $promo->valid_until?->format('d M Y') ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
$(function () {
    const markId = '{{ $marketer->id }}';
    const tok    = '{{ csrf_token() }}';

    // ── Campaigns Table ───────────────────────────────────────────────────────
    $('#marketer-campaigns-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url:  '{{ route('admin.marketers.marketer-campaigns.datatable', $marketer->id) }}',
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': tok },
        },
        columns: [
            {}, {}, {}, {}, {}, {}, {}, { orderable: false }
        ],
        order: [[6, 'desc']],
        pageLength: 10,
    });

    // ── Conversions Table ─────────────────────────────────────────────────────
    $('#marketer-conversions-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url:  '{{ route('admin.marketers.marketer-conversions.datatable', $marketer->id) }}',
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': tok },
        },
        columns: [
            {}, {}, {}, {}, {}, {}, { orderable: false }
        ],
        order: [[5, 'desc']],
        pageLength: 10,
    });

    // ── Approve / Reject / Suspend / Activate ─────────────────────────────────
    $('#btn-approve').on('click', function () {
        window.confirmDialog({ title: 'Approve marketer?', confirmText: 'Approve', onConfirm: () => {
            $.post('/admin/marketers/' + markId + '/approve', { _token: tok })
                .done(r => { window.Toast.success(r.message); setTimeout(() => location.reload(), 1200); })
                .fail(xhr => window.Toast.error(xhr.responseJSON?.message || 'Error'));
        }});
    });
    $('#btn-reject').on('click', function () {
        const reason = prompt('Rejection reason:');
        if (!reason) return;
        $.post('/admin/marketers/' + markId + '/reject', { _token: tok, reason })
            .done(r => { window.Toast.success(r.message); setTimeout(() => location.reload(), 1200); })
            .fail(xhr => window.Toast.error(xhr.responseJSON?.message || 'Error'));
    });
    $('#btn-suspend').on('click', function () {
        window.confirmDialog({ title: 'Suspend marketer?', onConfirm: () => {
            $.post('/admin/marketers/' + markId + '/suspend', { _token: tok })
                .done(r => { window.Toast.success(r.message); setTimeout(() => location.reload(), 1200); })
                .fail(xhr => window.Toast.error(xhr.responseJSON?.message || 'Error'));
        }});
    });
    $('#btn-activate').on('click', function () {
        $.post('/admin/marketers/' + markId + '/activate', { _token: tok })
            .done(r => { window.Toast.success(r.message); setTimeout(() => location.reload(), 1200); })
            .fail(xhr => window.Toast.error(xhr.responseJSON?.message || 'Error'));
    });

    // ── Sample inline actions ────────────────────────────────────────────────
    $(document).on('click', '.btn-approve-sample-inline', function () {
        const id = $(this).data('id');
        $.post('/admin/marketer-samples/' + id + '/approve', { _token: tok })
            .done(r => { window.Toast.success(r.message); setTimeout(() => location.reload(), 1200); })
            .fail(xhr => window.Toast.error(xhr.responseJSON?.message || 'Error'));
    });
    $(document).on('click', '.btn-dispatch-sample-inline', function () {
        const id = $(this).data('id');
        $.post('/admin/marketer-samples/' + id + '/dispatch', { _token: tok })
            .done(r => { window.Toast.success(r.message); setTimeout(() => location.reload(), 1200); })
            .fail(xhr => window.Toast.error(xhr.responseJSON?.message || 'Error'));
    });
});
</script>
@endpush
