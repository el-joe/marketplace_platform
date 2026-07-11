<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Country;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\ShippingMethod;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\SubOrder;
use App\Enums\DisputeReason;
use App\Enums\RefundReason;
use App\Enums\RefundType;
use App\Services\OrderInterventionService;
use Illuminate\Validation\Rule;
use App\Traits\HasDataTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class OrderController extends Controller
{
    use HasDataTable;

    public function __construct(private readonly OrderInterventionService $interventionService)
    {
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Index / Listing
    // ─────────────────────────────────────────────────────────────────────────

    public function index(): View
    {
        $countries = Country::query()
            ->where('is_active', true)
            ->orderBy('name_en')
            ->pluck('name_en', 'id');

        return view('admin.orders.index', [
            'breadcrumbs' => [
                ['label' => __('admin.nav.dashboard'), 'url' => route('admin.dashboard')],
                ['label' => __('admin.orders.title')],
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

            $cityVal = $addr['city'] ?? $addr['city_en'] ?? '';
            $city = is_array($cityVal) ? ($cityVal['en'] ?? $cityVal['ar'] ?? '') : $cityVal;
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
            'subOrders.shippingMethod',
            'subOrders.codSettlement.agent',
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
                ['label' => __('admin.nav.dashboard'), 'url' => route('admin.dashboard')],
                ['label' => __('admin.orders.title'), 'url' => route('admin.orders.index')],
                ['label' => '#' . $order->order_number],
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Update order-level status
    // ─────────────────────────────────────────────────────────────────────────

    public function updateOrderStatus(Request $request, string $id): JsonResponse
    {
        $order = Order::findOrFail($id);

        $request->validate([
            'new_status' => 'required|string',
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $this->interventionService->updateOrderStatus(
                $order,
                $request->new_status,
                $request->reason ?? '',
                auth('admin')->id()
            );

            return response()->json([
                'success' => true,
                'message' => __('admin.orders.order_status_updated_to', ['status' => $this->statusLabel($request->new_status)]),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => collect($e->errors())->flatten()->first()], 422);
        } catch (\Throwable $e) {
            Log::error('Order status update failed', ['order' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => __('admin.orders.update_failed')], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Update sub-order status
    // ─────────────────────────────────────────────────────────────────────────

    public function updateSubOrderStatus(Request $request): JsonResponse
    {
        $subOrder = SubOrder::findOrFail($request->input('sub_order_id'));

        $validTransitions = SubOrder::STATUS_TRANSITIONS[$subOrder->status->value] ?? [];

        $request->validate([
            'sub_order_id' => 'required|string',
            'new_status' => ['required', 'in:' . implode(',', $validTransitions ?: ['__none__'])],
            'reason' => 'required|string|max:500',
        ]);

        try {
            $this->interventionService->updateSubOrderStatus(
                $subOrder,
                $request->new_status,
                $request->reason,
                auth('admin')->id()
            );

            return response()->json([
                'success' => true,
                'message' => __('admin.orders.sub_order_status_updated_to', ['status' => $this->statusLabel($request->new_status)]),
                'new_status' => $request->new_status,
            ]);
        } catch (\Throwable $e) {
            Log::error('SubOrder status update failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => __('admin.orders.update_failed')], 500);
        }
    }

    // Returns valid next statuses for a sub-order
    public function nextStatuses(string $id): JsonResponse
    {
        $subOrder = SubOrder::findOrFail($id);

        return response()->json([
            'data' => $this->interventionService->getNextStatuses($subOrder),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Force cancel
    // ─────────────────────────────────────────────────────────────────────────

    public function forceCancel(Request $request, string $id): JsonResponse
    {
        $order = Order::with('subOrders')->findOrFail($id);

        $request->validate([
            'reason' => 'required|string|max:500',
            'force' => 'boolean',
        ]);

        try {
            $this->interventionService->forceCancel(
                $order,
                $request->reason,
                $request->boolean('force', false),
                auth('admin')->id()
            );

            return response()->json(['success' => true, 'message' => __('admin.orders.force_cancelled_successfully')]);
        } catch (\RuntimeException $e) {
            if (str_starts_with($e->getMessage(), 'blocked:')) {
                return response()->json([
                    'message' => __('admin.orders.force_cancel_blocked'),
                    'blocked' => explode(',', substr($e->getMessage(), 8)),
                ], 422);
            }
            Log::error('Force cancel failed', ['order' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => __('admin.orders.cancel_failed')], 500);
        } catch (\Throwable $e) {
            Log::error('Force cancel failed', ['order' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => __('admin.orders.cancel_failed')], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Process refund
    // ─────────────────────────────────────────────────────────────────────────

    public function processRefund(Request $request, string $id): JsonResponse
    {
        $order = Order::with('transactions')->findOrFail($id);

        $request->validate([
            'refund_type' => ['required', Rule::enum(RefundType::class)],
            'amount' => 'required_if:refund_type,partial|numeric|min:0.01',
            'reason' => ['required', Rule::enum(RefundReason::class)],
            'reason_notes' => 'nullable|string|max:500',
            'sub_order_id' => 'nullable|string|exists:sub_orders,id',
            'vendor_charged_back' => 'boolean',
        ]);

        try {
            $this->interventionService->processRefund(
                $order,
                $request->refund_type,
                $request->filled('amount') ? (float) $request->amount : null,
                $request->reason,
                $request->reason_notes,
                $request->sub_order_id,
                $request->boolean('vendor_charged_back'),
                auth('admin')->id()
            );

            return response()->json(['success' => true, 'message' => __('admin.orders.refund_queued')]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => $e->errors()], 422);
        } catch (\Throwable $e) {
            Log::error('Refund creation failed', ['order' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => __('admin.orders.refund_creation_failed')], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Escalate dispute
    // ─────────────────────────────────────────────────────────────────────────

    public function escalateDispute(Request $request, string $id): JsonResponse
    {
        $order = Order::findOrFail($id);

        $request->validate([
            'sub_order_id' => 'required|string|exists:sub_orders,id',
            'reason' => ['required', Rule::enum(DisputeReason::class)],
            'description' => 'required|string|max:2000',
        ]);

        try {
            $dispute = $this->interventionService->escalateDispute(
                $order,
                $request->sub_order_id,
                $request->reason,
                $request->description,
                auth('admin')->id()
            );

            return response()->json([
                'success' => true,
                'message' => __('admin.orders.dispute_escalated', ['number' => $dispute->dispute_number]),
            ]);
        } catch (\Throwable $e) {
            Log::error('Dispute creation failed', ['order' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => __('admin.orders.dispute_creation_failed')], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Flag fraud
    // ─────────────────────────────────────────────────────────────────────────

    public function flagFraud(Request $request, string $id): JsonResponse
    {
        $order = Order::findOrFail($id);

        $request->validate(['reason' => 'required|string|max:500']);

        try {
            $this->interventionService->flagFraud($order, $request->reason, auth('admin')->id());

            return response()->json(['success' => true, 'message' => __('admin.orders.fraud_flagged_successfully')]);
        } catch (\Throwable $e) {
            Log::error('Fraud flag failed', ['order' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => __('admin.orders.flag_failed')], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Shipping method assignment
    // ─────────────────────────────────────────────────────────────────────────

    public function availableShippingMethods(Request $request, SubOrder $subOrder): JsonResponse
    {
        $snapshot = $subOrder->order->shipping_address_snapshot ?? [];
        $cityId = $snapshot['city_id'] ?? null;

        if (!$cityId) {
            $cityName = $snapshot['city'] ?? $snapshot['city_en'] ?? null;
            if ($cityName) {
                $city = City::where('name_en', $cityName)
                    ->first();
                $cityId = $city?->id;
            }
        }

        if (!$cityId) {
            return response()->json([
                'destination_zone' => null,
                'methods' => ShippingMethod::where('is_active', true)
                    ->orderBy('name')
                    ->get(['id', 'name', 'code', 'min_delivery_days', 'max_delivery_days'])
                    ->map(fn($method) => [
                        'id' => $method->id,
                        'name' => $method->name,
                        'code' => $method->code,
                        'min_delivery_days' => $method->min_delivery_days,
                        'max_delivery_days' => $method->max_delivery_days,
                        'rates' => [],
                    ]),
            ]);
        }

        $destinationZoneId = City::find($cityId)?->shipping_zone_id;
        abort_unless($destinationZoneId, 422, __('admin.orders.shipping_zone_not_assigned'));

        $methods = ShippingMethod::where('is_active', true)
            ->whereHas('shippingRates', fn($q) =>
                $q->where('destination_zone_id', $destinationZoneId)
                    ->where('is_active', true))
            ->with([
                'shippingRates' => fn($q) =>
                    $q->where('destination_zone_id', $destinationZoneId)
                        ->where('is_active', true)
                        ->with('carrier')
            ])
            ->get()
            ->map(fn($method) => [
                'id' => $method->id,
                'name' => $method->name,
                'code' => $method->code,
                'min_delivery_days' => $method->min_delivery_days,
                'max_delivery_days' => $method->max_delivery_days,
                'rates' => $method->shippingRates->map(fn($rate) => [
                    'carrier_id' => $rate->carrier_id,
                    'carrier_name' => $rate->carrier?->name,
                    'base_fee_cents' => $rate->base_fee,
                    'cod_extra_fee_cents' => $rate->cod_extra_fee,
                    'free_shipping_threshold_cents' => $rate->free_shipping_threshold,
                ]),
            ]);

        return response()->json([
            'destination_zone' => ShippingZone::find($destinationZoneId)?->name,
            'methods' => $methods,
        ]);
    }

    public function assignShippingMethod(Request $request, SubOrder $subOrder): JsonResponse
    {
        $request->validate([
            'shipping_method_id' => 'required|uuid|exists:shipping_methods,id',
            'carrier_id' => 'nullable|uuid|exists:shipping_carriers,id',
        ]);

        if (in_array($subOrder->status->value, ['shipped', 'out_for_delivery', 'delivered', 'completed'])) {
            return response()->json(['success' => false, 'message' => __('admin.orders.cannot_change_shipping_after_shipped')], 422);
        }

        $snapshot = $subOrder->order->shipping_address_snapshot ?? [];
        $cityId = $snapshot['city_id'] ?? null;

        if ($cityId) {
            $destinationZoneId = City::find($cityId)?->shipping_zone_id;

            $eligible = ShippingRate::where('destination_zone_id', $destinationZoneId)
                ->where('shipping_method_id', $request->shipping_method_id)
                ->where('is_active', true)
                ->exists();

            abort_unless($eligible, 422, __('admin.orders.shipping_method_not_available_for_zone'));
        }

        $fromStatus = $subOrder->status->value;

        $subOrder->update([
            'shipping_method_id' => $request->shipping_method_id,
            'carrier_id' => $request->carrier_id,
        ]);

        OrderStatusHistory::create([
            'order_id' => $subOrder->order_id,
            'sub_order_id' => $subOrder->id,
            'from_status' => $fromStatus,
            'to_status' => $fromStatus,
            'changed_by_admin_id' => auth('admin')->id(),
            'reason' => __('admin.orders.shipping_method_assigned_reason', ['method' => ShippingMethod::find($request->shipping_method_id)?->name]),
            'metadata' => json_encode([
                'action' => 'shipping_method_assigned',
                'shipping_method_id' => $request->shipping_method_id,
                'carrier_id' => $request->carrier_id,
                'assigned_by' => auth('admin')->id(),
                'assigned_at' => now(),
            ]),
        ]);

        return response()->json(['success' => true, 'message' => __('admin.orders.shipping_method_assigned')]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'placed' => __('common.order_status.placed'),
            'confirmed' => __('common.order_status.confirmed'),
            'processing' => __('admin.orders.status_processing'),
            'packed' => __('admin.orders.status_packed'),
            'partially_shipped' => __('admin.orders.status_partially_shipped'),
            'shipped' => __('common.order_status.shipped'),
            'out_for_delivery' => __('admin.orders.status_out_for_delivery'),
            'partially_delivered' => __('admin.orders.status_partially_delivered'),
            'delivered' => __('common.order_status.delivered'),
            'completed' => __('common.order_status.completed'),
            'cancelled' => __('common.order_status.cancelled'),
            'returned' => __('admin.orders.status_returned'),
            'refunded' => __('common.order_status.refunded'),
            'disputed' => __('common.order_status.disputed'),
            default => ucwords(str_replace('_', ' ', $status)),
        };
    }

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
