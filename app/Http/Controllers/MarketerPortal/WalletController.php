<?php

namespace App\Http\Controllers\MarketerPortal;

use App\Http\Controllers\Controller;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WalletController extends Controller
{
    public function __construct(private WalletService $walletService) {}

    public function index()
    {
        $marketer = Auth::guard('marketer')->user();
        $wallet = $this->walletService->getOrCreateWallet('marketer', $marketer->id, 'EGP');
        $transactions = $wallet->transactions()->paginate(20);
        $withdrawalRequests = $wallet->withdrawalRequests()->latest()->take(10)->get();

        return view('marketer.wallet.index', compact('wallet', 'transactions', 'withdrawalRequests'));
    }

    public function requestWithdrawal(Request $request)
    {
        $marketer = Auth::guard('marketer')->user();
        $wallet = $this->walletService->getOrCreateWallet('marketer', $marketer->id, 'EGP');

        $data = $request->validate([
            'amount'    => ['required', 'numeric', 'min:1'],
            'bank_name' => ['required', 'string', 'max:150'],
            'bank_iban' => ['required', 'string', 'max:50'],
        ]);

        $amountCents = (int) round($data['amount'] * 100);

        $this->walletService->requestWithdrawal($wallet, $amountCents, [
            'bank_name' => $data['bank_name'],
            'bank_iban' => $data['bank_iban'],
        ]);

        return back()->with('success', 'Withdrawal request submitted successfully.');
    }
}
