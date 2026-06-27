<?php

namespace App\Http\Controllers\TravelAgencyPortal;

use App\Http\Controllers\Controller;
use App\Models\TravelBooking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        $booking->update(['status' => $request->status]);

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
