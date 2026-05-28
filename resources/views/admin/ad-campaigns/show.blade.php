@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/admin/ad-campaigns.js'])
@endpush

@section('title', 'Campaign: ' . $campaign->name)

@section('content')

    {{-- ─── Breadcrumb / header ─────────────────────────────────────────────────── --}}
    <div class="mb-6 flex items-start justify-between gap-4 flex-wrap">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('admin.ad-campaigns.index') }}" class="hover:text-primary-600">Ad Campaigns</a>
                <span>/</span>
                <span class="text-gray-800 font-medium truncate max-w-xs">{{ $campaign->name }}</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $campaign->name }}</h1>
            <p class="text-sm text-gray-500 mt-0.5">
                By <strong>{{ $campaign->vendor?->store_name ?? '—' }}</strong>
                @if($campaign->country)
                    · {{ $campaign->country->flag_emoji ?? '' }} {{ $campaign->country->name_en }}
                @endif
            </p>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            @if($campaign->status === 'pending_review')
                <button type="button"
                    class="btn btn-success js-approve-btn"
                    data-url="{{ route('admin.ad-campaigns.approve', $campaign->id) }}"
                    data-name="{{ e($campaign->name) }}">
                    Approve Campaign
                </button>
                <button type="button"
                    class="btn btn-danger js-reject-btn"
                    data-url="{{ route('admin.ad-campaigns.reject', $campaign->id) }}"
                    data-name="{{ e($campaign->name) }}">
                    Reject Campaign
                </button>
            @elseif($campaign->status === 'active')
                <button type="button"
                    class="btn btn-warning js-pause-btn"
                    data-url="{{ route('admin.ad-campaigns.pause', $campaign->id) }}"
                    data-name="{{ e($campaign->name) }}">
                    Pause Campaign
                </button>
            @elseif($campaign->status === 'paused')
                <button type="button"
                    class="btn btn-success js-resume-btn"
                    data-url="{{ route('admin.ad-campaigns.resume', $campaign->id) }}"
                    data-name="{{ e($campaign->name) }}">
                    Resume Campaign
                </button>
            @endif
            <a href="{{ route('admin.ad-campaigns.index') }}" class="btn btn-secondary">← Back</a>
        </div>
    </div>

    {{-- ─── Status banner for rejected campaigns ──────────────────────────────── --}}
    @if($campaign->status === 'rejected' && $campaign->rejection_reason)
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <strong>Rejection reason:</strong> {{ $campaign->rejection_reason }}
        </div>
    @endif

    {{-- ─── Tabs ────────────────────────────────────────────────────────────────── --}}
    <div
        x-data="{ activeTab: 'overview' }"
        class="space-y-5">

        {{-- Tab bar --}}
        <div class="flex border-b border-gray-200 gap-1 overflow-x-auto">
            @foreach([
                'overview'    => 'Overview',
                'products'    => 'Products (' . $campaign->products->count() . ')',
                'keywords'    => 'Keywords (' . $campaign->keywords->count() . ')',
                'categories'  => 'Categories (' . $campaign->categoryTargets->count() . ')',
                'performance' => 'Performance',
                'fraud'       => 'Fraud Alerts (' . $campaign->fraudPatterns->count() . ')',
            ] as $tab => $label)
                <button
                    type="button"
                    @click="activeTab = '{{ $tab }}'"
                    :class="activeTab === '{{ $tab }}' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="whitespace-nowrap px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        {{-- ═══════════ TAB: OVERVIEW ═══════════ --}}
        <div x-show="activeTab === 'overview'" x-transition>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- Campaign details --}}
                <div class="lg:col-span-2 space-y-5">

                    <x-card title="Campaign Details">
                        <dl class="grid grid-cols-2 sm:grid-cols-3 gap-x-4 gap-y-3 text-sm">
                            <div>
                                <dt class="text-gray-500 text-xs uppercase font-medium mb-0.5">Type</dt>
                                <dd>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-primary-100 text-primary-700">
                                        {{ strtoupper($campaign->type) }}
                                    </span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-gray-500 text-xs uppercase font-medium mb-0.5">Status</dt>
                                <dd>
                                    @php
                                        $statusColors = ['active'=>'success','pending_review'=>'warning','paused'=>'gray','rejected'=>'danger','ended'=>'gray','draft'=>'gray'];
                                        $sc = $statusColors[$campaign->status] ?? 'gray';
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-{{ $sc }}-100 text-{{ $sc }}-700">
                                        {{ ucwords(str_replace('_', ' ', $campaign->status)) }}
                                    </span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-gray-500 text-xs uppercase font-medium mb-0.5">Targeting</dt>
                                <dd class="font-medium">{{ ucfirst($campaign->targeting_type) }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500 text-xs uppercase font-medium mb-0.5">Total Budget</dt>
                                <dd class="font-semibold">${{ number_format($campaign->budget_total / 100, 2) }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500 text-xs uppercase font-medium mb-0.5">Daily Budget</dt>
                                <dd class="font-semibold">
                                    {{ $campaign->budget_daily ? '$' . number_format($campaign->budget_daily / 100, 2) : '—' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-gray-500 text-xs uppercase font-medium mb-0.5">Bid</dt>
                                <dd class="font-semibold">${{ number_format($campaign->bid / 100, 4) }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500 text-xs uppercase font-medium mb-0.5">Spent Total</dt>
                                <dd class="font-semibold text-orange-600">${{ number_format($campaign->budget_spent_total / 100, 2) }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500 text-xs uppercase font-medium mb-0.5">Spent Today</dt>
                                <dd class="font-semibold">${{ number_format($campaign->budget_spent_today / 100, 2) }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500 text-xs uppercase font-medium mb-0.5">Utilization</dt>
                                @php
                                    $util = $campaign->budget_total > 0 ? min(100, round($campaign->budget_spent_total / $campaign->budget_total * 100, 1)) : 0;
                                    $barClass = $util >= 90 ? 'bg-red-500' : ($util >= 70 ? 'bg-yellow-500' : 'bg-green-500');
                                @endphp
                                <dd>
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1 bg-gray-200 rounded-full h-1.5">
                                            <div class="{{ $barClass }} h-1.5 rounded-full" style="width:{{ $util }}%"></div>
                                        </div>
                                        <span class="text-xs font-medium">{{ $util }}%</span>
                                    </div>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-gray-500 text-xs uppercase font-medium mb-0.5">Starts At</dt>
                                <dd>{{ $campaign->starts_at ? $campaign->starts_at->format('d M Y H:i') : '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500 text-xs uppercase font-medium mb-0.5">Ends At</dt>
                                <dd>{{ $campaign->ends_at ? $campaign->ends_at->format('d M Y H:i') : '—' }}</dd>
                            </div>
                            @if($campaign->approvedByAdmin)
                                <div>
                                    <dt class="text-gray-500 text-xs uppercase font-medium mb-0.5">Approved By</dt>
                                    <dd>{{ $campaign->approvedByAdmin->name ?? '—' }}</dd>
                                </div>
                            @endif
                        </dl>
                    </x-card>

                    {{-- 7-day performance summary --}}
                    <x-card title="Performance (Last 7 Days)">
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            @php
                                $ctr7 = $perfSummary['ctr'] ?? 0;
                                $acos7 = $perfSummary['acos'] ?? 0;
                            @endphp
                            <div class="text-center p-3 rounded-lg bg-gray-50">
                                <div class="text-2xl font-bold text-gray-800">{{ number_format($perfSummary['impressions']) }}</div>
                                <div class="text-xs text-gray-500 mt-1">Impressions</div>
                            </div>
                            <div class="text-center p-3 rounded-lg bg-gray-50">
                                <div class="text-2xl font-bold text-gray-800">{{ number_format($perfSummary['clicks']) }}</div>
                                <div class="text-xs text-gray-500 mt-1">Clicks</div>
                            </div>
                            <div class="text-center p-3 rounded-lg bg-gray-50">
                                <div class="text-2xl font-bold text-gray-800">{{ number_format($perfSummary['conversions']) }}</div>
                                <div class="text-xs text-gray-500 mt-1">Conversions</div>
                            </div>
                            <div class="text-center p-3 rounded-lg bg-gray-50">
                                <div class="text-2xl font-bold text-primary-600">${{ number_format($perfSummary['spend_cents'] / 100, 2) }}</div>
                                <div class="text-xs text-gray-500 mt-1">Spend</div>
                            </div>
                            <div class="text-center p-3 rounded-lg bg-gray-50">
                                <div class="text-2xl font-bold text-gray-800">{{ number_format((float)$ctr7 * 100, 2) }}%</div>
                                <div class="text-xs text-gray-500 mt-1">CTR</div>
                            </div>
                            <div class="text-center p-3 rounded-lg bg-gray-50">
                                <div class="text-2xl font-bold text-gray-800">{{ number_format((float)$acos7 * 100, 2) }}%</div>
                                <div class="text-xs text-gray-500 mt-1">ACOS</div>
                            </div>
                            <div class="text-center p-3 rounded-lg bg-gray-50 sm:col-span-2">
                                <div class="text-2xl font-bold text-success-600">${{ number_format($perfSummary['revenue_attributed_cents'] / 100, 2) }}</div>
                                <div class="text-xs text-gray-500 mt-1">Revenue Attributed</div>
                            </div>
                        </div>
                    </x-card>
                </div>

                {{-- Quality score sidebar --}}
                <div class="space-y-5">
                    <x-card title="Quality Score">
                        @if($qualityScore)
                            @php
                                $scores = [
                                    'Overall'    => $qualityScore->score,
                                    'CTR Score'  => $qualityScore->ctr_score,
                                    'Relevance'  => $qualityScore->relevance_score,
                                    'Landing'    => $qualityScore->landing_score,
                                    'Vendor'     => $qualityScore->vendor_score,
                                ];
                            @endphp
                            <div class="space-y-3">
                                @foreach($scores as $label => $score)
                                    @php
                                        $pct = min(100, (float)$score * 10);
                                        $color = $score >= 7 ? 'success' : ($score >= 4 ? 'warning' : 'danger');
                                    @endphp
                                    <div>
                                        <div class="flex justify-between text-sm mb-1">
                                            <span class="text-gray-600">{{ $label }}</span>
                                            <span class="font-semibold text-{{ $color }}-600">{{ $score }}</span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-1.5">
                                            <div class="bg-{{ $color }}-500 h-1.5 rounded-full" style="width:{{ $pct }}%"></div>
                                        </div>
                                    </div>
                                @endforeach
                                <p class="text-xs text-gray-400 mt-2">Last calculated: {{ $qualityScore->calculated_at ? \Carbon\Carbon::parse($qualityScore->calculated_at)->diffForHumans() : '—' }}</p>
                            </div>
                        @else
                            <p class="text-sm text-gray-400 py-4 text-center">No quality score data yet.</p>
                        @endif
                    </x-card>
                </div>
            </div>
        </div>

        {{-- ═══════════ TAB: PRODUCTS ═══════════ --}}
        <div x-show="activeTab === 'products'" x-transition>
            <x-card title="Promoted Products">
                @if($campaign->products->isEmpty())
                    <p class="text-sm text-gray-400 py-6 text-center">No products assigned to this campaign.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-100 text-left">
                                    <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Product / Listing</th>
                                    <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Variant</th>
                                    <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Active</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach($campaign->products as $product)
                                    <tr>
                                        <td class="py-2 pr-4">
                                            <span class="font-medium">{{ $product->vendorListing?->id ?? $product->vendor_listing_id }}</span>
                                        </td>
                                        <td class="py-2 pr-4 text-gray-500">
                                            {{ $product->product_variant_id ?? '—' }}
                                        </td>
                                        <td class="py-2">
                                            @if($product->is_active)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-success-100 text-success-700">Active</span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Inactive</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-card>
        </div>

        {{-- ═══════════ TAB: KEYWORDS ═══════════ --}}
        <div x-show="activeTab === 'keywords'" x-transition>
            <x-card title="Keyword Targeting">
                @if($campaign->keywords->isEmpty())
                    <p class="text-sm text-gray-400 py-6 text-center">No keywords configured.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-100 text-left">
                                    <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Keyword</th>
                                    <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Match Type</th>
                                    <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Bid Override</th>
                                    <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Negative</th>
                                    <th class="py-2 text-xs font-medium text-gray-500 uppercase">Active</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach($campaign->keywords as $kw)
                                    @php
                                        $matchColors = ['broad' => 'gray', 'phrase' => 'primary', 'exact' => 'success'];
                                        $mc = $matchColors[$kw->match_type] ?? 'gray';
                                    @endphp
                                    <tr>
                                        <td class="py-2 pr-4 font-medium">{{ $kw->keyword }}</td>
                                        <td class="py-2 pr-4">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-{{ $mc }}-100 text-{{ $mc }}-700">
                                                {{ ucfirst($kw->match_type) }}
                                            </span>
                                        </td>
                                        <td class="py-2 pr-4">
                                            {{ $kw->bid_override ? '$' . number_format($kw->bid_override / 100, 4) : '—' }}
                                        </td>
                                        <td class="py-2 pr-4">
                                            @if($kw->is_negative)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">Negative</span>
                                            @else
                                                <span class="text-gray-400 text-xs">—</span>
                                            @endif
                                        </td>
                                        <td class="py-2">
                                            @if($kw->is_active)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-success-100 text-success-700">Active</span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Off</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-card>
        </div>

        {{-- ═══════════ TAB: CATEGORIES ═══════════ --}}
        <div x-show="activeTab === 'categories'" x-transition>
            <x-card title="Category Targeting">
                @if($campaign->categoryTargets->isEmpty())
                    <p class="text-sm text-gray-400 py-6 text-center">No category targets configured.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-100 text-left">
                                    <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Category</th>
                                    <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Bid Override</th>
                                    <th class="py-2 text-xs font-medium text-gray-500 uppercase">Active</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach($campaign->categoryTargets as $ct)
                                    <tr>
                                        <td class="py-2 pr-4 font-medium">
                                            {{ $ct->category?->name_en ?? $ct->category_id }}
                                        </td>
                                        <td class="py-2 pr-4">
                                            {{ $ct->bid_override ? '$' . number_format($ct->bid_override / 100, 4) : '—' }}
                                        </td>
                                        <td class="py-2">
                                            @if($ct->is_active)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-success-100 text-success-700">Active</span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Off</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-card>
        </div>

        {{-- ═══════════ TAB: PERFORMANCE ═══════════ --}}
        <div x-show="activeTab === 'performance'" x-transition>

            {{-- Chart --}}
            <x-card title="Impressions & Clicks (Last 30 Days)" class="mb-5">
                <div style="position:relative; height:280px;">
                    <canvas id="performance-chart"
                        data-labels="{{ json_encode($chartLabels) }}"
                        data-impressions="{{ json_encode($chartImpressions) }}"
                        data-clicks="{{ json_encode($chartClicks) }}">
                    </canvas>
                </div>
            </x-card>

            {{-- Daily stats table --}}
            <x-card title="Daily Breakdown">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100 text-left">
                                <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Impressions</th>
                                <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Clicks</th>
                                <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">CTR%</th>
                                <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Conversions</th>
                                <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Spend</th>
                                <th class="py-2 text-xs font-medium text-gray-500 uppercase">ACOS%</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse($dailyStats as $stat)
                                <tr class="hover:bg-gray-50">
                                    <td class="py-2 pr-4 font-medium text-gray-700">{{ \Carbon\Carbon::parse($stat->date)->format('d M Y') }}</td>
                                    <td class="py-2 pr-4">{{ number_format($stat->impressions) }}</td>
                                    <td class="py-2 pr-4">{{ number_format($stat->clicks) }}</td>
                                    <td class="py-2 pr-4">{{ number_format((float)$stat->ctr * 100, 2) }}%</td>
                                    <td class="py-2 pr-4">{{ number_format($stat->conversions) }}</td>
                                    <td class="py-2 pr-4 font-medium">${{ number_format($stat->spend_cents / 100, 2) }}</td>
                                    <td class="py-2">{{ number_format((float)$stat->acos * 100, 2) }}%</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-6 text-center text-gray-400 text-sm">No performance data yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>

        {{-- ═══════════ TAB: FRAUD ALERTS ═══════════ --}}
        <div x-show="activeTab === 'fraud'" x-transition>
            <x-card title="Fraud Patterns">
                @if($campaign->fraudPatterns->isEmpty())
                    <p class="text-sm text-gray-400 py-6 text-center">No fraud patterns detected for this campaign.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-100 text-left">
                                    <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">IP Address</th>
                                    <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Clicks/Hour</th>
                                    <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Clicks/24h</th>
                                    <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Blocked At</th>
                                    <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Reason</th>
                                    <th class="py-2 text-xs font-medium text-gray-500 uppercase">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach($campaign->fraudPatterns as $fp)
                                    <tr class="hover:bg-gray-50">
                                        <td class="py-2 pr-4 font-mono text-xs">{{ $fp->ip_address }}</td>
                                        <td class="py-2 pr-4 {{ $fp->clicks_last_hour >= 20 ? 'text-red-600 font-semibold' : '' }}">{{ $fp->clicks_last_hour }}</td>
                                        <td class="py-2 pr-4 {{ $fp->clicks_last_24h >= 100 ? 'text-red-600 font-semibold' : '' }}">{{ $fp->clicks_last_24h }}</td>
                                        <td class="py-2 pr-4">
                                            @if($fp->is_blocked)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">Blocked</span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">Suspicious</span>
                                            @endif
                                        </td>
                                        <td class="py-2 pr-4 text-gray-500">{{ $fp->blocked_at ? \Carbon\Carbon::parse($fp->blocked_at)->format('d M Y H:i') : '—' }}</td>
                                        <td class="py-2 pr-4 text-gray-500">{{ $fp->block_reason ?? '—' }}</td>
                                        <td class="py-2">
                                            @if(!$fp->is_blocked)
                                                <button type="button"
                                                    class="btn btn-xs btn-danger js-block-ip-btn"
                                                    data-url="{{ route('admin.ad-campaigns.fraud.block', $fp->id) }}"
                                                    data-ip="{{ $fp->ip_address }}">
                                                    Block IP
                                                </button>
                                            @else
                                                <span class="text-xs text-gray-400">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-card>
        </div>

    </div>{{-- end x-data tabs --}}

    {{-- ─── Shared Modals ──────────────────────────────────────────────────────── --}}
    <x-modal id="approve-modal" title="Approve Campaign" size="sm">
        <p class="text-sm text-gray-600">Approve <strong id="approve-campaign-name"></strong>? It will become active immediately.</p>
        <div class="flex justify-end gap-3 mt-5">
            <button type="button" class="btn btn-secondary" onclick="$('#approve-modal').modal('close')">Cancel</button>
            <button type="button" id="confirm-approve-btn" class="btn btn-success">Approve</button>
        </div>
    </x-modal>

    <x-modal id="reject-modal" title="Reject Campaign" size="md">
        <p class="text-sm text-gray-600 mb-3">Reject <strong id="reject-campaign-name"></strong>.</p>
        <label class="block text-sm font-medium text-gray-700 mb-1">Rejection Reason <span class="text-red-500">*</span></label>
        <textarea id="reject-reason-input" rows="3" class="form-input w-full text-sm" placeholder="Explain why this campaign is being rejected…"></textarea>
        <p class="text-xs text-red-500 hidden mt-1" id="reject-reason-error">Reason is required.</p>
        <div class="flex justify-end gap-3 mt-5">
            <button type="button" class="btn btn-secondary" onclick="$('#reject-modal').modal('close')">Cancel</button>
            <button type="button" id="confirm-reject-btn" class="btn btn-danger">Reject</button>
        </div>
    </x-modal>

    <x-modal id="pause-modal" title="Pause Campaign" size="sm">
        <p class="text-sm text-gray-600">Pause <strong id="pause-campaign-name"></strong>? The campaign will stop serving ads.</p>
        <div class="flex justify-end gap-3 mt-5">
            <button type="button" class="btn btn-secondary" onclick="$('#pause-modal').modal('close')">Cancel</button>
            <button type="button" id="confirm-pause-btn" class="btn btn-warning">Pause</button>
        </div>
    </x-modal>

    <x-modal id="resume-modal" title="Resume Campaign" size="sm">
        <p class="text-sm text-gray-600">Resume <strong id="resume-campaign-name"></strong>? It will start serving ads again.</p>
        <div class="flex justify-end gap-3 mt-5">
            <button type="button" class="btn btn-secondary" onclick="$('#resume-modal').modal('close')">Cancel</button>
            <button type="button" id="confirm-resume-btn" class="btn btn-success">Resume</button>
        </div>
    </x-modal>

    <x-modal id="block-ip-modal" title="Block IP Address" size="md">
        <p class="text-sm text-gray-600 mb-3">Block IP <strong id="block-ip-address" class="font-mono"></strong>?</p>
        <label class="block text-sm font-medium text-gray-700 mb-1">Block Reason</label>
        <input type="text" id="block-reason-input" class="form-input w-full text-sm" placeholder="e.g. Suspicious click pattern">
        <div class="flex justify-end gap-3 mt-5">
            <button type="button" class="btn btn-secondary" onclick="$('#block-ip-modal').modal('close')">Cancel</button>
            <button type="button" id="confirm-block-btn" class="btn btn-danger">Block IP</button>
        </div>
    </x-modal>

@endsection
