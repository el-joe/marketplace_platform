<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InfluencerDeal;
use App\Models\InfluencerDealDeliverable;
use App\Models\Marketer;
use App\Models\MarketerPayout;
use App\Models\Vendor;
use App\Notifications\Marketer\DealProposed;
use App\Traits\HasDataTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class InfluencerDealController extends Controller
{
    use HasDataTable;

    private const ELIGIBLE_MARKETER_TYPES = ['influencer', 'celebrity', 'brand_ambassador'];

    // ════════════════════════════════════════════════════════════════════════
    //  INFLUENCER DEALS
    // ════════════════════════════════════════════════════════════════════════

    public function index(Request $request): View
    {
        $stats = [
            'total' => InfluencerDeal::count(),
            'proposed' => InfluencerDeal::where('status', 'proposed')->count(),
            'pending_approval' => InfluencerDeal::where('status', 'content_submitted')->count(),
            'paid' => InfluencerDeal::where('status', 'paid')->count(),
        ];

        $marketers = Marketer::whereIn('type', self::ELIGIBLE_MARKETER_TYPES)
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        $vendors = Vendor::orderBy('store_name')->get(['id', 'store_name']);

        return view('admin.influencer_deals.index', [
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
                ['label' => 'Influencer Deals'],
            ],
            'stats' => $stats,
            'marketers' => $marketers,
            'vendors' => $vendors,
        ]);
    }

    public function datatable(Request $request): JsonResponse
    {
        $columns = [
            ['searchable_columns' => ['influencer_deals.deal_name']],
            ['searchable_columns' => ['marketers.name']],
            ['orderable_column' => 'vendors.store_name'],
            ['orderable_column' => 'influencer_deals.deal_type'],
            ['orderable_column' => 'influencer_deals.flat_fee_amount'],
            ['orderable_column' => 'influencer_deals.status'],
            [],
            [],
        ];

        $query = InfluencerDeal::query()
            ->join('marketers', 'marketers.id', '=', 'influencer_deals.marketer_id')
            ->leftJoin('vendors', 'vendors.id', '=', 'influencer_deals.vendor_id')
            ->withCount(['deliverables', 'deliverables as approved_deliverables_count' => function ($q) {
                $q->where('status', 'approved');
            }])
            ->select([
                'influencer_deals.id',
                'influencer_deals.deal_name',
                'influencer_deals.deal_type',
                'influencer_deals.flat_fee_amount',
                'influencer_deals.currency',
                'influencer_deals.status',
                'marketers.name as marketer_name',
                'marketers.type as marketer_type',
                'vendors.store_name as vendor_name',
            ]);

        $query = $this->applyFilters($query, $request, [
            'filter_status' => fn($q, $v) => $q->where('influencer_deals.status', $v),
            'filter_marketer' => fn($q, $v) => $q->where('influencer_deals.marketer_id', $v),
            'filter_vendor' => fn($q, $v) => $q->where('influencer_deals.vendor_id', $v),
        ]);

        return $this->dataTableResponse($request, $query, $columns, function ($row) {
            $done = $row->approved_deliverables_count;
            $total = $row->deliverables_count;

            return [
                '<a href="' . route('admin.influencer-deals.show', $row->id) . '" class="font-medium text-primary-600">' . e($row->deal_name) . '</a>',
                '<div><p class="font-medium">' . e($row->marketer_name) . '</p><span class="badge badge-' . $this->typeColor($row->marketer_type?->value ?? (string) $row->marketer_type) . '">' . ucfirst(str_replace('_', ' ', $row->marketer_type?->value ?? (string) $row->marketer_type)) . '</span></div>',
                e($row->vendor_name ?? '—'),
                ucfirst(str_replace('_', ' ', $row->deal_type?->value ?? (string) $row->deal_type)),
                number_format($row->flat_fee_amount) . ' ' . $row->currency,
                '<span class="badge badge-' . $this->statusColor($row->status?->value ?? (string) $row->status) . '">' . ucfirst(str_replace('_', ' ', $row->status?->value ?? (string) $row->status)) . '</span>',
                $total > 0 ? "{$done}/{$total} done" : '—',
                $this->actions($row),
            ];
        });
    }

    public function show(string $id): View
    {
        $deal = InfluencerDeal::with(['marketer', 'vendor', 'campaign', 'approvedByAdmin', 'deliverables'])
            ->findOrFail($id);

        return view('admin.influencer_deals.show', [
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
                ['label' => 'Influencer Deals', 'url' => route('admin.influencer-deals.index')],
                ['label' => $deal->deal_name],
            ],
            'deal' => $deal,
        ]);
    }

    public function propose(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'marketer_id' => ['required', 'exists:marketers,id'],
            'vendor_id' => ['nullable', 'exists:vendors,id'],
            'deal_name' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'deal_type' => ['required', 'in:flat_fee,hybrid,gifting'],
            'flat_fee_amount' => ['required', 'integer', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'hybrid_commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'negotiation_notes' => ['nullable', 'string'],
            'content_due_at' => ['nullable', 'date'],
            'payment_due_at' => ['nullable', 'date'],
        ]);

        $validator->after(function ($validator) use ($request) {
            $marketer = Marketer::find($request->input('marketer_id'));
            if ($marketer && !in_array($marketer->type?->value ?? (string) $marketer->type, self::ELIGIBLE_MARKETER_TYPES, true)) {
                $validator->errors()->add('marketer_id', 'Selected marketer is not eligible for influencer deals.');
            }
        });

        $validator->validate();

        $deal = DB::transaction(function () use ($request) {
            $deal = InfluencerDeal::create([
                'marketer_id' => $request->input('marketer_id'),
                'vendor_id' => $request->input('vendor_id'),
                'deal_name' => $request->input('deal_name'),
                'description' => $request->input('description'),
                'deal_type' => $request->input('deal_type'),
                'flat_fee_amount' => $request->input('flat_fee_amount'),
                'currency' => strtoupper($request->input('currency')),
                'hybrid_commission_rate' => $request->input('hybrid_commission_rate'),
                'status' => 'proposed',
                'proposed_by' => 'admin',
                'negotiation_notes' => $request->input('negotiation_notes'),
                'content_due_at' => $request->input('content_due_at'),
                'payment_due_at' => $request->input('payment_due_at'),
            ]);

            $deal->marketer->notify(new DealProposed($deal));

            return $deal;
        });

        return response()->json(['success' => true, 'message' => 'Deal proposed to marketer.', 'deal_id' => $deal->id]);
    }

    public function approve(string $id): JsonResponse
    {
        $deal = InfluencerDeal::with('deliverables')->findOrFail($id);

        if ($deal->status !== 'content_submitted') {
            return response()->json(['success' => false, 'message' => 'Deal is not awaiting approval.'], 422);
        }

        if ($deal->deliverables->isEmpty() || $deal->deliverables->contains(fn($d) => $d->status !== 'approved')) {
            return response()->json(['success' => false, 'message' => 'All deliverables must be approved before approving the deal.'], 422);
        }

        DB::transaction(function () use ($deal) {
            $deal->update([
                'status' => 'approved',
                'approved_by_admin_id' => auth()->guard('admin')->id(),
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Deal approved.']);
    }

    public function initiatePayment(string $id): JsonResponse
    {
        $deal = InfluencerDeal::findOrFail($id);

        if ($deal->status !== 'approved') {
            return response()->json(['success' => false, 'message' => 'Deal must be approved before payment can be initiated.'], 422);
        }

        DB::transaction(function () use ($deal) {
            MarketerPayout::create([
                'marketer_id' => $deal->marketer_id,
                'influencer_deal_id' => $deal->id,
                'payout_type' => 'flat_fee',
                'period_start' => now(),
                'period_end' => now(),
                'total_conversions' => 0,
                'gross_commission' => $deal->flat_fee_amount,
                'tax_deduction' => 0,
                'net_amount' => $deal->flat_fee_amount,
                'currency' => $deal->currency,
                'status' => 'approved',
                'approved_by_admin_id' => auth()->guard('admin')->id(),
            ]);

            $deal->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Payment initiated for this deal.']);
    }

    public function cancel(string $id, Request $request): JsonResponse
    {
        $request->validate(['reason' => 'required|string|max:500']);

        $deal = InfluencerDeal::findOrFail($id);

        if (in_array($deal->status, ['paid', 'cancelled', 'rejected'], true)) {
            return response()->json(['success' => false, 'message' => 'Deal cannot be cancelled from its current status.'], 422);
        }

        DB::transaction(function () use ($deal, $request) {
            $deal->update([
                'status' => 'cancelled',
                'cancellation_reason' => $request->input('reason'),
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Deal cancelled.']);
    }

    // ════════════════════════════════════════════════════════════════════════
    //  DELIVERABLES
    // ════════════════════════════════════════════════════════════════════════

    public function approveDeliverable(string $dealId, string $deliverableId): JsonResponse
    {
        $deliverable = InfluencerDealDeliverable::where('deal_id', $dealId)->findOrFail($deliverableId);

        if ($deliverable->status !== 'submitted') {
            return response()->json(['success' => false, 'message' => 'Only submitted deliverables can be approved.'], 422);
        }

        DB::transaction(function () use ($deliverable) {
            $deliverable->update([
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by_admin_id' => auth()->guard('admin')->id(),
                'rejected_at' => null,
                'rejection_reason' => null,
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Deliverable approved.']);
    }

    public function rejectDeliverable(string $dealId, string $deliverableId, Request $request): JsonResponse
    {
        $request->validate(['reason' => 'required|string|max:500']);

        $deliverable = InfluencerDealDeliverable::where('deal_id', $dealId)->findOrFail($deliverableId);

        if ($deliverable->status !== 'submitted') {
            return response()->json(['success' => false, 'message' => 'Only submitted deliverables can be rejected.'], 422);
        }

        DB::transaction(function () use ($deliverable, $request) {
            $deliverable->update([
                'status' => 'rejected',
                'rejected_at' => now(),
                'rejection_reason' => $request->input('reason'),
                'approved_by_admin_id' => auth()->guard('admin')->id(),
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Deliverable rejected.']);
    }

    // ════════════════════════════════════════════════════════════════════════
    //  HELPERS
    // ════════════════════════════════════════════════════════════════════════

    private function typeColor(string $type): string
    {
        return match ($type) {
            'celebrity' => 'warning',
            'influencer' => 'primary',
            'brand_ambassador' => 'secondary',
            default => 'secondary',
        };
    }

    private function statusColor(string $status): string
    {
        return match ($status) {
            'proposed', 'negotiating' => 'warning',
            'accepted', 'in_progress' => 'primary',
            'content_submitted' => 'info',
            'approved' => 'success',
            'paid' => 'success',
            'cancelled', 'rejected' => 'danger',
            default => 'secondary',
        };
    }

    private function actions(object $row): string
    {
        $url = route('admin.influencer-deals.show', $row->id);

        return '<a href="' . $url . '" class="btn btn-xs btn-ghost">View</a>';
    }
}
