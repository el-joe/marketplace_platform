<?php

namespace App\Services;

use App\Exceptions\InsufficientBalanceException;
use App\Models\Admin;
use App\Models\DeliveryAgent;
use App\Models\DeliveryAgentCodSettlement;
use App\Models\DeliveryAgentEarning;
use App\Models\Order;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\WalletWithdrawalRequest;
use Illuminate\Support\Facades\DB;

class WalletService
{
    public function getOrCreateWallet(string $ownerType, string $ownerId, string $currency): Wallet
    {
        return Wallet::firstOrCreate(
            ['owner_type' => $ownerType, 'owner_id' => $ownerId, 'currency' => $currency],
            ['balance_cents' => 0, 'pending_balance_cents' => 0]
        );
    }

    public function credit(
        Wallet $wallet,
        int $amountCents,
        string $sourceType,
        ?string $sourceId,
        string $description,
        ?string $performedByAdminId = null
    ): WalletTransaction {
        if ($wallet->is_frozen) {
            throw new \RuntimeException('Wallet is frozen: ' . ($wallet->frozen_reason ?? 'no reason given'));
        }

        return DB::transaction(function () use ($wallet, $amountCents, $sourceType, $sourceId, $description, $performedByAdminId) {
            $wallet->lockForUpdate()->refresh();
            $newBalance = $wallet->balance_cents + $amountCents;
            $wallet->update(['balance_cents' => $newBalance]);

            return WalletTransaction::create([
                'wallet_id'              => $wallet->id,
                'type'                   => 'credit',
                'amount_cents'           => $amountCents,
                'balance_after_cents'    => $newBalance,
                'source_type'            => $sourceType,
                'source_id'              => $sourceId,
                'description'            => $description,
                'performed_by_admin_id'  => $performedByAdminId,
                'created_at'             => now(),
            ]);
        });
    }

    public function debit(
        Wallet $wallet,
        int $amountCents,
        string $sourceType,
        ?string $sourceId,
        string $description,
        ?string $performedByAdminId = null
    ): WalletTransaction {
        if ($wallet->is_frozen) {
            throw new \RuntimeException('Wallet is frozen: ' . ($wallet->frozen_reason ?? 'no reason given'));
        }

        return DB::transaction(function () use ($wallet, $amountCents, $sourceType, $sourceId, $description, $performedByAdminId) {
            $wallet->lockForUpdate()->refresh();

            if ($wallet->balance_cents < $amountCents) {
                throw new InsufficientBalanceException($amountCents, $wallet->balance_cents, $wallet->currency);
            }

            $newBalance = $wallet->balance_cents - $amountCents;
            $wallet->update(['balance_cents' => $newBalance]);

            return WalletTransaction::create([
                'wallet_id'              => $wallet->id,
                'type'                   => 'debit',
                'amount_cents'           => $amountCents,
                'balance_after_cents'    => $newBalance,
                'source_type'            => $sourceType,
                'source_id'              => $sourceId,
                'description'            => $description,
                'performed_by_admin_id'  => $performedByAdminId,
                'created_at'             => now(),
            ]);
        });
    }

    public function requestWithdrawal(Wallet $wallet, int $amountCents, array $bankDetails): WalletWithdrawalRequest
    {
        return DB::transaction(function () use ($wallet, $amountCents, $bankDetails) {
            $wallet->lockForUpdate()->refresh();

            if ($wallet->balance_cents < $amountCents) {
                throw new InsufficientBalanceException($amountCents, $wallet->balance_cents, $wallet->currency);
            }

            // Reserve the amount: move from balance to pending
            $wallet->update([
                'balance_cents'         => $wallet->balance_cents - $amountCents,
                'pending_balance_cents' => $wallet->pending_balance_cents + $amountCents,
            ]);

            return WalletWithdrawalRequest::create([
                'wallet_id'    => $wallet->id,
                'amount_cents' => $amountCents,
                'currency'     => $wallet->currency,
                'bank_name'    => $bankDetails['bank_name'] ?? null,
                'bank_iban'    => $bankDetails['bank_iban'] ?? null,
                'status'       => 'pending',
            ]);
        });
    }

    public function approveWithdrawal(WalletWithdrawalRequest $request, Admin $admin): WalletWithdrawalRequest
    {
        return DB::transaction(function () use ($request, $admin) {
            $wallet = $request->wallet()->lockForUpdate()->first();

            // Release from pending and finalize debit
            $wallet->update([
                'pending_balance_cents' => max(0, $wallet->pending_balance_cents - $request->amount_cents),
            ]);

            WalletTransaction::create([
                'wallet_id'             => $wallet->id,
                'type'                  => 'debit',
                'amount_cents'          => $request->amount_cents,
                'balance_after_cents'   => $wallet->balance_cents,
                'source_type'           => 'withdrawal',
                'source_id'             => $request->id,
                'description'           => 'Withdrawal approved',
                'performed_by_admin_id' => $admin->id,
                'created_at'            => now(),
            ]);

            $request->update([
                'status'                => 'approved',
                'approved_by_admin_id'  => $admin->id,
            ]);

            return $request;
        });
    }

    public function rejectWithdrawal(WalletWithdrawalRequest $request, Admin $admin, string $reason): WalletWithdrawalRequest
    {
        return DB::transaction(function () use ($request, $admin, $reason) {
            $wallet = $request->wallet()->lockForUpdate()->first();

            // Return reserved amount back to balance
            $wallet->update([
                'balance_cents'         => $wallet->balance_cents + $request->amount_cents,
                'pending_balance_cents' => max(0, $wallet->pending_balance_cents - $request->amount_cents),
            ]);

            $request->update([
                'status'           => 'rejected',
                'rejection_reason' => $reason,
            ]);

            return $request;
        });
    }

    public function settleAgentCod(DeliveryAgent $agent, string $periodStart, string $periodEnd): DeliveryAgentCodSettlement
    {
        // Sum COD order totals delivered by this agent in the period
        $codCollected = DB::table('orders')
            ->join('delivery_assignments', 'delivery_assignments.order_id', '=', 'orders.id')
            ->where('delivery_assignments.agent_id', $agent->id)
            ->where('delivery_assignments.status', 'delivered')
            ->where('orders.payment_method', 'cod')
            ->whereBetween('delivery_assignments.delivered_at', [$periodStart . ' 00:00:00', $periodEnd . ' 23:59:59'])
            ->sum('orders.total_cents');

        // Sum approved earnings owed to this agent in the period
        $earningsOwed = DeliveryAgentEarning::where('agent_id', $agent->id)
            ->where('status', 'approved')
            ->whereBetween('created_at', [$periodStart . ' 00:00:00', $periodEnd . ' 23:59:59'])
            ->sum('amount_cents');

        $net = (int) $codCollected - (int) $earningsOwed;

        return DB::transaction(function () use ($agent, $periodStart, $periodEnd, $codCollected, $earningsOwed, $net) {
            $settlement = DeliveryAgentCodSettlement::create([
                'agent_id'                   => $agent->id,
                'period_start'               => $periodStart,
                'period_end'                 => $periodEnd,
                'total_cod_collected_cents'  => (int) $codCollected,
                'total_earnings_owed_cents'  => (int) $earningsOwed,
                'net_to_remit_cents'         => $net,
                'status'                     => 'pending',
            ]);

            $wallet = $this->getOrCreateWallet('delivery_agent', $agent->id, 'EGP');

            if ($net > 0) {
                // Agent collected more COD cash than owed — owes platform; debit wallet
                $this->debit($wallet, $net, 'cod_settlement', $settlement->id, "COD settlement {$periodStart} – {$periodEnd}");
            } elseif ($net < 0) {
                // Platform owes agent — credit wallet
                $this->credit($wallet, abs($net), 'cod_settlement', $settlement->id, "COD settlement {$periodStart} – {$periodEnd}");
            }

            return $settlement;
        });
    }
}
