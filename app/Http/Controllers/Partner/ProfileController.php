<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\VendorBankAccount;
use App\Models\VendorDocument;
use App\Models\VendorDocumentCountryRequirement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ProfileController extends Controller
{
    // ── Helpers ───────────────────────────────────────────────────────────────

    private function vendorAdmin()
    {
        return Auth::guard('vendor')->user();
    }

    private function vendorId(): string
    {
        return $this->vendorAdmin()->vendor_id;
    }

    // ── Actions ───────────────────────────────────────────────────────────────

    /**
     * Main profile page — loads vendor, dynamic required docs (country-aware), bank accounts summary.
     */
    public function index(): View
    {
        $admin  = $this->vendorAdmin();
        $vendor = $admin->vendor()->with('country')->firstOrFail();

        // Dynamic, country-aware list — same shared helper as registration.
        // Re-fetched on every page load so admin matrix changes reflect immediately.
        $requiredDocs = $vendor->country
            ? $vendor->country->requiredDocumentTypesFor()
            : collect();

        // Latest VendorDocument per type for this vendor, keyed by vendor_document_type_id.
        $typeIds = $requiredDocs->pluck('type.id');
        $latestDocByType = VendorDocument::where('vendor_id', $this->vendorId())
            ->whereIn('vendor_document_type_id', $typeIds)
            ->get()
            ->groupBy('vendor_document_type_id')
            ->map(fn ($group) => $group->sortByDesc('created_at')->first());

        $bankAccounts = VendorBankAccount::where('vendor_id', $this->vendorId())
            ->orderByDesc('is_primary')
            ->get();

        return view('partner.profile.index', compact(
            'admin', 'vendor', 'requiredDocs', 'latestDocByType', 'bankAccounts'
        ));
    }

    /**
     * Redirect /documents to profile page with documents tab pre-selected.
     */
    public function documentsIndex(): RedirectResponse
    {
        return redirect()->route('partner.profile.index', ['tab' => 'documents']);
    }

    /**
     * Update editable store fields (owner only).
     * Cannot update: email, country, business_type — require admin approval.
     */
    public function updateStore(Request $request): JsonResponse
    {
        $admin = $this->vendorAdmin();

        if (!$admin->isOwner()) {
            return response()->json(['message' => 'يحق للمالك فقط تعديل بيانات المتجر'], 403);
        }

        $validated = $request->validate([
            'store_name' => 'required|string|max:255',
            'store_description' => 'nullable|string|max:2000',
            'contact_email' => 'required|email|max:255',
            'contact_phone' => 'required|string|max:30',
            'whatsapp_number' => 'nullable|string|max:30',
        ]);

        $admin->vendor()->update($validated);

        return response()->json(['message' => 'تم تحديث بيانات المتجر بنجاح']);
    }

    /**
     * Change the authenticated vendor admin's password.
     * Requires current_password verification.
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $admin = $this->vendorAdmin();

        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if (!Hash::check($request->input('current_password'), $admin->password)) {
            return response()->json(['message' => 'كلمة المرور الحالية غير صحيحة'], 422);
        }

        $admin->update(['password' => Hash::make($request->input('password'))]);

        return response()->json(['message' => 'تم تغيير كلمة المرور بنجاح']);
    }

    /**
     * Upload a compliance document.
     * Validates that the document_type_id is actually required (mandatory/optional)
     * for this vendor's country — rejects 422 for not_applicable or unknown types.
     * Always creates a NEW pending row so the admin sees the resubmission.
     */
    public function uploadDocument(Request $request): JsonResponse
    {
        $admin    = $this->vendorAdmin();
        $vendor   = $admin->vendor()->with('country')->firstOrFail();
        $vendorId = $vendor->id;

        $request->validate([
            'document_type_id' => 'required|uuid|exists:vendor_document_types,id',
            'file'             => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $typeId = $request->input('document_type_id');

        // Confirm the type is applicable for this vendor's country.
        if (! $vendor->country) {
            return response()->json(['message' => 'لا يمكن تحديد بلد البائع'], 422);
        }

        $requirement = VendorDocumentCountryRequirement::where('vendor_document_type_id', $typeId)
            ->where('country_id', $vendor->country->id)
            ->where('requirement_level', '!=', 'not_applicable')
            ->first();

        if (! $requirement) {
            return response()->json(['message' => 'نوع الوثيقة غير مطلوب لبلدك'], 422);
        }

        $ext  = $request->file('file')->getClientOriginalExtension();
        $slug = \Illuminate\Support\Str::uuid();
        $path = $request->file('file')->storeAs(
            "vendor-docs/{$vendorId}",
            "{$typeId}-{$slug}.{$ext}",
            'public'
        );

        // Always insert a new row — admin reviews the fresh submission.
        VendorDocument::create([
            'vendor_id'               => $vendorId,
            'vendor_document_type_id' => $typeId,
            'file_path'               => $path,
            'status'                  => 'pending',
        ]);

        return response()->json(['message' => 'تم رفع الملف بنجاح، سيتم مراجعته قريباً']);
    }
}
