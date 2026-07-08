<?php

namespace App\Http\Controllers\Api\TravelAgencyPortal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\TravelAgencyPortal\Booking\StoreBookingRequest;
use App\Http\Requests\Api\TravelAgencyPortal\Booking\UpdateStatusRequest;
use App\Http\Resources\Api\TravelAgencyPortal\TravelBookingResource;
use App\Http\Responses\ApiResponse;
use App\Models\Customer;
use App\Models\TravelBooking;
use App\Models\TravelPackage;
use App\Models\TravelPackageInquiry;
use App\Notifications\Customer\TravelBookingCancelled as CustomerTravelBookingCancelled;
use App\Notifications\Customer\TravelBookingConfirmed;
use App\Notifications\TravelAgency\LowSeatsRemaining;
use App\Services\TravelAgency\BookingCreationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    public function __construct(private readonly BookingCreationService $bookingCreationService)
    {
    }

    private function agencyId(): string
    {
        return Auth::guard('travel_agencies')->id();
    }

    private function authorise(TravelBooking $booking): void
    {
        abort_if($booking->package->travel_agency_id !== $this->agencyId(), 403);
    }

    // ── Customer search ──────────────────────────────────────────────────────

    public function customerSearch(Request $request): JsonResponse
    {
        $term = trim($request->query('q', ''));

        if (strlen($term) < 2) {
            return ApiResponse::success([]);
        }

        $customers = Customer::query()
            ->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%");
            })
            ->select('id', 'name', 'email', 'phone')
            ->limit(10)
            ->get();

        return ApiResponse::success($customers);
    }

    // ── Index ─────────────────────────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $query = TravelBooking::query()
            ->whereHas('package', fn ($q) => $q->where('travel_agency_id', $this->agencyId()))
            ->with(['package', 'customer']);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($dateFrom = $request->query('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->query('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $paginator = $query->latest()->paginate($request->integer('per_page', 25));

        return ApiResponse::paginated($paginator, TravelBookingResource::class);
    }

    // ── Show ──────────────────────────────────────────────────────────────────

    public function show(TravelBooking $booking): JsonResponse
    {
        $this->authorise($booking);
        $booking->load(['package', 'customer']);

        return ApiResponse::success(new TravelBookingResource($booking));
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function store(StoreBookingRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $booking = $this->bookingCreationService->create($this->agencyId(), $validated);

        if (! empty($validated['from_inquiry'])) {
            $inquiry = TravelPackageInquiry::where('id', $validated['from_inquiry'])
                ->whereHas('package', fn ($q) => $q->where('travel_agency_id', $this->agencyId()))
                ->where('status', '!=', 'converted')
                ->first();

            $inquiry?->update([
                'status' => 'converted',
                'converted_to_booking_id' => $booking->id,
            ]);
        }

        $booking->load(['package', 'customer']);

        return ApiResponse::success(new TravelBookingResource($booking), 'Booking created.', 201);
    }

    // ── Status update (confirm / cancel) ─────────────────────────────────────

    public function updateStatus(UpdateStatusRequest $request, TravelBooking $booking): JsonResponse
    {
        $this->authorise($booking);

        $allowed = match ($request->status) {
            'confirmed' => in_array($booking->status, ['pending_documents']),
            'cancelled' => in_array($booking->status, ['pending_documents', 'confirmed']),
            default => false,
        };

        if (! $allowed) {
            return ApiResponse::error('This booking status cannot be changed.', [], 422);
        }

        DB::transaction(function () use ($booking, $request) {
            if ($request->status === 'confirmed') {
                $pkg = TravelPackage::lockForUpdate()->findOrFail($booking->travel_package_id);

                if ($pkg->available_seats !== null
                    && ($pkg->seats_booked + $booking->travelers_count) > $pkg->available_seats
                ) {
                    abort(422, 'Not enough seats available to confirm this booking.');
                }

                $pkg->increment('seats_booked', $booking->travelers_count);
                $pkg->refresh();

                if ($pkg->available_seats !== null
                    && $pkg->seats_booked >= $pkg->available_seats
                ) {
                    $pkg->update(['status' => 'sold_out']);
                } elseif ($pkg->available_seats !== null) {
                    $remaining = $pkg->available_seats - $pkg->seats_booked;
                    if ($remaining > 0 && $remaining <= 3) {
                        $pkg->agency->notify(new LowSeatsRemaining($pkg, $remaining));
                    }
                }
            }

            if ($request->status === 'cancelled' && $booking->status === 'confirmed') {
                $booking->package()->increment('seats_booked', -$booking->travelers_count);
            }

            $booking->update(['status' => $request->status]);
        });

        $booking->loadMissing(['package', 'customer']);

        if ($request->status === 'confirmed') {
            $booking->customer?->notify(new TravelBookingConfirmed($booking));
        } elseif ($request->status === 'cancelled') {
            $booking->customer?->notify(new CustomerTravelBookingCancelled($booking, 'agency'));
        }

        return ApiResponse::success(new TravelBookingResource($booking), 'Booking status updated.');
    }
}
