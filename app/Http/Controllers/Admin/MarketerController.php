<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Country;
use App\Models\Marketer;
use App\Models\MarketerCampaign;
use App\Models\MarketerCommissionTier;
use App\Models\MarketerConversion;
use App\Models\MarketerPayout;
use App\Models\MarketerSampleRequest;
use App\Models\MarketerSecretPromotion;
use App\Models\Vendor;
use App\Traits\HasDataTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MarketerController extends Controller
{
    use HasDataTable;

    // ════════════════════════════════════════════════════════════════════════
    //  MARKETERS
    // ════════════════════════════════════════════════════════════════════════

    public function index(): View
    {
        $stats = [
            'total' => Marketer::count(),
            'active' => Marketer::where('status', 'active')->count(),
            'pending' => Marketer::where('status', 'pending')->count(),
            'commissions_month' => MarketerConversion::where('status', 'approved')
                ->whereBetween('approved_at', [now()->startOfMonth(), now()])
                ->sum('commission_amount_cents'),
        ];

        $countries = Country::orderBy('name_en')->get(['id', 'name_en']);

        return view('admin.marketers.index', [
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
                ['label' => 'Marketers'],
            ],
            'stats' => $stats,
            'countries' => $countries,
        ]);
    }

    public function datatable(Request $request): JsonResponse
    {
        $columns = [
            ['searchable_columns' => ['marketers.name']],
            ['searchable_columns' => ['marketers.email', 'marketers.phone']],
            ['orderable_column' => 'marketers.type'],
            ['orderable_column' => 'countries.name_en'],
            ['orderable_column' => 'marketers.followers_count'],
            ['orderable_column' => 'marketers.total_clicks'],
            ['orderable_column' => 'marketers.total_conversions'],
            ['orderable_column' => 'marketers.total_earnings_cents'],
            ['orderable_column' => 'marketers.status'],
            [],
        ];

        $query = Marketer::query()
            ->leftJoin('countries', 'countries.id', '=', 'marketers.country_id')
            ->select([
                'marketers.id',
                'marketers.name',
                'marketers.email',
                'marketers.phone',
                'marketers.type',
                'marketers.profile_photo_path',
                'marketers.status',
                'marketers.referral_code',
                'marketers.followers_count',
                'marketers.total_clicks',
                'marketers.total_conversions',
                'marketers.total_earnings_cents',
                'marketers.created_at',
                'countries.name_en as country_name',
            ]);

        // Filters
        if ($type = $request->input('filter_type')) {
            $query->where('marketers.type', $type);
        }
        if ($status = $request->input('filter_status')) {
            $query->where('marketers.status', $status);
        }
        if ($country = $request->input('filter_country')) {
            $query->where('marketers.country_id', $country);
        }

        return $this->dataTableResponse($request, $query, $columns, function ($row) {
            return [
                $row->profile_photo_path
                ? '<img src="' . asset('storage/' . $row->profile_photo_path) . '" class="w-8 h-8 rounded-full object-cover">'
                : '<span class="w-8 h-8 rounded-full bg-purple-100 text-purple-700 flex items-center justify-center font-bold text-sm">' . strtoupper(substr($row->name, 0, 1)) . '</span>',
                '<div><p class="font-medium">' . e($row->name) . '</p><p class="text-xs text-gray-400">' . e($row->email) . '</p></div>',
                '<span class="badge badge-' . $this->typeColor($row->type) . '">' . ucfirst(str_replace('_', ' ', $row->type)) . '</span>',
                e($row->country_name ?? '—'),
                number_format($row->followers_count),
                number_format($row->total_clicks),
                number_format($row->total_conversions),
                number_format($row->total_earnings_cents / 100, 2),
                '<span class="badge badge-' . $this->statusColor($row->status) . '">' . ucfirst($row->status) . '</span>',
                $this->actions($row),
            ];
        });
    }

    public function show(Marketer $marketer): View
    {
        $marketer->load(['country', 'campaigns' => fn($q) => $q->latest()->limit(5)]);

        $stats = [
            'total_campaigns' => $marketer->campaigns()->count(),
            'active_campaigns' => $marketer->campaigns()->where('status', 'active')->count(),
            'total_conversions' => $marketer->conversions()->count(),
            'pending_earnings' => $marketer->conversions()->where('status', 'pending')->sum('commission_amount_cents'),
            'paid_earnings' => $marketer->conversions()->where('status', 'paid')->sum('commission_amount_cents'),
        ];

        return view('admin.marketers.show', [
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
                ['label' => 'Marketers', 'url' => route('admin.marketers.all.index')],
                ['label' => $marketer->name],
            ],
            'marketer' => $marketer,
            'stats' => $stats,
        ]);
    }

    public function approve(Marketer $marketer): JsonResponse
    {
        if ($marketer->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Marketer is not pending.'], 422);
        }

        $marketer->update([
            'status' => 'active',
            'approved_by_admin_id' => auth()->guard('admin')->id(),
            'approved_at' => now(),
        ]);

        // TODO: dispatch welcome email job
        // SendMarketerWelcomeMail::dispatch($marketer);

        return response()->json(['success' => true, 'message' => 'Marketer approved and activated.']);
    }

    public function reject(Request $request, Marketer $marketer): JsonResponse
    {
        $request->validate(['reason' => 'required|string|max:500']);

        $marketer->update(['status' => 'rejected']);

        // TODO: dispatch rejection email

        return response()->json(['success' => true, 'message' => 'Marketer rejected.']);
    }

    public function suspend(Request $request, Marketer $marketer): JsonResponse
    {
        $marketer->update(['status' => 'suspended']);
        return response()->json(['success' => true, 'message' => 'Marketer suspended.']);
    }

    public function activate(Marketer $marketer): JsonResponse
    {
        $marketer->update(['status' => 'active']);
        return response()->json(['success' => true, 'message' => 'Marketer activated.']);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:marketers,email',
            'phone' => 'nullable|string|max:30',
            'password' => 'required|string|min:8',
            'type' => 'required|in:influencer,celebrity,affiliate,brand_ambassador',
            'country_id' => 'nullable|exists:countries,id',
            'niche' => 'nullable|string|max:100',
            'followers_count' => 'nullable|integer|min:0',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'status' => 'in:pending,active',
            'social_instagram' => 'nullable|url|max:255',
            'social_tiktok' => 'nullable|url|max:255',
            'social_youtube' => 'nullable|url|max:255',
            'whatsapp_number' => 'nullable|string|max:30',
            'bio' => 'nullable|string|max:1000',
        ]);

        $marketer = Marketer::create([
            ...$data,
            'password' => Hash::make($data['password']),
            'status' => $data['status'] ?? 'active',
            'approved_by_admin_id' => auth()->guard('admin')->id(),
            'approved_at' => ($data['status'] ?? 'active') === 'active' ? now() : null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Marketer created successfully.',
            'redirect' => route('admin.marketers.all.show', $marketer),
        ], 201);
    }

    public function marketerCampaignsDatatable(Marketer $marketer, Request $request): JsonResponse
    {
        $columns = [
            ['searchable_columns' => ['marketer_campaigns.name']],
            ['orderable_column' => 'marketer_campaigns.campaign_type'],
            ['orderable_column' => 'marketer_campaigns.status'],
            ['orderable_column' => 'marketer_campaigns.total_clicks'],
            ['orderable_column' => 'marketer_campaigns.total_conversions'],
            ['orderable_column' => 'marketer_campaigns.total_revenue_cents'],
            ['orderable_column' => 'marketer_campaigns.created_at'],
            [],
        ];

        $query = $marketer->campaigns()->getQuery()
            ->select([
                'marketer_campaigns.*',
            ]);

        return $this->dataTableResponse($request, $query, $columns, fn($row) => [
            e($row->name),
            ucfirst(str_replace('_', ' ', $row->campaign_type)),
            '<span class="badge badge-' . $row->status_color . '">' . ucfirst($row->status) . '</span>',
            number_format($row->total_clicks),
            number_format($row->total_conversions),
            number_format($row->total_revenue_cents / 100, 2),
            $row->created_at->format('d M Y'),
            '<a href="' . route('admin.marketers.campaigns.show', $row->id) . '" class="btn btn-xs btn-ghost">View</a>',
        ]);
    }

    public function marketerConversionsDatatable(Marketer $marketer, Request $request): JsonResponse
    {
        return $this->buildConversionsDatatable($request, $marketer->conversions()->getQuery());
    }

    // ════════════════════════════════════════════════════════════════════════
    //  CAMPAIGNS (admin-wide)
    // ════════════════════════════════════════════════════════════════════════

    public function campaignsIndex(): View
    {
        $stats = [
            'total' => MarketerCampaign::count(),
            'active' => MarketerCampaign::where('status', 'active')->count(),
            'pending' => MarketerCampaign::where('status', 'draft')->count(),
            'total_clicks' => MarketerCampaign::sum('total_clicks'),
        ];

        return view('admin.marketers.campaigns.index', [
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
                ['label' => 'Marketers', 'url' => route('admin.marketers.all.index')],
                ['label' => 'Campaigns'],
            ],
            'stats' => $stats,
        ]);
    }

    public function campaignsDatatable(Request $request): JsonResponse
    {
        $columns = [
            ['searchable_columns' => ['marketer_campaigns.name']],
            ['searchable_columns' => ['marketers.name']],
            ['orderable_column' => 'marketer_campaigns.campaign_type'],
            ['orderable_column' => 'marketer_campaigns.status'],
            ['orderable_column' => 'marketer_campaigns.total_clicks'],
            ['orderable_column' => 'marketer_campaigns.total_conversions'],
            ['orderable_column' => 'marketer_campaigns.total_revenue_cents'],
            ['orderable_column' => 'marketer_campaigns.starts_at'],
            [],
        ];

        $query = MarketerCampaign::query()
            ->join('marketers', 'marketers.id', '=', 'marketer_campaigns.marketer_id')
            ->select([
                'marketer_campaigns.*',
                'marketers.name as marketer_name',
                'marketers.type as marketer_type',
            ]);

        if ($status = $request->input('filter_status')) {
            $query->where('marketer_campaigns.status', $status);
        }
        if ($type = $request->input('filter_type')) {
            $query->where('marketer_campaigns.campaign_type', $type);
        }

        return $this->dataTableResponse($request, $query, $columns, fn($row) => [
            e($row->name),
            '<a href="' . route('admin.marketers.all.show', $row->marketer_id) . '" class="text-primary-600 hover:underline">' . e($row->marketer_name) . '</a>',
            ucfirst(str_replace('_', ' ', $row->campaign_type)),
            '<span class="badge badge-' . $row->status_color . '">' . ucfirst($row->status) . '</span>',
            number_format($row->total_clicks),
            number_format($row->total_conversions),
            number_format($row->total_revenue_cents / 100, 2),
            $row->starts_at?->format('d M Y') ?? '—',
            $this->campaignActions($row),
        ]);
    }

    public function approveCampaign(MarketerCampaign $campaign): JsonResponse
    {
        if ($campaign->status !== 'draft') {
            return response()->json(['success' => false, 'message' => 'Campaign is not in draft status.'], 422);
        }

        $campaign->update([
            'status' => 'active',
            'approved_by_admin_id' => auth()->guard('admin')->id(),
            'approved_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Campaign approved and activated.']);
    }

    public function rejectCampaign(Request $request, MarketerCampaign $campaign): JsonResponse
    {
        $request->validate(['reason' => 'required|string|max:500']);

        $campaign->update(['status' => 'cancelled']);

        return response()->json(['success' => true, 'message' => 'Campaign rejected.']);
    }

    public function showCampaign(MarketerCampaign $campaign): View
    {
        $campaign->load(['marketer', 'products.vendorListing.product']);

        return view('admin.marketers.campaigns.show', [
            'breadcrumbs' => [
                ['label' => 'Campaigns', 'url' => route('admin.marketers.campaigns.index')],
                ['label' => $campaign->name],
            ],
            'campaign' => $campaign,
        ]);
    }

    // ════════════════════════════════════════════════════════════════════════
    //  CONVERSIONS (admin-wide)
    // ════════════════════════════════════════════════════════════════════════

    public function conversionsIndex(): View
    {
        $stats = [
            'total' => MarketerConversion::count(),
            'pending' => MarketerConversion::where('status', 'pending')->count(),
            'approved' => MarketerConversion::where('status', 'approved')->count(),
            'paid' => MarketerConversion::where('status', 'paid')->count(),
        ];

        return view('admin.marketers.conversions.index', [
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
                ['label' => 'Marketers', 'url' => route('admin.marketers.all.index')],
                ['label' => 'Conversions'],
            ],
            'stats' => $stats,
        ]);
    }

    public function conversionsDatatable(Request $request): JsonResponse
    {
        return $this->buildConversionsDatatable($request, MarketerConversion::query());
    }

    public function approveConversions(Request $request): JsonResponse
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'uuid']);

        $count = MarketerConversion::whereIn('id', $request->ids)
            ->where('status', 'pending')
            ->update([
                'status' => 'approved',
                'approved_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => $count . ' conversion(s) approved.',
        ]);
    }

    // ════════════════════════════════════════════════════════════════════════
    //  PAYOUTS (admin-wide)
    // ════════════════════════════════════════════════════════════════════════

    public function payoutsIndex(): View
    {
        $stats = [
            'total' => MarketerPayout::count(),
            'pending' => MarketerPayout::where('status', 'pending')->count(),
            'paid' => MarketerPayout::where('status', 'paid')->sum('net_amount_cents'),
            'approved' => MarketerPayout::where('status', 'approved')->sum('net_amount_cents'),
        ];

        $marketers = Marketer::where('status', 'active')->orderBy('name')->get(['id', 'name']);

        return view('admin.marketers.payouts.index', [
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
                ['label' => 'Marketers', 'url' => route('admin.marketers.all.index')],
                ['label' => 'Payouts'],
            ],
            'stats' => $stats,
            'marketers' => $marketers,
        ]);
    }

    public function payoutsDatatable(Request $request): JsonResponse
    {
        $columns = [
            ['searchable_columns' => ['marketer_payouts.payout_number']],
            ['searchable_columns' => ['marketers.name']],
            ['orderable_column' => 'marketer_payouts.period_start'],
            ['orderable_column' => 'marketer_payouts.total_conversions'],
            ['orderable_column' => 'marketer_payouts.net_amount_cents'],
            ['orderable_column' => 'marketer_payouts.status'],
            ['orderable_column' => 'marketer_payouts.processed_at'],
            [],
        ];

        $query = MarketerPayout::query()
            ->join('marketers', 'marketers.id', '=', 'marketer_payouts.marketer_id')
            ->select([
                'marketer_payouts.*',
                'marketers.name as marketer_name',
            ]);

        if ($status = $request->input('filter_status')) {
            $query->where('marketer_payouts.status', $status);
        }
        if ($marketer = $request->input('filter_marketer')) {
            $query->where('marketer_payouts.marketer_id', $marketer);
        }

        return $this->dataTableResponse($request, $query, $columns, fn($row) => [
            '<span class="font-mono text-xs">' . e($row->payout_number) . '</span>',
            e($row->marketer_name),
            $row->period_start . ' – ' . $row->period_end,
            number_format($row->total_conversions),
            number_format($row->net_amount_cents / 100, 2) . ' ' . $row->currency,
            '<span class="badge badge-' . $row->status_color . '">' . ucfirst($row->status) . '</span>',
            $row->processed_at?->format('d M Y') ?? '—',
            $this->payoutActions($row),
        ]);
    }

    public function generatePayout(Request $request): JsonResponse
    {
        $request->validate([
            'marketer_id' => 'required|uuid|exists:marketers,id',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after:period_start',
        ]);

        $marketer = Marketer::findOrFail($request->marketer_id);

        $conversions = MarketerConversion::where('marketer_id', $marketer->id)
            ->where('status', 'approved')
            ->whereBetween('approved_at', [$request->period_start, $request->period_end . ' 23:59:59'])
            ->get();

        if ($conversions->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No approved conversions in this period.'], 422);
        }

        $gross = $conversions->sum('commission_amount_cents');
        $tax = (int) round($gross * 0.05); // 5% tax — configurable
        $net = $gross - $tax;

        $payout = MarketerPayout::create([
            'marketer_id' => $marketer->id,
            'period_start' => $request->period_start,
            'period_end' => $request->period_end,
            'total_conversions' => $conversions->count(),
            'gross_commission_cents' => $gross,
            'tax_deduction_cents' => $tax,
            'net_amount_cents' => $net,
            'currency' => $conversions->first()->currency ?? 'SAR',
            'status' => 'pending',
            'bank_name' => $marketer->bank_name,
            'bank_iban' => $marketer->bank_iban,
        ]);

        // Mark conversions as part of this payout
        $conversions->each(fn($c) => $c->update(['status' => 'paid', 'paid_at' => now()]));

        // Update marketer totals
        $marketer->increment('total_earnings_cents', $net);

        return response()->json([
            'success' => true,
            'message' => 'Payout generated: ' . $payout->payout_number,
        ]);
    }

    public function approvePayout(MarketerPayout $payout): JsonResponse
    {
        if ($payout->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Payout is not pending.'], 422);
        }

        $payout->update([
            'status' => 'approved',
            'approved_by_admin_id' => auth()->guard('admin')->id(),
        ]);

        return response()->json(['success' => true, 'message' => 'Payout approved.']);
    }

    public function processPayout(Request $request, MarketerPayout $payout): JsonResponse
    {
        $request->validate(['payment_reference' => 'required|string|max:255']);

        if ($payout->status !== 'approved') {
            return response()->json(['success' => false, 'message' => 'Payout must be approved first.'], 422);
        }

        $payout->update([
            'status' => 'paid',
            'payment_reference' => $request->payment_reference,
            'processed_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Payout marked as paid.']);
    }

    // ════════════════════════════════════════════════════════════════════════
    //  PRIVATE HELPERS
    // ════════════════════════════════════════════════════════════════════════

    private function buildConversionsDatatable(Request $request, $baseQuery): JsonResponse
    {
        $columns = [
            ['searchable_columns' => ['marketers.name']],
            ['searchable_columns' => ['marketer_campaigns.name']],
            ['orderable_column' => 'marketer_conversions.order_value_cents'],
            ['orderable_column' => 'marketer_conversions.commission_amount_cents'],
            ['orderable_column' => 'marketer_conversions.status'],
            ['orderable_column' => 'marketer_conversions.created_at'],
            [],
        ];

        $query = $baseQuery
            ->join('marketers', 'marketers.id', '=', 'marketer_conversions.marketer_id')
            ->join('marketer_campaigns', 'marketer_campaigns.id', '=', 'marketer_conversions.campaign_id')
            ->select([
                'marketer_conversions.*',
                'marketers.name as marketer_name',
                'marketer_campaigns.name as campaign_name',
            ]);

        if ($status = $request->input('filter_status')) {
            $query->where('marketer_conversions.status', $status);
        }
        if ($from = $request->input('filter_from')) {
            $query->whereDate('marketer_conversions.created_at', '>=', $from);
        }
        if ($to = $request->input('filter_to')) {
            $query->whereDate('marketer_conversions.created_at', '<=', $to);
        }

        return $this->dataTableResponse($request, $query, $columns, fn($row) => [
            e($row->marketer_name),
            e($row->campaign_name),
            number_format($row->order_value_cents / 100, 2),
            number_format($row->commission_amount_cents / 100, 2),
            '<span class="badge badge-' . $row->status_color . '">' . ucfirst($row->status) . '</span>',
            $row->created_at->format('d M Y'),
            $row->status === 'pending'
            ? '<input type="checkbox" class="conv-check" value="' . $row->id . '">'
            : '',
        ]);
    }

    private function typeColor(string $type): string
    {
        return match ($type) {
            'celebrity' => 'warning',
            'influencer' => 'primary',
            'affiliate' => 'success',
            'brand_ambassador' => 'secondary',
            default => 'secondary',
        };
    }

    private function statusColor(string $status): string
    {
        return match ($status) {
            'active' => 'success',
            'pending' => 'warning',
            'suspended' => 'danger',
            'rejected' => 'secondary',
            default => 'secondary',
        };
    }

    private function actions(object $row): string
    {
        $url = route('admin.marketers.all.show', $row->id);
        $html = '<div class="flex gap-1">';
        $html .= '<a href="' . $url . '" class="btn btn-xs btn-ghost">View</a>';

        if ($row->status === 'pending') {
            $html .= '<button type="button" data-id="' . $row->id . '" class="btn btn-xs btn-success btn-approve-marketer">Approve</button>';
            $html .= '<button type="button" data-id="' . $row->id . '" class="btn btn-xs btn-danger btn-reject-marketer">Reject</button>';
        } elseif ($row->status === 'active') {
            $html .= '<button type="button" data-id="' . $row->id . '" class="btn btn-xs btn-warning btn-suspend-marketer">Suspend</button>';
        } elseif ($row->status === 'suspended') {
            $html .= '<button type="button" data-id="' . $row->id . '" class="btn btn-xs btn-success btn-activate-marketer">Activate</button>';
        }

        $html .= '</div>';
        return $html;
    }

    private function campaignActions(object $row): string
    {
        $html = '<div class="flex gap-1">';
        if ($row->status === 'draft') {
            $html .= '<button type="button" data-id="' . $row->id . '" class="btn btn-xs btn-success btn-approve-campaign">Approve</button>';
            $html .= '<button type="button" data-id="' . $row->id . '" class="btn btn-xs btn-danger btn-reject-campaign">Reject</button>';
        } else {
            $html .= '<a href="' . route('admin.marketers.campaigns.show', $row->id) . '" class="btn btn-xs btn-ghost">View</a>';
        }
        $html .= '</div>';
        return $html;
    }

    private function payoutActions(object $row): string
    {
        $html = '<div class="flex gap-1">';
        if ($row->status === 'pending') {
            $html .= '<button type="button" data-id="' . $row->id . '" class="btn btn-xs btn-primary btn-approve-payout">Approve</button>';
        } elseif ($row->status === 'approved') {
            $html .= '<button type="button" data-id="' . $row->id . '" class="btn btn-xs btn-success btn-process-payout">Process</button>';
        }
        $html .= '</div>';
        return $html;
    }

    // ════════════════════════════════════════════════════════════════════════
    //  COMMISSION TIERS
    // ════════════════════════════════════════════════════════════════════════

    public function tiersShow(Marketer $marketer): View
    {
        $tiers = MarketerCommissionTier::where('marketer_id', $marketer->id)
            ->whereNull('campaign_id')
            ->orderBy('tier_order')
            ->get();

        $salesCount = $marketer->conversions()
            ->where('status', '!=', 'reversed')
            ->count();

        return view('admin.marketers.tiers.show', [
            'breadcrumbs' => [
                ['label' => 'Marketers', 'url' => route('admin.marketers.all.index')],
                ['label' => $marketer->name, 'url' => route('admin.marketers.all.show', $marketer)],
                ['label' => 'Commission Tiers'],
            ],
            'marketer' => $marketer,
            'tiers' => $tiers,
            'salesCount' => $salesCount,
        ]);
    }

    public function storeTiers(Request $request, Marketer $marketer): JsonResponse
    {
        $request->validate([
            'tiers' => 'required|array|min:1',
            'tiers.*.min_sales_count' => 'required|integer|min:0',
            'tiers.*.max_sales_count' => 'nullable|integer|min:1',
            'tiers.*.commission_rate' => 'required|numeric|min:0|max:100',
        ]);

        // Validate contiguous
        $tiers = $request->tiers;
        usort($tiers, fn($a, $b) => $a['min_sales_count'] <=> $b['min_sales_count']);

        DB::transaction(function () use ($marketer, $tiers) {
            // Replace all global tiers for this marketer
            MarketerCommissionTier::where('marketer_id', $marketer->id)
                ->whereNull('campaign_id')
                ->delete();

            foreach ($tiers as $i => $tierData) {
                MarketerCommissionTier::create([
                    'marketer_id' => $marketer->id,
                    'campaign_id' => null,
                    'tier_order' => $i + 1,
                    'min_sales_count' => $tierData['min_sales_count'],
                    'max_sales_count' => $tierData['max_sales_count'] ?? null,
                    'commission_rate' => $tierData['commission_rate'],
                    'is_active' => 1,
                ]);
            }
        });

        return response()->json(['success' => true, 'message' => 'Tiers saved successfully.']);
    }

    // ════════════════════════════════════════════════════════════════════════
    //  SECRET PROMOTIONS
    // ════════════════════════════════════════════════════════════════════════

    public function secretPromotionsIndex(): View
    {
        $promotions = MarketerSecretPromotion::with(['vendor', 'vendorListing.product', 'marketer'])
            ->orderByDesc('created_at')
            ->paginate(30);

        return view('admin.marketers.secret-promotions.index', [
            'breadcrumbs' => [
                ['label' => 'Marketers', 'url' => route('admin.marketers.all.index')],
                ['label' => 'Secret Promotions'],
            ],
            'promotions' => $promotions,
        ]);
    }

    public function storeSecretPromotion(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'vendor_id' => 'required|uuid|exists:vendors,id',
            'vendor_listing_id' => 'required|uuid|exists:vendor_listings,id',
            'marketer_id' => 'nullable|uuid|exists:marketers,id',
            'product_value_cents' => 'required|integer|min:1',
            'total_commission_pct' => 'required|numeric|min:0|max:100',
            'marketer_share_pct' => 'required|numeric|min:0|max:100',
            'admin_share_pct' => 'required|numeric|min:0|max:100',
            'min_commission_pct' => 'required|numeric|min:0',
            'valid_until' => 'nullable|date|after:today',
        ]);

        // Validate shares add up to total
        $sumPct = $validated['marketer_share_pct'] + $validated['admin_share_pct'];
        if (abs($sumPct - $validated['total_commission_pct']) > 0.01) {
            return response()->json([
                'success' => false,
                'message' => 'Marketer share + admin share must equal total commission.',
            ], 422);
        }

        if ($validated['total_commission_pct'] < $validated['min_commission_pct']) {
            return response()->json([
                'success' => false,
                'message' => 'Total commission cannot be below minimum floor.',
            ], 422);
        }

        $promo = MarketerSecretPromotion::create(array_merge($validated, [
            'status' => 'active',
            'approved_by_admin_id' => auth()->guard('admin')->id(),
        ]));

        return response()->json(['success' => true, 'message' => 'Secret promotion created.', 'id' => $promo->id]);
    }

    public function updateSecretPromotion(Request $request, MarketerSecretPromotion $promo): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:active,paused,expired',
            'valid_until' => 'nullable|date',
        ]);

        $promo->update($validated);

        return response()->json(['success' => true, 'message' => 'Promotion updated.']);
    }

    // ════════════════════════════════════════════════════════════════════════
    //  SAMPLES
    // ════════════════════════════════════════════════════════════════════════

    public function samplesIndex(): View
    {
        $stats = [
            'total' => MarketerSampleRequest::count(),
            'requested' => MarketerSampleRequest::where('status', 'requested')->count(),
            'approved' => MarketerSampleRequest::where('status', 'approved')->count(),
            'dispatched' => MarketerSampleRequest::where('status', 'dispatched')->count(),
        ];

        return view('admin.marketers.samples.index', [
            'breadcrumbs' => [
                ['label' => 'Marketers', 'url' => route('admin.marketers.all.index')],
                ['label' => 'Sample Requests'],
            ],
            'stats' => $stats,
        ]);
    }

    public function samplesDatatable(Request $request): JsonResponse
    {
        $columns = [
            ['searchable_columns' => ['marketers.name']],
            ['searchable_columns' => ['vendors.store_name']],
            ['orderable_column' => 'marketer_sample_requests.status'],
            ['orderable_column' => 'marketer_sample_requests.created_at'],
            [],
        ];

        $query = MarketerSampleRequest::query()
            ->join('marketers', 'marketers.id', '=', 'marketer_sample_requests.marketer_id')
            ->join('vendors', 'vendors.id', '=', 'marketer_sample_requests.vendor_id')
            ->select([
                'marketer_sample_requests.*',
                'marketers.name as marketer_name',
                'vendors.store_name as vendor_name',
            ]);

        if ($status = $request->input('filter_status')) {
            $query->where('marketer_sample_requests.status', $status);
        }

        return $this->dataTableResponse($request, $query, $columns, fn($row) => [
            e($row->marketer_name),
            e($row->vendor_name),
            '<span class="badge badge-' . (new MarketerSampleRequest(['status' => $row->status]))->status_color . '">' . ucfirst($row->status) . '</span>',
            $row->created_at->format('d M Y'),
            $this->sampleActions($row),
        ]);
    }

    public function approveSample(MarketerSampleRequest $req): JsonResponse
    {
        if ($req->status !== 'requested') {
            return response()->json(['success' => false, 'message' => 'Request is not pending.'], 422);
        }

        $req->update([
            'status' => 'approved',
            'admin_approved_by' => auth()->guard('admin')->id(),
            'approved_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Sample request approved.']);
    }

    public function dispatchSample(MarketerSampleRequest $req): JsonResponse
    {
        if ($req->status !== 'approved') {
            return response()->json(['success' => false, 'message' => 'Request must be approved first.'], 422);
        }

        $req->update([
            'status' => 'dispatched',
            'dispatched_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Sample marked as dispatched.']);
    }

    private function sampleActions(object $row): string
    {
        $html = '<div class="flex gap-1">';
        if ($row->status === 'requested') {
            $html .= '<button type="button" data-id="' . $row->id . '" class="btn btn-xs btn-success btn-approve-sample">Approve</button>';
        } elseif ($row->status === 'approved') {
            $html .= '<button type="button" data-id="' . $row->id . '" class="btn btn-xs btn-primary btn-dispatch-sample">Dispatch</button>';
        }
        $html .= '</div>';
        return $html;
    }
}
