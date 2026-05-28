<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Country;
use App\Models\FlashSale;
use App\Models\FlashSaleSubmission;
use App\Models\Vendor;
use App\Services\FakeDiscountDetectionService;
use App\Services\FlashSaleService;
use App\Traits\HasDataTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class FlashSaleController extends Controller
{
    use HasDataTable;

    public function __construct(
        private readonly FlashSaleService $flashSaleService,
        private readonly FakeDiscountDetectionService $fakeDiscountService
    ) {
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Index / Listing
    // ─────────────────────────────────────────────────────────────────────────

    public function index(): View
    {
        return view('admin.flash-sales.index', [
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
                ['label' => 'Flash Sales'],
            ],
        ]);
    }

    public function datatable(Request $request): JsonResponse
    {
        $query = FlashSale::query()
            ->leftJoin('countries as c', 'c.id', '=', 'flash_sales.country_id')
            ->select([
                'flash_sales.id',
                'flash_sales.name_en',
                'flash_sales.name_ar',
                'flash_sales.status',
                'flash_sales.sale_starts_at',
                'flash_sales.sale_ends_at',
                'flash_sales.submission_opens_at',
                'flash_sales.is_featured',
                'flash_sales.is_exclusive',
                'flash_sales.approved_slots_count',
                'flash_sales.max_total_slots',
                'c.name_en as country_name',
            ]);

        $query = $this->applyFilters($query, $request, [
            'status' => fn($q, $v) => $q->where('flash_sales.status', $v),
            'country_id' => fn($q, $v) => $q->where('flash_sales.country_id', $v),
            'date_from' => fn($q, $v) => $q->whereDate('flash_sales.sale_starts_at', '>=', $v),
            'date_to' => fn($q, $v) => $q->whereDate('flash_sales.sale_ends_at', '<=', $v),
        ]);

        return $this->dataTableResponse($request, $query, $this->indexColumns(), function ($row) {
            return [
                'id' => $row->id,
                'name_en' => e($row->name_en),
                'name_ar' => e($row->name_ar),
                'status' => $row->status,
                'country_name' => e($row->country_name ?? '—'),
                'sale_starts_at' => $row->sale_starts_at,
                'sale_ends_at' => $row->sale_ends_at,
                'submission_opens_at' => $row->submission_opens_at,
                'is_featured' => (bool) $row->is_featured,
                'approved_slots_count' => $row->approved_slots_count,
                'max_total_slots' => $row->max_total_slots,
                'show_url' => route('admin.flash-sales.show', $row->id),
            ];
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Create / Store
    // ─────────────────────────────────────────────────────────────────────────

    public function create(): View
    {
        return view('admin.flash-sales.create', [
            'countries' => Country::orderBy('name_en')->get(),
            'categories' => Category::orderBy('name_en')->get(),
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
                ['label' => 'Flash Sales', 'url' => route('admin.flash-sales.index')],
                ['label' => 'New Flash Sale'],
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name_en' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
            'description_en' => 'nullable|string',
            'description_ar' => 'nullable|string',
            'country_id' => 'nullable|exists:countries,id',
            'banner_file_id' => 'nullable|exists:files,id',
            'submission_opens_at' => 'required|date',
            'submission_closes_at' => 'required|date|after:submission_opens_at',
            'review_deadline_at' => 'nullable|date|after:submission_closes_at',
            'sale_starts_at' => 'required|date|after:submission_closes_at',
            'sale_ends_at' => 'required|date|after:sale_starts_at',
            'min_discount_pct' => 'required|numeric|min:1|max:100',
            'max_products_per_vendor' => 'required|integer|min:1',
            'max_total_slots' => 'nullable|integer|min:1',
            'eligible_categories' => 'nullable|array',
            'eligible_categories.*' => 'exists:categories,id',
            'eligible_vendor_tiers' => 'nullable|array',
            'min_vendor_rating' => 'nullable|numeric|min:0|max:5',
            'commission_override_pct' => 'nullable|numeric|min:0|max:100',
            'is_featured' => 'boolean',
            'is_exclusive' => 'boolean',
            'price_drop_required' => 'boolean',
        ]);

        $sale = FlashSale::create([
            ...$validated,
            'status' => 'draft',
            'approved_slots_count' => 0,
            'created_by_admin_id' => auth('admin')->id(),
            'updated_by_admin_id' => auth('admin')->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Flash sale created.',
            'redirect' => route('admin.flash-sales.show', $sale->id),
        ], 201);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Show (detail)
    // ─────────────────────────────────────────────────────────────────────────

    public function show(FlashSale $flashSale): View
    {
        $flashSale->load(['country', 'bannerFile', 'createdByAdmin']);

        $submissionStats = FlashSaleSubmission::query()
            ->where('flash_sale_id', $flashSale->id)
            ->selectRaw('status, count(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status');

        $invitationCount = $flashSale->vendorInvitations()->count();

        return view('admin.flash-sales.show', [
            'sale' => $flashSale,
            'submissionStats' => $submissionStats,
            'invitationCount' => $invitationCount,
            'nextStatuses' => $flashSale->getNextStatuses(),
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
                ['label' => 'Flash Sales', 'url' => route('admin.flash-sales.index')],
                ['label' => $flashSale->name_en],
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Update
    // ─────────────────────────────────────────────────────────────────────────

    public function update(Request $request, FlashSale $flashSale): JsonResponse
    {
        if (!in_array($flashSale->status, ['draft', 'open', 'review'], true)) {
            return response()->json(['message' => 'Only draft, open, or review flash sales can be edited.'], 422);
        }

        $validated = $request->validate([
            'name_en' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
            'description_en' => 'nullable|string',
            'description_ar' => 'nullable|string',
            'country_id' => 'nullable|exists:countries,id',
            'banner_file_id' => 'nullable|exists:files,id',
            'submission_opens_at' => 'required|date',
            'submission_closes_at' => 'required|date|after:submission_opens_at',
            'review_deadline_at' => 'nullable|date|after:submission_closes_at',
            'sale_starts_at' => 'required|date|after:submission_closes_at',
            'sale_ends_at' => 'required|date|after:sale_starts_at',
            'min_discount_pct' => 'required|numeric|min:1|max:100',
            'max_products_per_vendor' => 'required|integer|min:1',
            'max_total_slots' => 'nullable|integer|min:1',
            'eligible_categories' => 'nullable|array',
            'eligible_categories.*' => 'exists:categories,id',
            'eligible_vendor_tiers' => 'nullable|array',
            'min_vendor_rating' => 'nullable|numeric|min:0|max:5',
            'commission_override_pct' => 'nullable|numeric|min:0|max:100',
            'is_featured' => 'boolean',
            'is_exclusive' => 'boolean',
            'price_drop_required' => 'boolean',
        ]);

        $flashSale->update([
            ...$validated,
            'updated_by_admin_id' => auth('admin')->id(),
        ]);

        return response()->json(['success' => true, 'message' => 'Flash sale updated.']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Status transitions
    // ─────────────────────────────────────────────────────────────────────────

    public function transition(Request $request, FlashSale $flashSale): JsonResponse
    {
        $request->validate(['action' => 'required|in:open,close-submissions,schedule,launch,end,cancel']);

        $admin = auth('admin')->user();

        try {
            match ($request->action) {
                'open' => $this->flashSaleService->openSubmissions($flashSale, $admin),
                'close-submissions' => $this->flashSaleService->closeSubmissions($flashSale, $admin),
                'schedule' => $this->flashSaleService->scheduleSale($flashSale, $admin),
                'launch' => $this->flashSaleService->launchSale($flashSale, $admin),
                'end' => $this->flashSaleService->endSale($flashSale, $admin),
                'cancel' => $this->flashSaleService->cancelSale($flashSale, $admin, $request->reason ?? ''),
            };

            return response()->json([
                'success' => true,
                'message' => 'Flash sale status updated.',
                'new_status' => $flashSale->fresh()->status,
            ]);
        } catch (\LogicException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            Log::error('Flash sale transition failed', ['id' => $flashSale->id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Transition failed.'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Vendor invitations
    // ─────────────────────────────────────────────────────────────────────────

    public function inviteVendors(Request $request, FlashSale $flashSale): JsonResponse
    {
        $request->validate([
            'mode' => 'required|in:auto,manual',
            'vendor_ids' => 'required_if:mode,manual|array',
            'vendor_ids.*' => 'exists:vendors,id',
        ]);

        $admin = auth('admin')->user();
        $count = 0;

        if ($request->mode === 'auto') {
            $count = $this->flashSaleService->inviteEligibleVendors($flashSale);
        } else {
            foreach ($request->vendor_ids as $vendorId) {
                $vendor = Vendor::find($vendorId);
                if ($vendor) {
                    $this->flashSaleService->inviteVendor($flashSale, $vendor);
                    $count++;
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => "{$count} vendor(s) invited.",
            'count' => $count,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Submissions
    // ─────────────────────────────────────────────────────────────────────────

    public function submissionsDatatable(Request $request, FlashSale $flashSale): JsonResponse
    {
        $query = FlashSaleSubmission::query()
            ->where('flash_sale_id', $flashSale->id)
            ->leftJoin('vendors as v', 'v.id', '=', 'flash_sale_submissions.vendor_id')
            ->leftJoin('vendor_listings as vl', 'vl.id', '=', 'flash_sale_submissions.vendor_listing_id')
            ->select([
                'flash_sale_submissions.id',
                'flash_sale_submissions.status',
                'flash_sale_submissions.flash_price',
                'flash_sale_submissions.original_price',
                'flash_sale_submissions.calculated_discount_pct',
                'flash_sale_submissions.quantity_sold',
                'flash_sale_submissions.max_quantity_total',
                'flash_sale_submissions.submitted_at',
                'v.store_name as vendor_name',
                'v.id as vendor_id',
                'vl.name_en as listing_name',
            ]);

        $query = $this->applyFilters($query, $request, [
            'status' => fn($q, $v) => $q->where('flash_sale_submissions.status', $v),
            'vendor_id' => fn($q, $v) => $q->where('flash_sale_submissions.vendor_id', $v),
        ]);

        return $this->dataTableResponse($request, $query, $this->submissionColumns(), function ($row) {
            return [
                'id' => $row->id,
                'vendor_name' => e($row->vendor_name ?? '—'),
                'listing_name' => e($row->listing_name ?? '—'),
                'flash_price' => $row->flash_price,
                'original_price' => $row->original_price,
                'flash_price_formatted' => number_format($row->flash_price / 100, 2),
                'original_price_formatted' => number_format($row->original_price / 100, 2),
                'discount_pct' => number_format($row->calculated_discount_pct ?? 0, 1) . '%',
                'quantity_sold' => $row->quantity_sold,
                'max_quantity_total' => $row->max_quantity_total,
                'status' => $row->status,
                'submitted_at' => $row->submitted_at,
                'approve_url' => route('admin.flash-sales.submissions.approve', $row->id),
                'reject_url' => route('admin.flash-sales.submissions.reject', $row->id),
            ];
        });
    }

    public function approveSubmission(Request $request, FlashSaleSubmission $submission): JsonResponse
    {
        $request->validate(['notes' => 'nullable|string|max:500']);

        $fraudCheck = $this->fakeDiscountService->check($submission);

        if ($fraudCheck['risk_level'] === 'high' && !$request->boolean('override_fraud_check')) {
            return response()->json([
                'message' => 'High fraud risk detected. Review the pricing history before approving.',
                'fraud_check' => $fraudCheck,
                'requires_override' => true,
            ], 422);
        }

        try {
            $this->flashSaleService->approveSubmission($submission, auth('admin')->user(), $request->notes);

            return response()->json([
                'success' => true,
                'message' => 'Submission approved.',
                'fraud_check' => $fraudCheck,
            ]);
        } catch (\LogicException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function rejectSubmission(Request $request, FlashSaleSubmission $submission): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
            'rejection_code' => 'nullable|string|max:50',
        ]);

        try {
            $this->flashSaleService->rejectSubmission(
                $submission,
                auth('admin')->user(),
                $request->reason,
                $request->rejection_code ?? 'manual_rejection'
            );

            return response()->json(['success' => true, 'message' => 'Submission rejected.']);
        } catch (\LogicException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function checkFraud(FlashSaleSubmission $submission): JsonResponse
    {
        $result = $this->fakeDiscountService->check($submission);
        return response()->json($result);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function indexColumns(): array
    {
        return [
            [
                'title' => 'Name',
                'data' => 'name_en',
                'name' => 'name_en',
                'orderable_column' => 'flash_sales.name_en',
                'searchable_columns' => ['flash_sales.name_en', 'flash_sales.name_ar'],
                'render' => 'function(data,t,row){return "<a href=\""+row.show_url+"\" class=\"font-medium text-primary-600 hover:underline\">"+data+"</a>";}',
            ],
            [
                'title' => 'Country',
                'data' => 'country_name',
                'name' => 'country_name',
                'orderable_column' => 'c.name_en',
                'searchable' => false,
            ],
            [
                'title' => 'Starts',
                'data' => 'sale_starts_at',
                'name' => 'sale_starts_at',
                'orderable_column' => 'flash_sales.sale_starts_at',
                'searchable' => false,
                'render' => 'function(data){return data ? Renderers.date(data) : "—";}',
            ],
            [
                'title' => 'Ends',
                'data' => 'sale_ends_at',
                'name' => 'sale_ends_at',
                'orderable_column' => 'flash_sales.sale_ends_at',
                'searchable' => false,
                'render' => 'function(data){return data ? Renderers.date(data) : "—";}',
            ],
            [
                'title' => 'Slots',
                'data' => 'slots',
                'name' => 'slots',
                'searchable' => false,
                'orderable' => false,
                'render' => 'function(d,t,row){return row.approved_slots_count+"/"+(row.max_total_slots||"∞");}',
            ],
            [
                'title' => 'Status',
                'data' => 'status',
                'name' => 'status',
                'orderable_column' => 'flash_sales.status',
                'searchable' => false,
                'render' => 'Renderers.badge({
                    draft:     { label: "Draft",            color: "gray"    },
                    open:      { label: "Submissions Open", color: "primary" },
                    review:    { label: "Under Review",     color: "warning" },
                    scheduled: { label: "Scheduled",        color: "primary" },
                    live:      { label: "Live",             color: "success" },
                    ended:     { label: "Ended",            color: "gray"    },
                    cancelled: { label: "Cancelled",        color: "danger"  }
                })',
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

    private function submissionColumns(): array
    {
        return [
            [
                'title' => 'Vendor',
                'data' => 'vendor_name',
                'name' => 'vendor_name',
                'orderable_column' => 'v.store_name',
                'searchable_columns' => ['v.store_name'],
            ],
            [
                'title' => 'Listing',
                'data' => 'listing_name',
                'name' => 'listing_name',
                'orderable_column' => 'vl.name_en',
                'searchable' => false,
            ],
            [
                'title' => 'Flash Price',
                'data' => 'flash_price_formatted',
                'name' => 'flash_price_formatted',
                'orderable_column' => 'flash_sale_submissions.flash_price',
                'searchable' => false,
                'className' => 'text-right font-semibold',
            ],
            [
                'title' => 'Original',
                'data' => 'original_price_formatted',
                'name' => 'original_price_formatted',
                'orderable_column' => 'flash_sale_submissions.original_price',
                'searchable' => false,
                'className' => 'text-right',
            ],
            [
                'title' => 'Discount',
                'data' => 'discount_pct',
                'name' => 'discount_pct',
                'orderable_column' => 'flash_sale_submissions.calculated_discount_pct',
                'searchable' => false,
                'className' => 'text-right',
            ],
            [
                'title' => 'Qty Sold / Max',
                'data' => 'qty',
                'name' => 'qty',
                'searchable' => false,
                'orderable' => false,
                'render' => 'function(d,t,row){return row.quantity_sold+"/"+(row.max_quantity_total||"∞");}',
            ],
            [
                'title' => 'Status',
                'data' => 'status',
                'name' => 'status',
                'orderable_column' => 'flash_sale_submissions.status',
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
                'orderable_column' => 'flash_sale_submissions.submitted_at',
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
                'render' => 'function(d,t,row){
                    var html = "";
                    if(["submitted","under_review","approved"].includes(row.status)){
                        html += "<button class=\"btn btn-success btn-xs mr-1\" onclick=\"approveSubmission(\""+row.id+"\",\""+row.approve_url+"\")\">Approve</button>";
                        html += "<button class=\"btn btn-danger btn-xs\" onclick=\"openRejectModal(\""+row.id+"\",\""+row.reject_url+"\")\">Reject</button>";
                    }
                    return html;
                }',
            ],
        ];
    }
}
