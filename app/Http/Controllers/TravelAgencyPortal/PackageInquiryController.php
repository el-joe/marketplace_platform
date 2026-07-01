<?php

namespace App\Http\Controllers\TravelAgencyPortal;

use App\Http\Controllers\Controller;
use App\Models\TravelPackage;
use App\Models\TravelPackageInquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PackageInquiryController extends Controller
{
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
            $query->where('status', $status);
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

        if (!in_array($inquiry->status, ['new'])) {
            return back()->withErrors(['status' => 'لا يمكن تغيير حالة هذا الطلب.']);
        }

        $inquiry->update([
            'status'       => 'contacted',
            'contacted_at' => now(),
        ]);

        return back()->with('success', 'تم تحديث الحالة: تم التواصل.');
    }

    // ── Convert to Booking ───────────────────────────────────────────────────

    public function convertToBooking(TravelPackageInquiry $inquiry): RedirectResponse
    {
        $this->authorise($inquiry);

        if (!in_array($inquiry->status, ['new', 'contacted'])) {
            return back()->withErrors(['status' => 'هذا الطلب لا يمكن تحويله لحجز.']);
        }

        return redirect()->route('travel-agency.bookings.create', $inquiry->package)
            ->with('prefill_inquiry', $inquiry->id)
            ->withInput([
                'from_inquiry'    => $inquiry->id,
                'customer_mode'   => 'new',
                'new_name'        => $inquiry->name,
                'new_phone'       => $inquiry->phone,
                'travelers_count' => $inquiry->travelers_count ?? 1,
            ]);
    }

    // ── Close ─────────────────────────────────────────────────────────────────

    public function close(Request $request, TravelPackageInquiry $inquiry): RedirectResponse
    {
        $this->authorise($inquiry);

        if (!in_array($inquiry->status, ['new', 'contacted'])) {
            return back()->withErrors(['status' => 'لا يمكن إغلاق هذا الطلب.']);
        }

        $inquiry->update(['status' => 'closed']);

        return back()->with('success', 'تم إغلاق الطلب.');
    }
}
