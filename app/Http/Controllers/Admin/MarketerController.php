<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MarketerSampleRequestStatus;
use App\Enums\SecretPromotionStatus;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Country;
use App\Models\Marketer;
use App\Models\MarketerCampaign;
use App\Models\MarketerCommissionTier;
use App\Models\MarketerConversion;
use App\Models\MarketerPayout;
use App\Models\MarketerSampleItem;
use App\Models\MarketerSampleRequest;
use App\Models\MarketerSecretPromotion;
use App\Models\Category;
use App\Models\VendorListing;
use App\Models\ClassifiedListing;
use App\Models\TravelPackage;
use App\Models\Vendor;
use App\Jobs\SendMarketerRejectionMail;
use App\Jobs\SendMarketerWelcomeMail;
use App\Notifications\Admin\PayoutBatchReadyForApproval;
use App\Notifications\Admin\SampleRequestSubmitted;
use App\Notifications\Marketer\AccountReactivated as MarketerAccountReactivated;
use App\Notifications\Marketer\AccountSuspended as MarketerAccountSuspended;
use App\Notifications\Marketer\ApplicationApproved as MarketerApplicationApproved;
use App\Notifications\Marketer\ApplicationRejected as MarketerApplicationRejected;
use App\Notifications\Marketer\CampaignApproved as MarketerCampaignApproved;
use App\Notifications\Marketer\CampaignRejected as MarketerCampaignRejected;
use App\Notifications\Marketer\ConversionApproved as MarketerConversionApproved;
use App\Notifications\Marketer\PayoutProcessed as MarketerPayoutProcessed;
use App\Notifications\Marketer\SampleDispatched as MarketerSampleDispatched;
use App\Notifications\Marketer\SampleRequestApproved as MarketerSampleRequestApproved;
use App\Notifications\Marketer\SampleRequestRejected as MarketerSampleRequestRejected;
use App\Services\SampleQuotaResolver;
use App\Traits\HasDataTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
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
            // Per-currency breakdown for this month's approved commissions.
            // Returned as a collection keyed by ISO currency code.
            'commissions_month_by_currency' => MarketerConversion::where('status', 'approved')
                ->whereBetween('approved_at', [now()->startOfMonth(), now()])
                ->selectRaw('currency, SUM(commission_amount_cents) as total')
                ->groupBy('currency')
                ->pluck('total', 'currency'),
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
                '<span class="badge badge-' . $this->typeColor($row->type?->value) . '">' . ucfirst(str_replace('_', ' ', $row->type?->value)) . '</span>',
                e($row->country_name ?? '—'),
                number_format($row->followers_count),
                number_format($row->total_clicks),
                number_format($row->total_conversions),
                number_format($row->total_earnings_cents / 100, 2),
                '<span class="badge badge-' . $this->statusColor($row->status?->value) . '">' . ucfirst($row->status?->value) . '</span>',
                $this->actions($row),
            ];
        });
    }

    public function show(Marketer $marketer): View
    {
        $marketer->load(['country', 'campaigns' => fn($q) => $q->latest()->limit(5)]);

        // Earnings grouped by currency — never sum across currencies.
        $pendingByCurrency = $marketer->conversions()->where('status', 'pending')
            ->selectRaw('currency, SUM(commission_amount_cents) as total')
            ->groupBy('currency')->pluck('total', 'currency');
        $paidByCurrency = $marketer->conversions()->where('status', 'paid')
            ->selectRaw('currency, SUM(commission_amount_cents) as total')
            ->groupBy('currency')->pluck('total', 'currency');

        $stats = [
            'total_campaigns' => $marketer->campaigns()->count(),
            'active_campaigns' => $marketer->campaigns()->where('status', 'active')->count(),
            'total_conversions' => $marketer->conversions()->count(),
            'pending_by_currency' => $pendingByCurrency,
            'paid_by_currency' => $paidByCurrency,
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
        if ($marketer->status !== \App\Enums\MarketerStatus::Pending) {
            return response()->json(['success' => false, 'message' => 'Marketer is not pending.'], 422);
        }

        $marketer->update([
            'status' => 'active',
            'approved_by_admin_id' => auth()->guard('admin')->id(),
            'approved_at' => now(),
        ]);

        SendMarketerWelcomeMail::dispatch($marketer);
        $marketer->notify(new MarketerApplicationApproved($marketer));

        return response()->json(['success' => true, 'message' => 'Marketer approved and activated.']);
    }

    public function reject(Request $request, Marketer $marketer): JsonResponse
    {
        $request->validate(['reason' => 'required|string|max:500']);

        $marketer->update(['status' => 'rejected']);

        SendMarketerRejectionMail::dispatch($marketer, $request->reason);
        $marketer->notify(new MarketerApplicationRejected($marketer, $request->reason));

        return response()->json(['success' => true, 'message' => 'Marketer rejected.']);
    }

    public function suspend(Request $request, Marketer $marketer): JsonResponse
    {
        $marketer->update(['status' => 'suspended']);
        $marketer->notify(new MarketerAccountSuspended($marketer, $request->input('reason', '')));
        return response()->json(['success' => true, 'message' => 'Marketer suspended.']);
    }

    public function activate(Marketer $marketer): JsonResponse
    {
        $marketer->update(['status' => 'active']);
        $marketer->notify(new MarketerAccountReactivated($marketer));
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
            ucfirst(str_replace('_', ' ', $row->campaign_type?->value)),
            '<span class="badge badge-' . $row->status_color . '">' . ucfirst($row->status?->value) . '</span>',
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
        if ($targetType = $request->input('filter_target_type')) {
            $map = [
                'vendor' => Vendor::class,
                'classified' => ClassifiedListing::class,
                'travel' => TravelPackage::class,
            ];
            if (isset($map[$targetType])) {
                $query->where('marketer_campaigns.campaignable_type', $map[$targetType]);
            }
        }

        return $this->dataTableResponse($request, $query, $columns, function ($row) {
            // Build campaign cell: name + campaignable-type badge + target secondary line
            // campaignable is lazy-loaded per row; page size is 25 so N+1 impact is negligible
            $campaignCell = '<a href="' . route('admin.marketers.campaigns.show', $row->id) . '" class="font-medium text-gray-900 hover:underline">' . e($row->name) . '</a>';

            if ($row->campaignable_type === ClassifiedListing::class) {
                $listing = $row->campaignable;
                $campaignCell .= '<div class="mt-0.5 flex items-center gap-1.5">'
                    . '<span class="badge badge-secondary text-[10px] px-1.5 py-0">Classified</span>'
                    . '<span class="text-xs text-gray-500">' . e($listing?->title_en ?? '—') . '</span>'
                    . '</div>';
            } elseif ($row->campaignable_type === TravelPackage::class) {
                $package = $row->campaignable;
                $campaignCell .= '<div class="mt-0.5 flex items-center gap-1.5">'
                    . '<span class="badge badge-primary text-[10px] px-1.5 py-0">Travel</span>'
                    . '<span class="text-xs text-gray-500">' . e($package?->title_en ?? '—') . '</span>'
                    . '</div>';
            }

            return [
                $campaignCell,
                '<a href="' . route('admin.marketers.all.show', $row->marketer_id) . '" class="text-primary-600 hover:underline">' . e($row->marketer_name) . '</a>',
                ucfirst(str_replace('_', ' ', $row->campaign_type?->value)),
                '<span class="badge badge-' . $row->status_color . '">' . ucfirst($row->status?->value) . '</span>',
                number_format($row->total_clicks),
                number_format($row->total_conversions),
                number_format($row->total_revenue_cents / 100, 2),
                $row->starts_at?->format('d M Y') ?? '—',
                $this->campaignActions($row),
            ];
        });
    }

    public function approveCampaign(MarketerCampaign $campaign): JsonResponse
    {
        if ($campaign->status !== \App\Enums\MarketerCampaignStatus::Draft) {
            return response()->json(['success' => false, 'message' => 'Campaign is not in draft status.'], 422);
        }

        DB::transaction(function () use ($campaign) {
            $campaign->update([
                'status' => 'active',
                'approved_by_admin_id' => auth()->guard('admin')->id(),
                'approved_at' => now(),
            ]);

            // Auto-create and approve sample requests grouped by vendor
            // if ($campaign->samples_required > 0) {
            $campaign->load('products.vendorListing');

            $byVendor = $campaign->products
                ->filter(fn($p) => $p->vendorListing?->vendor_id)
                ->groupBy(fn($p) => $p->vendorListing->vendor_id);

            foreach ($byVendor as $vendorId => $products) {
                $sampleRequest = MarketerSampleRequest::create([
                    'marketer_id' => $campaign->marketer_id,
                    'vendor_id' => $vendorId,
                    'campaign_id' => $campaign->id,
                    'status' => 'requested',
                ]);

                $listingIds = $products->pluck('vendorListing.id')->filter()->values()->all();
                $category = app(SampleQuotaResolver::class)->resolveFromListingIds($listingIds);
                foreach ($products as $product) {

                    MarketerSampleItem::create([
                        'sample_request_id' => $sampleRequest->id,
                        'vendor_listing_id' => $product->vendorListing->id,
                        'quantity' => $category?->marketer_sample_quota ?? 0,
                        'marketer_quantity' => $category?->marketer_sample_quota ?? 0,
                        'admin_quantity' => 0,
                        'is_mandatory' => false,
                        'sample_cost_cents' => 0,
                    ]);
                }

                \App\Jobs\MarketerAutoApproveJob::dispatch(null, $sampleRequest->id)->afterCommit();
            }
            // }
        });

        $campaign->marketer->notify(new MarketerCampaignApproved($campaign));

        return response()->json(['success' => true, 'message' => 'Campaign approved and activated.']);
    }

    public function rejectCampaign(Request $request, MarketerCampaign $campaign): JsonResponse
    {
        $request->validate(['reason' => 'required|string|max:500']);

        $campaign->update(['status' => 'cancelled']);

        $campaign->marketer->notify(new MarketerCampaignRejected($campaign, $request->reason));

        return response()->json(['success' => true, 'message' => 'Campaign rejected.']);
    }

    public function updateCampaignSamplesRequired(Request $request, MarketerCampaign $campaign): JsonResponse
    {
        abort_if(!in_array($campaign->status?->value, ['draft', 'active'], true), 422, 'Cannot update samples_required on a campaign that is ended or cancelled.');

        $validated = $request->validate([
            'samples_required' => 'required|integer|min:0|max:10',
        ]);

        $campaign->update(['samples_required' => $validated['samples_required']]);

        return response()->json(['success' => true, 'samples_required' => $campaign->samples_required]);
    }

    public function showCampaign(MarketerCampaign $campaign): View
    {
        $campaign->load(['marketer', 'products.vendorListing.productVariant.product', 'campaignable']);

        // For travel campaigns, also eager-load the agency on the already-loaded campaignable
        if ($campaign->campaignable instanceof TravelPackage) {
            $campaign->campaignable->loadMissing('agency');
        }

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

        $conversions = MarketerConversion::whereIn('id', $request->ids)
            ->where('status', 'pending')
            ->get();

        $count = $conversions->count();

        MarketerConversion::whereIn('id', $request->ids)
            ->where('status', 'pending')
            ->update([
                'status' => 'approved',
                'approved_at' => now(),
            ]);

        foreach ($conversions as $conversion) {
            $conversion->status = 'approved';
            $conversion->marketer->notify(new MarketerConversionApproved($conversion));
        }

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
            '<span class="badge badge-' . $row->status_color . '">' . ucfirst($row->status?->value) . '</span>',
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

        // Each currency group becomes its own payout row so amounts are never blended.
        // Tax rate: marketer_withholding_tax_rate is withholding tax on commission income —
        // a separate statutory concept from vat_rate (which is VAT on the sale).
        // Defaults to 0 if the marketer has no country linked or the country rate is unset.
        $taxRate = $marketer->country->marketer_withholding_tax_rate ?? 0;

        $payoutNumbers = [];

        foreach ($conversions->groupBy('currency') as $currency => $group) {
            $gross = $group->sum('commission_amount_cents');
            $tax = (int) round($gross * ($taxRate / 100));
            $net = $gross - $tax;

            $payout = MarketerPayout::create([
                'marketer_id' => $marketer->id,
                'period_start' => $request->period_start,
                'period_end' => $request->period_end,
                'total_conversions' => $group->count(),
                'gross_commission_cents' => $gross,
                'tax_deduction_cents' => $tax,
                'net_amount_cents' => $net,
                'currency' => $currency,
                'status' => 'pending',
                'bank_name' => $marketer->bank_name,
                'bank_iban' => $marketer->bank_iban,
            ]);

            $payoutNumbers[] = $payout->payout_number;
            $marketer->increment('total_earnings_cents', $net);
        }

        // Mark all conversions as paid only after all payout rows are committed.
        $conversions->each(fn($c) => $c->update(['status' => 'paid', 'paid_at' => now()]));

        Notification::send(
            Admin::permission('marketers.payouts.approve')->get(),
            new PayoutBatchReadyForApproval(
                batchType: 'marketer',
                payoutCount: count($payoutNumbers),
                periodStart: $request->period_start,
                periodEnd: $request->period_end,
            ),
        );

        return response()->json([
            'success' => true,
            'message' => count($payoutNumbers) . ' payout(s) generated: ' . implode(', ', $payoutNumbers),
        ]);
    }

    public function approvePayout(MarketerPayout $payout): JsonResponse
    {
        if ($payout->status !== \App\Enums\MarketerPayoutStatus::Pending) {
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

        if ($payout->status !== \App\Enums\MarketerPayoutStatus::Approved) {
            return response()->json(['success' => false, 'message' => 'Payout must be approved first.'], 422);
        }

        $payout->update([
            'status' => 'paid',
            'payment_reference' => $request->payment_reference,
            'processed_at' => now(),
        ]);

        $payout->marketer->notify(new MarketerPayoutProcessed($payout));

        return response()->json(['success' => true, 'message' => 'Payout marked as paid.']);
    }

    public function marketerSamplesDatatable(Marketer $marketer, Request $request): JsonResponse
    {
        $columns = [
            ['searchable_columns' => ['vendors.store_name']],
            ['orderable_column' => 'marketer_sample_requests.status'],
            ['orderable_column' => 'marketer_sample_requests.created_at'],
            [],
        ];

        $query = $marketer->sampleRequests()->getQuery()
            ->join('vendors', 'vendors.id', '=', 'marketer_sample_requests.vendor_id')
            ->select(['marketer_sample_requests.*', 'vendors.store_name as vendor_name']);

        if ($status = $request->input('filter_status')) {
            $query->where('marketer_sample_requests.status', $status);
        }

        return $this->dataTableResponse($request, $query, $columns, fn($row) => [
            e($row->vendor_name),
            '<span class="badge badge-' . (new MarketerSampleRequest(['status' => $row->status]))->status_color . '">' . ucfirst($row->status?->value) . '</span>',
            $row->created_at->format('d M Y'),
            $this->sampleActions($row),
        ]);
    }

    public function marketerSecretPromotionsDatatable(Marketer $marketer, Request $request): JsonResponse
    {
        $columns = [
            ['searchable_columns' => ['vendors.store_name']],
            ['orderable_column' => 'marketer_secret_promotions.total_commission_pct'],
            ['orderable_column' => 'marketer_secret_promotions.status'],
            ['orderable_column' => 'marketer_secret_promotions.valid_until'],
        ];

        $query = MarketerSecretPromotion::where('marketer_id', $marketer->id)
            ->join('vendors', 'vendors.id', '=', 'marketer_secret_promotions.vendor_id')
            ->leftJoin('vendor_listings', 'vendor_listings.id', '=', 'marketer_secret_promotions.vendor_listing_id')
            ->leftJoin('product_variants', 'product_variants.id', '=', 'vendor_listings.product_variant_id')
            ->leftJoin('products', 'products.id', '=', 'product_variants.product_id')
            ->select([
                'marketer_secret_promotions.*',
                'vendors.store_name as vendor_name',
                'products.name_en as product_name',
            ]);

        return $this->dataTableResponse($request, $query, $columns, fn($row) => [
            e($row->vendor_name) . '<br><span class="text-xs text-gray-400">' . e($row->product_name ?? '') . '</span>',
            $row->total_commission_pct . '%',
            '<span class="badge badge-' . ($row->status === SecretPromotionStatus::Active ? 'success' : ($row->status === SecretPromotionStatus::Paused ? 'warning' : 'secondary')) . '">' . ucfirst($row->status->value) . '</span>',
            $row->valid_until ? \Carbon\Carbon::parse($row->valid_until)->format('d M Y') : '—',
        ]);
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
            '<span class="badge badge-' . $row->status_color . '">' . ucfirst($row->status?->value) . '</span>',
            $row->created_at->format('d M Y'),
            $row->status === \App\Enums\MarketerTrackingStatus::Pending
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

        if ($row->status === \App\Enums\MarketerStatus::Pending) {
            $html .= '<button type="button" data-id="' . $row->id . '" class="btn btn-xs btn-success btn-approve-marketer">Approve</button>';
            $html .= '<button type="button" data-id="' . $row->id . '" class="btn btn-xs btn-danger btn-reject-marketer">Reject</button>';
        } elseif ($row->status === \App\Enums\MarketerStatus::Active) {
            $html .= '<button type="button" data-id="' . $row->id . '" class="btn btn-xs btn-warning btn-suspend-marketer">Suspend</button>';
        } elseif ($row->status === \App\Enums\MarketerStatus::Suspended) {
            $html .= '<button type="button" data-id="' . $row->id . '" class="btn btn-xs btn-success btn-activate-marketer">Activate</button>';
        }

        $html .= '</div>';
        return $html;
    }

    private function campaignActions(object $row): string
    {
        $html = '<div class="flex gap-1">';
        if ($row->status === \App\Enums\MarketerCampaignStatus::Draft) {
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
        if ($row->status === \App\Enums\MarketerPayoutStatus::Pending) {
            $html .= '<button type="button" data-id="' . $row->id . '" class="btn btn-xs btn-primary btn-approve-payout">Approve</button>';
        } elseif ($row->status === \App\Enums\MarketerPayoutStatus::Approved) {
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
            '<span class="badge badge-' . (new MarketerSampleRequest(['status' => $row->status]))->status_color . '">' . ucfirst($row->status?->value) . '</span>',
            $row->created_at->format('d M Y'),
            $this->sampleActions($row),
        ]);
    }

    public function approveSample(Request $httpRequest, MarketerSampleRequest $req): JsonResponse
    {
        if ($req->status !== MarketerSampleRequestStatus::Requested) {
            return response()->json(['success' => false, 'message' => 'Request is not pending.'], 422);
        }

        $validated = $httpRequest->validate([
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|uuid|exists:marketer_sample_items,id',
            'items.*.marketer_quantity' => 'required|integer|min:0',
            'items.*.admin_quantity' => 'required|integer|min:0',
        ]);

        DB::transaction(function () use ($req, $validated) {
            foreach ($validated['items'] as $itemData) {
                $item = $req->items()->find($itemData['id']);
                if (!$item)
                    continue;

                $mktQty = max(0, (int) $itemData['marketer_quantity']);
                $admQty = max(0, (int) $itemData['admin_quantity']);

                // marketer_quantity and admin_quantity coexist on the same row.
                // quantity = combined total visible to the vendor.
                $item->update([
                    'marketer_quantity' => $mktQty,
                    'admin_quantity' => $admQty,
                    'quantity' => $mktQty + $admQty,
                ]);
            }

            $req->update([
                'status' => 'approved',
                'admin_approved_by' => auth()->guard('admin')->id(),
                'approved_at' => now(),
            ]);
        });

        $req->marketer->notify(new MarketerSampleRequestApproved($req));

        return response()->json(['success' => true, 'message' => 'Sample request approved.']);
    }


    public function dispatchSample(MarketerSampleRequest $req): JsonResponse
    {
        if ($req->status !== MarketerSampleRequestStatus::Approved) {
            return response()->json(['success' => false, 'message' => 'Request must be approved first.'], 422);
        }

        $req->update([
            'status' => 'dispatched',
            'dispatched_at' => now(),
        ]);

        $req->marketer->notify(new MarketerSampleDispatched($req));

        return response()->json(['success' => true, 'message' => 'Sample marked as dispatched.']);
    }

    public function rejectSample(Request $httpRequest, MarketerSampleRequest $req): JsonResponse
    {
        if ($req->status !== MarketerSampleRequestStatus::Requested) {
            return response()->json(['success' => false, 'message' => 'Only pending requests can be rejected.'], 422);
        }

        $validated = $httpRequest->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        $req->update([
            'status' => 'rejected',
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        $req->marketer->notify(new MarketerSampleRequestRejected($req));

        return response()->json(['success' => true, 'message' => 'Sample request rejected.']);
    }

    public function showSample(MarketerSampleRequest $req): JsonResponse
    {
        $req->load([
            'marketer',
            'vendor',
            'campaign',
            'items.vendorListing.productVariant.product.category',
        ]);

        return response()->json([
            'id' => $req->id,
            'marketer' => $req->marketer->name,
            'vendor' => $req->vendor->store_name,
            'campaign' => $req->campaign?->name,
            'status' => $req->status?->value,
            'notes' => $req->notes,
            'rejection_reason' => $req->rejection_reason,
            'created_at' => $req->created_at->format('d M Y H:i'),
            'items' => $req->items->map(function ($item) {
                $variant = $item->vendorListing?->productVariant;
                $product = $variant?->product;
                $category = $product?->category;
                return [
                    'id' => $item->id,
                    'product_name' => $product?->name_en ?? '—',
                    'variant_name' => $variant?->variant_name,
                    'category_id' => $category?->id,
                    'category_name' => $category?->name_en ?? 'Uncategorised',
                    'category_admin_quota' => $category?->admin_sample_quota ?? 0,
                    'quantity' => $item->quantity,
                    'marketer_quantity' => $item->marketer_quantity,
                    'admin_quantity' => $item->admin_quantity,
                    'is_mandatory' => $item->is_mandatory,
                    'cost' => $item->sample_cost_cents ? number_format($item->sample_cost_cents / 100, 2) : null,
                ];
            }),
        ]);
    }

    private function sampleActions(object $row): string
    {
        $html = '<div class="flex gap-1">';
        $html .= '<button type="button" data-id="' . $row->id . '" class="btn btn-xs btn-secondary btn-view-sample">Details</button>';
        if ($row->status === MarketerSampleRequestStatus::Requested) {
            $html .= '<button type="button" data-id="' . $row->id . '" class="btn btn-xs btn-success btn-approve-sample">Approve</button>';
            $html .= '<button type="button" data-id="' . $row->id . '" class="btn btn-xs btn-danger btn-reject-sample">Reject</button>';
        } elseif ($row->status === MarketerSampleRequestStatus::Approved) {
            $html .= '<button type="button" data-id="' . $row->id . '" class="btn btn-xs btn-primary btn-dispatch-sample">Dispatch</button>';
        }
        $html .= '</div>';
        return $html;
    }
}
