@extends('layouts.admin')

@section('title', $sale->name_en)

@push('styles')
    @vite(['resources/js/components/flatpickr.js', 'resources/js/components/select2.js', 'resources/js/components/datatable.js', 'resources/js/components/column-renderers.js'])
@endpush

@push('scripts')
    @vite(['resources/js/admin/flash-sale-form.js', 'resources/js/admin/flash-sale-detail.js'])
@endpush

@section('content')

@php
    $isEditable = !in_array($sale->status, ['live', 'ended', 'cancelled']);
    $hasSubmissions = $sale->submissions()->exists();
@endphp

<div class="flex gap-6 items-start">

    {{-- ─── Left column ───────────────────────────────────────────────────── --}}
    <div class="flex-1 min-w-0"
         x-data="{ tab: '{{ $sale->status === 'live' ? 'live-monitor' : ($sale->status === 'ended' ? 'analytics' : 'details') }}' }">

        {{-- Tab nav --}}
        <div class="border-b border-gray-200 mb-6">
            <nav class="-mb-px flex overflow-x-auto" aria-label="Flash sale tabs">
                <button type="button" @click="tab='details'"
                    :class="tab==='details' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                    class="border-b-2 py-3 px-5 text-sm font-medium transition-colors duration-150 focus:outline-none whitespace-nowrap">
                    Details
                </button>
                <button type="button" @click="tab='rules'"
                    :class="tab==='rules' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                    class="border-b-2 py-3 px-5 text-sm font-medium transition-colors duration-150 focus:outline-none whitespace-nowrap">
                    Rules
                </button>
                @if($sale->status !== 'draft')
                <button type="button" @click="tab='invitations'"
                    :class="tab==='invitations' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                    class="border-b-2 py-3 px-5 text-sm font-medium transition-colors duration-150 focus:outline-none whitespace-nowrap">
                    Invitations
                </button>
                @endif
                @if($hasSubmissions)
                <button type="button" @click="tab='submissions'"
                    :class="tab==='submissions' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                    class="border-b-2 py-3 px-5 text-sm font-medium transition-colors duration-150 focus:outline-none whitespace-nowrap">
                    Submissions
                </button>
                @endif
                @if($sale->status === 'live')
                <button type="button" @click="tab='live-monitor'"
                    :class="tab==='live-monitor' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                    class="border-b-2 py-3 px-5 text-sm font-medium transition-colors duration-150 focus:outline-none whitespace-nowrap">
                    ⚡ Live Monitor
                </button>
                @endif
                @if($sale->status === 'ended')
                <button type="button" @click="tab='analytics'"
                    :class="tab==='analytics' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                    class="border-b-2 py-3 px-5 text-sm font-medium transition-colors duration-150 focus:outline-none whitespace-nowrap">
                    Analytics
                </button>
                @endif
            </nav>
        </div>

        {{-- ── Tab: details ──────────────────────────────────────────────── --}}
        <div x-show="tab==='details'">
            <form id="flash-sale-form" class="space-y-6" novalidate>
                @csrf
                @method('PUT')

                <x-card title="Event Identity">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <x-form-input name="name_en" label="Name (English)" :value="$sale->name_en" required :disabled="!$isEditable" />
                        <x-form-input name="name_ar" label="الاسم بالعربي"  :value="$sale->name_ar" dir="rtl" required :disabled="!$isEditable" />
                        <div class="sm:col-span-2">
                            <x-form-textarea name="description_en" label="Description (English)" :value="$sale->description_en" rows="3" :disabled="!$isEditable" />
                        </div>
                        <div class="sm:col-span-2">
                            <x-form-textarea name="description_ar" label="الوصف بالعربي" :value="$sale->description_ar" dir="rtl" rows="3" :disabled="!$isEditable" />
                        </div>
                        <x-form-select name="country_id" label="Country"
                            :options="$countries->mapWithKeys(fn($c) => [$c->id => $c->name_en])->toArray()"
                            :selected="$sale->country_id" :nullable="true" placeholder="All countries" :disabled="!$isEditable" />
                        <div class="flex items-end gap-6 pb-1">
                            <x-form-toggle name="is_featured"  label="Featured"  :value="$sale->is_featured"  :disabled="!$isEditable" />
                            <x-form-toggle name="is_exclusive" label="Exclusive" :value="$sale->is_exclusive" :disabled="!$isEditable" />
                        </div>
                    </div>
                </x-card>

                <x-card title="Timeline" id="timeline-card">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <x-form-date-picker name="submission_opens_at"  label="Submissions open"  :value="$sale->submission_opens_at?->format('Y-m-d H:i')"  :enableTime="true" required :disabled="!$isEditable" />
                        <x-form-date-picker name="submission_closes_at" label="Submissions close" :value="$sale->submission_closes_at?->format('Y-m-d H:i')" :enableTime="true" required :disabled="!$isEditable" />
                        <x-form-date-picker name="review_deadline_at"   label="Review deadline"   :value="$sale->review_deadline_at?->format('Y-m-d H:i')"   :enableTime="true" required :disabled="!$isEditable" />
                        <div></div>
                        <x-form-date-picker name="sale_starts_at" label="Sale starts" :value="$sale->sale_starts_at?->format('Y-m-d H:i')" :enableTime="true" required :disabled="!$isEditable" />
                        <x-form-date-picker name="sale_ends_at"   label="Sale ends"   :value="$sale->sale_ends_at?->format('Y-m-d H:i')"   :enableTime="true" required :disabled="!$isEditable" />
                    </div>
                    <div id="timeline-visual" class="mt-4"></div>
                </x-card>

                @if($isEditable)
                <div class="flex justify-end">
                    <button type="submit" id="flash-sale-submit-btn" class="btn btn-primary">Save Changes</button>
                </div>
                @endif
            </form>
        </div>

        {{-- ── Tab: rules ─────────────────────────────────────────────────── --}}
        <div x-show="tab==='rules'">
            <form id="flash-sale-form-rules" class="space-y-6" novalidate>
                @csrf @method('PUT')

                <x-card title="Discount Requirements">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <x-form-input name="min_discount_pct" label="Minimum discount %" type="number"
                            step="0.01" min="0" max="100" :value="$sale->min_discount_pct" required suffix="%" :disabled="!$isEditable" />
                        <x-form-input name="max_products_per_seller" label="Max products per vendor" type="number"
                            min="1" :value="$sale->max_products_per_seller" :disabled="!$isEditable" />
                        <div class="sm:col-span-2">
                            <x-form-toggle name="price_drop_required" label="Price drop required" :value="$sale->price_drop_required" :disabled="!$isEditable" />
                        </div>
                    </div>
                </x-card>

                <x-card title="Vendor Eligibility">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <x-form-checkbox-group name="eligible_seller_tiers"
                            label="Eligible tiers (empty = all)"
                            :options="['bronze' => 'Bronze', 'silver' => 'Silver', 'gold' => 'Gold', 'platinum' => 'Platinum']"
                            :values="$sale->eligible_seller_tiers ?? []" :disabled="!$isEditable" />
                        <x-form-input name="min_seller_rating" label="Minimum seller rating"
                            type="number" step="0.1" min="0" max="5" :value="$sale->min_seller_rating" :disabled="!$isEditable" />
                    </div>
                </x-card>

                <x-card title="Capacity &amp; Commission">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <x-form-input name="max_total_slots" label="Maximum approved slots"
                            type="number" min="1" :value="$sale->max_total_slots"
                            :hint="$sale->approved_slots_count ? 'Currently approved: ' . $sale->approved_slots_count : 'Empty = unlimited'"
                            :disabled="!$isEditable" />
                        <x-form-input name="commission_override_pct" label="Commission override %"
                            type="number" step="0.01" min="0" max="100" :value="$sale->commission_override_pct" :disabled="!$isEditable" />
                    </div>
                </x-card>

                <x-card title="Eligible Categories">
                    <x-form-checkbox-group name="eligible_categories"
                        label="Limit to specific categories (empty = all)"
                        :options="$categories->mapWithKeys(fn($c) => [$c->id => $c->name_en])->toArray()"
                        :values="$sale->eligible_categories ?? []" :disabled="!$isEditable" />
                </x-card>

                @if($isEditable)
                <div class="flex justify-end">
                    <button type="submit" id="flash-sale-rules-submit-btn" class="btn btn-primary">Save Changes</button>
                </div>
                @endif
            </form>
        </div>

        {{-- ── Tab: invitations ───────────────────────────────────────────── --}}
        @if($sale->status !== 'draft')
        <div x-show="tab==='invitations'" class="space-y-4">

            @php
                $invStatConfig = [
                    'pending'   => ['label' => 'Pending',   'color' => 'bg-amber-50 border-amber-200',    'dot' => 'bg-amber-400',   'text' => 'text-amber-800'],
                    'accepted'  => ['label' => 'Accepted',  'color' => 'bg-emerald-50 border-emerald-200','dot' => 'bg-emerald-500', 'text' => 'text-emerald-800'],
                    'declined'  => ['label' => 'Declined',  'color' => 'bg-red-50 border-red-200',        'dot' => 'bg-red-400',     'text' => 'text-red-800'],
                    'submitted' => ['label' => 'Submitted', 'color' => 'bg-blue-50 border-blue-200',      'dot' => 'bg-blue-500',    'text' => 'text-blue-800'],
                ];
            @endphp
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                @foreach($invStatConfig as $status => $cfg)
                    <div class="rounded-xl border {{ $cfg['color'] }} px-4 py-3 flex items-center gap-3">
                        <span class="w-2.5 h-2.5 rounded-full shrink-0 {{ $cfg['dot'] }}"></span>
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
                    @if(!in_array($sale->status, ['ended', 'cancelled']))
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
        @endif

        {{-- ── Tab: submissions ───────────────────────────────────────────── --}}
        @if($hasSubmissions)
        <div x-show="tab==='submissions'" class="space-y-4">

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
            <div class="flex flex-wrap items-center gap-2">
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
                    <button type="button" id="btn-bulk-reject" class="btn btn-danger btn-sm hidden">
                        Bulk Reject
                    </button>
                </div>
            </div>

            <x-card>
                <table id="submissions-table" class="w-full text-sm"></table>
            </x-card>
        </div>
        @endif

        {{-- ── Tab: live-monitor ──────────────────────────────────────────── --}}
        @if($sale->status === 'live')
        <div x-show="tab==='live-monitor'" id="live-monitor-section" class="space-y-4">
            @php $remaining = max(0, now()->diffInSeconds($sale->sale_ends_at, false)); @endphp

            <div class="text-center py-6"
                 x-data="{ remaining: {{ $remaining }} }"
                 x-init="setInterval(() => { if(remaining > 0) remaining-- }, 1000)">
                <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Time Remaining</p>
                <p class="font-mono text-4xl font-bold"
                   :class="remaining < 3600 ? 'text-danger-600' : 'text-gray-900'"
                   x-text="new Date(remaining * 1000).toISOString().slice(11,19)">
                </p>
            </div>

            <div class="grid grid-cols-3 gap-3">
                <x-stat-card label="Units Sold"  value="—" id="live-units"   color="success" />
                <x-stat-card label="Revenue"     value="—" id="live-revenue" color="primary" />
                <x-stat-card label="Sold Out"    value="—" id="live-soldout" color="danger"  />
            </div>

            <x-card title="Live Submissions">
                <table id="live-submissions-table" class="w-full text-sm">
                    <thead>
                        <tr class="text-xs uppercase text-gray-500 border-b">
                            <th class="py-2 px-3 text-left">Product</th>
                            <th class="py-2 px-3 text-right">Sold</th>
                            <th class="py-2 px-3 text-right">Remaining</th>
                            <th class="py-2 px-3 text-right">Revenue</th>
                            <th class="py-2 px-3">Status</th>
                        </tr>
                    </thead>
                    <tbody id="live-submissions-tbody"></tbody>
                </table>
            </x-card>
        </div>
        @endif

        {{-- ── Tab: analytics ─────────────────────────────────────────────── --}}
        @if($sale->status === 'ended')
        <div x-show="tab==='analytics'" class="space-y-4" id="analytics-section">
            <div class="grid grid-cols-3 gap-3">
                <x-stat-card label="Units Sold"          value="—" id="an-units"      color="primary" />
                <x-stat-card label="Gross Revenue"       value="—" id="an-revenue"    color="success" />
                <x-stat-card label="Discount Given"      value="—" id="an-discount"   color="warning" />
                <x-stat-card label="Commission"          value="—" id="an-commission" color="info" />
                <x-stat-card label="Vendor Payouts"      value="—" id="an-payout"     color="gray" />
                <x-stat-card label="Avg Conversion %"    value="—" id="an-conversion" color="info" />
            </div>
            <x-card title="Revenue vs Discount by Day">
                <div style="height:250px"><canvas id="analytics-chart"></canvas></div>
            </x-card>
        </div>
        @endif

    </div>{{-- /left column --}}

    {{-- ─── Right sidebar ──────────────────────────────────────────────────── --}}
    <div class="w-72 flex-shrink-0 space-y-4 sticky top-20">

        {{-- Status card --}}
        <x-card title="Status">
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
                $color = $statusColors[$sale->status] ?? 'gray';
                $label = \App\Models\FlashSale::STATUS_LABELS[$sale->status] ?? $sale->status;
            @endphp
            <div class="text-center mb-4">
                <span class="badge badge-{{ $color }} text-base px-4 py-1">{{ $label }}</span>
            </div>

            @if($sale->cancelled_at)
                <div class="text-xs text-danger-600 mb-3">
                    Cancelled {{ $sale->cancelled_at->diffForHumans() }}<br>
                    @if($sale->cancellation_reason)
                        <em>{{ $sale->cancellation_reason }}</em>
                    @endif
                </div>
            @endif

            {{-- Timeline steps --}}
            <ol class="space-y-2 mb-4">
                @foreach (\App\Models\FlashSale::STATUS_LABELS as $sv => $sl)
                    @if($sv === 'cancelled') @continue @endif
                    @php
                        $steps = ['draft','submission_open','submission_closed','under_review','approved','live','ended'];
                        $currentIdx = array_search($sale->status, $steps);
                        $stepIdx    = array_search($sv, $steps);
                        $isDone     = $currentIdx !== false && $stepIdx < $currentIdx;
                        $isCurrent  = $sv === $sale->status;
                    @endphp
                    <li class="flex items-center gap-2 text-xs">
                        <span class="w-5 h-5 rounded-full flex items-center justify-center flex-shrink-0
                            {{ $isDone ? 'bg-success-500 text-white' : ($isCurrent ? 'bg-primary-500 text-white' : 'border-2 border-gray-300 text-gray-300') }}">
                            @if($isDone) ✓ @elseif($isCurrent) ● @else ○ @endif
                        </span>
                        <span class="{{ $isCurrent ? 'font-semibold text-gray-900' : ($isDone ? 'text-success-700' : 'text-gray-400') }}">
                            {{ $sl }}
                        </span>
                    </li>
                @endforeach
            </ol>

            {{-- Action buttons --}}
            <div class="space-y-2" x-data="{}">
                @if($sale->status === 'draft')
                    <button data-transition="open_submissions" data-sale-id="{{ $sale->id }}"
                        class="btn btn-primary w-full justify-center btn-sm">Open Submissions</button>
                    <button data-transition="cancel" data-sale-id="{{ $sale->id }}"
                        class="btn btn-danger-outline w-full justify-center btn-sm">Cancel</button>
                @elseif($sale->status === 'submission_open')
                    <button data-transition="close_submissions" data-sale-id="{{ $sale->id }}"
                        class="btn btn-warning w-full justify-center btn-sm">Close Submissions Early</button>
                    <button data-transition="cancel" data-sale-id="{{ $sale->id }}"
                        class="btn btn-danger-outline w-full justify-center btn-sm">Cancel</button>
                @elseif($sale->status === 'submission_closed')
                    <button data-transition="move_to_review" data-sale-id="{{ $sale->id }}"
                        class="btn btn-primary w-full justify-center btn-sm">Start Review</button>
                    <button data-transition="cancel" data-sale-id="{{ $sale->id }}"
                        class="btn btn-danger-outline w-full justify-center btn-sm">Cancel</button>
                @elseif($sale->status === 'under_review')
                    <button data-transition="mark_approved" data-sale-id="{{ $sale->id }}"
                        class="btn btn-primary w-full justify-center btn-sm">Mark Approved</button>
                    <button data-transition="cancel" data-sale-id="{{ $sale->id }}"
                        class="btn btn-danger-outline w-full justify-center btn-sm">Cancel</button>
                @elseif($sale->status === 'approved')
                    <button data-transition="start_sale" data-sale-id="{{ $sale->id }}"
                        class="btn btn-primary w-full justify-center btn-sm">Start Sale Now</button>
                    <button data-transition="cancel" data-sale-id="{{ $sale->id }}"
                        class="btn btn-danger-outline w-full justify-center btn-sm">Cancel</button>
                @elseif($sale->status === 'live')
                    <button data-transition="end_sale" data-sale-id="{{ $sale->id }}"
                        class="btn btn-warning w-full justify-center btn-sm">End Sale Early</button>
                    <button data-transition="cancel" data-sale-id="{{ $sale->id }}"
                        class="btn btn-danger-outline w-full justify-center btn-sm">Cancel</button>
                @endif

                @if($sale->status === 'draft')
                    <form method="POST" action="{{ route('admin.flash-sales.destroy', $sale->id) }}"
                        onsubmit="return confirm('Delete this flash sale permanently?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger w-full justify-center btn-sm">Delete</button>
                    </form>
                @endif
            </div>
        </x-card>

        {{-- Event details --}}
        <x-card title="Event Details">
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-gray-500">Country</dt>
                    <dd class="font-medium">{{ $sale->country?->name_en ?? 'All countries' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Duration</dt>
                    <dd class="font-medium">{{ $sale->getDurationHours() }}h</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Slots</dt>
                    <dd class="font-medium">{{ $sale->approved_slots_count }} / {{ $sale->max_total_slots ?? '∞' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Min discount</dt>
                    <dd class="font-medium">{{ $sale->min_discount_pct }}%</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Commission</dt>
                    <dd class="font-medium">{{ $sale->commission_override_pct ? $sale->commission_override_pct . '%' : 'Default' }}</dd>
                </div>
            </dl>
        </x-card>

    </div>{{-- /sidebar --}}

</div>

{{-- Cancel modal --}}
<x-modal id="cancel-modal" title="Cancel Flash Sale">
    <form id="cancel-form">
        <x-form-textarea name="cancellation_reason" label="Cancellation reason" rows="3" required />
        <div class="flex justify-end gap-2 mt-4">
            <button type="button" onclick="closeModal('cancel-modal')" class="btn btn-ghost">Nevermind</button>
            <button type="submit" id="cancel-submit-btn" class="btn btn-danger">Cancel Sale</button>
        </div>
    </form>
</x-modal>

{{-- Bulk reject modal --}}
<x-modal id="bulk-reject-modal" title="Reject Selected Submissions">
    <form id="bulk-reject-form">
        <input type="hidden" name="bulk_ids">
        <x-form-select name="bulk_rejection_code" label="Rejection reason" required
            :options="[
                'discount_too_low'        => 'Discount too low',
                'fake_discount_suspected' => 'Fake discount detected',
                'insufficient_stock'      => 'Insufficient stock',
                'not_eligible_category'   => 'Category not eligible',
                'slot_limit_reached'      => 'Slot limit reached',
                'policy_violation'        => 'Policy violation',
                'vendor_not_eligible'     => 'Vendor not eligible',
            ]" />
        <x-form-textarea name="bulk_rejection_reason" label="Additional reason (optional)" rows="2" />
        <div class="flex justify-end gap-2 mt-4">
            <button type="button" onclick="closeModal('bulk-reject-modal')" class="btn btn-ghost">Cancel</button>
            <button type="submit" id="bulk-reject-submit" class="btn btn-danger">Reject All</button>
        </div>
    </form>
</x-modal>

{{-- Review modal --}}
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

<script>
window.FLASH_SALE_ID         = '{{ $sale->id }}';
window.FLASH_SALE_UPDATE_URL = '{{ route('admin.flash-sales.update', $sale->id) }}';
window.FLASH_SALE_STATUS     = '{{ $sale->status }}';
window.MIN_DISCOUNT_PCT      = {{ (float) $sale->min_discount_pct }};
window.URLS = {
    update:            '{{ route('admin.flash-sales.update', $sale->id) }}',
    submissionsDt:     '{{ route('admin.flash-sales.submissions.datatable', $sale->id) }}',
    invitationsDt:     '{{ route('admin.flash-sales.invitations.datatable', $sale->id) }}',
    transition:        '{{ route('admin.flash-sales.transition', $sale->id) }}',
    inviteVendors:     '{{ route('admin.flash-sales.invite-vendors', $sale->id) }}',
    eligibleCount:     '{{ route('admin.flash-sales.eligible-vendor-count', $sale->id) }}',
    bulkReview:        '{{ route('admin.flash-sales.submissions.bulk-review', $sale->id) }}',
    liveData:          '{{ route('admin.flash-sales.live-data', $sale->id) }}',
    priceHistory:      '{{ route('admin.flash-sales.price-history') }}',
    resendInvitation:  '{{ url('/flash-sales/' . $sale->id . '/invitations') }}',
    submissionDetail:  '{{ url('/flash-sales/submissions') }}',
    analyticsData:     '{{ route('admin.flash-sales.analytics-data', $sale->id) }}',
};
</script>

@endsection
