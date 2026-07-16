<?php

namespace App\Jobs;

use App\Enums\SubOrderStatus;
use App\Models\OrderStatusHistory;
use App\Models\SubOrder;
use App\Services\LedgerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoCompleteOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of days after delivery before auto-completing a sub-order.
     */
    private const AUTO_COMPLETE_DAYS = 14;

    public function handle(LedgerService $ledgerService): void
    {
        $cutoff = now()->subDays(self::AUTO_COMPLETE_DAYS);

        // Find eligible sub-orders: delivered and past the auto-complete window
        $subOrders = SubOrder::with('order')
            ->whereNull('deleted_at')
            ->where('status', SubOrderStatus::Delivered->value)
            ->whereNotNull('delivered_at')
            ->where('delivered_at', '<=', $cutoff)
            ->get();

        if ($subOrders->isEmpty()) {
            return;
        }

        $affectedOrderIds = $subOrders->pluck('order_id')->unique()->values();

        DB::transaction(function () use ($subOrders, $affectedOrderIds, $ledgerService) {
            foreach ($subOrders as $subOrder) {
                $subOrder->update(['status' => 'completed']);

                $this->ledgerDeliverySubsidy($subOrder, $ledgerService);

                OrderStatusHistory::create([
                    'order_id' => $subOrder->order_id,
                    'sub_order_id' => $subOrder->id,
                    'from_status' => 'delivered',
                    'to_status' => 'completed',
                    'changed_by_admin_id' => null,
                    'reason' => 'Auto-completed after ' . self::AUTO_COMPLETE_DAYS . ' days post-delivery.',
                    'metadata' => ['source' => 'AutoCompleteOrdersJob'],
                ]);
            }

            // For each affected parent order, check if all sub-orders are now completed
            foreach ($affectedOrderIds as $orderId) {
                $allCompleted = SubOrder::where('order_id', $orderId)
                    ->whereNull('deleted_at')
                    ->whereNotIn('status', ['completed', 'cancelled', 'refunded'])
                    ->doesntExist();

                if ($allCompleted) {
                    $order = \App\Models\Order::find($orderId);
                    if ($order && $order->status->value !== 'completed') {
                        $prevStatus = $order->status->value;
                        $order->update(['status' => 'completed', 'completed_at' => now()]);

                        OrderStatusHistory::create([
                            'order_id' => $orderId,
                            'sub_order_id' => null,
                            'from_status' => $prevStatus,
                            'to_status' => 'completed',
                            'changed_by_admin_id' => null,
                            'reason' => 'All sub-orders completed. Auto-completed.',
                            'metadata' => ['source' => 'AutoCompleteOrdersJob'],
                        ]);
                    }
                }
            }
        });

        Log::info('AutoCompleteOrdersJob: completed ' . $subOrders->count() . ' sub-order(s) across ' . $affectedOrderIds->count() . ' order(s).');
    }

    /**
     * Post the delivery admin_subsidy_cents / vendor_deduction_cents split for a
     * completed sub-order to the double-entry ledger. Guarded by
     * delivery_subsidy_ledgered so a sub-order is never posted twice.
     */
    private function ledgerDeliverySubsidy(SubOrder $subOrder, LedgerService $ledgerService): void
    {
        if ($subOrder->delivery_subsidy_ledgered) {
            return;
        }

        $adminSubsidyCents = (int) $subOrder->admin_subsidy_cents;
        $vendorDeductionCents = (int) $subOrder->vendor_deduction_cents;

        if ($adminSubsidyCents <= 0 && $vendorDeductionCents <= 0) {
            $subOrder->update(['delivery_subsidy_ledgered' => true]);

            return;
        }

        $entries = [];
        $groupId = $ledgerService->newGroupId();
        $currency = $subOrder->order->currency;

        if ($adminSubsidyCents > 0) {
            $entries[] = [
                'account_type' => 'shipping_revenue',
                'account_holder_type' => null,
                'account_holder_id' => null,
                'debit' => $adminSubsidyCents,
                'credit' => 0,
                'currency' => $currency,
                'reference_type' => 'sub_order',
                'reference_id' => $subOrder->id,
                'description' => "Platform delivery subsidy for sub-order {$subOrder->sub_order_number}",
            ];
            $entries[] = [
                'account_type' => 'platform_revenue',
                'account_holder_type' => null,
                'account_holder_id' => null,
                'debit' => 0,
                'credit' => $adminSubsidyCents,
                'currency' => $currency,
                'reference_type' => 'sub_order',
                'reference_id' => $subOrder->id,
                'description' => "Platform delivery subsidy for sub-order {$subOrder->sub_order_number}",
            ];
        }

        if ($vendorDeductionCents > 0) {
            $entries[] = [
                'account_type' => 'shipping_revenue',
                'account_holder_type' => null,
                'account_holder_id' => null,
                'debit' => $vendorDeductionCents,
                'credit' => 0,
                'currency' => $currency,
                'reference_type' => 'sub_order',
                'reference_id' => $subOrder->id,
                'description' => "Vendor-covered delivery deduction for sub-order {$subOrder->sub_order_number}",
            ];
            $entries[] = [
                'account_type' => 'seller_payable',
                'account_holder_type' => 'vendors',
                'account_holder_id' => $subOrder->vendor_id,
                'debit' => 0,
                'credit' => $vendorDeductionCents,
                'currency' => $currency,
                'reference_type' => 'sub_order',
                'reference_id' => $subOrder->id,
                'description' => "Vendor-covered delivery deduction for sub-order {$subOrder->sub_order_number}",
            ];
        }

        $ledgerService->record($groupId, $entries);

        $subOrder->update(['delivery_subsidy_ledgered' => true]);
    }
}
