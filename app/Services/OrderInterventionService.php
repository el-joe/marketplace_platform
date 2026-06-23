<?php

namespace App\Services;

use App\Models\Dispute;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Refund;
use App\Models\SubOrder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class OrderInterventionService
{
    // State machine constants are canonical on SubOrder (via HasStateMachine).
    // These aliases allow the service to be used as an authoritative reference
    // by controllers that already type-hint this class.
    public const STATUS_TRANSITIONS    = SubOrder::STATUS_TRANSITIONS;
    public const STATUS_LABELS         = SubOrder::STATUS_LABELS;
    public const BLOCK_CANCEL_STATUSES = SubOrder::BLOCK_CANCEL_STATUSES;

    // ─────────────────────────────────────────────────────────────────────────
    // State machine helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Return valid next statuses for a sub-order.
     *
     * @return Collection<int, array{value: string, label: string}>
     */
    public function getNextStatuses(SubOrder $subOrder): Collection
    {
        return $subOrder->getNextStatuses();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Update sub-order status
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Transition a sub-order to a new status and record the history.
     *
     * @throws ValidationException if the transition is not valid
     */
    public function updateSubOrderStatus(
        SubOrder $subOrder,
        string $newStatus,
        string $reason,
        string $adminId
    ): void {
        if (!$subOrder->canTransitionTo($newStatus)) {
            throw ValidationException::withMessages([
                'new_status' => ["Status transition from '{$subOrder->status}' to '{$newStatus}' is not allowed."],
            ]);
        }

        DB::transaction(function () use ($subOrder, $newStatus, $reason, $adminId) {
            $old = $subOrder->status;

            $updates = ['status' => $newStatus];

            if ($newStatus === 'shipped') {
                $updates['shipped_at'] = now();
            } elseif ($newStatus === 'delivered') {
                $updates['delivered_at'] = now();
            } elseif ($newStatus === 'cancelled') {
                $updates['cancelled_at'] = now();
            }

            $subOrder->update($updates);

            OrderStatusHistory::create([
                'order_id' => $subOrder->order_id,
                'sub_order_id' => $subOrder->id,
                'from_status' => $old,
                'to_status' => $newStatus,
                'changed_by_admin_id' => $adminId,
                'reason' => $reason,
            ]);

            // If all sub-orders of the parent order are now completed/cancelled,
            // auto-advance the parent order status.
            $this->syncParentOrderStatus($subOrder->order_id, $adminId);
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Force cancel
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Force-cancel an order and all its sub-orders.
     *
     * @param  bool  $force  Override block on shipped/delivered sub-orders
     * @throws \RuntimeException if blocked sub-orders exist and $force is false
     */
    public function forceCancel(
        Order $order,
        string $reason,
        bool $force,
        string $adminId
    ): void {
        $order->loadMissing('subOrders');

        $blocked = $order->subOrders->filter(fn ($so) => $so->blocksCancellation());

        if ($blocked->isNotEmpty() && !$force) {
            throw new \RuntimeException(
                'blocked:' . $blocked->pluck('sub_order_number')->implode(',')
            );
        }

        DB::transaction(function () use ($order, $reason, $adminId) {
            foreach ($order->subOrders as $subOrder) {
                if (in_array($subOrder->status, ['cancelled', 'refunded'], true)) {
                    continue;
                }

                $old = $subOrder->status;
                $subOrder->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'cancellation_reason' => $reason,
                ]);

                OrderStatusHistory::create([
                    'order_id' => $order->id,
                    'sub_order_id' => $subOrder->id,
                    'from_status' => $old,
                    'to_status' => 'cancelled',
                    'changed_by_admin_id' => $adminId,
                    'reason' => '[Force Cancel] ' . $reason,
                ]);
            }

            $originalStatus = $order->status;
            $order->update(['status' => 'cancelled', 'cancelled_at' => now()]);

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'sub_order_id' => null,
                'from_status' => $originalStatus,
                'to_status' => 'cancelled',
                'changed_by_admin_id' => $adminId,
                'reason' => '[Force Cancel] ' . $reason,
            ]);
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Process refund
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Record an admin-initiated refund against an order.
     *
     * @param  string  $refundType  full|partial|shipping_only
     * @param  float|null  $amount  Required when $refundType === 'partial'
     * @throws ValidationException
     */
    public function processRefund(
        Order $order,
        string $refundType,
        ?float $amount,
        string $reason,
        ?string $reasonNotes,
        ?string $subOrderId,
        bool $vendorChargedBack,
        string $adminId
    ): Refund {
        $order->loadMissing('transactions');

        $capturedTx = $order->transactions->firstWhere('type', 'capture')
            ?? $order->transactions->firstWhere('type', 'sale');

        if (!$capturedTx) {
            throw ValidationException::withMessages([
                'order_id' => ['No captured payment transaction found to refund against.'],
            ]);
        }

        $amountCents = match ($refundType) {
            'full' => $order->total,
            'shipping_only' => $order->shipping,
            default => (int) round(($amount ?? 0) * 100),
        };

        if ($amountCents <= 0) {
            throw ValidationException::withMessages([
                'amount' => ['Refund amount must be greater than zero.'],
            ]);
        }

        return Refund::create([
            'order_id' => $order->id,
            'sub_order_id' => $subOrderId,
            'original_transaction_id' => $capturedTx->id,
            'refund_transaction_id' => null,
            'amount' => $amountCents,
            'currency' => $order->currency,
            'reason' => $reason,
            'reason_notes' => $reasonNotes,
            'refund_type' => $refundType,
            'initiated_by_customer_id' => $order->customer_id,
            'approved_by_admin_id' => $adminId,
            'vendor_charged_back' => $vendorChargedBack,
            'status' => 'approved',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Escalate dispute
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Create a dispute record for an order/sub-order.
     */
    public function escalateDispute(
        Order $order,
        string $subOrderId,
        string $reason,
        string $description,
        string $adminId
    ): Dispute {
        $subOrder = SubOrder::findOrFail($subOrderId);

        return Dispute::create([
            'dispute_number' => 'DSP-' . strtoupper(substr(str_replace('-', '', (string) \Illuminate\Support\Str::uuid()), 0, 10)),
            'order_id' => $order->id,
            'sub_order_id' => $subOrder->id,
            'customer_id' => $order->customer_id,
            'vendor_id' => $subOrder->vendor_id,
            'reason' => $reason,
            'description' => $description,
            'status' => 'escalated',
            'assigned_to_admin_id' => $adminId,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Flag fraud
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Set risk score to 100 and log a fraud-flag status history entry.
     */
    public function flagFraud(Order $order, string $reason, string $adminId): void
    {
        DB::transaction(function () use ($order, $reason, $adminId) {
            $order->updateQuietly(['risk_score' => 100]);

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'sub_order_id' => null,
                'from_status' => $order->status,
                'to_status' => $order->status,
                'changed_by_admin_id' => $adminId,
                'reason' => '[Fraud Flagged] ' . $reason,
                'metadata' => ['action' => 'fraud_flagged'],
            ]);
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * After a sub-order status change, sync the parent order's overall status.
     * Rules:
     *  - All sub-orders completed → order = completed
     *  - All sub-orders cancelled → order = cancelled
     *  - Any sub-order delivered, none cancelled-only → order = delivered
     *  - Any sub-order shipped   → order = partially_shipped or shipped
     */
    private function syncParentOrderStatus(string $orderId, string $adminId): void
    {
        $order = Order::with('subOrders')->find($orderId);

        if (!$order) {
            return;
        }

        $statuses = $order->subOrders->pluck('status');

        $newStatus = null;

        if ($statuses->every(fn($s) => $s === 'completed')) {
            $newStatus = 'completed';
        } elseif ($statuses->every(fn($s) => in_array($s, ['cancelled', 'refunded'], true))) {
            $newStatus = 'cancelled';
        } elseif ($statuses->contains('delivered') && !$statuses->every(fn($s) => $s === 'delivered')) {
            $newStatus = 'partially_delivered';
        } elseif ($statuses->every(fn($s) => $s === 'delivered')) {
            $newStatus = 'delivered';
        } elseif (
            $statuses->contains(fn($s) => in_array($s, ['shipped', 'out_for_delivery'], true))
            && !$statuses->every(fn($s) => in_array($s, ['shipped', 'out_for_delivery', 'delivered', 'completed'], true))
        ) {
            $newStatus = 'partially_shipped';
        } elseif ($statuses->every(fn($s) => in_array($s, ['shipped', 'out_for_delivery'], true))) {
            $newStatus = 'shipped';
        }

        if ($newStatus && $newStatus !== $order->status) {
            $old = $order->status;
            $order->update([
                'status' => $newStatus,
                'completed_at' => $newStatus === 'completed' ? now() : $order->completed_at,
                'cancelled_at' => $newStatus === 'cancelled' ? now() : $order->cancelled_at,
            ]);

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'sub_order_id' => null,
                'from_status' => $old,
                'to_status' => $newStatus,
                'changed_by_admin_id' => $adminId,
                'reason' => '[Auto] Order status synced from sub-order changes.',
            ]);
        }
    }
}
