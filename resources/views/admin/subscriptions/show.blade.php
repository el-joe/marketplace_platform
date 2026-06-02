@extends('layouts.admin')

@section('title', 'Subscription Details')

@section('content')

    <x-breadcrumbs :items="$breadcrumbs" class="mb-4" />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- ─── Left: Subscription Info ─────────────────────────────────────────── --}}
        <div class="lg:col-span-1 space-y-4">

            <div class="bg-white rounded-2xl border border-gray-100 p-5">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-bold text-gray-900 text-lg">Subscription</h2>
                    <span class="badge badge-{{ $subscription->statusColor() }}">
                        {{ ucfirst($subscription->status) }}
                    </span>
                </div>

                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Vendor</dt>
                        <dd class="font-medium text-gray-800">
                            <a href="{{ route('admin.vendors.show', $subscription->vendor_id) }}" class="hover:underline">
                                {{ $subscription->vendor->business_name ?? $subscription->vendor->name }}
                            </a>
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Plan</dt>
                        <dd class="font-medium text-gray-800">{{ $subscription->plan->name_en ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Started</dt>
                        <dd class="font-medium text-gray-800">{{ $subscription->started_at?->format('d M Y') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Period</dt>
                        <dd class="font-medium text-gray-800">
                            {{ $subscription->period_start?->format('d M Y') }}
                            →
                            {{ $subscription->period_end?->format('d M Y') }}
                        </dd>
                    </div>
                    @if($subscription->isActive())
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Days Remaining</dt>
                            <dd class="font-bold text-green-600">{{ $subscription->daysRemaining() }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Auto-Renew</dt>
                        <dd>
                            <span class="badge badge-{{ $subscription->auto_renew ? 'success' : 'secondary' }} text-xs">
                                {{ $subscription->auto_renew ? 'On' : 'Off' }}
                            </span>
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Listings Used</dt>
                        <dd class="font-medium text-gray-800">
                            {{ $subscription->listings_used }}
                            /
                            {{ $subscription->plan?->hasUnlimitedListings() ? '∞' : ($subscription->plan?->max_listings ?? '—') }}
                        </dd>
                    </div>
                    @if($subscription->approved_by_admin_id)
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Approved By</dt>
                            <dd class="font-medium text-gray-800">
                                {{ $subscription->approvedByAdmin?->name ?? $subscription->approved_by_admin_id }}
                            </dd>
                        </div>
                    @endif
                    @if($subscription->cancelled_at)
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Cancelled At</dt>
                            <dd class="text-red-500">{{ $subscription->cancelled_at->format('d M Y') }}</dd>
                        </div>
                    @endif
                    @if($subscription->cancellation_reason)
                        <div class="pt-1">
                            <dt class="text-gray-500 text-xs mb-0.5">Cancellation Reason</dt>
                            <dd class="text-xs bg-gray-50 rounded p-2 text-gray-700">{{ $subscription->cancellation_reason }}
                            </dd>
                        </div>
                    @endif
                </dl>

                @if($subscription->isActive())
                    <div class="mt-5 pt-4 border-t border-gray-100">
                        <button type="button" id="btn-cancel-this-sub" class="btn btn-danger btn-sm w-full"
                            data-id="{{ $subscription->id }}">
                            Cancel Subscription
                        </button>
                    </div>
                @endif
            </div>

            {{-- Plan info card --}}
            @if($subscription->plan)
                <div class="bg-white rounded-2xl border border-gray-100 p-5">
                    <h3 class="font-semibold text-gray-800 mb-3 text-sm">Plan Details</h3>
                    <dl class="space-y-1.5 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Price</dt>
                            <dd class="font-medium">{{ number_format($subscription->plan->price_cents / 100) }}
                                {{ $subscription->plan->currency }}/mo</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Commission Discount</dt>
                            <dd class="font-medium">{{ $subscription->plan->commission_discount_pct }}%</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Free Shipping</dt>
                            <dd>
                                <span
                                    class="badge badge-{{ $subscription->plan->free_shipping_included ? 'success' : 'secondary' }} text-xs">
                                    {{ $subscription->plan->free_shipping_included ? 'Yes' : 'No' }}
                                </span>
                            </dd>
                        </div>
                        @foreach(($subscription->plan->features ?? []) as $feature)
                            <div class="flex items-center gap-1.5 text-xs text-gray-600">
                                <span class="text-green-500">✓</span>
                                {{ ucwords(str_replace('_', ' ', $feature)) }}
                            </div>
                        @endforeach
                    </dl>
                </div>
            @endif

        </div>

        {{-- ─── Right: Invoices ────────────────────────────────────────────────── --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-bold text-gray-900">Invoices</h3>
                    <span class="text-xs text-gray-400">{{ $invoices->count() }} invoice(s)</span>
                </div>

                @if($invoices->isEmpty())
                    <div class="p-10 text-center text-gray-400 text-sm">No invoices yet.</div>
                @else
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                            <tr>
                                <th class="px-4 py-3 text-left">Invoice #</th>
                                <th class="px-4 py-3 text-left">Amount</th>
                                <th class="px-4 py-3 text-left">Status</th>
                                <th class="px-4 py-3 text-left">Period</th>
                                <th class="px-4 py-3 text-left">Paid At</th>
                                <th class="px-4 py-3 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($invoices as $invoice)
                                <tr class="hover:bg-gray-50/50">
                                    <td class="px-4 py-3 font-mono text-xs text-gray-700">{{ $invoice->invoice_number }}</td>
                                    <td class="px-4 py-3 font-semibold text-gray-900">{{ $invoice->amountFormatted() }}</td>
                                    <td class="px-4 py-3">
                                        <span
                                            class="badge badge-{{ $invoice->statusColor() }} text-xs">{{ ucfirst($invoice->status) }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-500">
                                        {{ $invoice->period_start?->format('d M') }} → {{ $invoice->period_end?->format('d M Y') }}
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-500">
                                        {{ $invoice->paid_at?->format('d M Y H:i') ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($invoice->status !== 'paid')
                                            <button type="button" class="btn btn-xs btn-success btn-mark-paid"
                                                data-id="{{ $invoice->id }}">
                                                Mark Paid
                                            </button>
                                        @else
                                            <span class="text-xs text-gray-400">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>

    </div>

    {{-- ─── Cancel Subscription Modal ───────────────────────────────────────────── --}}
    <div id="cancel-sub-modal" class="modal" style="display:none;">
        <div class="modal-box max-w-sm">
            <h3 class="font-bold text-lg mb-4">Cancel Subscription</h3>
            <div>
                <label class="label-sm">Reason (optional)</label>
                <textarea id="cancel-sub-reason" rows="3" class="form-input w-full text-sm"
                    placeholder="Reason for cancellation..."></textarea>
            </div>
            <div class="flex gap-3 justify-end mt-5 pt-4 border-t border-gray-100">
                <button type="button" id="cancel-sub-close" class="btn btn-ghost btn-sm">Close</button>
                <button type="button" id="cancel-sub-confirm" class="btn btn-danger btn-sm">Cancel Subscription</button>
            </div>
        </div>
    </div>

    {{-- ─── Mark Paid Modal ─────────────────────────────────────────────────────── --}}
    <div id="mark-paid-modal" class="modal" style="display:none;">
        <div class="modal-box max-w-sm">
            <h3 class="font-bold text-lg mb-4">Mark Invoice Paid</h3>
            <input type="hidden" id="mp-invoice-id">
            <div>
                <label class="label-sm">Payment Transaction ID (optional)</label>
                <input type="text" id="mp-txid" class="form-input w-full text-sm" placeholder="e.g. TXN-123456">
            </div>
            <div class="flex gap-3 justify-end mt-5 pt-4 border-t border-gray-100">
                <button type="button" id="mp-close" class="btn btn-ghost btn-sm">Close</button>
                <button type="button" id="mp-confirm" class="btn btn-success btn-sm">Mark Paid</button>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        $(function () {
            const tok = '{{ csrf_token() }}';

            // ── Cancel Subscription ────────────────────────────────────────────────────
            $('#btn-cancel-this-sub').on('click', () => {
                $('#cancel-sub-reason').val('');
                $('#cancel-sub-modal').show();
            });
            $('#cancel-sub-close').on('click', () => $('#cancel-sub-modal').hide());
            $('#cancel-sub-confirm').on('click', function () {
                fetch('/admin/subscriptions/{{ $subscription->id }}/cancel', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': tok, 'Content-Type': 'application/json' },
                    body: JSON.stringify({ reason: $('#cancel-sub-reason').val() }),
                }).then(r => r.json()).then(data => {
                    if (data.success) { window.Toast.success(data.message); location.reload(); }
                    else { window.Toast.error(data.message); }
                });
            });

            // ── Mark Invoice Paid ──────────────────────────────────────────────────────
            $(document).on('click', '.btn-mark-paid', function () {
                $('#mp-invoice-id').val($(this).data('id'));
                $('#mp-txid').val('');
                $('#mark-paid-modal').show();
            });
            $('#mp-close').on('click', () => $('#mark-paid-modal').hide());
            $('#mp-confirm').on('click', function () {
                const id = $('#mp-invoice-id').val();
                fetch('/admin/subscriptions/invoices/' + id + '/mark-paid', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': tok, 'Content-Type': 'application/json' },
                    body: JSON.stringify({ payment_transaction_id: $('#mp-txid').val() }),
                }).then(r => r.json()).then(data => {
                    if (data.success) { window.Toast.success(data.message); location.reload(); }
                    else { window.Toast.error(data.message); }
                });
            });
        });
    </script>
@endpush
