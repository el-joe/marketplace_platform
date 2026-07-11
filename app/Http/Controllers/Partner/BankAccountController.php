<?php

namespace App\Http\Controllers\Partner;

use App\Enums\VendorBankAccountVerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\Payout;
use App\Models\VendorBankAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BankAccountController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function vendorId(): string
    {
        return Auth::guard('vendor')->user()->vendor_id;
    }

    private function authorise(VendorBankAccount $account): void
    {
        if ($account->vendor_id !== $this->vendorId()) {
            abort(403);
        }
    }

    private function maskIban(string $iban): string
    {
        $clean = preg_replace('/\s+/', '', $iban);
        $len = strlen($clean);
        if ($len <= 4) {
            return $clean;
        }
        return str_repeat('*', $len - 4) . substr($clean, -4);
    }

    private function formatAccount(VendorBankAccount $account): array
    {
        return [
            'id' => $account->id,
            'account_holder_name' => $account->account_holder_name,
            'bank_name' => $account->bank_name,
            'branch' => $account->branch,
            'iban_masked' => $this->maskIban($account->iban),
            'swift_code' => $account->swift_code,
            'currency' => $account->currency,
            'is_primary' => $account->is_primary,
            'verification_status' => $account->verification_status->value,
            'created_at' => $account->created_at?->format('Y-m-d'),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Index
    // ─────────────────────────────────────────────────────────────────────────

    public function index(): View
    {
        $accounts = VendorBankAccount::where('vendor_id', $this->vendorId())
            ->orderByDesc('is_primary')
            ->orderBy('created_at')
            ->get();

        return view('partner.bank-accounts.index', compact('accounts'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Store
    // ─────────────────────────────────────────────────────────────────────────

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'account_holder_name' => ['required', 'string', 'max:100'],
            'bank_name' => ['required', 'string', 'max:100'],
            'branch' => ['nullable', 'string', 'max:100'],
            'iban' => ['required', 'string', 'max:34'],
            'swift_code' => ['nullable', 'string', 'max:11'],
            'currency' => ['required', 'string', 'size:3'],
        ]);

        $account = VendorBankAccount::create([
            ...$data,
            'vendor_id' => $this->vendorId(),
            'account_number_encrypted' => Crypt::encryptString($data['iban']),
            'verification_status' => VendorBankAccountVerificationStatus::Pending,
            'is_primary' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة الحساب البنكي. سيتم مراجعته خلال يومي عمل.',
            'account' => $this->formatAccount($account),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Set Primary
    // ─────────────────────────────────────────────────────────────────────────

    public function setPrimary(VendorBankAccount $account): JsonResponse
    {
        $this->authorise($account);

        if ($account->verification_status !== VendorBankAccountVerificationStatus::Verified) {
            return response()->json([
                'success' => false,
                'message' => 'يمكن تعيين الحسابات المعتمدة فقط كحسابات رئيسية.',
            ], 422);
        }

        DB::transaction(function () use ($account) {
            VendorBankAccount::where('vendor_id', $this->vendorId())
                ->where('is_primary', true)
                ->update(['is_primary' => false]);

            $account->update(['is_primary' => true]);
        });

        return response()->json([
            'success' => true,
            'message' => 'تم تعيينه كالحساب الرئيسي.',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Destroy
    // ─────────────────────────────────────────────────────────────────────────

    public function destroy(VendorBankAccount $account): JsonResponse
    {
        $this->authorise($account);

        if ($account->is_primary && $account->verification_status === VendorBankAccountVerificationStatus::Verified) {
            $hasPendingPayouts = Payout::where('vendor_id', $this->vendorId())
                ->where('bank_account_id', $account->id)
                ->whereIn('status', ['pending', 'approved', 'processing'])
                ->exists();

            if ($hasPendingPayouts) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن حذف الحساب الرئيسي المعتمد أثناء وجود مدفوعات معلقة.',
                ], 422);
            }
        }

        $account->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الحساب البنكي.',
        ]);
    }
}
