<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\BankAccountRequest;
use App\Http\Resources\Vendor\BankAccountResource;
use App\Http\Resources\Vendor\LedgerEntryResource;
use App\Http\Resources\Vendor\PayoutDetailResource;
use App\Http\Resources\Vendor\PayoutResource;
use App\Http\Responses\ApiResponse;
use App\Models\LedgerEntry;
use App\Models\Payout;
use App\Models\Vendor;
use App\Models\VendorBankAccount;
use App\Services\Vendor\FinanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinanceController extends Controller
{
    public function __construct(private readonly FinanceService $financeService) {}

    private function vendor(): Vendor
    {
        /** @var \App\Models\VendorAdmin $auth */
        $auth = auth('vendor')->user();
        /** @var Vendor $vendor */
        $vendor = $auth->vendor;
        return $vendor;
    }

    private function vendorId(): string
    {
        /** @var \App\Models\VendorAdmin $auth */
        $auth = auth('vendor')->user();
        return $auth->vendor_id;
    }

    public function summary(): JsonResponse
    {
        return ApiResponse::success($this->financeService->getSummary($this->vendor()));
    }

    public function payouts(Request $request): JsonResponse
    {
        $vendorId = $this->vendorId();

        $query = Payout::where('vendor_id', $vendorId)
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->query('date_from'), fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->query('date_to'), fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->latest();

        $payouts = $query->paginate((int) ($request->query('per_page', 20)));

        return ApiResponse::paginated($payouts, PayoutResource::class);
    }

    public function showPayout(int $id): JsonResponse
    {
        $payout = Payout::where('vendor_id', $this->vendorId())
            ->with(['items', 'bankAccount'])
            ->findOrFail($id);

        return ApiResponse::success(new PayoutDetailResource($payout));
    }

    public function payoutInvoice(int $id): JsonResponse
    {
        $payout = Payout::where('vendor_id', $this->vendorId())->findOrFail($id);

        if (!$payout->receipt_url) {
            return ApiResponse::error('Invoice not yet available for this payout. It is generated after the payout completes.', [], 404);
        }

        return ApiResponse::success(['invoice_url' => $payout->receipt_url]);
    }

    public function ledger(Request $request): JsonResponse
    {
        $vendorId = $this->vendorId();

        // Strictly scoped: only entries where account_holder is this vendor.
        // Never leaks platform-side or other vendors' accounts even by id.
        $entries = LedgerEntry::where('account_holder_type', Vendor::class)
            ->where('account_holder_id', $vendorId)
            ->latest()
            ->paginate((int) ($request->query('per_page', 30)));

        return ApiResponse::paginated($entries, LedgerEntryResource::class);
    }

    public function commissionRates(): JsonResponse
    {
        return ApiResponse::success($this->financeService->resolveCommissionRates($this->vendor()));
    }

    // ── Bank Accounts ─────────────────────────────────────────────────────────

    public function bankAccounts(): JsonResponse
    {
        $accounts = VendorBankAccount::where('vendor_id', $this->vendorId())->get();

        return ApiResponse::success(BankAccountResource::collection($accounts)->resolve());
    }

    public function storeBankAccount(BankAccountRequest $request): JsonResponse
    {
        $account = $this->financeService->createBankAccount($this->vendor(), $request->validated());

        return ApiResponse::success(new BankAccountResource($account), 'Bank account added successfully.', 201);
    }

    public function setPrimaryBankAccount(string $id): JsonResponse
    {
        $account = VendorBankAccount::where('vendor_id', $this->vendorId())->findOrFail($id);

        $this->financeService->setPrimaryAccount($this->vendor(), $account);

        return ApiResponse::success(new BankAccountResource($account->fresh()), 'Primary bank account updated.');
    }

    public function deleteBankAccount(string $id): JsonResponse
    {
        $vendor  = $this->vendor();
        $account = VendorBankAccount::where('vendor_id', $vendor->id)->findOrFail($id);

        $this->financeService->deleteBankAccount($vendor, $account);

        return ApiResponse::success(null, 'Bank account removed.');
    }
}
