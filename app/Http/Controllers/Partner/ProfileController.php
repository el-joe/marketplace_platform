<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\VendorBankAccount;
use App\Models\VendorDocument;
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
     * Main profile page — loads vendor, documents, bank accounts summary.
     */
    public function index(): View
    {
        $admin = $this->vendorAdmin();
        $vendor = $admin->vendor()->with('country')->firstOrFail();

        $documents = VendorDocument::where('vendor_id', $this->vendorId())
            ->whereNull('deleted_at')
            ->get()
            ->keyBy('document_type');

        $bankAccounts = VendorBankAccount::where('vendor_id', $this->vendorId())
            ->orderByDesc('is_primary')
            ->get();

        return view('partner.profile.index', compact('admin', 'vendor', 'documents', 'bankAccounts'));
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
     * Upload or replace a vendor document file.
     * Stores to: storage/vendor-docs/{vendor_id}/{type}.{ext}
     */
    public function uploadDocument(Request $request): JsonResponse
    {
        $vendorId = $this->vendorId();

        $request->validate([
            'document_type' => 'required|in:business_license,tax_certificate,owner_id,bank_proof,vat_registration',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $type = $request->input('document_type');
        $ext = $request->file('file')->getClientOriginalExtension();

        $path = $request->file('file')->storeAs(
            "vendor-docs/{$vendorId}",
            "{$type}.{$ext}",
            'public'
        );

        VendorDocument::updateOrCreate(
            ['vendor_id' => $vendorId, 'document_type' => $type],
            [
                'file_path' => $path,
                'status' => 'pending',
                'verified_at' => null,
                'verified_by_admin_id' => null,
                'rejection_reason' => null,
            ]
        );

        return response()->json(['message' => 'تم رفع الملف بنجاح، سيتم مراجعته قريباً']);
    }
}
