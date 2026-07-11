<?php

namespace App\Console\Commands;

use App\Enums\DeliveryAgentPayoutStatus;
use App\Enums\MarketerPayoutStatus;
use App\Models\Marketer;
use App\Models\MarketerConversion;
use App\Models\MarketerPayout;
use App\Models\Payout;
use App\Models\Vendor;
use App\Services\PayoutCalculationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Audit payouts that were generated before the currency-grouping bug was fixed.
 *
 * This command is READ-ONLY — it never modifies any row. It reports:
 *   - How many existing payouts had the wrong currency/amount due to blending
 *   - Whether any affected payouts are already 'paid'/'completed' (real money moved)
 *   - Pending/approved payouts that can be safely corrected before disbursement
 *
 * Run BEFORE deploying the currency-grouping fixes to measure blast radius.
 * DO NOT auto-correct 'paid'/'completed' payouts — report them for manual reconciliation.
 */
class AuditPayoutCurrencyBug extends Command
{
    protected $signature = 'audit:payout-currency-bug
                            {--output= : Path to write a CSV report (optional)}';

    protected $description = 'Read-only audit of payouts affected by the pre-fix currency-blending bug.';

    public function handle(PayoutCalculationService $calculationService): int
    {
        $this->info('=== Payout Currency Bug Audit ===');
        $this->info('READ-ONLY — no rows will be modified.');
        $this->newLine();

        $csvRows = [];

        $this->auditMarketerPayouts($csvRows);
        $this->auditVendorPayouts($csvRows, $calculationService);
        $this->auditDeliveryPayouts($csvRows);

        if ($path = $this->option('output')) {
            $this->writeCsv($path, $csvRows);
            $this->info("Report written to: {$path}");
        }

        return self::SUCCESS;
    }

    // ── Marketer payouts ──────────────────────────────────────────────────────

    private function auditMarketerPayouts(array &$csvRows): void
    {
        $this->info('--- MARKETER PAYOUTS ---');

        $affected = 0;
        $alreadyPaid = 0;

        MarketerPayout::with('marketer.country')->chunk(200, function ($payouts) use (&$affected, &$alreadyPaid, &$csvRows) {
            foreach ($payouts as $payout) {
                // Re-derive what this payout SHOULD have been by currency.
                $conversions = MarketerConversion::where('marketer_id', $payout->marketer_id)
                    ->where('status', 'paid')
                    ->whereBetween('paid_at', [$payout->period_start . ' 00:00:00', $payout->period_end . ' 23:59:59'])
                    ->get();

                if ($conversions->isEmpty()) {
                    continue;
                }

                $byCurrency = $conversions->groupBy('currency');
                $taxRate = $payout->marketer->country->marketer_withholding_tax_rate ?? 0;

                // If conversions span more than one currency, the stored payout blended them.
                $currencyMismatch = $byCurrency->count() > 1;

                // Check the stored currency matches the dominant group.
                foreach ($byCurrency as $currency => $group) {
                    $expectedGross = $group->sum('commission_amount_cents');
                    $expectedTax   = (int) round($expectedGross * ($taxRate / 100));
                    $expectedNet   = $expectedGross - $expectedTax;

                    $storedGross = $payout->gross_commission_cents;
                    $storedNet   = $payout->net_amount_cents;

                    $grossDiff = $storedGross - $expectedGross;
                    $netDiff   = $storedNet - $expectedNet;

                    // Flag if amounts differ OR the payout currency doesn't match this group.
                    if ($currencyMismatch || $payout->currency !== $currency || $grossDiff !== 0) {
                        $affected++;
                        $isPaid = $payout->status === MarketerPayoutStatus::Paid;
                        if ($isPaid) {
                            $alreadyPaid++;
                        }

                        $note = $isPaid
                            ? '*** ALREADY PAID — manual reconciliation required ***'
                            : 'Safe to recalculate (not yet paid)';

                        $this->line(sprintf(
                            '  MKT payout %s | marketer %s | stored currency=%s | group currency=%s | gross diff=%+d | net diff=%+d | status=%s | %s',
                            $payout->payout_number,
                            $payout->marketer_id,
                            $payout->currency,
                            $currency,
                            $grossDiff,
                            $netDiff,
                            $payout->status,
                            $note,
                        ));

                        $csvRows[] = [
                            'type'             => 'marketer',
                            'payout_number'    => $payout->payout_number,
                            'entity_id'        => $payout->marketer_id,
                            'stored_currency'  => $payout->currency,
                            'expected_currency'=> $currency,
                            'gross_diff_cents' => $grossDiff,
                            'net_diff_cents'   => $netDiff,
                            'status'           => $payout->status,
                            'already_disbursed'=> $isPaid ? 'YES' : 'no',
                        ];
                    }
                }
            }
        });

        $this->info("  Affected marketer payouts: {$affected} (of which already paid: {$alreadyPaid})");
        $this->newLine();
    }

    // ── Vendor payouts ────────────────────────────────────────────────────────

    private function auditVendorPayouts(array &$csvRows, PayoutCalculationService $calculationService): void
    {
        $this->info('--- VENDOR PAYOUTS ---');

        $affected = 0;
        $alreadyPaid = 0;

        Payout::chunk(200, function ($payouts) use ($calculationService, &$affected, &$alreadyPaid, &$csvRows) {
            foreach ($payouts as $payout) {
                $vendor = Vendor::find($payout->vendor_id);
                if (!$vendor) {
                    continue;
                }

                $calcByCurrency = $calculationService->calculateForVendor(
                    $vendor,
                    Carbon::parse($payout->period_start)->startOfDay(),
                    Carbon::parse($payout->period_end)->endOfDay(),
                );

                // The old code produced a single row with 'USD' hardcoded regardless of actual
                // sales currency, so compare stored against expected per-currency breakdown.
                $totalExpectedNet = array_sum(array_column($calcByCurrency, 'net_amount'));
                $netDiff = $payout->net_amount - $totalExpectedNet;

                $currenciesInData = implode(',', array_keys($calcByCurrency));
                $currencyWrong = ($payout->currency === 'USD' && $currenciesInData !== 'USD')
                    || count($calcByCurrency) > 1;

                if ($currencyWrong || $netDiff !== 0) {
                    $affected++;
                    // Vendor payout statuses: pending, approved, processing, completed, failed, on_hold
                    $isDisbursed = in_array($payout->status, ['processing', 'completed']);
                    if ($isDisbursed) {
                        $alreadyPaid++;
                    }

                    $note = $isDisbursed
                        ? '*** ALREADY DISBURSED — manual reconciliation required ***'
                        : 'Safe to recalculate (not yet disbursed)';

                    $this->line(sprintf(
                        '  VND payout %s | vendor %s | stored currency=%s | data currencies=%s | net diff=%+d | status=%s | %s',
                        $payout->payout_number,
                        $payout->vendor_id,
                        $payout->currency,
                        $currenciesInData ?: 'none',
                        $netDiff,
                        $payout->status,
                        $note,
                    ));

                    $csvRows[] = [
                        'type'             => 'vendor',
                        'payout_number'    => $payout->payout_number,
                        'entity_id'        => $payout->vendor_id,
                        'stored_currency'  => $payout->currency,
                        'expected_currency'=> $currenciesInData,
                        'gross_diff_cents' => 'n/a',
                        'net_diff_cents'   => $netDiff,
                        'status'           => $payout->status,
                        'already_disbursed'=> $isDisbursed ? 'YES' : 'no',
                    ];
                }
            }
        });

        $this->info("  Affected vendor payouts: {$affected} (of which already disbursed: {$alreadyPaid})");
        $this->newLine();
    }

    // ── Delivery agent payouts ────────────────────────────────────────────────

    private function auditDeliveryPayouts(array &$csvRows): void
    {
        $this->info('--- DELIVERY AGENT PAYOUTS ---');

        $affected = 0;
        $alreadyPaid = 0;

        DB::table('delivery_agent_payouts')->orderBy('id')->chunk(200, function ($payouts) use (&$affected, &$alreadyPaid, &$csvRows) {
            foreach ($payouts as $payout) {
                // Re-derive per-currency breakdown for this agent/period.
                $rows = DB::table('delivery_agent_earnings')
                    ->where('agent_id', $payout->agent_id)
                    ->where('status', 'approved')
                    ->whereBetween('created_at', [$payout->period_start . ' 00:00:00', $payout->period_end . ' 23:59:59'])
                    ->selectRaw('
                        currency,
                        SUM(CASE WHEN earning_type != ? THEN amount_cents ELSE 0 END) as gross_cents,
                        SUM(CASE WHEN earning_type = ? THEN ABS(amount_cents) ELSE 0 END) as deductions_cents
                    ', ['deduction', 'deduction'])
                    ->groupBy('currency')
                    ->get();

                if ($rows->isEmpty()) {
                    continue;
                }

                $currencyMismatch = $rows->count() > 1;
                $storedCurrencyRow = $rows->firstWhere('currency', $payout->currency);

                foreach ($rows as $row) {
                    $expectedNet = (int) $row->gross_cents - (int) $row->deductions_cents;
                    $netDiff = ($row->currency === $payout->currency)
                        ? ($payout->net_amount_cents - $expectedNet)
                        : null; // different currency group entirely — always a mismatch

                    if ($currencyMismatch || $payout->currency !== $row->currency || ($netDiff !== null && $netDiff !== 0)) {
                        $affected++;
                        $isDisbursed = $payout->status === DeliveryAgentPayoutStatus::Paid->value;
                        if ($isDisbursed) {
                            $alreadyPaid++;
                        }

                        $note = $isDisbursed
                            ? '*** ALREADY PAID — manual reconciliation required ***'
                            : 'Safe to recalculate (not yet paid)';

                        $this->line(sprintf(
                            '  DAP payout %s | agent %s | stored currency=%s | group currency=%s | net diff=%s | status=%s | %s',
                            $payout->payout_number,
                            $payout->agent_id,
                            $payout->currency,
                            $row->currency,
                            $netDiff !== null ? sprintf('%+d', $netDiff) : 'wrong-currency-group',
                            $payout->status,
                            $note,
                        ));

                        $csvRows[] = [
                            'type'             => 'delivery',
                            'payout_number'    => $payout->payout_number,
                            'entity_id'        => $payout->agent_id,
                            'stored_currency'  => $payout->currency,
                            'expected_currency'=> $row->currency,
                            'gross_diff_cents' => 'n/a',
                            'net_diff_cents'   => $netDiff ?? 'wrong-currency-group',
                            'status'           => $payout->status,
                            'already_disbursed'=> $isDisbursed ? 'YES' : 'no',
                        ];
                    }
                }
            }
        });

        $this->info("  Affected delivery payouts: {$affected} (of which already paid: {$alreadyPaid})");
        $this->newLine();
    }

    // ── CSV output ────────────────────────────────────────────────────────────

    private function writeCsv(string $path, array $rows): void
    {
        if (empty($rows)) {
            return;
        }

        $fp = fopen($path, 'w');
        fputcsv($fp, array_keys($rows[0]));
        foreach ($rows as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);
    }
}
