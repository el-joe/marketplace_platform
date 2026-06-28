<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Mail\VendorApplicationReceivedMail;
use App\Models\Admin;
use App\Notifications\Admin\NewVendorApplicationSubmitted;
use Illuminate\Support\Facades\Notification;
use App\Models\Address;
use App\Models\City;
use App\Models\Country;
use App\Models\Vendor;
use App\Models\VendorAdmin;
use App\Models\VendorDocument;
use App\Services\ActivityLoggerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    // ── Step validation rule sets ─────────────────────────────────────────

    private function stepRules(int $step): array
    {
        return match ($step) {
            1 => [
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:vendors,email',
                'phone' => 'required|string|max:30',
                'country_id' => 'required|exists:countries,id',
                'password' => 'required|min:8|confirmed',
                'password_confirmation' => 'required',
            ],
            2 => [
                'store_name' => 'required|string|max:255',
                'store_slug' => 'required|string|max:120|alpha_dash|unique:vendors,store_slug',
                'business_type' => 'required|in:individual,sole_prop,llc,corp',
                'business_name' => 'required|string|max:255',
                'business_registration_number' => 'nullable|string|max:100',
                'tax_id' => 'nullable|string|max:100',
                'store_description' => 'nullable|string|max:1000',
            ],
            3 => [
                'contact_email' => 'required|email|max:255',
                'contact_phone' => 'required|string|max:30',
                'whatsapp_number' => 'nullable|string|max:30',
                'city_id' => 'required|exists:cities,id',
                'area' => 'nullable|string|max:255',
                'street_address' => 'required|string|max:500',
                'building' => 'nullable|string|max:100',
                'floor' => 'nullable|string|max:20',
                'apartment' => 'nullable|string|max:20',
                'postal_code' => 'nullable|string|max:20',
            ],
            default => [],
        };
    }

    private function stepMessages(int $step): array
    {
        if ($step === 1) {
            return [
                'email.unique' => 'هذا البريد الإلكتروني مسجل مسبقاً.',
                'password.min' => 'كلمة المرور يجب أن تكون ٨ أحرف على الأقل.',
                'password.confirmed' => 'كلمة المرور وتأكيدها غير متطابقتين.',
            ];
        }
        if ($step === 2) {
            return [
                'store_slug.unique' => 'اسم المتجر المختصر مستخدم مسبقاً، جرب آخر.',
                'store_slug.alpha_dash' => 'يجب أن يحتوي المختصر على أحرف وأرقام وشرطات فقط.',
            ];
        }
        return [];
    }

    // ── Public pages ──────────────────────────────────────────────────────

    /**
     * GET /register — show multi-step wizard
     */
    public function show(): View
    {
        $countries = Country::where('is_active', true)->orderBy('name_ar')->get();
        $currentStep = session('reg_data.current_step', 1);
        $savedData = session('reg_data', []);

        return view('portal.register', compact('countries', 'currentStep', 'savedData'));
    }

    /**
     * GET /register/success
     */
    public function success(): View
    {
        return view('portal.register-success');
    }

    // ── AJAX step save ────────────────────────────────────────────────────

    /**
     * POST /register/step/{step}
     */
    public function storeStep(Request $request, int $step): JsonResponse
    {
        abort_unless(in_array($step, [1, 2, 3], true), 404);

        $validator = Validator::make(
            $request->all(),
            $this->stepRules($step),
            $this->stepMessages($step)
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        session(["reg_data.step{$step}" => $validator->validated()]);
        session(['reg_data.current_step' => $step + 1]);

        return response()->json([
            'success' => true,
            'next_step' => $step + 1,
        ]);
    }

    // ── Document upload ───────────────────────────────────────────────────

    /**
     * POST /register/upload
     */
    public function uploadDocument(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'document_type' => 'required|in:business_license,owner_id,tax_certificate,vat_registration',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $file = $request->file('file');
        $type = $request->input('document_type');
        $ext = $file->getClientOriginalExtension();
        $uuid = (string) Str::uuid();
        $path = "vendor-docs/temp/{$uuid}.{$ext}";

        // Delete previous temp for this type if any
        $existing = session("reg_data.documents.{$type}");
        if ($existing) {
            Storage::disk('public')->delete($existing);
        }

        Storage::disk('public')->put($path, file_get_contents($file->getRealPath()));

        session(["reg_data.documents.{$type}" => $path]);

        return response()->json([
            'success' => true,
            'path' => $path,
            'filename' => $file->getClientOriginalName(),
            'type' => $type,
            'is_image' => in_array(strtolower($ext), ['jpg', 'jpeg', 'png']),
            'url' => Storage::disk('public')->url($path),
        ]);
    }

    /**
     * DELETE /register/document
     */
    public function removeDocument(Request $request): JsonResponse
    {
        $type = $request->input('document_type');
        abort_unless(
            in_array($type, ['business_license', 'owner_id', 'tax_certificate', 'vat_registration'], true),
            422
        );

        $path = session("reg_data.documents.{$type}");
        if ($path) {
            Storage::disk('public')->delete($path);
            session()->forget("reg_data.documents.{$type}");
        }

        return response()->json(['success' => true]);
    }

    // ── Final submission ──────────────────────────────────────────────────

    /**
     * POST /register/complete
     */
    public function complete(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'terms_agreed' => 'required|accepted',
            'privacy_agreed' => 'required|accepted',
        ], [
            'terms_agreed.accepted' => 'يجب الموافقة على الشروط والأحكام.',
            'privacy_agreed.accepted' => 'يجب الموافقة على سياسة الخصوصية.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $step1 = session('reg_data.step1', []);
        $step2 = session('reg_data.step2', []);
        $step3 = session('reg_data.step3', []);
        $docs = session('reg_data.documents', []);

        // Guard: all steps must be present
        if (empty($step1['email']) || empty($step2['store_name']) || empty($step3['street_address'])) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات التسجيل غير مكتملة. ارجع وأعد إدخال البيانات.',
            ], 422);
        }

        // Required documents
        if (empty($docs['business_license']) || empty($docs['owner_id'])) {
            return response()->json([
                'success' => false,
                'errors' => [
                    'documents' => ['السجل التجاري وهوية المالك مطلوبان.'],
                ],
            ], 422);
        }

        // try {
        DB::transaction(function () use ($step1, $step2, $step3, $docs) {
            // ① Create Vendor
            $vendor = Vendor::create([
                'name' => $step1['name'],
                'email' => $step1['email'],
                'phone' => $step1['phone'],
                'password' => Hash::make($step1['password']),
                'country_id' => $step1['country_id'],
                'store_name' => $step2['store_name'],
                'store_slug' => $step2['store_slug'],
                'store_description' => $step2['store_description'] ?? null,
                'business_type' => $step2['business_type'],
                'business_name' => $step2['business_name'],
                'business_registration_number' => $step2['business_registration_number'] ?? null,
                'tax_id' => $step2['tax_id'] ?? null,
                'contact_email' => $step3['contact_email'],
                'contact_phone' => $step3['contact_phone'],
                'whatsapp_number' => $step3['whatsapp_number'] ?? null,
                'global_status' => 'pending',
                // 'approved_at' => 0,
            ]);

            // ② Create billing address (polymorphic)
            $address = Address::create([
                'addressable_type' => Vendor::class,
                'addressable_id' => $vendor->id,
                'address_type' => 'billing',
                'country_id' => $step1['country_id'],
                'city_id' => $step3['city_id'],
                'area' => $step3['area'] ?? null,
                'street_address' => $step3['street_address'],
                'building' => $step3['building'] ?? null,
                'floor' => $step3['floor'] ?? null,
                'apartment' => $step3['apartment'] ?? null,
                'postal_code' => $step3['postal_code'] ?? null,
                'recipient_name' => $step1['name'],
                'recipient_phone' => $step3['contact_phone'],
                'is_default' => true,
            ]);

            // ③ Link address back to vendor
            $vendor->update(['business_address_id' => $address->id]);

            // ④ Create VendorAdmin (owner)
            VendorAdmin::create([
                'vendor_id' => $vendor->id,
                'name' => $step1['name'],
                'email' => $step1['email'],
                'password' => Hash::make($step1['password']),
                'role' => 'owner',
                'is_active' => 1,
            ]);

            // ⑤ Move temp documents → permanent, create VendorDocument records
            foreach ($docs as $docType => $tempPath) {
                $ext = pathinfo($tempPath, PATHINFO_EXTENSION);
                $permPath = "vendor-docs/{$vendor->id}/{$docType}.{$ext}";

                if (Storage::disk('public')->exists($tempPath)) {
                    Storage::disk('public')->move($tempPath, $permPath);
                }

                VendorDocument::create([
                    'vendor_id' => $vendor->id,
                    'document_type' => $docType,
                    'file_path' => $permPath,
                    'status' => 'pending',
                ]);
            }

            // ⑥ Welcome email (non-blocking)
            try {
                Mail::to($vendor->email)->send(new VendorApplicationReceivedMail($vendor));
            } catch (\Throwable $e) {
                Log::warning('VendorApplicationReceivedMail failed: ' . $e->getMessage());
            }

            // ⑧ Notify admins of new application
            try {
                Notification::send(
                    Admin::permission('vendors.approve')->get(),
                    new NewVendorApplicationSubmitted($vendor),
                );
            } catch (\Throwable $e) {
                Log::warning('NewVendorApplicationSubmitted notification failed: ' . $e->getMessage());
            }

            // ⑦ Activity log (non-blocking)
            try {
                app(ActivityLoggerService::class)->log(
                    'New vendor registration submitted',
                    $vendor,
                    null,
                    ['email' => $vendor->email, 'store' => $vendor->store_name],
                    'vendor',
                    'created'
                );
            } catch (\Throwable $e) {
                Log::warning('Activity log failed after vendor registration: ' . $e->getMessage());
            }
        });

        session()->forget('reg_data');

        return response()->json([
            'success' => true,
            'redirect' => route('portal.register.success'),
        ]);
        // } catch (\Throwable $e) {
        //     Log::error('Vendor registration DB error: ' . $e->getMessage(), [
        //         'trace' => $e->getTraceAsString(),
        //     ]);

        //     return response()->json([
        //         'success' => false,
        //         'message' => 'حدث خطأ أثناء التسجيل. حاول مرة أخرى أو تواصل مع الدعم.',
        //     ], 500);
        // }
    }

    // ── AJAX helpers ──────────────────────────────────────────────────────

    /**
     * GET /register/cities?country_id=X
     */
    public function cities(Request $request): JsonResponse
    {
        $request->validate(['country_id' => 'required|exists:countries,id']);

        $cities = City::where('country_id', $request->input('country_id'))
            ->where('is_active', true)
            ->orderBy('name_ar')
            ->get(['id', 'name_ar', 'name_en']);

        return response()->json(['success' => true, 'cities' => $cities]);
    }

    /**
     * GET /register/check-slug?slug=X
     */
    public function checkSlug(Request $request): JsonResponse
    {
        $slug = Str::slug($request->input('slug', ''));
        $taken = Vendor::where('store_slug', $slug)->exists();

        return response()->json(['available' => !$taken, 'slug' => $slug]);
    }
}
