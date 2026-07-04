<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Country;
use App\Models\Marketer;
use App\Models\MarketerCampaign;
use App\Models\VendorCampaignInvitation;
use App\Models\VendorCampaignOffer;
use App\Models\VendorCampaignOfferProduct;
use App\Models\VendorListing;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Notifications\Admin\VendorCampaignOfferSubmitted;
use App\Notifications\Marketer\VendorCampaignInvitationReceived;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class CampaignOfferController extends Controller
{
    private function vendorId(): string
    {
        return Auth::guard('vendor')->user()->vendor_id;
    }

    public function index(): View
    {
        $vendorId = $this->vendorId();

        $offers = VendorCampaignOffer::where('vendor_id', $vendorId)
            ->withCount(['invitations', 'invitations as accepted_count' => fn($q) => $q->where('status', 'accepted')])
            ->latest()
            ->get();

        $stats = [
            'draft'       => $offers->where('status', 'draft')->count(),
            'active'      => $offers->where('status', 'active')->count(),
            'invited'     => $offers->sum('invitations_count'),
            'accepted'    => $offers->sum('accepted_count'),
            'conversions' => VendorCampaignOffer::where('vendor_id', $vendorId)
                ->join('vendor_campaign_invitations', 'vendor_campaign_offers.id', '=', 'vendor_campaign_invitations.vendor_campaign_offer_id')
                ->join('marketer_campaigns', 'vendor_campaign_invitations.resulting_campaign_id', '=', 'marketer_campaigns.id')
                ->join('marketer_conversions', 'marketer_campaigns.id', '=', 'marketer_conversions.marketer_campaign_id')
                ->count('marketer_conversions.id'),
        ];

        return view('partner.campaign-offers.index', compact('offers', 'stats'));
    }

    public function create(): View
    {
        $countries = Country::orderBy('name_en')->get(['id', 'name_ar', 'name_en']);

        return view('partner.campaign-offers.create', compact('countries'));
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $vendorId = $this->vendorId();

        $validated = $request->validate([
            'name'                                  => 'required|string|max:255',
            'description'                           => 'nullable|string|max:2000',
            'requirements'                          => 'nullable|string|max:2000',
            'campaign_type'                         => 'required|in:product_promotion,store_promotion,brand_deal,product_specific,flash_sale',
            'offered_commission_rate'               => 'required|numeric|min:0|max:100',
            'commission_type'                       => 'required|in:percentage,flat_per_order,flat_per_click',
            'budget_per_marketer_cents_display'     => 'nullable|numeric|min:0',
            'total_budget_cents_display'            => 'nullable|numeric|min:0',
            'starts_at'                             => 'required|date|after_or_equal:today',
            'ends_at'                               => 'required|date|after:starts_at',
            'invitation_deadline'                   => 'nullable|date|before:starts_at',
            'attribution_model'                     => 'required|in:last_click,first_click,linear',
            'whatsapp_sharing_enabled'              => 'boolean',
            'listing_ids'                           => 'required|array|min:1',
            'listing_ids.*'                         => 'uuid',
            'commission_overrides'                  => 'nullable|array',
            'commission_overrides.*.listing_id'     => 'uuid',
            'commission_overrides.*.rate'           => 'numeric|min:0|max:100',
        ]);

        // Validate all listing_ids belong to this vendor
        $listingCount = VendorListing::where('vendor_id', $vendorId)
            ->whereIn('id', $validated['listing_ids'])
            ->count();

        if ($listingCount !== count($validated['listing_ids'])) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'أحد المنتجات المحددة لا ينتمي لمتجرك.'], 422);
            }
            return back()->withErrors(['listing_ids' => 'أحد المنتجات المحددة لا ينتمي لمتجرك.'])->withInput();
        }

        $overridesMap = collect($validated['commission_overrides'] ?? [])
            ->keyBy('listing_id');

        $offer = null;

        DB::transaction(function () use ($validated, $vendorId, $overridesMap, &$offer) {
            $offer = VendorCampaignOffer::create([
                'vendor_id'                  => $vendorId,
                'name'                       => $validated['name'],
                'description'                => $validated['description'] ?? null,
                'requirements'               => $validated['requirements'] ?? null,
                'campaign_type'              => $validated['campaign_type'],
                'offered_commission_rate'    => $validated['offered_commission_rate'],
                'commission_type'            => $validated['commission_type'],
                'budget_per_marketer_cents'  => isset($validated['budget_per_marketer_cents_display'])
                    ? (int) round($validated['budget_per_marketer_cents_display'] * 100) : null,
                'total_budget_cents'         => isset($validated['total_budget_cents_display'])
                    ? (int) round($validated['total_budget_cents_display'] * 100) : null,
                'starts_at'                  => $validated['starts_at'],
                'ends_at'                    => $validated['ends_at'],
                'invitation_deadline'        => $validated['invitation_deadline'] ?? null,
                'attribution_model'          => $validated['attribution_model'],
                'whatsapp_sharing_enabled'   => $validated['whatsapp_sharing_enabled'] ?? false,
                'status'                     => 'draft',
            ]);

            foreach ($validated['listing_ids'] as $i => $listingId) {
                VendorCampaignOfferProduct::create([
                    'vendor_campaign_offer_id' => $offer->id,
                    'vendor_listing_id'        => $listingId,
                    'position'                 => $i + 1,
                    'commission_override'      => $overridesMap->get($listingId)['rate'] ?? null,
                ]);
            }
        });

        return redirect()->route('partner.campaign-offers.show', $offer->id)
            ->with('success', 'تم إنشاء العرض التسويقي. يمكنك الآن دعوة المسوّقين.');
    }

    public function show(VendorCampaignOffer $offer): View
    {
        abort_if($offer->vendor_id !== $this->vendorId(), 404);

        $offer->load([
            'products.listing.productVariant.product',
            'invitations.marketer',
            'invitations.resultingCampaign',
        ]);

        $invitationStats = [
            'invited'  => $offer->invitations->count(),
            'accepted' => $offer->invitations->where('status', 'accepted')->count(),
            'declined' => $offer->invitations->whereIn('status', ['declined', 'expired', 'revoked'])->count(),
        ];

        return view('partner.campaign-offers.show', compact('offer', 'invitationStats'));
    }

    public function submitForReview(VendorCampaignOffer $offer): JsonResponse
    {
        abort_if($offer->vendor_id !== $this->vendorId(), 404);

        if ($offer->status !== 'draft') {
            return response()->json(['success' => false, 'message' => 'يمكن تقديم العروض ذات حالة مسودة فقط.'], 422);
        }

        $offer->update(['status' => 'pending_admin']);

        Notification::send(Admin::permission('campaign_offers.view')->get(), new VendorCampaignOfferSubmitted($offer));

        return response()->json(['success' => true, 'message' => 'تم إرسال العرض للمراجعة.', 'status' => 'pending_admin']);
    }

    public function pauseOffer(VendorCampaignOffer $offer): JsonResponse
    {
        abort_if($offer->vendor_id !== $this->vendorId(), 404);

        if ($offer->status !== 'active') {
            return response()->json(['success' => false, 'message' => 'العرض غير نشط حالياً.'], 422);
        }

        $offer->update(['status' => 'paused']);

        MarketerCampaign::whereIn('id', $offer->invitations()
            ->where('status', 'accepted')
            ->pluck('resulting_campaign_id'))
            ->update(['status' => 'paused']);

        return response()->json(['success' => true, 'message' => 'تم إيقاف العرض مؤقتاً.', 'status' => 'paused']);
    }

    public function resumeOffer(VendorCampaignOffer $offer): JsonResponse
    {
        abort_if($offer->vendor_id !== $this->vendorId(), 404);

        if ($offer->status !== 'paused') {
            return response()->json(['success' => false, 'message' => 'العرض ليس متوقفاً.'], 422);
        }

        $offer->update(['status' => 'active']);

        MarketerCampaign::whereIn('id', $offer->invitations()
            ->where('status', 'accepted')
            ->pluck('resulting_campaign_id'))
            ->update(['status' => 'active']);

        return response()->json(['success' => true, 'message' => 'تم استئناف العرض.', 'status' => 'active']);
    }

    public function invite(Request $request, VendorCampaignOffer $offer): JsonResponse
    {
        abort_if($offer->vendor_id !== $this->vendorId(), 404);

        if (!in_array($offer->status, ['active', 'draft', 'pending_admin'])) {
            return response()->json(['success' => false, 'message' => 'لا يمكن دعوة مسوّقين لهذا العرض في حالته الحالية.'], 422);
        }

        $validated = $request->validate([
            'marketer_ids'   => 'required|array|min:1|max:50',
            'marketer_ids.*' => 'uuid|exists:marketers,id',
            'vendor_note'    => 'nullable|string|max:1000',
        ]);

        $activeMarketers = Marketer::whereIn('id', $validated['marketer_ids'])
            ->where('status', 'active')
            ->pluck('id')
            ->all();

        $skipped = array_diff($validated['marketer_ids'], $activeMarketers);
        $created = 0;

        foreach ($activeMarketers as $marketerId) {
            $invitation = VendorCampaignInvitation::firstOrCreate(
                ['vendor_campaign_offer_id' => $offer->id, 'marketer_id' => $marketerId],
                [
                    'status'      => 'pending',
                    'vendor_note' => $validated['vendor_note'] ?? null,
                    'expires_at'  => $offer->invitation_deadline,
                ]
            );

            if ($invitation->wasRecentlyCreated) {
                $created++;
                // Only fan-out immediately when the offer is already active;
                // pending_admin offers send invitations on approval instead.
                if ($offer->status === 'active') {
                    Notification::send($invitation->marketer, new VendorCampaignInvitationReceived($invitation));
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => "تمت دعوة {$created} مسوّق" . ($created !== 1 ? 'ين' : '') . '.',
            'skipped' => count($skipped),
        ]);
    }

    public function revokeInvitation(VendorCampaignInvitation $invitation): JsonResponse
    {
        abort_if($invitation->offer->vendor_id !== $this->vendorId(), 404);

        if ($invitation->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'لا يمكن سحب دعوة تمت الاستجابة عليها.'], 422);
        }

        $invitation->update(['status' => 'revoked']);

        return response()->json(['success' => true, 'message' => 'تم سحب الدعوة.']);
    }

    public function searchMarketers(Request $request): JsonResponse
    {
        $q    = $request->input('q', '');
        $type  = $request->input('type');
        $niche = $request->input('niche');

        return response()->json(
            Marketer::where('status', 'active')
                ->where(fn($query) => $query
                    ->where('name', 'like', "%{$q}%")
                    ->orWhere('niche', 'like', "%{$q}%"))
                ->when($type, fn($q, $t) => $q->where('type', $t))
                ->when($niche, fn($q, $n) => $q->where('niche', 'like', "%{$n}%"))
                ->select(['id', 'name', 'type', 'niche', 'followers_count', 'profile_photo_path', 'commission_rate'])
                ->limit(20)
                ->get()
                ->map(fn($m) => [
                    'id'             => $m->id,
                    'name'           => $m->name,
                    'type'           => ucfirst($m->type),
                    'niche'          => $m->niche,
                    'followers'      => $m->followers_count
                        ? number_format($m->followers_count) . ' followers'
                        : null,
                    'avatar_initial' => mb_substr($m->name, 0, 1),
                    'photo_url'      => $m->profile_photo_path
                        ? Storage::url($m->profile_photo_path)
                        : null,
                ])
        );
    }
}
