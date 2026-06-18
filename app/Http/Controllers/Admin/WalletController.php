<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\DeliveryAgent;
use App\Models\DeliveryAgentCodSettlement;
use App\Models\Wallet;
use App\Models\WalletWithdrawalRequest;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WalletController extends Controller
{
    public function __construct(private WalletService $walletService) {}

    public function index(Request $request)
    {
        $wallets = Wallet::query()
            ->when($request->owner_type, fn($q, $t) => $q->where('owner_type', $t))
            ->when($request->currency, fn($q, $c) => $q->where('currency', $c))
            ->when($request->frozen, fn($q) => $q->where('is_frozen', true))
            ->latest()
            ->paginate(25);

        return view('admin.wallets.index', compact('wallets'));
    }

    public function show(Wallet $wallet)
    {
        $transactions = $wallet->transactions()->paginate(30);
        return view('admin.wallets.show', compact('wallet', 'transactions'));
    }

    public function adjustBalance(Request $request, Wallet $wallet)
    {
        $data = $request->validate([
            'type'        => ['required', 'in:credit,debit'],
            'amount'      => ['required', 'numeric', 'min:0.01'],
            'description' => ['required', 'string', 'max:255'],
        ]);

        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();
        $amountCents = (int) round($data['amount'] * 100);

        if ($data['type'] === 'credit') {
            $this->walletService->credit($wallet, $amountCents, 'admin_adjustment', null, $data['description'], $admin->id);
        } else {
            $this->walletService->debit($wallet, $amountCents, 'admin_adjustment', null, $data['description'], $admin->id);
        }

        return back()->with('success', 'Balance adjusted successfully.');
    }

    public function freezeWallet(Request $request, Wallet $wallet)
    {
        $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $wallet->update(['is_frozen' => true, 'frozen_reason' => $request->reason]);
        return back()->with('success', 'Wallet frozen.');
    }

    public function unfreezeWallet(Wallet $wallet)
    {
        $wallet->update(['is_frozen' => false, 'frozen_reason' => null]);
        return back()->with('success', 'Wallet unfrozen.');
    }

    public function withdrawalRequests(Request $request)
    {
        $requests = WalletWithdrawalRequest::with('wallet')
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(25);

        return view('admin.wallets.withdrawal-requests', compact('requests'));
    }

    public function approveWithdrawal(WalletWithdrawalRequest $withdrawal)
    {
        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();
        $this->walletService->approveWithdrawal($withdrawal, $admin);
        return back()->with('success', 'Withdrawal approved.');
    }

    public function rejectWithdrawal(Request $request, WalletWithdrawalRequest $withdrawal)
    {
        $request->validate(['reason' => ['required', 'string', 'max:500']]);
        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();
        $this->walletService->rejectWithdrawal($withdrawal, $admin, $request->reason);
        return back()->with('success', 'Withdrawal rejected.');
    }

    public function markWithdrawalProcessed(WalletWithdrawalRequest $withdrawal)
    {
        $withdrawal->update(['status' => 'processed', 'processed_at' => now()]);
        return back()->with('success', 'Marked as processed.');
    }

    public function codSettlements(Request $request)
    {
        $settlements = DeliveryAgentCodSettlement::with('agent')
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(25);

        $agents = DeliveryAgent::select('id', 'name')->orderBy('name')->get();

        return view('admin.wallets.cod-settlements', compact('settlements', 'agents'));
    }

    public function runCodSettlement(Request $request)
    {
        $data = $request->validate([
            'agent_id'     => ['required', 'exists:delivery_agents,id'],
            'period_start' => ['required', 'date'],
            'period_end'   => ['required', 'date', 'after_or_equal:period_start'],
        ]);

        $agent = DeliveryAgent::findOrFail($data['agent_id']);
        $settlement = $this->walletService->settleAgentCod($agent, $data['period_start'], $data['period_end']);

        return back()->with('success', "Settlement created (net: {$settlement->net_to_remit_cents} cents).");
    }

    public function markSettlementSettled(DeliveryAgentCodSettlement $settlement)
    {
        $settlement->update(['status' => 'settled', 'settled_at' => now()]);
        return back()->with('success', 'Settlement marked as settled.');
    }
}
