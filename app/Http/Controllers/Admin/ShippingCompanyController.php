<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ShippingCompanyStatus;
use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Country;
use App\Models\ShippingCompany;
use App\Models\ShippingCompanySupervisor;
use App\Models\ShippingFallbackRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ShippingCompanyController extends Controller
{
    public function index(): View
    {
        $stats = [
            'total'     => ShippingCompany::count(),
            'active'    => ShippingCompany::where('status', ShippingCompanyStatus::Active)->count(),
            'pending'   => ShippingCompany::where('status', ShippingCompanyStatus::Pending)->count(),
            'suspended' => ShippingCompany::where('status', ShippingCompanyStatus::Suspended)->count(),
        ];

        $companies = ShippingCompany::with('country')
            ->withCount(['supervisors', 'agents'])
            ->latest()
            ->paginate(20);

        return view('admin.shipping-companies.index', compact('companies', 'stats'));
    }

    public function show(ShippingCompany $shippingCompany): View
    {
        $shippingCompany->load(['country', 'supervisors.country', 'agents' => fn ($q) => $q->limit(20)]);

        $countries = Country::where('is_active', true)->orderBy('name_en')->get(['id', 'name_en']);

        return view('admin.shipping-companies.show', compact('shippingCompany', 'countries'));
    }

    public function approve(ShippingCompany $shippingCompany): RedirectResponse
    {
        $shippingCompany->update([
            'status'              => ShippingCompanyStatus::Active,
            'approved_by_admin_id' => auth('admin')->id(),
            'approved_at'         => now(),
        ]);

        return back()->with('success', 'تم تفعيل شركة الشحن بنجاح.');
    }

    public function suspend(ShippingCompany $shippingCompany): RedirectResponse
    {
        $shippingCompany->update(['status' => ShippingCompanyStatus::Suspended]);

        return back()->with('success', 'تم إيقاف شركة الشحن.');
    }

    public function toggleSupervisorNotifications(ShippingCompanySupervisor $supervisor): JsonResponse
    {
        $supervisor->update([
            'receives_all_notifications' => ! $supervisor->receives_all_notifications,
        ]);

        return response()->json([
            'success' => true,
            'enabled' => $supervisor->receives_all_notifications,
            'message' => $supervisor->receives_all_notifications
                ? 'تم تفعيل الإشعارات للمشرف.'
                : 'تم إيقاف الإشعارات للمشرف.',
        ]);
    }

    // ── Store Supervisor ───────────────────────────────────────────────────

    public function storeSupervisor(Request $request): JsonResponse
    {
        $data = $request->validate([
            'shipping_company_id' => ['required', 'exists:shipping_companies,id'],
            'country_id'          => ['nullable', 'exists:countries,id'],
            'name'                => ['required', 'string', 'max:255'],
            'email'               => ['required', 'email', 'max:255', 'unique:shipping_company_supervisors,email'],
            'phone'               => ['nullable', 'string', 'max:30'],
            'permissions'         => ['required', 'array', 'min:1'],
            'permissions.*'       => ['string', 'in:manage_agents,view_orders,assign_orders,view_reports'],
            'is_active'           => ['boolean'],
        ]);

        $tempPassword = Str::password(12, letters: true, numbers: true, symbols: false);

        $supervisor = ShippingCompanySupervisor::create([
            'shipping_company_id' => $data['shipping_company_id'],
            'country_id'          => $data['country_id'] ?? null,
            'name'                => $data['name'],
            'email'               => $data['email'],
            'phone'               => $data['phone'] ?? null,
            'password'            => $tempPassword,   // model casts hash automatically
            'permissions'         => $data['permissions'],
            'is_active'           => $data['is_active'] ?? true,
            'receives_all_notifications' => false,
        ]);

        // In production a SupervisorInviteMail would be dispatched here.
        // Returning the temp password so the admin can relay it manually.

        return response()->json([
            'success'       => true,
            'message'       => 'Supervisor created successfully.',
            'temp_password' => $tempPassword,
            'data'          => [
                'id'          => $supervisor->id,
                'name'        => $supervisor->name,
                'email'       => $supervisor->email,
                'country'     => $supervisor->country?->name_en,
                'permissions' => $supervisor->permissions,
            ],
        ], 201);
    }

    // ── Destroy Supervisor ─────────────────────────────────────────────────

    public function destroySupervisor(ShippingCompanySupervisor $supervisor): JsonResponse
    {
        $supervisor->delete();   // SoftDeletes trait handles this

        return response()->json(['success' => true, 'message' => 'Supervisor removed.']);
    }

    // ── Fallback Rules ─────────────────────────────────────────────────────

    public function fallbackRules(): View
    {
        $rules = ShippingFallbackRule::with(['unservedCity', 'fallbackShippingCompany'])
            ->orderBy('priority')
            ->paginate(30);

        $cities    = City::orderBy('name_en')->get(['id', 'name_en', 'name_ar']);
        $companies = ShippingCompany::active()->orderBy('name')->get(['id', 'name']);

        return view('admin.shipping-companies.fallback-rules', compact('rules', 'cities', 'companies'));
    }

    public function storeFallbackRule(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'unserved_city_id'              => ['required', 'exists:cities,id'],
            'fallback_shipping_company_id'  => ['required', 'exists:shipping_companies,id'],
            'priority'                      => ['required', 'integer', 'min:1'],
        ]);

        ShippingFallbackRule::create($data);

        return back()->with('success', 'تم إضافة قاعدة الاحتياط.');
    }

    public function destroyFallbackRule(ShippingFallbackRule $rule): RedirectResponse
    {
        $rule->delete();

        return back()->with('success', 'تم حذف قاعدة الاحتياط.');
    }
}
