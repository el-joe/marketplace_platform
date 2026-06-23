<?php

namespace App\Http\Controllers\TravelAgencyPortal;

use App\Http\Controllers\Controller;
use App\Models\TravelPackage;
use App\Models\TravelPackageMedia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PackageController extends Controller
{
    private function agencyId(): string
    {
        return Auth::guard('travel_agency')->id();
    }

    private function authorise(TravelPackage $package): void
    {
        if ($package->travel_agency_id !== $this->agencyId()) {
            abort(403);
        }
    }

    // ── Index ─────────────────────────────────────────────────────────────────

    public function index(): View
    {
        $packages = TravelPackage::where('travel_agency_id', $this->agencyId())
            ->with('media')
            ->latest()
            ->paginate(20);

        return view('travel-agency.packages.index', compact('packages'));
    }

    // ── Create / Store ────────────────────────────────────────────────────────

    public function create(): View
    {
        return view('travel-agency.packages.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title_en'           => ['required', 'string', 'max:255'],
            'title_ar'           => ['required', 'string', 'max:255'],
            'description_en'     => ['nullable', 'string'],
            'description_ar'     => ['nullable', 'string'],
            'destination_country' => ['required', 'string', 'max:100'],
            'destination_city'   => ['nullable', 'string', 'max:100'],
            'price_cents'        => ['required', 'integer', 'min:1'],
            'currency'           => ['required', 'string', 'size:3'],
            'duration_days'      => ['required', 'integer', 'min:1'],
            'duration_nights'    => ['required', 'integer', 'min:0'],
            'departure_date'     => ['required', 'date', 'after:today'],
            'return_date'        => ['required', 'date', 'after:departure_date'],
            'available_seats'    => ['nullable', 'integer', 'min:1'],
            'inclusions'         => ['nullable', 'array'],
            'inclusions.*'       => ['string'],
            'media'              => ['nullable', 'array', 'max:10'],
            'media.*'            => ['file', 'mimes:jpg,jpeg,png,webp,mp4,mov', 'max:51200'],
        ]);

        $package = TravelPackage::create([
            ...$data,
            'travel_agency_id' => $this->agencyId(),
            'status'           => 'draft',
        ]);

        $this->handleMediaUploads($request, $package);

        return redirect()->route('travel-agency.packages.show', $package)
            ->with('success', 'Package saved as draft.');
    }

    // ── Show ──────────────────────────────────────────────────────────────────

    public function show(TravelPackage $package): View
    {
        $this->authorise($package);
        $package->load(['media', 'bookings.customer']);

        return view('travel-agency.packages.show', compact('package'));
    }

    // ── Edit / Update ─────────────────────────────────────────────────────────

    public function edit(TravelPackage $package): View
    {
        $this->authorise($package);
        $package->load('media');

        return view('travel-agency.packages.edit', compact('package'));
    }

    public function update(Request $request, TravelPackage $package): RedirectResponse
    {
        $this->authorise($package);

        if (!in_array($package->status, ['draft', 'pending_review'])) {
            return back()->withErrors(['status' => 'Active packages cannot be edited. Contact support.']);
        }

        $data = $request->validate([
            'title_en'           => ['required', 'string', 'max:255'],
            'title_ar'           => ['required', 'string', 'max:255'],
            'description_en'     => ['nullable', 'string'],
            'description_ar'     => ['nullable', 'string'],
            'destination_country' => ['required', 'string', 'max:100'],
            'destination_city'   => ['nullable', 'string', 'max:100'],
            'price_cents'        => ['required', 'integer', 'min:1'],
            'currency'           => ['required', 'string', 'size:3'],
            'duration_days'      => ['required', 'integer', 'min:1'],
            'duration_nights'    => ['required', 'integer', 'min:0'],
            'departure_date'     => ['required', 'date'],
            'return_date'        => ['required', 'date', 'after:departure_date'],
            'available_seats'    => ['nullable', 'integer', 'min:1'],
            'inclusions'         => ['nullable', 'array'],
            'inclusions.*'       => ['string'],
            'media'              => ['nullable', 'array', 'max:10'],
            'media.*'            => ['file', 'mimes:jpg,jpeg,png,webp,mp4,mov', 'max:51200'],
        ]);

        $package->update($data);
        $this->handleMediaUploads($request, $package);

        return redirect()->route('travel-agency.packages.show', $package)
            ->with('success', 'Package updated.');
    }

    // ── Submit for review ─────────────────────────────────────────────────────

    public function submitForReview(TravelPackage $package): RedirectResponse
    {
        $this->authorise($package);

        if ($package->status !== 'draft') {
            return back()->withErrors(['status' => 'Only draft packages can be submitted for review.']);
        }

        $package->update(['status' => 'pending_review']);

        return back()->with('success', 'Package submitted for admin review.');
    }

    // ── Delete media ──────────────────────────────────────────────────────────

    public function destroyMedia(TravelPackage $package, TravelPackageMedia $media): RedirectResponse
    {
        $this->authorise($package);

        Storage::disk('public')->delete($media->file_path);
        $media->delete();

        return back()->with('success', 'Media removed.');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function handleMediaUploads(Request $request, TravelPackage $package): void
    {
        if (!$request->hasFile('media')) {
            return;
        }

        $position = $package->media()->max('position') ?? 0;

        foreach ($request->file('media') as $file) {
            $ext  = $file->getClientOriginalExtension();
            $type = in_array(strtolower($ext), ['mp4', 'mov']) ? 'video' : 'image';
            $path = $file->store("travel-packages/{$package->id}", 'public');

            TravelPackageMedia::create([
                'travel_package_id' => $package->id,
                'media_type'        => $type,
                'file_path'         => $path,
                'position'          => ++$position,
            ]);
        }
    }
}
