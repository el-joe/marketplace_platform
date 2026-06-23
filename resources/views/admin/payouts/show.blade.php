@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/admin/payouts.js'])
@endpush

@section('title', 'Payout #' . $payout->payout_number)

@section('content')
    @php
        $currency = strtoupper($payout->currency);
        $fmt = fn($cents) => $currency . ' ' . number_format($cents / 100, 2);

        $statusColors = [
            'pending'    => 'gray',
            'approved'   => 'primary',
            'processing' => 'primary',
            'completed'  => 'success',
            'failed'     => 'danger',
            'on_hold'    => 'warning',
        ];

        $methodLabels = [
            'bank_transfer' => 'Bank Transfer',
            'wallet'        => 'Wallet',
            'paypal'        => 'PayPal',
        ];

        $accountTypeLabels = [
            'customer_payment'    => 'Customer Payment',
            'platform_revenue'    => 'Platform Revenue',
            'platform_commission' => 'Platform Commission',
            'seller_payable'      => 'Seller Payable',
            'gateway_fee'         => 'Gateway Fee',
            'tax_payable'         => 'Tax Payable',
            'refund_liability'    => 'Refund Liability',
            'shipping_revenue'    => 'Shipping Revenue',
            'cod_clearing'        => 'COD Clearing',
        ];
    @endphp

    <script>window.PAYOUT_ID = {{ $payout->id }};</script>

    <div class="flex gap-6 items-start">

        {{-- ═══════════════════════════════════════════════════════════════════════ --}}
        {{-- MAIN COLUMN --}}
        {{-- ═══════════════════════════════════════════════════════════════════════ --}}
        <div class="flex-1 min-w-0 space-y-6">

            {{-- ──────────────────────────────────── --}}
            {{-- Financial Breakdown --}}
            {{-- ──────────────────────────────────── --}}
            <x-card title="Financial Breakdown">
                <div class="space-y-0 divide-y divide-gray-100">
                    {{-- Income rows --}}
                    <div class="flex items-center justify-between py-3 text-sm">
                        <span class="text-gray-600">Gross Sales</span>
                        <span class="font-medium text-gray-900">{{ $fmt($payout->gross_sales) }}</span>
                    </div>

                    {{-- Deduction rows --}}
                    <div class="flex items-center justify-between py-3 text-sm">
                        <span class="text-gray-500">Platform Commission</span>
                        <span class="text-danger-600">−{{ $fmt($payout->commission) }}</span>
                    </div>
                    @if($payout->refunds_deducted > 0)
                        <div class="flex items-center justify-between py-3 text-sm">
                            <span class="text-gray-500">Refunds Deducted</span>
                            <span class="text-danger-600">−{{ $fmt($payout->refunds_deducted) }}</span>
                        </div>
                    @endif
                    @if($payout->chargebacks_deducted > 0)
                        <div class="flex items-center justify-between py-3 text-sm">
                            <span class="text-gray-500">Chargebacks Deducted</span>
                            <span class="text-danger-600">−{{ $fmt($payout->chargebacks_deducted) }}</span>
                        </div>
                    @endif
                    @if($payout->storage_fees > 0)
                        <div class="flex items-center justify-between py-3 text-sm">
                            <span class="text-gray-500">Storage Fees (FBN)</span>
                            <span class="text-danger-600">−{{ $fmt($payout->storage_fees) }}</span>
                        </div>
                    @endif
                    @if($payout->ad_fees > 0)
                        <div class="flex items-center justify-between py-3 text-sm">
                            <span class="text-gray-500">Advertising Fees</span>
                            <span class="text-danger-600">−{{ $fmt($payout->ad_fees) }}</span>
                        </div>
                    @endif
                    @if($payout->other_adjustments != 0)
                        <div class="flex items-center justify-between py-3 text-sm">
                            <span class="text-gray-500">Other Adjustments</span>
                            <span class="{{ $payout->other_adjustments < 0 ? 'text-danger-600' : 'text-success-600' }}">
                                {{ $payout->other_adjustments < 0 ? '−' : '+' }}{{ $fmt(abs($payout->other_adjustments)) }}
                            </span>
                        </div>
                    @endif

                    {{-- Net total --}}
                    <div class="flex items-center justify-between py-4 text-base font-bold bg-gray-50 px-4 -mx-4 rounded-b-xl mt-2">
                        <span class="text-gray-900">Net Payout Amount</span>
                        <span class="text-primary-700 text-lg">{{ $fmt($payout->net_amount) }}</span>
                    </div>
                </div>
            </x-card>

            {{-- ──────────────────────────────────── --}}
            {{-- Sub-Order Items --}}
            {{-- ──────────────────────────────────── --}}
            @if($payout->items->isNotEmpty())
                <x-card title="Sub-Orders Included">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Sub-Order</th>
                                    <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-500 uppercase">Gross</th>
                                    <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-500 uppercase">Commission</th>
                                    <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-500 uppercase">Net</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($payout->items as $item)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 font-mono text-xs text-gray-600">
                                            {{ $item->subOrder->sub_order_number ?? $item->sub_order_id }}
                                        </td>
                                        <td class="px-4 py-3 text-right">{{ $fmt($item->gross) }}</td>
                                        <td class="px-4 py-3 text-right text-danger-600">−{{ $fmt($item->commission) }}</td>
                                        <td class="px-4 py-3 text-right font-medium">{{ $fmt($item->net) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="border-t-2 border-gray-200 bg-gray-50">
                                <tr>
                                    <td class="px-4 py-3 font-semibold text-gray-700 text-sm">Total</td>
                                    <td class="px-4 py-3 text-right font-semibold">{{ $fmt($payout->items->sum('gross')) }}</td>
                                    <td class="px-4 py-3 text-right font-semibold text-danger-600">−{{ $fmt($payout->items->sum('commission')) }}</td>
                                    <td class="px-4 py-3 text-right font-semibold text-primary-700">{{ $fmt($payout->items->sum('net')) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </x-card>
            @endif

            {{-- ──────────────────────────────────── --}}
            {{-- Ledger Entries --}}
            {{-- ──────────────────────────────────── --}}
            @if($ledgerEntries->isNotEmpty())
                <x-card title="Ledger Entries">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Account Type</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Description</th>
                                    <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-500 uppercase">Debit</th>
                                    <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-500 uppercase">Credit</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Created</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($ledgerEntries as $entry)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700 ring-1 ring-gray-200">
                                                {{ $accountTypeLabels[$entry->account_type] ?? $entry->account_type }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-gray-600 max-w-xs truncate">{{ $entry->description }}</td>
                                        <td class="px-4 py-3 text-right font-mono text-sm">
                                            @if($entry->debit > 0)
                                                <span class="text-danger-600">{{ $fmt($entry->debit) }}</span>
                                            @else
                                                <span class="text-gray-300">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-right font-mono text-sm">
                                            @if($entry->credit > 0)
                                                <span class="text-success-600">{{ $fmt($entry->credit) }}</span>
                                            @else
                                                <span class="text-gray-300">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-xs text-gray-400">
                                            {{ \Carbon\Carbon::parse($entry->created_at)->format('M j, Y H:i') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-card>
            @endif

        </div>{{-- /main column --}}

        {{-- ═══════════════════════════════════════════════════════════════════════ --}}
        {{-- SIDEBAR --}}
        {{-- ═══════════════════════════════════════════════════════════════════════ --}}
        <div class="w-80 flex-shrink-0 space-y-4 sticky top-20">

            {{-- Payout Summary --}}
            <x-card title="Payout Summary">
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Payout #</span>
                        <span class="font-mono font-semibold text-gray-900">{{ $payout->payout_number }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Period</span>
                        <span class="text-gray-700">
                            {{ \Carbon\Carbon::parse($payout->period_start)->format('M j') }}
                            →
                            {{ \Carbon\Carbon::parse($payout->period_end)->format('M j, Y') }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-500">Status</span>
                        <x-badge :color="$statusColors[$payout->status] ?? 'gray'">
                            {{ ucwords(str_replace('_', ' ', $payout->status)) }}
                        </x-badge>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Method</span>
                        <span class="text-gray-700">{{ $methodLabels[$payout->payout_method] ?? $payout->payout_method }}</span>
                    </div>
                    @if($payout->processed_at)
                        <div class="flex justify-between">
                            <span class="text-gray-500">Processed</span>
                            <span class="text-gray-700">{{ \Carbon\Carbon::parse($payout->processed_at)->format('M j, Y') }}</span>
                        </div>
                    @endif
                    @if($payout->gateway_reference)
                        <div class="flex justify-between items-start gap-2">
                            <span class="text-gray-500 flex-shrink-0">Ref #</span>
                            <span class="font-mono text-xs text-gray-700 break-all text-right">{{ $payout->gateway_reference }}</span>
                        </div>
                    @endif
                    @if($payout->receipt_url)
                        <div class="pt-1">
                            <a href="{{ $payout->receipt_url }}" target="_blank"
                                class="inline-flex items-center gap-1.5 text-xs text-primary-600 hover:text-primary-800 hover:underline">
                                <x-heroicon name="document-arrow-down" class="w-3.5 h-3.5" />
                                Download Receipt
                            </a>
                        </div>
                    @endif
                </div>

                <div class="border-t border-gray-100 mt-3 pt-3">
                    <div class="flex justify-between font-bold text-base text-gray-900">
                        <span>Net Amount</span>
                        <span class="text-primary-700">{{ $fmt($payout->net_amount) }}</span>
                    </div>
                </div>
            </x-card>

            {{-- Vendor Info --}}
            <x-card title="Vendor">
                @if($payout->vendor)
                    <div class="space-y-2 text-sm">
                        <p class="font-semibold text-gray-900">{{ $payout->vendor->store_name }}</p>
                        @if($payout->vendor->business_name)
                            <p class="text-gray-500 text-xs">{{ $payout->vendor->business_name }}</p>
                        @endif
                        <a href="{{ route('admin.vendors.show', $payout->vendor->id) }}"
                            class="inline-flex items-center gap-1 text-xs text-primary-600 hover:underline mt-1">
                            <x-heroicon name="arrow-top-right-on-square" class="w-3.5 h-3.5" />
                            View Vendor Profile
                        </a>
                    </div>
                @else
                    <p class="text-sm text-gray-400 italic">Vendor not found.</p>
                @endif
            </x-card>

            {{-- Bank Account --}}
            @if($payout->bankAccount)
                <x-card title="Bank Account">
                    <div class="space-y-1.5 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Bank</span>
                            <span class="text-gray-800 font-medium">{{ $payout->bankAccount->bank_name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Account Holder</span>
                            <span class="text-gray-700">{{ $payout->bankAccount->account_holder_name }}</span>
                        </div>
                        @if($payout->bankAccount->iban)
                            <div class="flex justify-between items-start gap-2">
                                <span class="text-gray-500 flex-shrink-0">IBAN</span>
                                <span class="font-mono text-xs text-gray-700 text-right">
                                    {{ '****' . substr($payout->bankAccount->iban, -4) }}
                                </span>
                            </div>
                        @endif
                        @if($payout->bankAccount->swift_code)
                            <div class="flex justify-between">
                                <span class="text-gray-500">SWIFT</span>
                                <span class="font-mono text-xs text-gray-700">{{ $payout->bankAccount->swift_code }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between items-center">
                            <span class="text-gray-500">Verification</span>
                            <x-badge :color="$payout->bankAccount->verification_status === 'verified' ? 'success' : 'warning'">
                                {{ ucfirst($payout->bankAccount->verification_status) }}
                            </x-badge>
                        </div>
                    </div>
                </x-card>
            @endif

            {{-- Approved By --}}
            @if($payout->approvedByAdmin)
                <x-card title="Approved By">
                    <p class="text-sm font-medium text-gray-800">{{ $payout->approvedByAdmin->name }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $payout->approvedByAdmin->email }}</p>
                </x-card>
            @endif

            {{-- On-hold reason --}}
            @if($payout->status === 'on_hold' && $payout->failed_reason)
                <x-card title="Hold Reason">
                    <p class="text-sm text-amber-700">{{ $payout->failed_reason }}</p>
                </x-card>
            @endif

            {{-- Failed reason --}}
            @if($payout->status === 'failed' && $payout->failed_reason)
                <x-card title="Failure Reason">
                    <p class="text-sm text-danger-700">{{ $payout->failed_reason }}</p>
                </x-card>
            @endif

            {{-- Actions --}}
            <x-card title="Actions">
                <div class="space-y-2">
                    @if($payout->status === 'pending')
                        <button type="button" data-modal-open="approve-modal"
                            class="btn btn-primary w-full justify-center">
                            <x-heroicon name="check-circle" class="w-4 h-4 mr-1.5" />
                            Approve Payout
                        </button>
                        <button type="button" data-modal-open="recalculate-modal"
                            class="btn btn-secondary w-full justify-center">
                            <x-heroicon name="arrow-path" class="w-4 h-4 mr-1.5" />
                            Recalculate
                        </button>
                    @endif
                    @if($payout->status === 'approved')
                        <button type="button" data-modal-open="process-modal"
                            class="btn btn-primary w-full justify-center">
                            <x-heroicon name="banknotes" class="w-4 h-4 mr-1.5" />
                            Mark as Completed
                        </button>
                    @endif
                    @if(!in_array($payout->status, ['completed', 'failed']))
                        <button type="button" data-modal-open="hold-modal"
                            class="btn btn-ghost w-full justify-center text-warning-700 hover:bg-warning-50">
                            <x-heroicon name="pause-circle" class="w-4 h-4 mr-1.5" />
                            Put on Hold
                        </button>
                    @endif
                    @if($payout->status === 'on_hold')
                        <button type="button" data-modal-open="recalculate-modal"
                            class="btn btn-secondary w-full justify-center">
                            <x-heroicon name="arrow-path" class="w-4 h-4 mr-1.5" />
                            Recalculate
                        </button>
                    @endif
                </div>
            </x-card>

        </div>{{-- /sidebar --}}

    </div>{{-- /flex row --}}

    {{-- ═══════════════════════════════════════════════════════════════════════════ --}}
    {{-- MODALS --}}
    {{-- ═══════════════════════════════════════════════════════════════════════════ --}}

    {{-- 1. Approve Payout --}}
    <x-modal id="approve-modal" title="Approve Payout" size="sm">
        <form id="approve-form">
            @csrf
            <div class="space-y-4">
                <div class="rounded-lg bg-primary-50 border border-primary-200 p-3 text-sm text-primary-800">
                    Approving will create a ledger entry and allow the payout to be processed.
                    Net amount: <strong>{{ $fmt($payout->net_amount) }}</strong>
                </div>
                <div>
                    <label class="form-label">Notes (optional)</label>
                    <textarea name="notes" rows="2" class="form-textarea w-full"
                        placeholder="Approval notes…"></textarea>
                </div>
            </div>
            <x-slot:footer>
                <button type="button" data-modal-close class="btn btn-ghost">Cancel</button>
                <button type="submit" class="btn btn-primary">Approve</button>
            </x-slot:footer>
        </form>
    </x-modal>

    {{-- 2. Process / Complete Payout --}}
    <x-modal id="process-modal" title="Mark Payout as Completed" size="sm">
        <form id="process-form">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="form-label">Gateway Reference (optional)</label>
                    <input type="text" name="gateway_reference" class="form-input w-full"
                        placeholder="Bank transaction ID…">
                </div>
                <div>
                    <label class="form-label">Receipt URL (optional)</label>
                    <input type="url" name="receipt_url" class="form-input w-full"
                        placeholder="https://…">
                </div>
            </div>
            <x-slot:footer>
                <button type="button" data-modal-close class="btn btn-ghost">Cancel</button>
                <button type="submit" class="btn btn-primary">Mark Completed</button>
            </x-slot:footer>
        </form>
    </x-modal>

    {{-- 3. Hold Payout --}}
    <x-modal id="hold-modal" title="Put Payout on Hold" size="sm">
        <form id="hold-form">
            @csrf
            <div class="space-y-4">
                <div class="rounded-lg bg-amber-50 border border-amber-200 p-3 text-sm text-amber-800 flex items-start gap-2">
                    <x-heroicon name="exclamation-triangle" class="w-5 h-5 flex-shrink-0 mt-0.5" />
                    <span>This payout will be paused. Provide a reason below.</span>
                </div>
                <div>
                    <label class="form-label">Reason <span class="text-danger-500">*</span></label>
                    <textarea name="reason" rows="3" class="form-textarea w-full"
                        placeholder="Why is this payout being held?"></textarea>
                </div>
            </div>
            <x-slot:footer>
                <button type="button" data-modal-close class="btn btn-ghost">Cancel</button>
                <button type="submit" class="btn btn-warning">Put on Hold</button>
            </x-slot:footer>
        </form>
    </x-modal>

    {{-- 4. Recalculate Payout --}}
    <x-modal id="recalculate-modal" title="Recalculate Payout" size="sm">
        <form id="recalculate-form">
            @csrf
            <div class="space-y-4">
                <p class="text-sm text-gray-600">
                    This will recalculate gross sales, commission, refunds, and chargebacks
                    for the period <strong>{{ \Carbon\Carbon::parse($payout->period_start)->format('M j') }}
                    → {{ \Carbon\Carbon::parse($payout->period_end)->format('M j, Y') }}</strong>
                    and update the net amount.
                </p>
            </div>
            <x-slot:footer>
                <button type="button" data-modal-close class="btn btn-ghost">Cancel</button>
                <button type="submit" class="btn btn-secondary">Recalculate</button>
            </x-slot:footer>
        </form>
    </x-modal>

@endsection
