<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\VendorApprovedJob;
use App\Models\Admin;
use App\Models\Country;
use App\Models\Vendor;
use App\Models\VendorAdmin;
use App\Models\VendorDocument;
use App\Services\VendorApprovalService;
use App\Traits\HasDataTable;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class VendorApplicationController extends Controller
{
    use HasDataTable;

    /** Required document types that must be verified before approval */
    private const REQUIRED_DOC_TYPES = ['business_license', 'tax_certificate', 'owner_id'];

    public function __construct(private VendorApprovalService $approvalService)
    {
    }

    // ─── Index ────────────────────────────────────────────────────────────────

    public function index(): \Illuminate\View\View
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('vendors.view'), 403);

        $stats = [
            'pending' => Vendor::where('global_status', 'pending')
                ->whereNotNull('onboarding_completed_at')
                ->count(),
            'under_review' => Vendor::where('global_status', 'under_review')
                ->whereNotNull('onboarding_completed_at')
                ->count(),
            'waiting_5plus' => Vendor::whereIn('global_status', ['pending', 'under_review'])
                ->whereNotNull('onboarding_completed_at')
                ->where('onboarding_completed_at', '<=', now()->subDays(5))
                ->count(),
        ];

        $countries = Country::orderBy('name_en')->get(['id', 'name_en', 'flag_emoji']);

        return view('admin.vendor-applications.index', compact('stats', 'countries'));
    }

    // ─── DataTable ────────────────────────────────────────────────────────────

    public function datatable(Request $request): JsonResponse
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('vendors.view'), 403);

        $query = Vendor::query()
            ->with(['country', 'documents', 'bankAccounts'])
            ->whereIn('global_status', ['pending', 'under_review'])
            ->whereNotNull('onboarding_completed_at')
            ->select('vendors.*');

        $query = $this->applyFilters($query, $request, [
            'country_id' => fn($q, $v) => $q->where('country_id', $v),
            'status' => fn($q, $v) => $q->where('global_status', $v),
            'days_min' => fn($q, $v) => $q->where('onboarding_completed_at', '<=', now()->subDays((int) $v)),
            'days_max' => fn($q, $v) => $q->where('onboarding_completed_at', '>=', now()->subDays((int) $v)),
        ]);

        $columns = [
            ['searchable_columns' => ['vendors.store_name', 'vendors.business_name'], 'orderable_column' => 'vendors.store_name'],
            ['searchable_columns' => ['vendors.business_name'], 'orderable_column' => 'vendors.business_name'],
            ['searchable_columns' => [], 'orderable_column' => null], // country
            ['searchable_columns' => ['vendors.business_type'], 'orderable_column' => 'vendors.business_type'],
            ['searchable_columns' => [], 'orderable_column' => null], // docs status
            ['searchable_columns' => [], 'orderable_column' => null], // bank status
            ['searchable_columns' => [], 'orderable_column' => 'vendors.onboarding_completed_at'], // days waiting
            ['searchable_columns' => [], 'orderable_column' => 'vendors.created_at'],
            ['searchable_columns' => [], 'orderable_column' => null], // actions
        ];

        return $this->dataTableResponse($request, $query, $columns, function (Vendor $vendor) {
            $daysWaiting = (int) now()->diffInDays($vendor->onboarding_completed_at);

            $urgencyClass = match (true) {
                $daysWaiting > 5 => 'text-red-600 font-semibold',
                $daysWaiting >= 2 => 'text-yellow-600 font-semibold',
                default => 'text-green-600',
            };

            $statusColors = [
                'pending' => 'warning',
                'under_review' => 'primary',
            ];
            $statusColor = $statusColors[$vendor->global_status] ?? 'gray';
            $statusLabel = ucwords(str_replace('_', ' ', $vendor->global_status));
            $statusBadge = "<span class=\"inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-{$statusColor}-100 text-{$statusColor}-700\">{$statusLabel}</span>";

            // Documents X / Y verified
            $totalDocs = $vendor->documents->count();
            $verifiedDocs = $vendor->documents->filter(fn($d) => in_array($d->status, ['verified', 'approved']))->count();
            $docsStatus = "{$verifiedDocs}/{$totalDocs} verified";
            $docsColor = $verifiedDocs === $totalDocs && $totalDocs > 0 ? 'text-green-600' : 'text-yellow-600';

            // Bank status
            $primaryBank = $vendor->bankAccounts->where('is_primary', true)->first()
                ?? $vendor->bankAccounts->first();
            $bankColors = ['verified' => 'success', 'pending' => 'warning', 'rejected' => 'danger'];
            $bankStatus = $primaryBank?->verification_status ?? 'none';
            $bc = $bankColors[$bankStatus] ?? 'gray';
            $bankBadge = "<span class=\"inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-{$bc}-100 text-{$bc}-700\">" . ucfirst($bankStatus) . "</span>";

            $showUrl = route('admin.vendor-applications.show', $vendor->id);
            $actions = "<div class=\"flex items-center gap-1\">"
                . "<a href=\"{$showUrl}\" class=\"btn btn-xs btn-primary\">Review</a>"
                . "</div>";

            return [
                'store_name' => '<div><span class="font-medium">' . e($vendor->store_name) . '</span>' . '<br><span class="text-xs text-gray-400">' . $statusBadge . '</span></div>',
                'business_name' => e($vendor->business_name ?? '—'),
                'country' => e($vendor->country?->name_en ?? '—'),
                'business_type' => e(ucfirst($vendor->business_type ?? '—')),
                'docs_status' => "<span class=\"text-sm {$docsColor}\">{$docsStatus}</span>",
                'bank_status' => $bankBadge,
                'days_waiting' => "<span class=\"{$urgencyClass}\">{$daysWaiting}d</span>",
                'created_at' => $vendor->created_at->format('d M Y'),
                'actions' => $actions,
                'DT_RowData' => ['id' => $vendor->id, 'status' => $vendor->global_status],
            ];
        });
    }

    // ─── Show ─────────────────────────────────────────────────────────────────

    public function show(Vendor $vendor): \Illuminate\View\View
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('vendors.view'), 403);

        $vendor->load([
            'country',
            'approvedByAdmin',
            'accountManagerAdmin',
            'documents.verifiedByAdmin',
            'bankAccounts',
            'addresses',
        ]);

        $admins = Admin::orderBy('name')->get(['id', 'name']);
        $daysWaiting = (int) now()->diffInDays($vendor->onboarding_completed_at ?? $vendor->created_at);

        // Required docs checklist
        $requiredDocs = collect(self::REQUIRED_DOC_TYPES)->mapWithKeys(function ($type) use ($vendor) {
            $doc = $vendor->documents->firstWhere('document_type', $type);
            return [
                $type => [
                    'label' => $this->docTypeLabel($type),
                    'doc' => $doc,
                    'uploaded' => (bool) $doc,
                    'verified' => $doc ? in_array($doc->status, ['verified', 'approved']) : false,
                ]
            ];
        });

        // Checklist items (for right panel)
        $businessInfoComplete = !empty($vendor->business_name) && !empty($vendor->business_type);
        $allRequiredUploaded = $requiredDocs->every(fn($d) => $d['uploaded']);
        $allRequiredVerified = $requiredDocs->every(fn($d) => $d['verified']);
        $hasBankAccount = $vendor->bankAccounts->isNotEmpty();
        $storeProfileComplete = !empty($vendor->store_name) && !empty($vendor->store_description);

        $checklist = [
            'business_info' => ['label' => 'Business info complete', 'pass' => $businessInfoComplete],
            'docs_uploaded' => ['label' => 'All required docs uploaded', 'pass' => $allRequiredUploaded],
            'docs_verified' => ['label' => 'All required docs verified', 'pass' => $allRequiredVerified],
            'bank_account' => ['label' => 'Bank account added', 'pass' => $hasBankAccount],
            'store_profile' => ['label' => 'Store profile complete', 'pass' => $storeProfileComplete],
        ];

        $canApprove = collect($checklist)->every(fn($c) => $c['pass']);

        return view('admin.vendor-applications.show', compact(
            'vendor',
            'admins',
            'daysWaiting',
            'requiredDocs',
            'checklist',
            'canApprove'
        ));
    }

    // ─── Start Review ─────────────────────────────────────────────────────────

    public function startReview(Vendor $vendor): JsonResponse
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('vendors.edit'), 403);

        if (!in_array($vendor->global_status, ['pending', 'under_review'])) {
            return response()->json(['message' => 'Application is not pending review.'], 422);
        }

        $vendor->update([
            'global_status' => 'under_review',
            'account_manager_admin_id' => $vendor->account_manager_admin_id ?? $admin->id,
        ]);

        return response()->json(['message' => 'Application marked as under review.', 'status' => 'under_review']);
    }

    // ─── Assign Me ────────────────────────────────────────────────────────────

    public function assignMe(Vendor $vendor): JsonResponse
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('vendors.edit'), 403);

        $vendor->update(['account_manager_admin_id' => $admin->id]);

        return response()->json(['message' => 'You have been assigned as account manager.']);
    }

    // ─── Approve ──────────────────────────────────────────────────────────────

    public function approve(Request $request, Vendor $vendor): JsonResponse
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('vendors.edit'), 403);

        $request->validate([
            'commission_rate_override' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'account_manager_admin_id' => ['nullable', 'string', 'exists:admins,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        // 1. Check onboarding completed
        if (!$vendor->onboarding_completed_at) {
            return response()->json(['message' => 'Vendor has not completed onboarding.'], 422);
        }

        // 2. Check all required document types are verified
        $missingOrUnverified = [];
        foreach (self::REQUIRED_DOC_TYPES as $type) {
            $doc = $vendor->documents()->where('document_type', $type)->first();
            if (!$doc || !in_array($doc->status, ['verified', 'approved'])) {
                $missingOrUnverified[] = $this->docTypeLabel($type);
            }
        }
        if (!empty($missingOrUnverified)) {
            return response()->json([
                'message' => 'The following required documents must be verified before approval.',
                'missing' => $missingOrUnverified,
            ], 422);
        }

        // 3. Check at least one bank account
        if (!$vendor->bankAccounts()->exists()) {
            return response()->json(['message' => 'Vendor must have at least one bank account on file.'], 422);
        }

        DB::transaction(function () use ($vendor, $admin, $request) {
            // 1. Update vendor status
            $vendor->update([
                'global_status' => 'active',
                'status' => 'active',
                'approved_by_admin_id' => $admin->id,
                'approved_at' => now(),
            ]);

            // 2. Commission rate override
            if ($request->filled('commission_rate_override')) {
                $vendor->update(['commission_rate' => $request->input('commission_rate_override')]);
            }

            // 3. Account manager
            if ($request->filled('account_manager_admin_id')) {
                $vendor->update(['account_manager_admin_id' => $request->input('account_manager_admin_id')]);
            }

            // 4. Create VendorAdmin (owner role) if not exists
            $exists = VendorAdmin::where('vendor_id', $vendor->id)->where('role', 'owner')->exists();
            if (!$exists) {
                VendorAdmin::create([
                    'vendor_id' => $vendor->id,
                    'name' => $vendor->business_name ?? $vendor->store_name,
                    'email' => $vendor->email,
                    'password' => Hash::make(Str::random(12)),
                    'role' => 'owner',
                    'is_active' => true,
                ]);
            }

            // 5. Dispatch welcome job
            VendorApprovedJob::dispatch($vendor->id);
        });

        return response()->json(['message' => 'Vendor approved. Welcome email dispatched.']);
    }

    // ─── Reject ───────────────────────────────────────────────────────────────

    public function reject(Request $request, Vendor $vendor): JsonResponse
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('vendors.edit'), 403);

        $request->validate([
            'rejection_reason' => ['required', 'string', 'min:5', 'max:2000'],
            'rejection_codes' => ['nullable', 'array'],
            'rejection_codes.*' => ['string', 'in:documents_incomplete,documents_invalid,business_not_verifiable,policy_violation,duplicate_account,prohibited_category,other'],
        ]);

        $reason = $request->input('rejection_reason');
        if (!empty($request->input('rejection_codes'))) {
            $codes = implode(', ', array_map(fn($c) => ucwords(str_replace('_', ' ', $c)), $request->input('rejection_codes')));
            $reason = "[{$codes}] {$reason}";
        }

        $this->approvalService->reject($vendor, $reason, $admin);

        return response()->json(['message' => 'Application rejected. Notification dispatched.']);
    }

    // ─── Request More Info ────────────────────────────────────────────────────

    public function requestMoreInfo(Request $request, Vendor $vendor): JsonResponse
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('vendors.edit'), 403);

        $request->validate([
            'required_document_types' => ['required', 'array', 'min:1'],
            'required_document_types.*' => ['string'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->approvalService->requestMoreInfo(
            $vendor,
            $request->input('required_document_types'),
            $admin
        );

        return response()->json(['message' => 'More information requested. Vendor notified.']);
    }

    // ─── Verify Document ──────────────────────────────────────────────────────

    public function verifyDocument(Request $request, VendorDocument $document): JsonResponse
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('vendors.edit'), 403);

        $document->update([
            'status' => 'verified',
            'verified_by_admin_id' => $admin->id,
            'verified_at' => now(),
            'rejection_reason' => null,
        ]);

        return response()->json([
            'message' => 'Document verified.',
            'doc_id' => $document->id,
            'status' => 'verified',
        ]);
    }

    // ─── Reject Document ──────────────────────────────────────────────────────

    public function rejectDocument(Request $request, VendorDocument $document): JsonResponse
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('vendors.edit'), 403);

        $request->validate([
            'rejection_reason' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        $document->update([
            'status' => 'rejected',
            'rejection_reason' => $request->input('rejection_reason'),
        ]);

        return response()->json([
            'message' => 'Document rejected.',
            'doc_id' => $document->id,
            'status' => 'rejected',
        ]);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function docTypeLabel(string $type): string
    {
        return match ($type) {
            'business_license' => 'Business License',
            'tax_certificate' => 'Tax Certificate',
            'owner_id' => 'Owner ID',
            'bank_statement' => 'Bank Statement',
            'trade_license' => 'Trade License',
            default => ucwords(str_replace('_', ' ', $type)),
        };
    }
}
