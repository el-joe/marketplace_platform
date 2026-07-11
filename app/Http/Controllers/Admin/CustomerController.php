<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CustomerStatus;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Country;
use App\Models\Customer;
use App\Models\Notification;
use App\Traits\HasDataTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    use HasDataTable;

    // ─── Index ────────────────────────────────────────────────────────────────

    public function index(): \Illuminate\View\View
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('customers.view'), 403);

        $stats = [
            'total' => Customer::count(),
            'active' => Customer::where('status', CustomerStatus::Active)->count(),
            'suspended' => Customer::where('status', CustomerStatus::Suspended)->count(),
            'banned' => Customer::where('status', CustomerStatus::Banned)->count(),
            'new_this_week' => Customer::where('created_at', '>=', now()->startOfWeek())->count(),
        ];

        $countries = Country::orderBy('name_en')->get(['id', 'name_en']);

        return view('admin.customers.index', compact('stats', 'countries'));
    }

    // ─── DataTable ────────────────────────────────────────────────────────────

    public function datatable(Request $request): JsonResponse
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('customers.view'), 403);

        $query = Customer::query()->with('country');

        $query = $this->applyFilters($query, $request, [
            'status' => fn($q, $v) => $q->where('status', $v),
            'country_id' => fn($q, $v) => $q->where('country_id', $v),
            'date_from' => fn($q, $v) => $q->whereDate('created_at', '>=', $v),
            'date_to' => fn($q, $v) => $q->whereDate('created_at', '<=', $v),
            'min_orders' => fn($q, $v) => $q->where('total_orders', '>=', (int) $v),
            'verified_only' => fn($q, $v) => $v ? $q->whereNotNull('email_verified_at') : $q,
        ]);

        $columns = [
            ['searchable_columns' => ['customers.name'], 'orderable_column' => 'name'],
            ['searchable_columns' => ['customers.email'], 'orderable_column' => 'email'],
            ['searchable_columns' => ['customers.phone'], 'orderable_column' => 'phone'],
            ['searchable_columns' => [], 'orderable_column' => null],  // country
            ['searchable_columns' => [], 'orderable_column' => 'status'],
            ['searchable_columns' => [], 'orderable_column' => 'total_orders'],
            ['searchable_columns' => [], 'orderable_column' => 'total_spent'],
            ['searchable_columns' => [], 'orderable_column' => 'loyalty_points'],
            ['searchable_columns' => [], 'orderable_column' => 'created_at'],
            ['searchable_columns' => [], 'orderable_column' => null],  // actions
        ];

        $statusColors = [
            'active' => 'success',
            'suspended' => 'warning',
            'banned' => 'danger',
        ];

        return $this->dataTableResponse($request, $query, $columns, function (Customer $row) use ($statusColors) {
            $color = $statusColors[$row->status->value] ?? 'gray';
            $canEdit = auth('admin')->user()->hasPermissionTo('customers.edit');
            $canSuspend = auth('admin')->user()->hasPermissionTo('customers.suspend');

            return [
                'name' => e($row->name),
                'email' => e($row->email),
                'phone' => e($row->phone ?? '—'),
                'country' => $row->country
                    ? ($row->country->flag_emoji ? $row->country->flag_emoji . ' ' : '') . e($row->country->name_en)
                    : '—',
                'status' => '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-' . $color . '-100 text-' . $color . '-700">'
                    . e($row->status->label()) . '</span>',
                'total_orders' => $row->total_orders,
                'total_spent' => number_format((float) $row->total_spent, 2),
                'loyalty_points' => number_format((float) $row->loyalty_points, 2),
                'created_at' => $row->created_at->format('d M Y'),
                'actions' => $this->buildRowActions($row, $canEdit, $canSuspend),
                'DT_RowData' => [
                    'id' => $row->id,
                    'status' => $row->status->value,
                ],
            ];
        });
    }

    private function buildRowActions(Customer $customer, bool $canEdit, bool $canSuspend): string
    {
        $viewUrl = route('admin.customers.show', $customer->id);
        $suspendUrl = route('admin.customers.suspend', $customer->id);
        $banUrl = route('admin.customers.ban', $customer->id);
        $reactivateUrl = route('admin.customers.reactivate', $customer->id);

        $actions = '<div class="flex items-center gap-1">';
        $actions .= '<a href="' . $viewUrl . '" class="btn btn-xs btn-secondary">View</a>';

        if ($canSuspend) {
            if ($customer->status === CustomerStatus::Active) {
                $actions .= '<button type="button" class="btn btn-xs btn-warning js-suspend-btn"'
                    . ' data-url="' . $suspendUrl . '" data-name="' . e($customer->name) . '">Suspend</button>';
                $actions .= '<button type="button" class="btn btn-xs btn-danger js-ban-btn"'
                    . ' data-url="' . $banUrl . '" data-name="' . e($customer->name) . '">Ban</button>';
            } elseif ($customer->status === CustomerStatus::Suspended) {
                $actions .= '<button type="button" class="btn btn-xs btn-success js-reactivate-btn"'
                    . ' data-url="' . $reactivateUrl . '" data-name="' . e($customer->name) . '">Reactivate</button>';
                $actions .= '<button type="button" class="btn btn-xs btn-danger js-ban-btn"'
                    . ' data-url="' . $banUrl . '" data-name="' . e($customer->name) . '">Ban</button>';
            } elseif ($customer->status === CustomerStatus::Banned) {
                $actions .= '<button type="button" class="btn btn-xs btn-success js-reactivate-btn"'
                    . ' data-url="' . $reactivateUrl . '" data-name="' . e($customer->name) . '">Reactivate</button>';
            }
        }

        $actions .= '</div>';
        return $actions;
    }

    // ─── Show ─────────────────────────────────────────────────────────────────

    public function show(Customer $customer): \Illuminate\View\View
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('customers.view'), 403);

        $customer->load([
            'country',
            'referredBy',
            'addresses.country',
        ]);

        $orders = $customer->orders()->latest('placed_at')->take(20)->get();
        $reviews = $customer->reviews()->with('product')->latest()->take(20)->get();
        $paymentMethods = $customer->paymentMethods()->latest()->get();
        $returnRequests = $customer->returnRequests()->latest()->take(20)->get();
        $disputes = $customer->disputes()->latest()->take(20)->get();
        $tickets = $customer->supportTickets()
            ->latest()
            ->take(20)
            ->get();

        $activityLog = Activity::where('causer_type', Customer::class)
            ->where('causer_id', $customer->id)
            ->latest()
            ->take(50)
            ->get();

        return view('admin.customers.show', compact(
            'customer',
            'orders',
            'reviews',
            'paymentMethods',
            'returnRequests',
            'disputes',
            'tickets',
            'activityLog'
        ));
    }

    // ─── Update ───────────────────────────────────────────────────────────────

    public function update(Request $request, Customer $customer): JsonResponse
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('customers.edit'), 403);

        $validated = $request->validate([
            'status' => ['sometimes', Rule::enum(CustomerStatus::class)],
            'loyalty_points' => 'sometimes|numeric|min:0',
        ]);

        $customer->update($validated);

        return response()->json(['message' => 'Customer updated.']);
    }

    // ─── Suspend ──────────────────────────────────────────────────────────────

    public function suspend(Request $request, Customer $customer): JsonResponse
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('customers.suspend'), 403);

        $request->validate(['reason' => 'required|string|max:1000']);

        $customer->update(['status' => CustomerStatus::Suspended]);

        Notification::create([
            'notifiable_type' => Customer::class,
            'notifiable_id' => $customer->id,
            'type' => 'account_suspended',
            'channel' => 'database',
            'data' => json_encode([
                'title' => 'Account Suspended',
                'message' => 'Your account has been suspended. Reason: ' . $request->input('reason'),
            ]),
            'sent_at' => now(),
        ]);

        Activity::create([
            'log_name' => 'customers',
            'description' => 'Customer suspended',
            'subject_type' => Customer::class,
            'subject_id' => $customer->id,
            'causer_type' => get_class($admin),
            'causer_id' => $admin->id,
            'event' => 'suspended',
            'properties' => json_encode(['reason' => $request->input('reason')]),
            'ip_address' => $request->ip(),
        ]);

        return response()->json(['message' => 'Customer suspended.']);
    }

    // ─── Ban ──────────────────────────────────────────────────────────────────

    public function ban(Request $request, Customer $customer): JsonResponse
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('customers.suspend'), 403);

        $request->validate(['reason' => 'required|string|max:1000']);

        $customer->update(['status' => CustomerStatus::Banned]);

        // Revoke Sanctum tokens if the model has the HasApiTokens trait
        if (method_exists($customer, 'tokens')) {
            $customer->tokens()->delete();
        }

        Activity::create([
            'log_name' => 'customers',
            'description' => 'Customer banned',
            'subject_type' => Customer::class,
            'subject_id' => $customer->id,
            'causer_type' => get_class($admin),
            'causer_id' => $admin->id,
            'event' => 'banned',
            'properties' => json_encode(['reason' => $request->input('reason')]),
            'ip_address' => $request->ip(),
        ]);

        return response()->json(['message' => 'Customer banned.']);
    }

    // ─── Reactivate ───────────────────────────────────────────────────────────

    public function reactivate(Customer $customer): JsonResponse
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('customers.suspend'), 403);

        $customer->update(['status' => CustomerStatus::Active]);

        Activity::create([
            'log_name' => 'customers',
            'description' => 'Customer reactivated',
            'subject_type' => Customer::class,
            'subject_id' => $customer->id,
            'causer_type' => get_class($admin),
            'causer_id' => $admin->id,
            'event' => 'reactivated',
            'properties' => json_encode([]),
            'ip_address' => request()->ip(),
        ]);

        return response()->json(['message' => 'Customer reactivated.']);
    }

    // ─── Adjust Loyalty Points ────────────────────────────────────────────────

    public function adjustLoyaltyPoints(Request $request, Customer $customer): JsonResponse
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('customers.edit'), 403);

        $request->validate([
            'adjustment' => 'required|integer|not_in:0',
            'reason' => 'required|string|max:500',
        ]);

        $adjustment = (int) $request->input('adjustment');

        if ($adjustment > 0) {
            $customer->increment('loyalty_points', $adjustment);
        } else {
            $customer->decrement('loyalty_points', abs($adjustment));
        }

        $customer->refresh();

        Activity::create([
            'log_name' => 'customers',
            'description' => 'Loyalty points adjusted',
            'subject_type' => Customer::class,
            'subject_id' => $customer->id,
            'causer_type' => get_class($admin),
            'causer_id' => $admin->id,
            'event' => 'loyalty_adjusted',
            'properties' => json_encode([
                'adjustment' => $adjustment,
                'reason' => $request->input('reason'),
                'new_balance' => (float) $customer->loyalty_points,
            ]),
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Loyalty points adjusted.',
            'new_balance' => number_format((float) $customer->loyalty_points, 2),
        ]);
    }

    // ─── Orders DataTable ─────────────────────────────────────────────────────

    public function orders(Request $request, Customer $customer): JsonResponse
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('customers.view'), 403);

        $query = $customer->orders()->getQuery();

        $columns = [
            ['searchable_columns' => ['order_number'], 'orderable_column' => 'order_number'],
            ['searchable_columns' => [], 'orderable_column' => 'total'],
            ['searchable_columns' => [], 'orderable_column' => 'status'],
            ['searchable_columns' => [], 'orderable_column' => null],  // items
            ['searchable_columns' => [], 'orderable_column' => 'placed_at'],
        ];

        $statusColors = [
            'pending' => 'gray',
            'confirmed' => 'primary',
            'processing' => 'primary',
            'shipped' => 'primary',
            'out_for_delivery' => 'warning',
            'delivered' => 'success',
            'completed' => 'success',
            'cancelled' => 'danger',
            'return_requested' => 'warning',
            'returned' => 'danger',
            'refunded' => 'danger',
        ];

        return $this->dataTableResponse($request, $query, $columns, function ($row) use ($statusColors) {
            $color = $statusColors[$row->status] ?? 'gray';
            return [
                'order_number' => e($row->order_number),
                'total' => number_format((float) $row->total, 2) . ' ' . strtoupper($row->currency ?? ''),
                'status' => '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-' . $color . '-100 text-' . $color . '-700">'
                    . ucwords(str_replace('_', ' ', $row->status)) . '</span>',
                'items' => '—',
                'placed_at' => $row->placed_at ? \Carbon\Carbon::parse($row->placed_at)->format('d M Y') : '—',
            ];
        });
    }

    // ─── Export Data (GDPR) ───────────────────────────────────────────────────

    public function exportData(Customer $customer): Response
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('customers.view'), 403);

        $orders = $customer->orders()->latest('placed_at')->take(100)->get();
        $reviews = $customer->reviews()->with('product')->latest()->take(100)->get();

        $export = [
            'exported_at' => now()->toIso8601String(),
            'profile' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'email_verified_at' => $customer->email_verified_at?->toIso8601String(),
                'phone' => $customer->phone,
                'phone_verified_at' => $customer->phone_verified_at?->toIso8601String(),
                'country_id' => $customer->country_id,
                'status' => $customer->status->value,
                'date_of_birth' => $customer->date_of_birth,
                'last_login_at' => $customer->last_login_at?->toIso8601String(),
                'last_login_ip' => $customer->last_login_ip,
                'total_orders' => $customer->total_orders,
                'total_spent' => (float) $customer->total_spent,
                'loyalty_points' => (float) $customer->loyalty_points,
                'referral_code' => $customer->referral_code,
                'created_at' => $customer->created_at->toIso8601String(),
            ],
            'orders' => $orders->map(fn($o) => [
                'order_number' => $o->order_number,
                'status' => $o->status->value,
                'total' => (float) $o->total,
                'currency' => $o->currency,
                'payment_method' => $o->payment_method,
                'payment_status' => $o->payment_status->value,
                'placed_at' => $o->placed_at ? \Carbon\Carbon::parse($o->placed_at)->toIso8601String() : null,
            ])->values(),
            'reviews' => $reviews->map(fn($r) => [
                'product' => $r->product?->name ?? $r->product_id,
                'rating' => $r->rating,
                'title' => $r->title,
                'status' => $r->status->value,
                'created_at' => $r->created_at->toIso8601String(),
            ])->values(),
        ];

        $filename = 'customer_export_' . $customer->id . '_' . now()->format('Ymd_His') . '.json';

        return response(json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    // ─── Send Notification ────────────────────────────────────────────────────

    public function sendNotification(Request $request, Customer $customer): JsonResponse
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('customers.edit'), 403);

        $request->validate([
            'channel' => 'required|in:database,email,sms',
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
        ]);

        Notification::create([
            'notifiable_type' => Customer::class,
            'notifiable_id' => $customer->id,
            'type' => 'admin_message',
            'channel' => $request->input('channel'),
            'data' => json_encode([
                'title' => $request->input('title'),
                'message' => $request->input('message'),
            ]),
            'sent_at' => now(),
        ]);

        return response()->json(['message' => 'Notification sent.']);
    }
}
