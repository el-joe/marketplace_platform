<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dispute;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Refund;
use App\Models\SubOrder;
use App\Traits\HasDataTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class OrderController extends Controller
{
    use HasDataTable;

    // ── Sub-order status state machine ────────────────────────────────────────
    private const STATUS_TRANSITIONS = [
        'placed' => ['confirmed', 'cancelled'],
        'confirmed' => ['processing', 'cancelled'],
        'processing' => ['packed', 'cancelled'],
        'packed' => ['shipped', 'cancelled'],
        'shipped' => ['out_for_delivery', 'delivered'],
        'out_for_delivery' => ['delivered'],
        'delivered' => ['completed', 'returned'],
        'completed' => [],
        'cancelled' => [],
        'returned' => ['refunded'],
        'refunded' => [],
    ];

    private const STATUS_LABELS = [
        'placed' => 'Placed',
        'confirmed' => 'Confirmed',
        'processing' => 'Processing',
        'packed' => 'Packed',
        'shipped' => 'Shipped',
        'out_for_delivery' => 'Out for Delivery',
        'delivered' => 'Delivered',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        'returned' => 'Returned',
        'refunded' => 'Refunded',
    ];

    // Sub-orders in these statuses block force-cancel unless overridden
    private const BLOCK_CANCEL_STATUSES = [
        'shipped',
        'out_for_delivery',
        'delivered',
        'completed',
        'returned',
        'refunded',
    ];

    // ─────────────────────────────────────────────────────────────────────────
    // Index / Listing
    // ─────────────────────────────────────────────────────────────────────────

    public function index(): View
    {
        $countries = DB::table('countries')
            ->where('is_active', true)
            ->orderBy('name_en')
            ->pluck('name_en', 'id');

        return view('admin.orders.index', [
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
                ['label' => 'Orders'],
            ],
            'countries' => $countries,
        ]);
    }

    public function datatable(Request $request): JsonResponse
    {
        $columns = $this->columnDefinitions();

        $query = Order::query()
            ->leftJoin('customers as c', 'c.id', '=', 'orders.customer_id')
            ->whereNull('orders.deleted_at')
            ->select([
                'orders.id',
                'orders.order_number',
                'orders.status',
                'orders.payment_method',
                'orders.payment_status',
                'orders.currency',
                'orders.total',
                'orders.risk_score',
                'orders.placed_at',
                'orders.shipping_address_snapshot',
                'c.name as customer_name',
                'c.country_id',
            ])
            ->addSelect([
                'items_count' => OrderItem::selectRaw('COUNT(*)')
                    ->whereColumn('order_id', 'orders.id'),
            ]);

        $query = $this->applyFilters($query, $request, [
            'status' => fn($q, $v) => $q->where('orders.status', $v),
            'payment_status' => fn($q, $v) => $q->where('orders.payment_status', $v),
            'country_id' => fn($q, $v) => $q->where('c.country_id', $v),
            'date_from' => fn($q, $v) => $q->whereDate('orders.placed_at', '>=', $v),
            'date_to' => fn($q, $v) => $q->whereDate('orders.placed_at', '<=', $v),
            'min_total' => fn($q, $v) => $q->where('orders.total', '>=', (int) round((float) $v * 100)),
            'max_total' => fn($q, $v) => $q->where('orders.total', '<=', (int) round((float) $v * 100)),
            'risk_score_min' => fn($q, $v) => $q->where('orders.risk_score', '>=', (float) $v),
        ]);

        return $this->dataTableResponse($request, $query, $columns, function ($row) {
            $addr = is_string($row->shipping_address_snapshot)
                ? json_decode($row->shipping_address_snapshot, true)
                : (array) ($row->shipping_address_snapshot ?? []);

            $city = $addr['city'] ?? $addr['city_en'] ?? '';
            $parts = explode(' ', trim($row->customer_name ?? ''), 2);
            $masked = $parts[0] . (isset($parts[1]) ? ' ' . strtoupper($parts[1][0]) . '.' : '');
            $customer = trim($masked . ($city ? ', ' . $city : ''));

            return [
                'id' => $row->id,
                'order_number' => $row->order_number,
                'customer' => e($customer),
                'items_count' => (int) $row->items_count,
                'total_formatted' => strtoupper($row->currency) . ' ' . number_format($row->total / 100, 2),
                'payment_method' => $row->payment_method,
                'payment_status' => $row->payment_status,
                'status' => $row->status,
                'risk_score' => $row->risk_score !== null ? (float) $row->risk_score : null,
                'placed_at' => $row->placed_at,
                'show_url' => route('admin.orders.show', $row->id),
            ];
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Show (detail)
    // ─────────────────────────────────────────────────────────────────────────

    public function show(string $id): View
    {
        $order = Order::with([
            'subOrders.items.productVariant',
            'subOrders.vendor',
            'subOrders.carrier',
            'subOrders.statusHistories.changedByAdmin',
            'transactions.paymentMethod',
            'statusHistories.changedByAdmin',
            'disputes.messages',
            'refunds.approvedByAdmin',
            'customer',
        ])->whereNull('deleted_at')->findOrFail($id);

        return view('admin.orders.show', [
            'order' => $order,
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
                ['label' => 'Orders', 'url' => route('admin.orders.index')],
                ['label' => '#' . $order->order_number],
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Update sub-order status
    // ─────────────────────────────────────────────────────────────────────────

    public function updateSubOrderStatus(Request $request): JsonResponse
    {
        $subOrder = SubOrder::findOrFail($request->input('sub_order_id'));

        $validTransitions = self::STATUS_TRANSITIONS[$subOrder->status] ?? [];

        $request->validate([
            'sub_order_id' => 'required|string',
            'new_status' => ['required', 'in:' . implode(',', $validTransitions ?: ['__none__'])],
            'reason' => 'required|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $old = $subOrder->status;
            $subOrder->update(['status' => $request->new_status]);

            OrderStatusHistory::create([
                'id' => (string) Str::uuid(),
                'order_id' => $subOrder->order_id,
                'sub_order_id' => $subOrder->id,
                'from_status' => $old,
                'to_status' => $request->new_status,
                'changed_by_admin_id' => auth('admin')->id(),
                'reason' => $request->reason,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Status updated to ' . (self::STATUS_LABELS[$request->new_status] ?? $request->new_status),
                'new_status' => $request->new_status,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('SubOrder status update failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Update failed.'], 500);
        }
    }

    // Returns valid next statuses for a sub-order
    public function nextStatuses(string $id): JsonResponse
    {
        $subOrder = SubOrder::findOrFail($id);
        $transitions = self::STATUS_TRANSITIONS[$subOrder->status] ?? [];

        return response()->json([
            'data' => collect($transitions)->map(fn($s) => [
                'value' => $s,
                'label' => self::STATUS_LABELS[$s] ?? $s,
            ])->values(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Force cancel
    // ─────────────────────────────────────────────────────────────────────────

    public function forceCancel(Request $request, string $id): JsonResponse
    {
        $order = Order::with('subOrders')->whereNull('deleted_at')->findOrFail($id);

        $request->validate([
            'reason' => 'required|string|max:500',
            'force' => 'boolean',
        ]);

        $force = $request->boolean('force', false);

        $blocked = $order->subOrders->filter(
            fn($so) => in_array($so->status, self::BLOCK_CANCEL_STATUSES)
        );

        if ($blocked->isNotEmpty() && !$force) {
            return response()->json([
                'message' => 'One or more sub-orders are already shipped/delivered. Set force=true to override.',
                'blocked' => $blocked->pluck('sub_order_number')->values(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            foreach ($order->subOrders as $subOrder) {
                if (in_array($subOrder->status, ['cancelled', 'refunded'])) {
                    continue;
                }
                $old = $subOrder->status;
                $subOrder->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'cancellation_reason' => $request->reason,
                ]);
                OrderStatusHistory::create([
                    'id' => (string) Str::uuid(),
                    'order_id' => $order->id,
                    'sub_order_id' => $subOrder->id,
                    'from_status' => $old,
                    'to_status' => 'cancelled',
                    'changed_by_admin_id' => auth('admin')->id(),
                    'reason' => '[Force Cancel] ' . $request->reason,
                ]);
            }

            $originalOrderStatus = $order->status;
            $order->update(['status' => 'cancelled', 'cancelled_at' => now()]);
            OrderStatusHistory::create([
                'id' => (string) Str::uuid(),
                'order_id' => $order->id,
                'sub_order_id' => null,
                'from_status' => $originalOrderStatus,
                'to_status' => 'cancelled',
                'changed_by_admin_id' => auth('admin')->id(),
                'reason' => '[Force Cancel] ' . $request->reason,
            ]);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Order force-cancelled successfully.']);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Force cancel failed', ['order' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Cancel failed.'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Process refund
    // ─────────────────────────────────────────────────────────────────────────

    public function processRefund(Request $request, string $id): JsonResponse
    {
        $order = Order::with('transactions')->whereNull('deleted_at')->findOrFail($id);

        $request->validate([
            'refund_type' => 'required|in:full,partial,shipping_only',
            'amount' => 'required_if:refund_type,partial|numeric|min:0.01',
            'reason' => 'required|in:customer_request,out_of_stock,damaged,wrong_item,not_as_described,late_delivery,duplicate_order,other',
            'reason_notes' => 'nullable|string|max:500',
            'sub_order_id' => 'nullable|string|exists:sub_orders,id',
            'vendor_charged_back' => 'boolean',
        ]);

        $capturedTx = $order->transactions->firstWhere('type', 'capture')
            ?? $order->transactions->firstWhere('type', 'sale');

        if (!$capturedTx) {
            return response()->json(['message' => 'No captured transaction found to refund against.'], 422);
        }

        $amountCents = match ($request->refund_type) {
            'full' => $order->total,
            'shipping_only' => $order->shipping,
            default => (int) round((float) $request->amount * 100),
        };

        try {
            Refund::create([
                'id' => (string) Str::uuid(),
                'order_id' => $order->id,
                'sub_order_id' => $request->sub_order_id,
                'original_transaction_id' => $capturedTx->id,
                'refund_transaction_id' => null,
                'amount' => $amountCents,
                'currency' => $order->currency,
                'reason' => $request->reason,
                'reason_notes' => $request->reason_notes,
                'refund_type' => $request->refund_type,
                'initiated_by_customer_id' => $order->customer_id,
                'approved_by_admin_id' => auth('admin')->id(),
                'vendor_charged_back' => $request->boolean('vendor_charged_back'),
                'status' => 'approved',
            ]);

            return response()->json(['success' => true, 'message' => 'Refund queued for processing.']);
        } catch (\Throwable $e) {
            Log::error('Refund creation failed', ['order' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Refund creation failed.'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Escalate dispute
    // ─────────────────────────────────────────────────────────────────────────

    public function escalateDispute(Request $request, string $id): JsonResponse
    {
        $order = Order::whereNull('deleted_at')->findOrFail($id);

        $request->validate([
            'sub_order_id' => 'required|string|exists:sub_orders,id',
            'reason' => 'required|in:item_not_received,item_damaged,item_not_as_described,counterfeit,wrong_item,quality_issue,seller_unresponsive,refund_not_received,other',
            'description' => 'required|string|max:2000',
        ]);

        $subOrder = SubOrder::findOrFail($request->sub_order_id);

        try {
            $dispute = Dispute::create([
                'id' => (string) Str::uuid(),
                'dispute_number' => 'DSP-' . strtoupper(Str::random(10)),
                'order_id' => $order->id,
                'sub_order_id' => $subOrder->id,
                'customer_id' => $order->customer_id,
                'vendor_id' => $subOrder->vendor_id,
                'reason' => $request->reason,
                'description' => $request->description,
                'status' => 'escalated',
                'assigned_to_admin_id' => auth('admin')->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Dispute escalated: ' . $dispute->dispute_number,
            ]);
        } catch (\Throwable $e) {
            Log::error('Dispute creation failed', ['order' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Dispute creation failed.'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Flag fraud
    // ─────────────────────────────────────────────────────────────────────────

    public function flagFraud(Request $request, string $id): JsonResponse
    {
        $order = Order::whereNull('deleted_at')->findOrFail($id);

        $request->validate(['reason' => 'required|string|max:500']);

        try {
            DB::table('orders')->where('id', $id)->update([
                'risk_score' => 100,
                'updated_at' => now(),
            ]);

            OrderStatusHistory::create([
                'id' => (string) Str::uuid(),
                'order_id' => $order->id,
                'sub_order_id' => null,
                'from_status' => $order->status,
                'to_status' => $order->status,
                'changed_by_admin_id' => auth('admin')->id(),
                'reason' => '[Fraud Flagged] ' . $request->reason,
                'metadata' => ['action' => 'fraud_flagged'],
            ]);

            return response()->json(['success' => true, 'message' => 'Order flagged as potential fraud.']);
        } catch (\Throwable $e) {
            Log::error('Fraud flag failed', ['order' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Flag failed.'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function columnDefinitions(): array
    {
        return [
            [
                'title' => 'Order #',
                'data' => 'order_number',
                'name' => 'order_number',
                'orderable_column' => 'orders.order_number',
                'searchable_columns' => ['orders.order_number'],
                'render' => 'function(data,t,row){return "<a href=\""+row.show_url+"\" class=\"font-medium text-primary-600 hover:text-primary-800 hover:underline\">"+data+"</a>";}',
            ],
            [
                'title' => 'Customer',
                'data' => 'customer',
                'name' => 'customer',
                'orderable' => false,
                'searchable' => false,
            ],
            [
                'title' => 'Items',
                'data' => 'items_count',
                'name' => 'items_count',
                'orderable' => false,
                'searchable' => false,
                'className' => 'text-center',
            ],
            [
                'title' => 'Total',
                'data' => 'total_formatted',
                'name' => 'total_formatted',
                'orderable_column' => 'orders.total',
                'searchable' => false,
                'className' => 'text-right font-medium',
            ],
            [
                'title' => 'Payment',
                'data' => 'payment_method',
                'name' => 'payment_method',
                'orderable_column' => 'orders.payment_method',
                'searchable' => false,
                'render' => 'Renderers.badge({
                    card:          { label: "Card",          color: "primary" },
                    wallet:        { label: "Wallet",        color: "primary" },
                    cod:           { label: "Cash on Del.",  color: "gray"    },
                    bnpl:          { label: "BNPL",          color: "warning" },
                    bank_transfer: { label: "Bank Transfer", color: "gray"    }
                })',
            ],
            [
                'title' => 'Status',
                'data' => 'status',
                'name' => 'status',
                'orderable_column' => 'orders.status',
                'searchable' => false,
                'render' => 'Renderers.badge({
                    placed:               { label: "Placed",             color: "gray"    },
                    confirmed:            { label: "Confirmed",          color: "primary" },
                    partially_shipped:    { label: "Part. Shipped",      color: "primary" },
                    shipped:              { label: "Shipped",            color: "primary" },
                    partially_delivered:  { label: "Part. Delivered",    color: "primary" },
                    delivered:            { label: "Delivered",          color: "success" },
                    completed:            { label: "Completed",          color: "success" },
                    cancelled:            { label: "Cancelled",          color: "danger"  },
                    refunded:             { label: "Refunded",           color: "warning" },
                    disputed:             { label: "Disputed",           color: "danger"  }
                })',
            ],
            [
                'title' => 'Risk',
                'data' => 'risk_score',
                'name' => 'risk_score',
                'orderable_column' => 'orders.risk_score',
                'searchable' => false,
                'className' => 'text-center',
                'render' => 'function(data){
                    if(data===null||data===undefined){return "<span class=\"text-gray-300\">—</span>";}
                    var n=Math.round(data);
                    var cls=n>=70?"bg-red-100 text-red-700 ring-red-200":n>=40?"bg-amber-100 text-amber-800 ring-amber-200":"bg-green-100 text-green-700 ring-green-200";
                    return "<span class=\"inline-flex items-center justify-center w-9 h-9 rounded-full text-xs font-bold ring-1 "+cls+"\">"+n+"</span>";
                }',
            ],
            [
                'title' => 'Placed',
                'data' => 'placed_at',
                'name' => 'placed_at',
                'orderable_column' => 'orders.placed_at',
                'searchable' => false,
                'render' => 'Renderers.dateAgo',
            ],
            [
                'title' => '',
                'data' => 'actions',
                'name' => 'actions',
                'orderable' => false,
                'searchable' => false,
                'className' => 'text-right',
                'render' => 'Renderers.actions([
                    { type: "link", label: "View", url: ":show_url", class: "btn-primary btn-sm" }
                ])',
            ],
        ];
    }
}
