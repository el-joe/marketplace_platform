<?php

namespace App\Http\Controllers\TravelAgencyPortal;

use App\Http\Controllers\Controller;
use App\Models\TravelBooking;
use App\Models\TravelPackage;
use App\Notifications\Customer\TravelBookingCancelled as CustomerTravelBookingCancelled;
use App\Notifications\Customer\TravelBookingConfirmed;
use App\Notifications\TravelAgency\LowSeatsRemaining;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BookingController extends Controller
{
    private function agencyId(): string
    {
        return Auth::guard('travel_agency')->id();
    }

    // ── Index ─────────────────────────────────────────────────────────────────

    public function index(Request $request): View
    {
        $query = TravelBooking::query()
            ->whereHas('package', fn ($q) => $q->where('travel_agency_id', $this->agencyId()))
            ->with(['package', 'customer']);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $bookings = $query->latest()->paginate(25)->withQueryString();

        return view('travel-agency.bookings.index', compact('bookings'));
    }

    // ── Show ──────────────────────────────────────────────────────────────────

    public function show(TravelBooking $booking): View
    {
        $this->authorise($booking);
        $booking->load(['package', 'customer']);

        return view('travel-agency.bookings.show', compact('booking'));
    }

    // ── Status update (confirm / cancel) ─────────────────────────────────────

    public function updateStatus(Request $request, TravelBooking $booking): RedirectResponse
    {
        $this->authorise($booking);

        $request->validate([
            'status' => ['required', 'in:confirmed,cancelled'],
        ]);

        $allowed = match ($request->status) {
            'confirmed' => in_array($booking->status, ['pending_documents']),
            'cancelled' => in_array($booking->status, ['pending_documents', 'confirmed']),
            default     => false,
        };

        if (!$allowed) {
            return back()->withErrors(['status' => 'لا يمكن تغيير حالة هذا الحجز.']);
        }

        DB::transaction(function () use ($booking, $request) {
            if ($request->status === 'confirmed') {
                $pkg = TravelPackage::lockForUpdate()->findOrFail($booking->travel_package_id);

                if ($pkg->available_seats !== null
                    && ($pkg->seats_booked + $booking->travelers_count) > $pkg->available_seats
                ) {
                    abort(422, 'لا توجد مقاعد كافية لتأكيد هذا الحجز.');
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

        $booking->loadMissing('customer');

        if ($request->status === 'confirmed') {
            $booking->customer?->notify(new TravelBookingConfirmed($booking));
        } elseif ($request->status === 'cancelled') {
            $booking->customer?->notify(new CustomerTravelBookingCancelled($booking, 'agency'));
        }

        $label = $request->status === 'confirmed' ? 'تم تأكيد الحجز.' : 'تم إلغاء الحجز.';

        return back()->with('success', $label);
    }

    // ── Auth guard ────────────────────────────────────────────────────────────

    private function authorise(TravelBooking $booking): void
    {
        if ($booking->package->travel_agency_id !== $this->agencyId()) {
            abort(403);
        }
    }
}
