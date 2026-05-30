<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\CountryPaymentMethod;
use App\Services\Payment\PaymentGatewayFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentMethodController extends Controller
{
    public function index()
    {
        $countries = Country::where('is_active', 1)
            ->with(['countryPaymentMethods' => fn($q) => $q->orderBy('sort_order')])
            ->orderBy('name_en')
            ->get();

        $methodTypes = [
            'card' => ['label' => 'Credit/Debit Card', 'icon' => '💳'],
            'cod' => ['label' => 'Cash on Delivery', 'icon' => '💵'],
            'wallet' => ['label' => 'Digital Wallet', 'icon' => '👛'],
            'bank_transfer' => ['label' => 'Bank Transfer', 'icon' => '🏦'],
            'bnpl' => ['label' => 'Buy Now Pay Later', 'icon' => '📆'],
        ];

        $gateways = app(PaymentGatewayFactory::class)->all();

        return view('admin.payment-methods.index', compact('countries', 'methodTypes', 'gateways'));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'country_id' => ['required', 'exists:countries,id'],
            'method_type' => ['required', Rule::in(['card', 'cod', 'wallet', 'bank_transfer', 'bnpl'])],
            'provider' => ['nullable', 'string', 'max:50'],
            'display_name_en' => ['required', 'string', 'max:100'],
            'display_name_ar' => ['nullable', 'string', 'max:100'],
            'is_active' => ['boolean'],
            'fee_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'fee_fixed_cents' => ['nullable', 'integer', 'min:0'],
            'min_order_cents' => ['nullable', 'integer', 'min:0'],
            'max_order_cents' => ['nullable', 'integer', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $method = CountryPaymentMethod::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Payment method created.',
            'data' => $method,
        ], 201);
    }

    public function update(Request $request, CountryPaymentMethod $method): JsonResponse
    {
        $data = $request->validate([
            'method_type' => ['sometimes', Rule::in(['card', 'cod', 'wallet', 'bank_transfer', 'bnpl'])],
            'provider' => ['nullable', 'string', 'max:50'],
            'display_name_en' => ['sometimes', 'required', 'string', 'max:100'],
            'display_name_ar' => ['nullable', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
            'fee_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'fee_fixed_cents' => ['nullable', 'integer', 'min:0'],
            'min_order_cents' => ['nullable', 'integer', 'min:0'],
            'max_order_cents' => ['nullable', 'integer', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $method->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Payment method updated.',
            'data' => $method->fresh(),
        ]);
    }

    public function destroy(CountryPaymentMethod $method): JsonResponse
    {
        $method->delete();

        return response()->json(['success' => true, 'message' => 'Payment method removed.']);
    }

    public function toggleActive(CountryPaymentMethod $method): JsonResponse
    {
        $method->update(['is_active' => !$method->is_active]);

        return response()->json([
            'success' => true,
            'message' => 'Status updated.',
            'is_active' => $method->is_active,
        ]);
    }

    public function updateSortOrder(Request $request): JsonResponse
    {
        $items = $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'exists:country_payment_methods,id'],
            'items.*.sort_order' => ['required', 'integer', 'min:0'],
        ])['items'];

        foreach ($items as $item) {
            CountryPaymentMethod::where('id', $item['id'])
                ->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json(['success' => true, 'message' => 'Sort order updated.']);
    }

    public function testGateway(Request $request): JsonResponse
    {
        $request->validate(['provider' => ['required', 'string']]);

        try {
            $gateway = app(PaymentGatewayFactory::class)->make($request->provider);
            $result = $gateway->testConnection();
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'data' => ['success' => false, 'latency_ms' => 0, 'message' => $e->getMessage()],
            ], 422);
        }

        return response()->json(['success' => true, 'data' => $result]);
    }

    public function gatewayConfig()
    {
        $gateways = app(PaymentGatewayFactory::class)->all();

        return view('admin.payment-methods.gateway-config', compact('gateways'));
    }
}
