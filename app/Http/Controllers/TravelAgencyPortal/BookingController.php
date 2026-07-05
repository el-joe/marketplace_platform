<?php

namespace App\Http\Controllers\TravelAgencyPortal;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\TravelBooking;
use App\Models\TravelPackage;
use App\Models\TravelPackageInquiry;
use App\Notifications\Customer\TravelBookingCancelled as CustomerTravelBookingCancelled;
use App\Notifications\Customer\TravelBookingConfirmed;
use App\Notifications\TravelAgency\LowSeatsRemaining;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BookingController extends Controller
{
    private function agencyId(): string
    {
        return Auth::guard('travel_agency')->id();
    }

    // ── Customer search (AJAX) ────────────────────────────────────────────────

    public function customerSearch(Request $request): JsonResponse
    {
        $term = trim($request->query('q', ''));

        if (strlen($term) < 2) {
            return response()->json([]);
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

        return response()->json($customers);
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public function create(Request $request): View
    {
        $packages = TravelPackage::where('travel_agency_id', $this->agencyId())
            ->where('status', 'active')
            ->orderBy('departure_date')
            ->get()
            ->filter(fn (TravelPackage $pkg) => $pkg->available_seats === null || $pkg->seatsRemaining() > 0)
            ->values();

        $selectedPackageId = old('travel_package_id', $request->query('package_id'));

        $package = $selectedPackageId
            ? $packages->firstWhere('id', $selectedPackageId)
            : null;

        return view('travel-agency.bookings.create', compact('packages', 'package'));
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function store(Request $request): RedirectResponse
    {
        $mode = $request->input('customer_mode', 'existing');

        $rules = [
            'travel_package_id' => [
                'required',
                'uuid',
                Rule::exists('travel_packages', 'id')->where('travel_agency_id', $this->agencyId()),
            ],
            'travelers_count' => ['required', 'integer', 'min:1'],
            'customer_mode'   => ['required', 'in:existing,new'],
        ];

        if ($mode === 'existing') {
            $rules['customer_id'] = ['required', 'uuid', 'exists:customers,id'];
        } else {
            $rules['new_name']  = ['required', 'string', 'max:255'];
            $rules['new_phone'] = ['required', 'string', 'max:30'];
            $rules['new_email'] = ['required', 'email', 'max:255', 'unique:customers,email'];
        }

        $validated = $request->validate($rules);

        $booking = DB::transaction(function () use ($validated, $mode) {
            $pkg = TravelPackage::lockForUpdate()->findOrFail($validated['travel_package_id']);

            $this->authorisePackage($pkg);

            if ($pkg->status !== 'active') {
                throw ValidationException::withMessages(['travelers_count' => 'الباقة لم تعد نشطة.']);
            }

            if ($pkg->available_seats !== null
                && ($pkg->seats_booked + $validated['travelers_count']) > $pkg->available_seats
            ) {
                throw ValidationException::withMessages(['travelers_count' => 'لا توجد مقاعد كافية متاحة.']);
            }

            if ($mode === 'existing') {
                $customer = Customer::findOrFail($validated['customer_id']);
            } else {
                $customer = Customer::create([
                    'name'     => $validated['new_name'],
                    'email'    => $validated['new_email'],
                    'phone'    => $validated['new_phone'],
                    'password' => Str::random(32), // agency-created; customer sets own password via forgot-password
                    'status'   => 'active',
                ]);
            }

            $booking = TravelBooking::create([
                'travel_package_id'  => $pkg->id,
                'customer_id'        => $customer->id,
                'travelers_count'    => $validated['travelers_count'],
                'total_price_cents'  => $pkg->price_cents * $validated['travelers_count'],
                'status'             => 'pending_documents',
            ]);

            $pkg->increment('seats_booked', $validated['travelers_count']);

            return $booking;
        });

        // Link inquiry → booking if this booking originated from a lead inquiry
        if ($inquiryId = $request->input('from_inquiry')) {
            $inquiry = TravelPackageInquiry::where('id', $inquiryId)
                ->whereHas('package', fn ($q) => $q->where('travel_agency_id', $this->agencyId()))
                ->where('status', '!=', 'converted')
                ->first();

            $inquiry?->update([
                'status'                 => 'converted',
                'converted_to_booking_id' => $booking->id,
            ]);
        }

        return redirect()
            ->route('travel-agency.bookings.show', $booking)
            ->with('success', 'تم إنشاء الحجز بنجاح.');
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

    // ── Auth guards ───────────────────────────────────────────────────────────

    private function authorise(TravelBooking $booking): void
    {
        if ($booking->package->travel_agency_id !== $this->agencyId()) {
            abort(403);
        }
    }

    private function authorisePackage(TravelPackage $package): void
    {
        if ($package->travel_agency_id !== $this->agencyId()) {
            abort(403);
        }
    }
}
