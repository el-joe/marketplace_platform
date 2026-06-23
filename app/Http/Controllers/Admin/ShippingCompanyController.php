<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Country;
use App\Models\ShippingCompany;
use App\Models\ShippingCompanySupervisor;
use App\Models\ShippingFallbackRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShippingCompanyController extends Controller
{
    public function index(): View
    {
        $stats = [
            'total'     => ShippingCompany::count(),
            'active'    => ShippingCompany::where('status', 'active')->count(),
            'pending'   => ShippingCompany::where('status', 'pending')->count(),
            'suspended' => ShippingCompany::where('status', 'suspended')->count(),
        ];

        $companies = ShippingCompany::with('country')
            ->withCount(['supervisors', 'agents'])
            ->latest()
            ->paginate(20);

        return view('admin.shipping-companies.index', compact('companies', 'stats'));
    }

    public function show(ShippingCompany $shippingCompany): View
    {
        $shippingCompany->load(['country', 'supervisors', 'agents' => fn ($q) => $q->limit(20)]);

        return view('admin.shipping-companies.show', compact('shippingCompany'));
    }

    public function approve(ShippingCompany $shippingCompany): RedirectResponse
    {
        $shippingCompany->update([
            'status'              => 'active',
            'approved_by_admin_id' => auth('admin')->id(),
            'approved_at'         => now(),
        ]);

        return back()->with('success', 'تم تفعيل شركة الشحن بنجاح.');
    }

    public function suspend(ShippingCompany $shippingCompany): RedirectResponse
    {
        $shippingCompany->update(['status' => 'suspended']);

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
