<?php

namespace App\Http\Controllers\TravelAgencyPortal;

use App\Enums\TravelPackageInquiryStatus;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\TravelPackage;
use App\Models\TravelPackageInquiry;
use App\Services\TravelAgency\BookingCreationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PackageInquiryController extends Controller
{
    public function __construct(private readonly BookingCreationService $bookingCreationService)
    {
    }

    private function agencyId(): string
    {
        return Auth::guard('travel_agency')->id();
    }

    private function authorise(TravelPackageInquiry $inquiry): void
    {
        if ($inquiry->package->travel_agency_id !== $this->agencyId()) {
            abort(403);
        }
    }

    // ── Index — all inquiries across the agency's packages ───────────────────

    public function index(Request $request): View
    {
        $query = TravelPackageInquiry::query()
            ->whereHas('package', fn ($q) => $q->where('travel_agency_id', $this->agencyId()))
            ->with('package')
            ->latest();

        if ($status = $request->query('status')) {
            $query->where('status', TravelPackageInquiryStatus::from($status));
        }

        if ($packageId = $request->query('package_id')) {
            $query->where('travel_package_id', $packageId);
        }

        $inquiries = $query->paginate(30)->withQueryString();

        $packages = TravelPackage::where('travel_agency_id', $this->agencyId())
            ->orderBy('title_ar')
            ->get(['id', 'title_ar', 'title_en']);

        return view('travel-agency.inquiries.index', compact('inquiries', 'packages'));
    }

    // ── Mark Contacted ───────────────────────────────────────────────────────

    public function markContacted(TravelPackageInquiry $inquiry): RedirectResponse
    {
        $this->authorise($inquiry);

        if (!in_array($inquiry->status, [TravelPackageInquiryStatus::New])) {
            return back()->withErrors(['status' => 'لا يمكن تغيير حالة هذا الطلب.']);
        }

        $inquiry->update([
            'status'       => TravelPackageInquiryStatus::Contacted,
            'contacted_at' => now(),
        ]);

        return back()->with('success', 'تم تحديث الحالة: تم التواصل.');
    }

    // ── Convert to Booking ───────────────────────────────────────────────────

    public function convertToBooking(TravelPackageInquiry $inquiry): RedirectResponse
    {
        $this->authorise($inquiry);

        if (!in_array($inquiry->status, [TravelPackageInquiryStatus::New, TravelPackageInquiryStatus::Contacted])) {
            return back()->withErrors(['status' => 'هذا الطلب لا يمكن تحويله لحجز.']);
        }

        $customer = $inquiry->email ? Customer::where('email', $inquiry->email)->first() : null;
        $customer ??= Customer::where('phone', $inquiry->phone)->first();

        if ($customer) {
            $data = [
                'travel_package_id' => $inquiry->travel_package_id,
                'travelers_count'   => $inquiry->travelers_count ?? 1,
                'customer_mode'     => 'existing',
                'customer_id'       => $customer->id,
            ];
        } elseif ($inquiry->email) {
            $data = [
                'travel_package_id' => $inquiry->travel_package_id,
                'travelers_count'   => $inquiry->travelers_count ?? 1,
                'customer_mode'     => 'new',
                'new_name'          => $inquiry->name,
                'new_phone'         => $inquiry->phone,
                'new_email'         => $inquiry->email,
            ];
        } else {
            return back()->withErrors([
                'email' => 'لا يمكن تحويل هذا الطلب لحجز: لا يوجد بريد إلكتروني لإنشاء عميل جديد.',
            ]);
        }

        $booking = $this->bookingCreationService->create($this->agencyId(), $data);

        $inquiry->update([
            'status'                  => TravelPackageInquiryStatus::Converted,
            'converted_to_booking_id' => $booking->id,
        ]);

        return redirect()
            ->route('travel-agency.bookings.show', $booking)
            ->with('success', 'تم تحويل الطلب إلى حجز بنجاح.');
    }

    // ── Close ─────────────────────────────────────────────────────────────────

    public function close(Request $request, TravelPackageInquiry $inquiry): RedirectResponse
    {
        $this->authorise($inquiry);

        if (!in_array($inquiry->status, [TravelPackageInquiryStatus::New, TravelPackageInquiryStatus::Contacted])) {
            return back()->withErrors(['status' => 'لا يمكن إغلاق هذا الطلب.']);
        }

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $inquiry->update([
            'status'       => TravelPackageInquiryStatus::Closed,
            'close_reason' => $validated['reason'] ?? null,
        ]);

        return back()->with('success', 'تم إغلاق الطلب.');
    }
}
