<?php

namespace App\Http\Controllers\TravelAgencyPortal;

use App\Enums\TravelPackageStatus;
use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\TravelCity;
use App\Models\TravelCountry;
use App\Models\TravelPackage;
use App\Models\TravelPackageMedia;
use Illuminate\Http\JsonResponse;
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
            ->with(['media', 'destinationCountry', 'destinationCity'])
            ->latest()
            ->paginate(20);

        return view('travel-agency.packages.index', compact('packages'));
    }

    // ── Create / Store ────────────────────────────────────────────────────────

    private function formData(): array
    {
        return [
            'travelCountries' => TravelCountry::where('is_active', true)->orderBy('name_en')->get(['id', 'name_en', 'flag_emoji']),
            'currencies'      => Currency::where('is_active', true)->orderBy('code')->get(['code', 'name', 'symbol']),
        ];
    }

    public function create(): View
    {
        return view('travel-agency.packages.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title_en'                      => ['required', 'string', 'max:255'],
            'title_ar'                      => ['required', 'string', 'max:255'],
            'description_en'                => ['nullable', 'string'],
            'description_ar'                => ['nullable', 'string'],
            'destination_travel_country_id' => ['required', 'uuid', 'exists:travel_countries,id'],
            'destination_travel_city_id'    => ['nullable', 'uuid', 'exists:travel_cities,id'],
            'price_cents'                   => ['required', 'integer', 'min:1'],
            'currency'                      => ['required', 'string', 'size:3', 'exists:currencies,code'],
            'duration_days'                 => ['required', 'integer', 'min:1'],
            'duration_nights'               => ['required', 'integer', 'min:0'],
            'departure_date'                => ['required', 'date', 'after:today'],
            'return_date'                   => ['required', 'date', 'after:departure_date'],
            'available_seats'               => ['nullable', 'integer', 'min:1'],
            'inclusions'                    => ['nullable', 'array'],
            'inclusions.*'                  => ['string'],
            'media'                         => ['nullable', 'array', 'max:10'],
            'media.*'                       => ['file', 'mimes:jpg,jpeg,png,webp,mp4,mov', 'max:51200'],
            'contract_file'                 => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        $package = TravelPackage::create([
            ...$data,
            'travel_agency_id' => $this->agencyId(),
            'status' => TravelPackageStatus::Draft,
        ]);

        $this->storeContractFile($request, $package);
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

        return view('travel-agency.packages.edit', ['package' => $package, ...$this->formData()]);
    }

    public function update(Request $request, TravelPackage $package): RedirectResponse
    {
        $this->authorise($package);

        if (!in_array($package->status, [TravelPackageStatus::Draft, TravelPackageStatus::PendingReview])) {
            return back()->withErrors(['status' => 'Active packages cannot be edited. Contact support.']);
        }

        $data = $request->validate([
            'title_en'                      => ['required', 'string', 'max:255'],
            'title_ar'                      => ['required', 'string', 'max:255'],
            'description_en'                => ['nullable', 'string'],
            'description_ar'                => ['nullable', 'string'],
            'destination_travel_country_id' => ['required', 'uuid', 'exists:travel_countries,id'],
            'destination_travel_city_id'    => ['nullable', 'uuid', 'exists:travel_cities,id'],
            'price_cents'                   => ['required', 'integer', 'min:1'],
            'currency'                      => ['required', 'string', 'size:3', 'exists:currencies,code'],
            'duration_days'                 => ['required', 'integer', 'min:1'],
            'duration_nights'               => ['required', 'integer', 'min:0'],
            'departure_date'                => ['required', 'date'],
            'return_date'                   => ['required', 'date', 'after:departure_date'],
            'available_seats'               => ['nullable', 'integer', 'min:1'],
            'inclusions'                    => ['nullable', 'array'],
            'inclusions.*'                  => ['string'],
            'media'                         => ['nullable', 'array', 'max:10'],
            'media.*'                       => ['file', 'mimes:jpg,jpeg,png,webp,mp4,mov', 'max:51200'],
            'contract_file'                 => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        $package->update($data);

        if ($request->hasFile('contract_file')) {
            if ($package->contract_file_path) {
                Storage::disk('local')->delete($package->contract_file_path);
            }
            $this->storeContractFile($request, $package);
        }

        $this->handleMediaUploads($request, $package);

        return redirect()->route('travel-agency.packages.show', $package)
            ->with('success', 'Package updated.');
    }

    // ── Cities for country (AJAX) ─────────────────────────────────────────────

    public function citiesForCountry(string $travelCountryId): JsonResponse
    {
        return response()->json(
            TravelCity::where('travel_country_id', $travelCountryId)
                ->where('is_active', true)
                ->orderBy('name_en')
                ->get(['id', 'name_en', 'name_ar'])
        );
    }

    // ── Submit for review ─────────────────────────────────────────────────────

    public function submitForReview(TravelPackage $package): RedirectResponse
    {
        $this->authorise($package);

        if ($package->status !== TravelPackageStatus::Draft) {
            return back()->withErrors(['status' => 'Only draft packages can be submitted for review.']);
        }

        $errors = $package->reviewReadinessErrors();
        if (! empty($errors)) {
            return back()->withErrors(['submission' => $errors]);
        }

        $package->update(['status' => TravelPackageStatus::PendingReview]);

        return back()->with('success', 'Package submitted for admin review.');
    }

    // ── Delete media ──────────────────────────────────────────────────────────

    public function destroyMedia(TravelPackage $package, TravelPackageMedia $media): RedirectResponse
    {
        $this->authorise($package);
        abort_if($media->travel_package_id !== $package->id, 404);

        Storage::disk('public')->delete($media->file_path);
        $media->delete();

        return back()->with('success', 'Media removed.');
    }

    // ── Download contract ─────────────────────────────────────────────────────

    public function downloadContract(TravelPackage $package): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $this->authorise($package);

        abort_unless($package->contract_file_path && Storage::disk('local')->exists($package->contract_file_path), 404);

        return Storage::disk('local')->download(
            $package->contract_file_path,
            $package->contract_file_original_name ?? 'contract.pdf'
        );
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function storeContractFile(Request $request, TravelPackage $package): void
    {
        $file = $request->file('contract_file');
        $path = $file->store("travel-packages/{$package->id}/contracts", 'local');

        $package->update([
            'contract_file_path'          => $path,
            'contract_file_original_name' => $file->getClientOriginalName(),
            'contract_uploaded_at'        => now(),
        ]);
    }

    private function handleMediaUploads(Request $request, TravelPackage $package): void
    {
        if (!$request->hasFile('media')) {
            return;
        }

        $position = $package->media()->max('position') ?? 0;

        foreach ($request->file('media') as $file) {
            $ext = $file->getClientOriginalExtension();
            $type = in_array(strtolower($ext), ['mp4', 'mov']) ? 'video' : 'image';
            $path = $file->store("travel-packages/{$package->id}", 'public');

            TravelPackageMedia::create([
                'travel_package_id' => $package->id,
                'media_type' => $type,
                'file_path' => $path,
                'position' => ++$position,
            ]);
        }
    }
}
