@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/column-renderers.js', 'resources/js/admin/flash-sales.js'])
@endpush

@section('title', 'Flash Sale: ' . $sale->name_en)

@section('content')
    @php
        $statusColors = [
            'draft'     => 'gray',
            'open'      => 'primary',
            'review'    => 'warning',
            'scheduled' => 'primary',
            'live'      => 'success',
            'ended'     => 'gray',
            'cancelled' => 'danger',
        ];
        $statusLabels = [
            'draft'     => 'Draft',
            'open'      => 'Submissions Open',
            'review'    => 'Under Review',
            'scheduled' => 'Scheduled',
            'live'      => 'Live',
            'ended'     => 'Ended',
            'cancelled' => 'Cancelled',
        ];

        $submissionColumns = [
            ['title' => 'Vendor', 'data' => 'vendor_name', 'name' => 'vendor_name', 'searchable' => false],
            ['title' => 'Listing', 'data' => 'listing_name', 'name' => 'listing_name', 'searchable' => false],
            [
                'title' => 'Flash Price',
                'data' => 'flash_price_formatted',
                'name' => 'flash_price_formatted',
                'searchable' => false,
                'className' => 'text-right font-semibold',
            ],
            [
                'title' => 'Original',
                'data' => 'original_price_formatted',
                'name' => 'original_price_formatted',
                'searchable' => false,
                'className' => 'text-right',
            ],
            [
                'title' => 'Discount',
                'data' => 'discount_pct',
                'name' => 'discount_pct',
                'searchable' => false,
                'className' => 'text-right',
            ],
            [
                'title' => 'Qty',
                'data' => 'qty',
                'name' => 'qty',
                'searchable' => false,
                'orderable' => false,
                'render' => 'function(d,t,row){return "<span class=\"font-mono\">"+row.quantity_sold+"/"+(row.max_quantity_total||"∞")+"</span>";}',
            ],
            [
                'title' => 'Status',
                'data' => 'status',
                'name' => 'status',
                'searchable' => false,
                'render' => 'Renderers.badge({
                    submitted:    { label: "Submitted",    color: "gray"    },
                    under_review: { label: "Under Review", color: "warning" },
                    approved:     { label: "Approved",     color: "primary" },
                    active:       { label: "Active",       color: "success" },
                    sold_out:     { label: "Sold Out",     color: "danger"  },
                    rejected:     { label: "Rejected",     color: "danger"  },
                    ended:        { label: "Ended",        color: "gray"    }
                })',
            ],
            [
                'title' => 'Submitted',
                'data' => 'submitted_at',
                'name' => 'submitted_at',
                'searchable' => false,
                'render' => 'function(data){return data ? Renderers.dateAgo(data) : "—";}',
            ],
            [
                'title' => '',
                'data' => 'row_actions',
                'name' => 'row_actions',
                'orderable' => false,
                'searchable' => false,
                'className' => 'text-right',
                'render' => 'renderSubmissionActions',
            ],
        ];
    @endphp

    <script>
        window.FLASH_SALE_ID   = '{{ $sale->id }}';
        window.FLASH_SALE_STATUS = '{{ $sale->status }}';
        window.URLS = {
            transition:        '{{ route('admin.flash-sales.transition', $sale->id) }}',
            inviteVendors:     '{{ route('admin.flash-sales.invite-vendors', $sale->id) }}',
            submissionsDt:     '{{ route('admin.flash-sales.submissions.datatable', $sale->id) }}',
        };
    </script>

    <div class="flex gap-6 items-start">

        {{-- ═══════════════════════════════════════════════════════════════════ --}}
        {{-- MAIN COLUMN --}}
        {{-- ═══════════════════════════════════════════════════════════════════ --}}
        <div class="flex-1 min-w-0 space-y-6">

            {{-- Tabs --}}
            <div x-data="{ tab: 'submissions' }">
                <nav class="flex gap-1 border-b border-gray-200 mb-5">
                    @foreach(['submissions' => 'Submissions', 'details' => 'Details', 'vendors' => 'Vendors'] as $key => $label)
                        <button type="button"
                            @click="tab = '{{ $key }}'"
                            :class="tab === '{{ $key }}' ? 'border-b-2 border-primary-600 text-primary-700 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                            class="px-4 py-2 text-sm transition-colors">
                            {{ $label }}
                            @if($key === 'submissions')
                                <span class="ml-1.5 rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">
                                    {{ $submissionStats->sum() }}
                                </span>
                            @elseif($key === 'vendors')
                                <span class="ml-1.5 rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">
                                    {{ $invitationCount }}
                                </span>
                            @endif
                        </button>
                    @endforeach
                </nav>

                {{-- ─── Submissions tab ──────────────────────────────────────── --}}
                <div x-show="tab === 'submissions'">
                    {{-- Submission status summary pills --}}
                    @if($submissionStats->isNotEmpty())
                        <div class="flex flex-wrap gap-2 mb-4">
                            @php
                                $pillColors = [
                                    'submitted'    => 'bg-gray-100 text-gray-700',
                                    'under_review' => 'bg-amber-100 text-amber-800',
                                    'approved'     => 'bg-primary-100 text-primary-800',
                                    'active'       => 'bg-success-100 text-success-800',
                                    'sold_out'     => 'bg-orange-100 text-orange-800',
                                    'rejected'     => 'bg-danger-100 text-danger-800',
                                    'ended'        => 'bg-gray-100 text-gray-500',
                                ];
                            @endphp
                            @foreach($submissionStats as $status => $count)
                                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium {{ $pillColors[$status] ?? 'bg-gray-100 text-gray-700' }}">
                                    {{ ucwords(str_replace('_', ' ', $status)) }}
                                    <span class="font-bold">{{ $count }}</span>
                                </span>
                            @endforeach
                        </div>
                    @endif

                    <x-table.datatable
                        id="submissions-table"
                        :url="route('admin.flash-sales.submissions.datatable', $sale->id)"
                        :columns="$submissionColumns"
                        :filters="[
                            ['type' => 'select', 'name' => 'status', 'label' => 'Status', 'options' => [
                                'submitted' => 'Submitted', 'under_review' => 'Under Review',
                                'approved' => 'Approved', 'active' => 'Active',
                                'sold_out' => 'Sold Out', 'rejected' => 'Rejected', 'ended' => 'Ended',
                            ]],
                        ]"
                        :page-length="20"
                    />
                </div>

                {{-- ─── Details tab ──────────────────────────────────────────── --}}
                <div x-show="tab === 'details'">
                    <x-card title="Flash Sale Details">
                        <div class="grid grid-cols-1 gap-y-3 sm:grid-cols-2 text-sm">
                            <div>
                                <p class="text-gray-500">Name (EN)</p>
                                <p class="font-medium text-gray-900">{{ $sale->name_en }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500">Name (AR)</p>
                                <p class="font-medium text-gray-900 text-right" dir="rtl">{{ $sale->name_ar }}</p>
                            </div>
                            @if($sale->description_en)
                                <div class="sm:col-span-2">
                                    <p class="text-gray-500">Description (EN)</p>
                                    <p class="text-gray-700">{{ $sale->description_en }}</p>
                                </div>
                            @endif
                            <div>
                                <p class="text-gray-500">Submission Opens</p>
                                <p class="text-gray-700">{{ $sale->submission_opens_at?->format('M j, Y H:i') ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500">Submission Closes</p>
                                <p class="text-gray-700">{{ $sale->submission_closes_at?->format('M j, Y H:i') ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500">Sale Starts</p>
                                <p class="text-gray-700">{{ $sale->sale_starts_at?->format('M j, Y H:i') ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500">Sale Ends</p>
                                <p class="text-gray-700">{{ $sale->sale_ends_at?->format('M j, Y H:i') ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500">Min Discount</p>
                                <p class="font-medium text-gray-900">{{ $sale->min_discount_pct }}%</p>
                            </div>
                            <div>
                                <p class="text-gray-500">Max Products / Vendor</p>
                                <p class="font-medium text-gray-900">{{ $sale->max_products_per_vendor }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500">Max Total Slots</p>
                                <p class="font-medium text-gray-900">{{ $sale->max_total_slots ?? '∞' }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500">Commission Override</p>
                                <p class="font-medium text-gray-900">{{ $sale->commission_override_pct ? $sale->commission_override_pct . '%' : 'Default' }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500">Min Vendor Rating</p>
                                <p class="font-medium text-gray-900">{{ $sale->min_vendor_rating ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500">Eligible Tiers</p>
                                <p class="font-medium text-gray-900">
                                    {{ !empty($sale->eligible_vendor_tiers) ? implode(', ', $sale->eligible_vendor_tiers) : 'All' }}
                                </p>
                            </div>
                            <div class="sm:col-span-2">
                                <p class="text-gray-500">Eligible Categories</p>
                                <p class="font-medium text-gray-900">
                                    {{ !empty($sale->eligible_categories) ? count($sale->eligible_categories) . ' categories selected' : 'All' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-gray-500">Featured</p>
                                <p class="font-medium">{{ $sale->is_featured ? '✓ Yes' : 'No' }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500">Exclusive</p>
                                <p class="font-medium">{{ $sale->is_exclusive ? '✓ Yes (invite-only)' : 'No' }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500">Price Drop Required</p>
                                <p class="font-medium">{{ $sale->price_drop_required ? '✓ Yes' : 'No' }}</p>
                            </div>
                        </div>
                    </x-card>
                </div>

                {{-- ─── Vendors tab ──────────────────────────────────────────── --}}
                <div x-show="tab === 'vendors'">
                    <x-card title="Vendor Invitations">
                        <div class="flex items-center justify-between mb-4">
                            <p class="text-sm text-gray-500">{{ $invitationCount }} vendor(s) invited to this flash sale.</p>
                            @if(in_array($sale->status, ['draft', 'open']))
                                <div class="flex gap-2">
                                    <button type="button" id="btn-auto-invite"
                                        class="btn btn-secondary btn-sm">
                                        <x-heroicon name="user-group" class="w-4 h-4 mr-1.5" />
                                        Auto-Invite Eligible
                                    </button>
                                    <button type="button" data-modal-open="manual-invite-modal"
                                        class="btn btn-ghost btn-sm">
                                        <x-heroicon name="user-plus" class="w-4 h-4 mr-1.5" />
                                        Invite Manually
                                    </button>
                                </div>
                            @endif
                        </div>

                        @php
                            $invitations = $sale->vendorInvitations()->with('vendor')->latest('invited_at')->get();
                        @endphp

                        @if($invitations->isNotEmpty())
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-gray-50 border-b border-gray-200">
                                        <tr>
                                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Vendor</th>
                                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Type</th>
                                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Slots</th>
                                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Invited At</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach($invitations as $inv)
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-4 py-3 font-medium text-gray-900">
                                                    {{ $inv->vendor?->store_name ?? '—' }}
                                                </td>
                                                <td class="px-4 py-3 text-gray-500 capitalize">{{ $inv->invitation_type }}</td>
                                                <td class="px-4 py-3">
                                                    <x-badge :color="$inv->status === 'accepted' ? 'success' : ($inv->status === 'declined' ? 'danger' : 'gray')">
                                                        {{ ucfirst($inv->status) }}
                                                    </x-badge>
                                                </td>
                                                <td class="px-4 py-3 font-mono">{{ $inv->slots_allocated }}</td>
                                                <td class="px-4 py-3 text-gray-400 text-xs">
                                                    {{ \Carbon\Carbon::parse($inv->invited_at)->format('M j, Y') }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-sm text-gray-400 italic text-center py-6">No vendors invited yet.</p>
                        @endif
                    </x-card>
                </div>

            </div>{{-- /x-data tabs --}}

        </div>{{-- /main column --}}

        {{-- ═══════════════════════════════════════════════════════════════════ --}}
        {{-- SIDEBAR --}}
        {{-- ═══════════════════════════════════════════════════════════════════ --}}
        <div class="w-80 flex-shrink-0 space-y-4 sticky top-20">

            {{-- Banner --}}
            @if($sale->bannerFile)
                <div class="rounded-xl overflow-hidden border border-gray-200">
                    <img src="{{ $sale->bannerFile->full_path }}" alt="{{ $sale->bannerFile->alt_text_en }}"
                        class="w-full object-cover h-36">
                </div>
            @endif

            {{-- Summary card --}}
            <x-card title="Flash Sale">
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-500">Status</span>
                        <x-badge :color="$statusColors[$sale->status] ?? 'gray'">
                            {{ $statusLabels[$sale->status] ?? $sale->status }}
                        </x-badge>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Country</span>
                        <span class="text-gray-700">{{ $sale->country?->name_en ?? 'All' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Approved Slots</span>
                        <span class="font-mono font-medium">{{ $sale->approved_slots_count }} / {{ $sale->max_total_slots ?? '∞' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Submissions</span>
                        <span class="font-mono font-medium">{{ $submissionStats->sum() }}</span>
                    </div>
                    @if($sale->is_featured)
                        <div class="flex items-center gap-1.5 text-amber-700 text-xs font-medium">
                            <x-heroicon name="star" class="w-3.5 h-3.5" />
                            Featured
                        </div>
                    @endif
                    @if($sale->is_exclusive)
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
                        'Submissions Open' => $sale->submission_opens_at,
                        'Submissions Close' => $sale->submission_closes_at,
                        'Review Deadline' => $sale->review_deadline_at,
                        'Sale Starts' => $sale->sale_starts_at,
                        'Sale Ends' => $sale->sale_ends_at,
                    ] as $label => $date)
                        <div class="flex justify-between">
                            <span class="text-gray-500">{{ $label }}</span>
                            <span>{{ $date ? \Carbon\Carbon::parse($date)->format('M j, Y H:i') : '—' }}</span>
                        </div>
                    @endforeach
                </div>
            </x-card>

            {{-- Actions --}}
            @if(!in_array($sale->status, ['ended', 'cancelled']))
                <x-card title="Actions">
                    <div class="space-y-2">
                        @foreach($nextStatuses as $next)
                            @php
                                $actionMap = [
                                    'open'      => ['action' => 'open',              'label' => 'Open Submissions', 'color' => 'primary'],
                                    'review'    => ['action' => 'close-submissions',  'label' => 'Close Submissions', 'color' => 'secondary'],
                                    'scheduled' => ['action' => 'schedule',           'label' => 'Mark Scheduled',   'color' => 'secondary'],
                                    'live'      => ['action' => 'launch',             'label' => 'Launch Sale',      'color' => 'success'],
                                    'ended'     => ['action' => 'end',               'label' => 'End Sale',         'color' => 'secondary'],
                                    'cancelled' => ['action' => 'cancel',            'label' => 'Cancel Sale',      'color' => 'danger'],
                                ];
                                $act = $actionMap[$next['value']] ?? null;
                            @endphp
                            @if($act)
                                <button type="button"
                                    class="btn btn-{{ $act['color'] }} w-full justify-center flash-sale-transition"
                                    data-action="{{ $act['action'] }}"
                                    data-confirm="{{ $act['action'] === 'cancel' ? 'Are you sure you want to cancel this flash sale?' : '' }}">
                                    {{ $act['label'] }}
                                </button>
                            @endif
                        @endforeach
                    </div>
                </x-card>
            @endif

            {{-- Created by --}}
            @if($sale->createdByAdmin)
                <div class="text-xs text-gray-400 text-center">
                    Created by {{ $sale->createdByAdmin->name }}
                    on {{ $sale->created_at->format('M j, Y') }}
                </div>
            @endif

        </div>{{-- /sidebar --}}

    </div>

    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    {{-- MODALS --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}

    {{-- Approve submission --}}
    <x-modal id="approve-modal" title="Approve Submission" size="sm">
        <form id="approve-form">
            @csrf
            <input type="hidden" name="_submission_id" id="approve-submission-id">
            <div class="space-y-4">
                <div>
                    <label class="form-label">Notes (optional)</label>
                    <textarea name="notes" rows="2" class="form-textarea w-full"></textarea>
                </div>
                <div id="fraud-warning" class="hidden rounded-lg bg-amber-50 border border-amber-200 p-3 text-sm text-amber-800">
                    <strong>Pricing Warning:</strong>
                    <ul id="fraud-reasons" class="mt-1 list-disc list-inside"></ul>
                    <label class="flex items-center gap-2 mt-2 cursor-pointer">
                        <input type="checkbox" name="override_fraud_check" value="1" class="form-checkbox">
                        <span>I acknowledge the risk and want to approve anyway</span>
                    </label>
                </div>
            </div>
            <x-slot:footer>
                <button type="button" data-modal-close class="btn btn-ghost">Cancel</button>
                <button type="submit" class="btn btn-primary">Approve</button>
            </x-slot:footer>
        </form>
    </x-modal>

    {{-- Reject submission --}}
    <x-modal id="reject-modal" title="Reject Submission" size="sm">
        <form id="reject-form">
            @csrf
            <input type="hidden" name="_submission_id" id="reject-submission-id">
            <div class="space-y-4">
                <div>
                    <label class="form-label">Rejection Code</label>
                    <select name="rejection_code" class="form-select w-full">
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
                    <label class="form-label">Reason <span class="text-danger-500">*</span></label>
                    <textarea name="reason" rows="3" class="form-textarea w-full" required
                        placeholder="Explain why this submission is being rejected…"></textarea>
                </div>
            </div>
            <x-slot:footer>
                <button type="button" data-modal-close class="btn btn-ghost">Cancel</button>
                <button type="submit" class="btn btn-danger">Reject</button>
            </x-slot:footer>
        </form>
    </x-modal>

    {{-- Manual invite modal --}}
    <x-modal id="manual-invite-modal" title="Invite Vendor Manually" size="sm">
        <form id="manual-invite-form">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="form-label">Vendor IDs</label>
                    <textarea name="vendor_ids_raw" rows="4" class="form-textarea w-full font-mono text-sm"
                        placeholder="Paste one vendor ID per line…"></textarea>
                    <p class="text-xs text-gray-400 mt-1">One UUID per line.</p>
                </div>
            </div>
            <x-slot:footer>
                <button type="button" data-modal-close class="btn btn-ghost">Cancel</button>
                <button type="submit" class="btn btn-primary">Send Invitations</button>
            </x-slot:footer>
        </form>
    </x-modal>

@endsection
