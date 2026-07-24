<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CountryPaymentMethodEnvironment;
use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\CountryPaymentMethod;
use App\Models\Currency;
use App\Models\PaymentTransaction;
use App\Services\Payment\PaymentGatewayFactory as LegacyPaymentGatewayFactory;
use App\Services\PaymentService;
use App\Services\Payments\PaymentGatewayFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
            'card'          => ['label' => 'Credit/Debit Card', 'icon' => '💳'],
            'cod'           => ['label' => 'Cash on Delivery', 'icon' => '💵'],
            'wallet'        => ['label' => 'Digital Wallet', 'icon' => '👛'],
            'bank_transfer' => ['label' => 'Bank Transfer', 'icon' => '🏦'],
            'bnpl'          => ['label' => 'Buy Now Pay Later', 'icon' => '📆'],
        ];

        $gateways          = app(LegacyPaymentGatewayFactory::class)->all();
        $availableGateways = PaymentGatewayFactory::availableCodes();
        $currencies        = Currency::where('is_active', 1)->orderBy('code')->get();

        $methods = CountryPaymentMethod::with('country')
            ->orderBy('country_id')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('country_id');

        return view('admin.payment-methods.index', compact(
            'countries', 'methodTypes', 'gateways', 'availableGateways', 'currencies', 'methods'
        ));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'country_id'           => ['required', 'exists:countries,id'],
            'method_type'          => ['required', Rule::in(['card', 'cod', 'wallet', 'bank_transfer', 'bnpl'])],
            'provider'             => ['nullable', 'string', 'max:50'],
            'gateway_code'         => ['nullable', 'string', 'max:50', Rule::in(array_merge(PaymentGatewayFactory::availableCodes(), ['']))],
            'display_name_en'      => ['required', 'string', 'max:100'],
            'display_name_ar'      => ['nullable', 'string', 'max:100'],
            'is_active'            => ['boolean'],
            'fee_pct'              => ['nullable', 'numeric', 'min:0', 'max:100'],
            'fee_fixed'      => ['nullable', 'integer', 'min:0'],
            'min_order'      => ['nullable', 'integer', 'min:0'],
            'max_order'      => ['nullable', 'integer', 'min:0', 'gte:min_order'],
            'settlement_currency'  => ['nullable', 'string', 'size:3', 'exists:currencies,code'],
            'environment'          => ['nullable', Rule::enum(CountryPaymentMethodEnvironment::class)],
            'sort_order'           => ['nullable', 'integer', 'min:0'],
            'installments_count'   => ['nullable', 'integer', 'min:2', 'max:60'],
            'installment_label_en' => ['nullable', 'string', 'max:200'],
            'installment_label_ar' => ['nullable', 'string', 'max:200'],
            'provider_logo_path'   => ['nullable', 'string', 'max:255'],
            'learn_more_url'       => ['nullable', 'url', 'max:500'],
            'credentials'          => ['nullable', 'array'],
            'credentials.*'        => ['nullable', 'string'],
            'webhook_secret'       => ['nullable', 'string'],
        ]);

        $insertData = array_filter($data, fn($k) => !in_array($k, ['credentials', 'webhook_secret']), ARRAY_FILTER_USE_KEY);
        $insertData['min_order'] = $insertData['min_order'] ?? 0;

        $method = CountryPaymentMethod::create($insertData);

        if (!empty($data['credentials'])) {
            $method->setCredentials(array_filter($data['credentials']));
        }
        if (!empty($data['webhook_secret'])) {
            $method->setWebhookSecret($data['webhook_secret']);
        }

        Cache::forget("benefits:bnpl:{$method->country_id}");
        Cache::forget("benefits:bank:{$method->country_id}");

        return response()->json([
            'success' => true,
            'message' => 'Payment method created.',
            'data'    => $method->fresh()->append(['is_configured']),
        ], 201);
    }

    public function update(Request $request, CountryPaymentMethod $method): JsonResponse
    {
        $data = $request->validate([
            'method_type'         => ['sometimes', Rule::in(['card', 'cod', 'wallet', 'bank_transfer', 'bnpl'])],
            'provider'            => ['nullable', 'string', 'max:50'],
            'gateway_code'        => ['nullable', 'string', 'max:50'],
            'display_name_en'     => ['sometimes', 'required', 'string', 'max:100'],
            'display_name_ar'     => ['nullable', 'string', 'max:100'],
            'is_active'           => ['sometimes', 'boolean'],
            'fee_pct'             => ['nullable', 'numeric', 'min:0', 'max:100'],
            'fee_fixed'     => ['nullable', 'integer', 'min:0'],
            'min_order'     => ['nullable', 'integer', 'min:0'],
            'max_order'     => ['nullable', 'integer', 'min:0', 'gte:min_order'],
            'settlement_currency' => ['nullable', 'string', 'size:3', 'exists:currencies,code'],
            'environment'         => ['nullable', Rule::enum(CountryPaymentMethodEnvironment::class)],
            'sort_order'          => ['nullable', 'integer', 'min:0'],
            'installments_count'   => ['nullable', 'integer', 'min:2', 'max:60'],
            'installment_label_en' => ['nullable', 'string', 'max:200'],
            'installment_label_ar' => ['nullable', 'string', 'max:200'],
            'provider_logo_path'   => ['nullable', 'string', 'max:255'],
            'learn_more_url'       => ['nullable', 'url', 'max:500'],
            'credentials'         => ['nullable', 'array'],
            'credentials.*'       => ['nullable', 'string'],
            'webhook_secret'      => ['nullable', 'string'],
        ]);

        $method->update(
            array_filter($data, fn($k) => !in_array($k, ['credentials', 'webhook_secret']), ARRAY_FILTER_USE_KEY)
        );

        // Only update credentials/secret when non-empty values are submitted (preserve existing on blank)
        if (!empty(array_filter($data['credentials'] ?? []))) {
            $method->setCredentials(array_filter($data['credentials']));
        }
        if (!empty($data['webhook_secret'])) {
            $method->setWebhookSecret($data['webhook_secret']);
        }

        Cache::forget("benefits:bnpl:{$method->country_id}");
        Cache::forget("benefits:bank:{$method->country_id}");

        return response()->json([
            'success' => true,
            'message' => 'Payment method updated.',
            'data'    => $method->fresh()->append(['is_configured']),
        ]);
    }

    public function destroy(CountryPaymentMethod $method): JsonResponse
    {
        $hasTransactions = PaymentTransaction::where('gateway', $method->gateway_code)->exists();

        if ($hasTransactions) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete — transactions exist for this gateway. Deactivate instead.',
            ], 422);
        }

        $method->delete();

        return response()->json(['success' => true, 'message' => 'Payment method removed.']);
    }

    public function toggleActive(CountryPaymentMethod $method): JsonResponse
    {
        $method->update(['is_active' => !$method->is_active]);

        return response()->json([
            'success'   => true,
            'message'   => 'Status updated.',
            'is_active' => $method->is_active,
        ]);
    }

    public function updateSortOrder(Request $request): JsonResponse
    {
        $items = $request->validate([
            'items'             => ['required', 'array'],
            'items.*.id'        => ['required', 'exists:country_payment_methods,id'],
            'items.*.sort_order' => ['required', 'integer', 'min:0'],
        ])['items'];

        foreach ($items as $item) {
            CountryPaymentMethod::where('id', $item['id'])
                ->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json(['success' => true, 'message' => 'Sort order updated.']);
    }

    public function testConnection(CountryPaymentMethod $method): JsonResponse
    {
        try {
            $result = app(PaymentService::class)->testGatewayConnection($method);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => $result->success,
            'message' => $result->message,
            'details' => $result->details,
        ]);
    }

    public function webhookLogs(CountryPaymentMethod $method): JsonResponse
    {
        $logs = $method->webhookLogs()
            ->latest('created_at')
            ->paginate(50);

        return response()->json($logs);
    }

    public function gatewayIndex()
    {
        $countries         = Country::where('is_active', 1)->orderBy('name_en')->get();
        $availableGateways = PaymentGatewayFactory::availableCodes();
        $currencies        = Currency::where('is_active', 1)->orderBy('code')->get();

        $methods = CountryPaymentMethod::with('country')
            ->orderBy('country_id')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('country_id');

        return view('admin.payment-gateways.index', compact(
            'countries', 'availableGateways', 'currencies', 'methods'
        ));
    }

    // ── Legacy methods (existing config-based gateways) ───────────────────────

    public function testGateway(Request $request): JsonResponse
    {
        $request->validate(['provider' => ['required', 'string']]);

        try {
            $gateway = app(LegacyPaymentGatewayFactory::class)->make($request->provider);
            $result  = $gateway->testConnection();
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'data'    => ['success' => false, 'latency_ms' => 0, 'message' => $e->getMessage()],
            ], 422);
        }

        return response()->json(['success' => true, 'data' => $result]);
    }

    public function gatewayConfig()
    {
        $gateways = app(LegacyPaymentGatewayFactory::class)->allWithCodes();
        return view('admin.payment-methods.gateway-config', compact('gateways'));
    }
}
