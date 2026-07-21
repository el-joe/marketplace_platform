<?php

namespace App\Http\Controllers\TravelAgencyPortal;

use App\Http\Controllers\Controller;
use App\Models\TravelAgencyChangeRequest;
use App\Models\TravelAgencyMember;
use App\Services\TravelAgencyChangeRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ChangeRequestController extends Controller
{
    public function __construct(private readonly TravelAgencyChangeRequestService $changeRequests) {}

    private function member(): TravelAgencyMember
    {
        return Auth::guard('travel_agency')->user();
    }

    public function index(): View
    {
        abort_unless(config('features.travel_agency_bank_accounts', false), 404);

        $requests = $this->member()->travelAgency->changeRequests()
            ->latest()
            ->paginate(20);

        return view('travel-agency.change-requests.index', compact('requests'));
    }

    public function cancel(TravelAgencyChangeRequest $changeRequest): JsonResponse
    {
        abort_unless(config('features.travel_agency_bank_accounts', false), 404);

        $member = $this->member();

        try {
            $this->changeRequests->cancelRequest($changeRequest, $member);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => __('travel.change_requests.cancelled'),
        ]);
    }
}
