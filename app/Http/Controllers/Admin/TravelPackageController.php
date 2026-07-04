<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TravelCountry;
use App\Models\TravelPackage;
use Illuminate\Support\Facades\Storage;
use App\Notifications\TravelAgency\PackageApproved;
use App\Notifications\TravelAgency\PackageRejected;
use App\Traits\HasDataTable;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TravelPackageController extends Controller
{
    use HasDataTable;

    // ── Index ─────────────────────────────────────────────────────────────────

    public function index(): \Illuminate\View\View
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('travel.view'), 403);

        $urgencyThreshold = now()->addDays(7);

        $stats = [
            'pending' => TravelPackage::where('status', 'pending_review')->count(),
            'active' => TravelPackage::where('status', 'active')->count(),
            'sold_out' => TravelPackage::where('status', 'sold_out')->count(),
            // Packages departing within 7 days that are still pending — fulfillment urgency
            'urgent' => TravelPackage::where('status', 'pending_review')
                ->whereDate('departure_date', '<=', $urgencyThreshold)
                ->count(),
        ];

        $travelCountries = TravelCountry::where('is_active', true)
            ->orderBy('name_en')
            ->get(['id', 'name_en', 'flag_emoji']);

        return view('admin.travel-packages.index', compact('stats', 'travelCountries'));
    }

    // ── DataTable ─────────────────────────────────────────────────────────────

    public function datatable(Request $request): JsonResponse
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('travel.view'), 403);

        $query = TravelPackage::query()
            ->select('travel_packages.*')
            ->with(['agency', 'destinationCountry', 'media'])
            ->join('travel_agencies', 'travel_agencies.id', '=', 'travel_packages.travel_agency_id');

        $query = $this->applyFilters($query, $request, [
            'status' => fn($q, $v) => $q->where('travel_packages.status', $v),
            'destination_travel_country_id' => fn($q, $v) => $q->where('travel_packages.destination_travel_country_id', $v),
            'agency_id' => fn($q, $v) => $q->where('travel_packages.travel_agency_id', $v),
            'departure_from' => fn($q, $v) => $q->whereDate('travel_packages.departure_date', '>=', $v),
            'departure_to' => fn($q, $v) => $q->whereDate('travel_packages.departure_date', '<=', $v),
        ]);

        $columns = [
            ['searchable_columns' => ['travel_packages.title_en', 'travel_packages.title_ar'], 'orderable_column' => 'travel_packages.title_en'],
            ['searchable_columns' => ['travel_agencies.name'], 'orderable_column' => 'travel_agencies.name'],
            ['searchable_columns' => [], 'orderable_column' => null], // destination
            ['searchable_columns' => [], 'orderable_column' => 'travel_packages.price_cents'],
            ['searchable_columns' => [], 'orderable_column' => 'travel_packages.departure_date'],
            ['searchable_columns' => [], 'orderable_column' => null], // seats
            ['searchable_columns' => [], 'orderable_column' => 'travel_packages.status'],
            ['searchable_columns' => [], 'orderable_column' => null], // actions
        ];

        $statusColors = [
            'draft' => 'gray',
            'pending_review' => 'warning',
            'active' => 'success',
            'sold_out' => 'purple',
            'expired' => 'gray',
        ];

        $canEdit = $admin->hasPermissionTo('travel.view');

        return $this->dataTableResponse($request, $query, $columns, function (TravelPackage $row) use ($statusColors, $canEdit) {
            $cover = $row->media->where('media_type', 'image')->first();
            $thumbHtml = $cover
                ? "<img src=\"/storage/{$cover->file_path}\" class=\"w-10 h-10 rounded object-cover inline-block mr-2 align-middle\" alt=\"\">"
                : "<span class=\"inline-block w-10 h-10 rounded bg-gray-100 mr-2 align-middle\"></span>";

            $title = $thumbHtml . '<span class="font-medium text-gray-900">' . e($row->title_en) . '</span>';

            $country = $row->destinationCountry;
            $destination = $country
                ? ($country->flag_emoji ? $country->flag_emoji . ' ' : '') . e($country->name_en)
                : e($row->destination_country ?? '—');
            if ($row->destination_city) {
                $destination .= '<br><span class="text-xs text-gray-400">' . e($row->destination_city) . '</span>';
            }

            $price = e($row->currency) . ' ' . number_format($row->price_cents / 100, 2);

            $departure = Carbon::parse($row->departure_date)->format('d M Y');

            $seatsAvail = $row->available_seats;
            $seatsBooked = $row->seats_booked;
            $seatDisplay = $seatsAvail !== null ? "{$seatsBooked} / {$seatsAvail}" : "{$seatsBooked} / ∞";

            $statusColor = $statusColors[$row->status] ?? 'gray';
            $statusLabel = ucwords(str_replace('_', ' ', $row->status));
            $statusBadge = "<span class=\"inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-{$statusColor}-100 text-{$statusColor}-700\">{$statusLabel}</span>";

            $showUrl = route('admin.travel.packages.show', $row->id);
            $approveUrl = route('admin.travel.packages.approve', $row->id);
            $rejectUrl = route('admin.travel.packages.reject', $row->id);

            $actions = '<div class="flex items-center gap-1">';
            $actions .= "<a href=\"{$showUrl}\" class=\"btn btn-xs btn-secondary\">View</a>";
            if ($canEdit && $row->status === 'pending_review') {
                $actions .= "<button type=\"button\" class=\"btn btn-xs btn-success js-approve-btn\" data-url=\"{$approveUrl}\" data-name=\"" . e($row->title_en) . "\">Approve</button>";
                $actions .= "<button type=\"button\" class=\"btn btn-xs btn-danger js-reject-btn\" data-url=\"{$rejectUrl}\" data-name=\"" . e($row->title_en) . "\">Reject</button>";
            }
            $actions .= '</div>';

            return [
                'title' => $title,
                'agency' => e($row->agency?->name ?? '—'),
                'destination' => $destination,
                'price' => $price,
                'departure' => $departure,
                'seats' => $seatDisplay,
                'status' => $statusBadge,
                'actions' => $actions,
                'DT_RowData' => ['id' => $row->id, 'status' => $row->status],
            ];
        });
    }

    // ── Show ──────────────────────────────────────────────────────────────────

    public function show(TravelPackage $travelPackage): \Illuminate\View\View
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('travel.view'), 403);

        $travelPackage->load([
            'agency',
            'media',
            'categories',
            'approvedByAdmin',
            'destinationCountry',
            'destinationCity',
        ]);

        $bookingStats = [
            'total' => $travelPackage->bookings()->count(),
            'confirmed' => $travelPackage->bookings()->where('status', 'confirmed')->count(),
            'cancelled' => $travelPackage->bookings()->where('status', 'cancelled')->count(),
            'revenue_cents' => $travelPackage->bookings()
                ->whereIn('status', ['confirmed', 'completed'])
                ->sum('total_price_cents'),
        ];

        $fillPct = ($travelPackage->available_seats && $travelPackage->available_seats > 0)
            ? min(100, round($travelPackage->seats_booked / $travelPackage->available_seats * 100))
            : null;

        return view('admin.travel-packages.show', compact(
            'travelPackage',
            'bookingStats',
            'fillPct'
        ));
    }

    // ── Approve ───────────────────────────────────────────────────────────────

    public function approve(TravelPackage $travelPackage): JsonResponse
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('travel.approve'), 403);

        if ($travelPackage->status !== 'pending_review') {
            return response()->json(['message' => 'Package is not pending review.'], 422);
        }

        // Minimum lead time: 3 days (judgment call — shorter windows make
        // contract signing, document collection, and logistics unrealistic).
        // Raise or lower this threshold if business requirements change.
        // $minimumDeparture = now()->addDays(3);
        // if ($travelPackage->departure_date->lt($minimumDeparture)) {
        //     return response()->json([
        //         'message' => 'Cannot approve: departure is in the past or fewer than 3 days away — insufficient time to fulfill.',
        //     ], 422);
        // }

        $travelPackage->update([
            'status' => 'active',
            'approved_by_admin_id' => $admin->id,
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);

        $travelPackage->agency->notify(new PackageApproved($travelPackage));

        return response()->json(['message' => 'Package approved and is now live.']);
    }

    // ── Reject ────────────────────────────────────────────────────────────────

    public function reject(Request $request, TravelPackage $travelPackage): JsonResponse
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('travel.reject'), 403);

        $request->validate([
            'rejection_reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        // The status enum has no 'rejected' value; package is returned to draft
        // so the agency can amend and resubmit. The rejection_reason preserves
        // context that would otherwise be lost with a silent status flip.
        $travelPackage->update([
            'status' => 'draft',
            'rejection_reason' => $request->input('rejection_reason'),
        ]);

        $travelPackage->agency->notify(new PackageRejected($travelPackage, $request->input('rejection_reason')));

        return response()->json(['message' => 'Package returned to agency as draft with reason recorded.']);
    }

    // ── Expire ────────────────────────────────────────────────────────────────

    public function expire(TravelPackage $travelPackage): JsonResponse
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('travel.suspend'), 403);

        if (!in_array($travelPackage->status, ['active', 'sold_out'])) {
            return response()->json(['message' => 'Package cannot be expired from its current status.'], 422);
        }

        $travelPackage->update(['status' => 'expired']);

        return response()->json(['message' => 'Package marked as expired.']);
    }

    // ── Download contract ─────────────────────────────────────────────────────

    public function downloadContract(TravelPackage $travelPackage): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('travel.view'), 403);

        abort_unless(
            $travelPackage->contract_file_path && Storage::disk('local')->exists($travelPackage->contract_file_path),
            404
        );

        return Storage::disk('local')->download(
            $travelPackage->contract_file_path,
            $travelPackage->contract_file_original_name ?? 'contract.pdf'
        );
    }
}
