@extends('layouts.admin')

@section('title', $sale->name_en)

@push('styles')
    @vite(['resources/js/components/flatpickr.js', 'resources/js/components/select2.js'])
@endpush

@push('scripts')
    @vite('resources/js/admin/flash-sales.js')
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
        <div class="flex gap-1 border-b border-gray-200 mb-6 -mt-1 overflow-x-auto">
            <button type="button" @click="tab='details'"     :class="tab==='details'     ? 'tab-active' : 'tab'" class="tab whitespace-nowrap">Details</button>
            <button type="button" @click="tab='rules'"       :class="tab==='rules'       ? 'tab-active' : 'tab'" class="tab whitespace-nowrap">Rules</button>
            @if($sale->status !== 'draft')
            <button type="button" @click="tab='invitations'" :class="tab==='invitations' ? 'tab-active' : 'tab'" class="tab whitespace-nowrap">Invitations</button>
            @endif
            @if($hasSubmissions)
            <button type="button" @click="tab='submissions'" :class="tab==='submissions' ? 'tab-active' : 'tab'" class="tab whitespace-nowrap">Submissions</button>
            @endif
            @if($sale->status === 'live')
            <button type="button" @click="tab='live-monitor'" :class="tab==='live-monitor' ? 'tab-active' : 'tab'" class="tab whitespace-nowrap">⚡ Live Monitor</button>
            @endif
            @if($sale->status === 'ended')
            <button type="button" @click="tab='analytics'"  :class="tab==='analytics'   ? 'tab-active' : 'tab'" class="tab whitespace-nowrap">Analytics</button>
            @endif
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
                            :selected="$sale->eligible_seller_tiers ?? []" :disabled="!$isEditable" />
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
                    <x-form-checkbox-group name="eligible_categories[]"
                        label="Limit to specific categories (empty = all)"
                        :options="$categories->mapWithKeys(fn($c) => [$c->id => $c->name_en])->toArray()"
                        :selected="$sale->eligible_categories ?? []" :disabled="!$isEditable" />
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
                $invStats = [
                    'total'     => $sale->vendorInvitations()->count(),
                    'accepted'  => $sale->vendorInvitations()->where('status', 'accepted')->count(),
                    'declined'  => $sale->vendorInvitations()->where('status', 'declined')->count(),
                    'submitted' => $sale->vendorInvitations()->where('status', 'submitted')->count(),
                ];
            @endphp
            <div class="grid grid-cols-4 gap-3">
                <x-stat-card label="Invited"   :value="$invStats['total']"     color="gray" />
                <x-stat-card label="Accepted"  :value="$invStats['accepted']"  color="success" />
                <x-stat-card label="Declined"  :value="$invStats['declined']"  color="danger" />
                <x-stat-card label="Submitted" :value="$invStats['submitted']" color="primary" />
            </div>

            @if($isEditable)
            <div class="flex justify-end">
                <button id="invite-vendors-btn" class="btn btn-primary btn-sm" data-sale-id="{{ $sale->id }}">
                    Invite Eligible Vendors
                </button>
            </div>
            @endif

            <x-card title="Invited Vendors">
                <table id="invitations-table" class="w-full text-sm"></table>
            </x-card>
        </div>
        @endif

        {{-- ── Tab: submissions ───────────────────────────────────────────── --}}
        @if($hasSubmissions)
        <div x-show="tab==='submissions'" class="space-y-4">

            <div class="grid grid-cols-4 gap-3">
                <x-stat-card label="Submitted"    value="—" id="stat-submitted"    color="primary" />
                <x-stat-card label="Approved"     value="—" id="stat-approved"     color="success" />
                <x-stat-card label="Rejected"     value="—" id="stat-rejected"     color="danger"  />
                <x-stat-card label="Pending"      value="—" id="stat-pending"      color="warning" />
            </div>

            <x-card>
                <div class="flex flex-wrap gap-2 mb-3">
                    <select id="filter-submission-status" class="form-select form-select-sm w-36">
                        <option value="">All statuses</option>
                        <option value="submitted">Submitted</option>
                        <option value="under_review">Under Review</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                    <label class="flex items-center gap-1.5 text-sm cursor-pointer">
                        <input type="checkbox" id="filter-suspect" class="rounded"> Suspected only
                    </label>

                    <div class="ml-auto flex gap-2">
                        <button id="bulk-approve-submissions" class="btn btn-success btn-sm">Approve Selected</button>
                        <button id="bulk-reject-submissions"  class="btn btn-danger  btn-sm">Reject Selected</button>
                    </div>
                </div>

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
                @elseif(in_array($sale->status, ['submission_closed', 'under_review']))
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

{{-- Review modal (extracted partial) --}}
@include('admin.flash-sales.partials.review-modal', ['sale' => $sale])

<script>
const FLASH_SALE_ID            = '{{ $sale->id }}';
const FLASH_SALE_UPDATE_URL    = '{{ route('admin.flash-sales.update', $sale->id) }}';
const FLASH_SALE_MIN_DISC      = {{ (float) $sale->min_discount_pct }};
const SUBMISSIONS_DATATABLE_URL= '{{ route('admin.flash-sales.submissions.datatable', $sale->id) }}';
const INVITATIONS_DATATABLE_URL= '{{ route('admin.flash-sales.invitations.datatable', $sale->id) }}';
const TRANSITION_URL           = '{{ route('admin.flash-sales.transition', $sale->id) }}';
const INVITE_URL               = '{{ route('admin.flash-sales.invite-vendors', $sale->id) }}';
const ELIGIBLE_COUNT_URL       = '{{ route('admin.flash-sales.eligible-vendor-count', $sale->id) }}';
const BULK_REVIEW_URL          = '{{ route('admin.flash-sales.submissions.bulk-review', $sale->id) }}';
const LIVE_DATA_URL            = '{{ route('admin.flash-sales.live-data', $sale->id) }}';
const PRICE_HISTORY_URL        = '{{ route('admin.flash-sales.price-history') }}';
const IS_LIVE                  = {{ $sale->status === 'live' ? 'true' : 'false' }};
const IS_ENDED                 = {{ $sale->status === 'ended' ? 'true' : 'false' }};
</script>

@endsection
