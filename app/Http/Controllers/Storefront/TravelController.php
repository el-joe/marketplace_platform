<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\TravelBooking;
use App\Models\TravelPackage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TravelController extends Controller
{
    // ── Index — search ────────────────────────────────────────────────────────

    public function index(Request $request): View
    {
        $query = TravelPackage::query()
            ->where('status', 'active')
            ->with(['agency', 'media'])
            ->when($request->input('country'), fn($q, $v) => $q->where('destination_country', $v))
            ->when($request->input('city'), fn($q, $v) => $q->where('destination_city', 'like', "%{$v}%"))
            ->when($request->input('departure_from'), fn($q, $v) => $q->whereDate('departure_date', '>=', $v))
            ->when($request->input('departure_to'), fn($q, $v) => $q->whereDate('departure_date', '<=', $v))
            ->when($request->input('max_price'), fn($q, $v) => $q->where('price_cents', '<=', (int) $v * 100))
            ->when($request->input('min_days'), fn($q, $v) => $q->where('duration_days', '>=', (int) $v));

        $packages = $query->orderBy('departure_date')->paginate(16)->withQueryString();

        $countries = TravelPackage::where('status', 'active')
            ->distinct()
            ->orderBy('destination_country')
            ->pluck('destination_country');

        return view('storefront.travel.index', compact('packages', 'countries'));
    }

    // ── Show ──────────────────────────────────────────────────────────────────

    public function show(TravelPackage $package): View
    {
        if ($package->status !== 'active') {
            abort(404);
        }

        $package->load(['agency', 'media']);

        return view('storefront.travel.show', compact('package'));
    }

    // ── Book form ─────────────────────────────────────────────────────────────

    public function bookForm(TravelPackage $package): View
    {
        if ($package->status !== 'active') {
            abort(404);
        }

        $seats = $package->seatsRemaining();
        if ($seats !== null && $seats === 0) {
            return redirect()->route('travel.show', $package)
                ->withErrors(['seats' => 'This package is sold out.']);
        }

        return view('storefront.travel.book', compact('package'));
    }

    // ── Book ──────────────────────────────────────────────────────────────────

    public function book(Request $request, TravelPackage $package): RedirectResponse
    {
        if ($package->status !== 'active') {
            abort(404);
        }

        $request->validate([
            'travelers_count'        => ['required', 'integer', 'min:1', 'max:20'],
            'passport_file'          => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'contract_signature_data' => ['required', 'string'],
        ]);

        $count    = (int) $request->input('travelers_count');
        $total    = $package->price_cents * $count;

        $passportPath = $request->file('passport_file')
            ->store("travel-bookings/passports", 'public');

        $booking = TravelBooking::create([
            'travel_package_id'       => $package->id,
            'customer_id'             => Auth::guard('web')->id(),
            'travelers_count'         => $count,
            'total_price_cents'       => $total,
            'passport_file_path'      => $passportPath,
            'contract_signed_at'      => now(),
            'contract_signature_data' => $request->input('contract_signature_data'),
            'status'                  => 'confirmed',
        ]);

        // Increment seats booked
        $package->increment('seats_booked', $count);

        // Check sold out
        if ($package->available_seats !== null
            && $package->seats_booked >= $package->available_seats
        ) {
            $package->update(['status' => 'sold_out']);
        }

        return redirect()->route('travel.booking.confirmed', $booking)
            ->with('success', 'Booking confirmed! Reference: ' . $booking->booking_number);
    }

    // ── Booking confirmation ───────────────────────────────────────────────────

    public function bookingConfirmed(TravelBooking $booking): View
    {
        if ($booking->customer_id !== Auth::guard('web')->id()) {
            abort(403);
        }

        $booking->load('package.agency');

        return view('storefront.travel.confirmed', compact('booking'));
    }
}
