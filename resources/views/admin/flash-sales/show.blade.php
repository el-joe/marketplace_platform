@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/column-renderers.js'])
@endpush

@push('scripts')
    @vite('resources/js/admin/flash-sale-detail.js')
@endpush

@section('title', 'Flash Sale: ' . $flashSale->name_en)

@section('content')
    @php
        $statusColors = [
            'draft'             => 'gray',
            'submission_open'   => 'primary',
            'submission_closed' => 'warning',
            'under_review'      => 'info',
            'approved'          => 'success',
            'live'              => 'success',
            'ended'             => 'gray',
            'cancelled'         => 'danger',
        ];
        $statusLabels = [
            'draft'             => 'Draft',
            'submission_open'   => 'Accepting Submissions',
            'submission_closed' => 'Submissions Closed',
            'under_review'      => 'Under Review',
            'approved'          => 'Approved',
            'live'              => 'Live',
            'ended'             => 'Ended',
            'cancelled'         => 'Cancelled',
        ];
    @endphp

    {{-- Pass data to JS --}}
    <script>
        window.FLASH_SALE_ID     = '{{ $flashSale->id }}';
        window.FLASH_SALE_STATUS = '{{ $flashSale->status }}';
        window.URLS = {
            show:              '{{ route('admin.flash-sales.show', $flashSale->id) }}',
            edit:              '{{ route('admin.flash-sales.edit', $flashSale->id) }}',
            transition:        '{{ route('admin.flash-sales.transition', $flashSale->id) }}',
            inviteVendors:     '{{ route('admin.flash-sales.invite-vendors', $flashSale->id) }}',
            eligibleCount:     '{{ route('admin.flash-sales.eligible-vendor-count', $flashSale->id) }}',
            submissionStats:   '{{ route('admin.flash-sales.submission-stats', $flashSale->id) }}',
            submissionsDt:     '{{ route('admin.flash-sales.submissions.datatable', $flashSale->id) }}',
            invitationsDt:     '{{ route('admin.flash-sales.invitations.datatable', $flashSale->id) }}',
            resendInvitation:  '{{ url('/flash-sales/' . $flashSale->id . '/invitations') }}',
            bulkReview:        '{{ route('admin.flash-sales.submissions.bulk-review', $flashSale->id) }}',
            liveData:          '{{ route('admin.flash-sales.live-data', $flashSale->id) }}',
            analyticsData:     '{{ route('admin.flash-sales.analytics-data', $flashSale->id) }}',
            submissionDetail:  '{{ url('/flash-sales/submissions') }}',
        };
        window.MIN_DISCOUNT_PCT = {{ (float) $flashSale->min_discount_pct }};
    </script>

    <div class="flex gap-6 items-start">

        {{-- ═══════════════════════════════════════════════════════════════════ --}}
        {{-- MAIN COLUMN --}}
        {{-- ═══════════════════════════════════════════════════════════════════ --}}
        <div class="flex-1 min-w-0 space-y-4">

            {{-- Tab nav --}}
            <div x-data="{ tab: 'submissions' }">
                <nav class="flex gap-1 border-b border-gray-200 mb-5 overflow-x-auto">
                    @foreach([
                        'submissions'  => ['label' => 'Submissions',  'icon' => 'inbox-stack'],
                        'invitations'  => ['label' => 'Invitations',  'icon' => 'envelope'],
                        'live'         => ['label' => 'Live Monitor', 'icon' => 'signal'],
                        'analytics'    => ['label' => 'Analytics',    'icon' => 'chart-bar'],
                        'settings'     => ['label' => 'Settings',     'icon' => 'cog-6-tooth'],
                    ] as $key => $cfg)
                        <button type="button"
                            @click="tab = '{{ $key }}'"
                            :class="tab === '{{ $key }}'
                                ? 'border-b-2 border-primary-600 text-primary-700 font-semibold'
                                : 'text-gray-500 hover:text-gray-700'"
                            class="flex items-center gap-1.5 px-4 py-2.5 text-sm transition-colors whitespace-nowrap">
                            <x-heroicon name="{{ $cfg['icon'] }}" class="w-4 h-4" />
                            {{ $cfg['label'] }}
                            @if($key === 'submissions')
                                <span class="ml-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">
                                    {{ $submissionStats->sum() }}
                                </span>
                            @elseif($key === 'invitations')
                                <span class="ml-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">
                                    {{ $invitationCount }}
                                </span>
                            @endif
                        </button>
                    @endforeach
                </nav>

                {{-- ─── Submissions tab ──────────────────────────────────────── --}}
                <div x-show="tab === 'submissions'" x-cloak>

                    {{-- Status strip --}}
                    <div class="flex flex-wrap items-center gap-2 mb-4">
                        @php
                            $pillColors = [
                                'submitted'    => 'bg-gray-100 text-gray-700',
                                'under_review' => 'bg-amber-100 text-amber-800',
                                'approved'     => 'bg-primary-100 text-primary-800',
                                'live'         => 'bg-emerald-100 text-emerald-800',
                                'sold_out'     => 'bg-orange-100 text-orange-800',
                                'rejected'     => 'bg-red-100 text-red-800',
                                'withdrawn'    => 'bg-gray-100 text-gray-500',
                                'ended'        => 'bg-gray-100 text-gray-500',
                            ];
                        @endphp
                        @foreach($submissionStats as $status => $count)
                            <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium {{ $pillColors[$status] ?? 'bg-gray-100 text-gray-700' }}">
                                {{ ucwords(str_replace('_', ' ', $status)) }}
                                <span class="font-bold">{{ $count }}</span>
                            </span>
                        @endforeach

                        <div class="ml-auto flex items-center gap-2">
                            <select id="sub-filter-status" class="form-select form-select-sm">
                                <option value="">All statuses</option>
                                @foreach(['submitted' => 'Submitted','under_review' => 'Under Review','approved' => 'Approved','rejected' => 'Rejected','live' => 'Live','sold_out' => 'Sold Out','ended' => 'Ended'] as $v => $l)
                                    <option value="{{ $v }}">{{ $l }}</option>
                                @endforeach
                            </select>
                            @if(auth('admin')->user()->can('flash_sales.review_submissions'))
                                <button type="button" id="btn-bulk-reject" class="btn btn-danger btn-sm hidden">
                                    Bulk Reject
                                </button>
                            @endif
                        </div>
                    </div>

                    <table id="submissions-table" class="w-full text-sm"></table>
                </div>

                {{-- ─── Invitations tab ──────────────────────────────────────── --}}
                <div x-show="tab === 'invitations'" x-cloak>

                    {{-- Stat cards --}}
                    @php
                        $invStatConfig = [
                            'pending'   => ['label' => 'Pending',   'color' => 'bg-amber-50 border-amber-200',   'dot' => 'bg-amber-400',   'text' => 'text-amber-800'],
                            'accepted'  => ['label' => 'Accepted',  'color' => 'bg-emerald-50 border-emerald-200','dot' => 'bg-emerald-500', 'text' => 'text-emerald-800'],
                            'declined'  => ['label' => 'Declined',  'color' => 'bg-red-50 border-red-200',       'dot' => 'bg-red-400',     'text' => 'text-red-800'],
                            'submitted' => ['label' => 'Submitted', 'color' => 'bg-blue-50 border-blue-200',     'dot' => 'bg-blue-500',    'text' => 'text-blue-800'],
                        ];
                    @endphp
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
                        @foreach($invStatConfig as $status => $cfg)
                            <div class="rounded-xl border {{ $cfg['color'] }} px-4 py-3 flex items-center gap-3">
                                <span class="w-2.5 h-2.5 rounded-full flex-shrink-0 {{ $cfg['dot'] }}"></span>
                                <div>
                                    <p class="text-xs font-medium {{ $cfg['text'] }}">{{ $cfg['label'] }}</p>
                                    <p class="text-2xl font-bold {{ $cfg['text'] }} leading-tight">{{ $invitationStats[$status] ?? 0 }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <x-card>
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <p class="text-sm text-gray-500">{{ $invitationCount }} vendor(s) invited total.</p>
                                <select id="inv-filter-status" class="form-select form-select-sm">
                                    <option value="">All statuses</option>
                                    @foreach($invStatConfig as $status => $cfg)
                                        <option value="{{ $status }}">{{ $cfg['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @if(!in_array($flashSale->status, ['ended', 'cancelled']))
                                <div class="flex gap-2">
                                    <button type="button" id="btn-auto-invite" class="btn btn-secondary btn-sm">
                                        <x-heroicon name="user-group" class="w-4 h-4 mr-1.5" />
                                        Auto-Invite Eligible
                                    </button>
                                    <button type="button" data-modal-open="manual-invite-modal" class="btn btn-ghost btn-sm">
                                        <x-heroicon name="user-plus" class="w-4 h-4 mr-1.5" />
                                        Invite Manually
                                    </button>
                                </div>
                            @endif
                        </div>
                        <table id="invitations-table" class="w-full text-sm"></table>
                    </x-card>
                </div>

                {{-- ─── Live Monitor tab ─────────────────────────────────────── --}}
                <div x-show="tab === 'live'" x-cloak>
                    @if($flashSale->status === 'live')
                        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                            <x-stat-card label="Units Sold"     id="live-units"     value="—" color="success" icon="shopping-bag" />
                            <x-stat-card label="Revenue"        id="live-revenue"   value="—" color="primary" icon="currency-dollar" />
                            <x-stat-card label="Sold Out"       id="live-sold-out"  value="—" color="warning" icon="x-circle" />
                            <x-stat-card label="Time Remaining" id="live-countdown" value="—" color="info"    icon="clock" />
                        </div>

                        <x-card title="Top Products">
                            <table class="w-full text-sm">
                                <thead class="text-xs text-gray-500 uppercase bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left">Product</th>
                                        <th class="px-4 py-2 text-right">Sold</th>
                                        <th class="px-4 py-2 text-right">Remaining</th>
                                        <th class="px-4 py-2 text-right">Revenue</th>
                                    </tr>
                                </thead>
                                <tbody id="live-top-tbody" class="divide-y divide-gray-100">
                                    <tr><td colspan="4" class="text-center py-8 text-gray-400 text-sm italic">Loading…</td></tr>
                                </tbody>
                            </table>
                        </x-card>
                    @else
                        <x-card>
                            <div class="text-center py-12 text-gray-400">
                                <x-heroicon name="signal" class="w-12 h-12 mx-auto mb-3 opacity-30" />
                                <p class="text-sm">Live monitor is only available while the sale is active.</p>
                                <p class="text-xs mt-1">Current status: <span class="font-medium">{{ $statusLabels[$flashSale->status] ?? $flashSale->status }}</span></p>
                            </div>
                        </x-card>
                    @endif
                </div>

                {{-- ─── Analytics tab ────────────────────────────────────────── --}}
                <div x-show="tab === 'analytics'" x-cloak>
                    @if(in_array($flashSale->status, ['live', 'ended']))
                        <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                            <x-stat-card label="Total Units Sold"    id="an-units"      value="—" color="primary" icon="shopping-bag" />
                            <x-stat-card label="Gross Revenue"       id="an-revenue"    value="—" color="success" icon="currency-dollar" />
                            <x-stat-card label="Discount Given"      id="an-discount"   value="—" color="warning" icon="tag" />
                            <x-stat-card label="Platform Commission" id="an-commission" value="—" color="info"    icon="building-library" />
                            <x-stat-card label="Vendor Payout"       id="an-payout"     value="—" color="success" icon="banknotes" />
                            <x-stat-card label="Avg Conversion"      id="an-conversion" value="—" color="gray"    icon="arrow-trending-up" />
                        </div>

                        <x-card title="Daily Breakdown">
                            <table class="w-full text-sm">
                                <thead class="text-xs text-gray-500 uppercase bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left">Date</th>
                                        <th class="px-4 py-2 text-right">Units</th>
                                        <th class="px-4 py-2 text-right">Revenue</th>
                                        <th class="px-4 py-2 text-right">Discount</th>
                                    </tr>
                                </thead>
                                <tbody id="analytics-tbody">
                                    <tr><td colspan="4" class="text-center py-8 text-gray-400 text-sm italic">Loading…</td></tr>
                                </tbody>
                            </table>
                        </x-card>
                    @else
                        <x-card>
                            <div class="text-center py-12 text-gray-400">
                                <x-heroicon name="chart-bar" class="w-12 h-12 mx-auto mb-3 opacity-30" />
                                <p class="text-sm">Analytics will be available once the sale goes live.</p>
                            </div>
                        </x-card>
                    @endif
                </div>

                {{-- ─── Settings tab ─────────────────────────────────────────── --}}
                <div x-show="tab === 'settings'" x-cloak>
                    <x-card title="Flash Sale Details">
                        <div class="grid grid-cols-1 gap-y-4 sm:grid-cols-2 text-sm">
                            <div>
                                <p class="text-gray-500 text-xs mb-0.5">Name (English)</p>
                                <p class="font-medium text-gray-900">{{ $flashSale->name_en }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500 text-xs mb-0.5">Name (Arabic)</p>
                                <p class="font-medium text-gray-900 text-right" dir="rtl">{{ $flashSale->name_ar }}</p>
                            </div>
                            @if($flashSale->description_en)
                                <div class="sm:col-span-2">
                                    <p class="text-gray-500 text-xs mb-0.5">Description (EN)</p>
                                    <p class="text-gray-700">{{ $flashSale->description_en }}</p>
                                </div>
                            @endif
                            <div>
                                <p class="text-gray-500 text-xs mb-0.5">Submission Opens</p>
                                <p class="text-gray-700">{{ $flashSale->submission_opens_at?->format('M j, Y H:i') ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500 text-xs mb-0.5">Submission Closes</p>
                                <p class="text-gray-700">{{ $flashSale->submission_closes_at?->format('M j, Y H:i') ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500 text-xs mb-0.5">Review Deadline</p>
                                <p class="text-gray-700">{{ $flashSale->review_deadline_at?->format('M j, Y H:i') ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500 text-xs mb-0.5">Sale Starts</p>
                                <p class="text-gray-700">{{ $flashSale->sale_starts_at?->format('M j, Y H:i') ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500 text-xs mb-0.5">Sale Ends</p>
                                <p class="text-gray-700">{{ $flashSale->sale_ends_at?->format('M j, Y H:i') ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500 text-xs mb-0.5">Min Discount</p>
                                <p class="font-medium text-gray-900">{{ $flashSale->min_discount_pct }}%</p>
                            </div>
                            <div>
                                <p class="text-gray-500 text-xs mb-0.5">Max Products / Vendor</p>
                                <p class="font-medium text-gray-900">{{ $flashSale->max_products_per_seller ?? '∞' }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500 text-xs mb-0.5">Max Total Slots</p>
                                <p class="font-medium text-gray-900">{{ $flashSale->max_total_slots ?? '∞' }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500 text-xs mb-0.5">Commission Override</p>
                                <p class="font-medium text-gray-900">{{ $flashSale->commission_override_pct ? $flashSale->commission_override_pct . '%' : 'Default' }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500 text-xs mb-0.5">Min Seller Rating</p>
                                <p class="font-medium text-gray-900">{{ $flashSale->min_seller_rating ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500 text-xs mb-0.5">Eligible Tiers</p>
                                <p class="font-medium text-gray-900">
                                    {{ !empty($flashSale->eligible_seller_tiers) ? implode(', ', $flashSale->eligible_seller_tiers) : 'All' }}
                                </p>
                            </div>
                            <div class="sm:col-span-2">
                                <p class="text-gray-500 text-xs mb-0.5">Eligible Categories</p>
                                <p class="font-medium text-gray-900">
                                    {{ !empty($flashSale->eligible_categories) ? count($flashSale->eligible_categories) . ' categories selected' : 'All' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-gray-500 text-xs mb-0.5">Featured</p>
                                <p class="font-medium">{{ $flashSale->is_featured ? '✓ Yes' : 'No' }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500 text-xs mb-0.5">Exclusive</p>
                                <p class="font-medium">{{ $flashSale->is_exclusive ? '✓ Yes (invite-only)' : 'No' }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500 text-xs mb-0.5">Price Drop Required</p>
                                <p class="font-medium">{{ $flashSale->price_drop_required ? '✓ Yes' : 'No' }}</p>
                            </div>
                        </div>

                        @if(!in_array($flashSale->status, ['live', 'ended', 'cancelled']))
                            <div class="mt-4 pt-4 border-t border-gray-100">
                                <a href="{{ route('admin.flash-sales.edit', $flashSale->id) }}" class="btn btn-secondary btn-sm">
                                    <x-heroicon name="pencil-square" class="w-4 h-4 mr-1.5" />
                                    Edit Settings
                                </a>
                            </div>
                        @endif
                    </x-card>
                </div>

            </div>{{-- /tabs --}}
        </div>{{-- /main column --}}

        {{-- ═══════════════════════════════════════════════════════════════════ --}}
        {{-- SIDEBAR --}}
        {{-- ═══════════════════════════════════════════════════════════════════ --}}
        <div class="w-80 flex-shrink-0 space-y-4 sticky top-20">

            {{-- Summary card --}}
            <x-card title="{{ $flashSale->name_en }}">
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-500">Status</span>
                        <x-badge :color="$statusColors[$flashSale->status] ?? 'gray'">
                            {{ $statusLabels[$flashSale->status] ?? $flashSale->status }}
                        </x-badge>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Country</span>
                        <span class="text-gray-700">{{ $flashSale->country?->name_en ?? 'All' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Approved Slots</span>
                        <span class="font-mono font-medium">{{ $flashSale->approved_slots_count }} / {{ $flashSale->max_total_slots ?? '∞' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Submissions</span>
                        <span class="font-mono font-medium">{{ $submissionStats->sum() }}</span>
                    </div>
                    @if($flashSale->is_featured)
                        <div class="flex items-center gap-1.5 text-amber-700 text-xs font-medium">
                            <x-heroicon name="star" class="w-3.5 h-3.5" />
                            Featured
                        </div>
                    @endif
                    @if($flashSale->is_exclusive)
                        <div class="flex items-center gap-1.5 text-purple-700 text-xs font-medium">
                            <x-heroicon name="lock-closed" class="w-3.5 h-3.5" />
                            Exclusive / Invite-Only
                        </div>
                    @endif
                </div>
            </x-card>

            {{-- Timeline --}}
            <x-card title="Timeline">
                <div class="space-y-2 text-xs text-gray-600">
                    @foreach([
                        'Submissions Open'  => $flashSale->submission_opens_at,
                        'Submissions Close' => $flashSale->submission_closes_at,
                        'Review Deadline'   => $flashSale->review_deadline_at,
                        'Sale Starts'       => $flashSale->sale_starts_at,
                        'Sale Ends'         => $flashSale->sale_ends_at,
                    ] as $label => $date)
                        <div class="flex justify-between">
                            <span class="text-gray-500">{{ $label }}</span>
                            <span>{{ $date?->format('M j, Y H:i') ?? '—' }}</span>
                        </div>
                    @endforeach
                </div>
            </x-card>

            {{-- Actions --}}
            @if(!in_array($flashSale->status, ['ended', 'cancelled']))
                <x-card title="Actions">
                    <div class="space-y-2">
                        @php
                            $actionMap = [
                                'submission_open'   => ['action' => 'open_submissions',  'label' => 'Open Submissions',  'color' => 'primary'],
                                'submission_closed' => ['action' => 'close_submissions', 'label' => 'Close Submissions', 'color' => 'secondary'],
                                'under_review'      => ['action' => 'move_to_review',    'label' => 'Move to Review',    'color' => 'info'],
                                'approved'          => ['action' => 'mark_approved',     'label' => 'Mark Approved',     'color' => 'success'],
                                'live'              => ['action' => 'start_sale',        'label' => 'Launch Sale',       'color' => 'success'],
                                'ended'             => ['action' => 'end_sale',          'label' => 'End Sale',          'color' => 'secondary'],
                                'cancelled'         => ['action' => 'cancel',            'label' => 'Cancel',            'color' => 'danger'],
                            ];
                        @endphp
                        @foreach($nextStatuses as $next)
                            @php $act = $actionMap[$next['value']] ?? null; @endphp
                            @if($act)
                                <button type="button"
                                    class="btn btn-{{ $act['color'] }} w-full justify-center flash-sale-transition"
                                    data-action="{{ $act['action'] }}"
                                    data-needs-confirm="{{ $act['action'] === 'cancel' ? '1' : '0' }}">
                                    {{ $act['label'] }}
                                </button>
                            @endif
                        @endforeach
                        @if(!in_array($flashSale->status, ['cancelled']) && !in_array('cancelled', collect($nextStatuses)->pluck('value')->toArray()))
                            <button type="button"
                                class="btn btn-danger w-full justify-center flash-sale-transition"
                                data-action="cancel"
                                data-needs-confirm="1">
                                Cancel Sale
                            </button>
                        @endif
                    </div>
                </x-card>
            @endif

            {{-- Meta --}}
            @if($flashSale->createdBy)
                <div class="text-xs text-gray-400 text-center">
                    Created by {{ $flashSale->createdBy->name }}
                    on {{ $flashSale->created_at->format('M j, Y') }}
                </div>
            @endif

        </div>{{-- /sidebar --}}
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    {{-- MODALS --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}

    {{-- Review submission modal --}}
    <x-modal id="review-modal" title="Review Submission" size="lg">
        <div class="space-y-4">
            <div id="review-product-info" class="flex items-center gap-3 pb-3 border-b border-gray-100 hidden">
                <img id="review-product-img" src="" alt="" class="w-14 h-14 object-cover rounded-lg border border-gray-200">
                <div>
                    <p id="review-product-name" class="font-medium text-gray-900 text-sm"></p>
                    <p class="text-xs text-gray-500 mt-0.5">
                        <span id="review-flash-price" class="font-semibold text-primary-600"></span>
                        <span class="mx-1 text-gray-300">vs</span>
                        <span id="review-original-price" class="line-through text-gray-400"></span>
                        <span id="review-discount-pct" class="ml-1.5 font-medium text-emerald-600"></span>
                    </p>
                </div>
            </div>

            <div id="review-price-history-wrap" class="bg-gray-50 rounded-lg p-3">
                <p class="text-xs font-medium text-gray-500 uppercase mb-2">30-day Price History</p>
                <div id="review-price-chart" class="h-16 flex items-end gap-px overflow-hidden">
                    <span class="text-xs text-gray-400">Loading…</span>
                </div>
            </div>

            <div id="fraud-warning" class="hidden rounded-lg bg-amber-50 border border-amber-200 p-3 text-sm text-amber-800">
                <strong>⚠ Pricing Warning:</strong>
                <ul id="fraud-reasons" class="mt-1 list-disc list-inside text-xs"></ul>
                <label class="flex items-center gap-2 mt-2 cursor-pointer">
                    <input type="checkbox" id="override-fraud-check" class="form-checkbox">
                    <span>I acknowledge this risk and approve anyway</span>
                </label>
            </div>

            <div id="review-stock-info" class="text-xs text-gray-500 flex gap-4 hidden">
                <span>Max qty: <strong id="review-max-qty">—</strong></span>
                <span>Sold: <strong id="review-qty-sold">—</strong></span>
                <span>Remaining: <strong id="review-qty-remaining">—</strong></span>
            </div>

            <div x-data="{ decision: 'approved' }">
                <label class="form-label">Decision</label>
                <div class="flex gap-3 mt-1">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" x-model="decision" value="approved" class="form-radio text-primary-600">
                        <span class="text-sm font-medium text-emerald-700">Approve</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" x-model="decision" value="rejected" class="form-radio text-danger-600">
                        <span class="text-sm font-medium text-red-700">Reject</span>
                    </label>
                </div>

                <div x-show="decision === 'rejected'" class="mt-3 space-y-3">
                    <div>
                        <label class="form-label">Rejection Code <span class="text-danger-500">*</span></label>
                        <select id="review-rejection-code" class="form-select w-full">
                            <option value="manual_rejection">Manual Review</option>
                            <option value="price_too_low">Price Too Low</option>
                            <option value="insufficient_discount">Insufficient Discount</option>
                            <option value="fake_discount_detected">Fake Discount Detected</option>
                            <option value="ineligible_category">Ineligible Category</option>
                            <option value="ineligible_vendor">Ineligible Vendor</option>
                            <option value="duplicate_submission">Duplicate Submission</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Reason</label>
                        <textarea id="review-rejection-reason" rows="2" class="form-textarea w-full" placeholder="Reason for rejection…"></textarea>
                    </div>
                </div>

                <div class="mt-3">
                    <label class="form-label">Admin Notes (optional)</label>
                    <textarea id="review-admin-notes" rows="2" class="form-textarea w-full" placeholder="Internal notes…"></textarea>
                </div>

                <input type="hidden" id="review-submission-id">
                <input type="hidden" id="review-decision" :value="decision">
            </div>
        </div>
        <x-slot:footer>
            <button type="button" data-modal-close class="btn btn-ghost">Cancel</button>
            <button type="button" id="btn-confirm-review" class="btn btn-primary">Confirm Decision</button>
        </x-slot:footer>
    </x-modal>

    {{-- Bulk reject modal --}}
    <x-modal id="bulk-reject-modal" title="Bulk Reject Submissions" size="sm">
        <div class="space-y-3">
            <p class="text-sm text-gray-600">Reject <strong id="bulk-reject-count">0</strong> selected submission(s).</p>
            <div>
                <label class="form-label">Rejection Code <span class="text-danger-500">*</span></label>
                <select id="bulk-rejection-code" class="form-select w-full">
                    <option value="manual_rejection">Manual Review</option>
                    <option value="insufficient_discount">Insufficient Discount</option>
                    <option value="fake_discount_detected">Fake Discount Detected</option>
                    <option value="ineligible_category">Ineligible Category</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div>
                <label class="form-label">Reason</label>
                <textarea id="bulk-rejection-reason" rows="2" class="form-textarea w-full"></textarea>
            </div>
        </div>
        <x-slot:footer>
            <button type="button" data-modal-close class="btn btn-ghost">Cancel</button>
            <button type="button" id="btn-confirm-bulk-reject" class="btn btn-danger">Reject All Selected</button>
        </x-slot:footer>
    </x-modal>

    {{-- Cancel modal --}}
    <x-modal id="cancel-modal" title="Cancel Flash Sale" size="sm">
        <div class="space-y-3">
            <p class="text-sm text-gray-600">
                Are you sure you want to cancel <strong>{{ $flashSale->name_en }}</strong>?
                This cannot be undone.
            </p>
            <div>
                <label class="form-label">Cancellation Reason</label>
                <textarea id="cancel-reason" rows="3" class="form-textarea w-full" placeholder="Why is this sale being cancelled?"></textarea>
            </div>
        </div>
        <x-slot:footer>
            <button type="button" data-modal-close class="btn btn-ghost">Go Back</button>
            <button type="button" id="btn-confirm-cancel" class="btn btn-danger">Cancel Sale</button>
        </x-slot:footer>
    </x-modal>

    {{-- Auto-invite confirmation modal --}}
    <x-modal id="auto-invite-modal" title="Auto-Invite Eligible Vendors" size="sm">
        <div class="space-y-3">
            <div id="auto-invite-loading" class="py-6 text-center text-sm text-gray-400">
                Checking eligible vendors…
            </div>
            <div id="auto-invite-content" class="hidden space-y-3">
                <p class="text-sm text-gray-700">
                    <span id="auto-invite-count" class="font-bold text-gray-900 text-lg">0</span>
                    vendor(s) match this flash sale's eligibility criteria and have not yet been invited.
                </p>
                <div id="auto-invite-zero-msg" class="hidden rounded-lg bg-amber-50 border border-amber-200 p-3 text-sm text-amber-800">
                    <strong>No new vendors to invite.</strong>
                    <p class="mt-1 text-xs" id="auto-invite-criteria-hint"></p>
                </div>
                <div id="auto-invite-confirm-area">
                    <p class="text-xs text-gray-500">Invitations will be created as <em>Auto</em> type and notifications queued immediately.</p>
                </div>
            </div>
        </div>
        <x-slot:footer>
            <button type="button" data-modal-close class="btn btn-ghost">Cancel</button>
            <button type="button" id="btn-confirm-auto-invite" class="btn btn-secondary hidden">
                Send Invitations
            </button>
        </x-slot:footer>
    </x-modal>

    {{-- Decline reason modal --}}
    <x-modal id="decline-reason-modal" title="Decline Reason" size="sm">
        <div class="space-y-2">
            <p class="text-sm text-gray-600" id="decline-reason-vendor"></p>
            <blockquote class="rounded-lg bg-red-50 border border-red-200 p-3 text-sm text-red-800 italic" id="decline-reason-text"></blockquote>
        </div>
        <x-slot:footer>
            <button type="button" data-modal-close class="btn btn-ghost">Close</button>
        </x-slot:footer>
    </x-modal>

    {{-- Manual invite modal --}}
    <x-modal id="manual-invite-modal" title="Invite Vendor Manually" size="sm">
        <div class="space-y-3">
            <p class="text-sm text-gray-500">Enter one vendor ID per line.</p>
            <div>
                <label class="form-label">Vendor IDs</label>
                <textarea id="manual-invite-ids" rows="5" class="form-textarea w-full font-mono text-sm" placeholder="Paste UUIDs, one per line…"></textarea>
            </div>
        </div>
        <x-slot:footer>
            <button type="button" data-modal-close class="btn btn-ghost">Cancel</button>
            <button type="button" id="btn-confirm-manual-invite" class="btn btn-primary">Send Invitations</button>
        </x-slot:footer>
    </x-modal>

@endsection
