<?php

namespace App\Jobs;

use App\Models\Payout;
use App\Models\Vendor;
use App\Models\VendorBankAccount;
use App\Services\PayoutCalculationService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateVendorPayoutsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param  \Carbon\Carbon  $periodStart  Start of the payout period (inclusive)
     * @param  \Carbon\Carbon  $periodEnd    End of the payout period (inclusive)
     */
    public function __construct(
        private readonly Carbon $periodStart,
        private readonly Carbon $periodEnd
    ) {
    }

    public function handle(PayoutCalculationService $calculationService): void
    {
        $generated = 0;

        Vendor::where('status', 'active')
            ->whereHas('bankAccounts', fn($q) => $q->where('is_primary', true)
                ->where('verification_status', 'verified'))
            ->chunkById(50, function ($vendors) use ($calculationService, &$generated) {
                foreach ($vendors as $vendor) {
                    // Skip if a payout already exists for this vendor/period
                    $exists = Payout::where('vendor_id', $vendor->id)
                        ->where('period_start', $this->periodStart->toDateString())
                        ->where('period_end', $this->periodEnd->toDateString())
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    $primaryAccount = VendorBankAccount::where('vendor_id', $vendor->id)
                        ->where('is_primary', true)
                        ->where('verification_status', 'verified')
                        ->first();

                    if (!$primaryAccount) {
                        continue;
                    }

                    $calc = $calculationService->calculateForVendor(
                        $vendor,
                        $this->periodStart,
                        $this->periodEnd
                    );

                    // Skip vendors with no payable earnings
                    if ($calc['net_amount'] <= 0) {
                        continue;
                    }

                    Payout::create([
                        'payout_number' => $this->generatePayoutNumber(),
                        'vendor_id' => $vendor->id,
                        'period_start' => $calc['period_start'],
                        'period_end' => $calc['period_end'],
                        'gross_sales' => $calc['gross_sales'],
                        'commission' => $calc['commission'],
                        'refunds_deducted' => $calc['refunds_deducted'],
                        'chargebacks_deducted' => $calc['chargebacks_deducted'],
                        'storage_fees' => $calc['storage_fees'],
                        'ad_fees' => $calc['ad_fees'],
                        'other_adjustments' => $calc['other_adjustments'],
                        'net_amount' => $calc['net_amount'],
                        'currency' => $primaryAccount->currency ?? 'USD',
                        'status' => 'pending',
                        'bank_account_id' => $primaryAccount->id,
                        'payout_method' => 'bank_transfer',
                    ]);

                    $generated++;
                }
            });

        Log::info("GenerateVendorPayoutsJob: generated {$generated} payout(s) for period {$this->periodStart->toDateString()} → {$this->periodEnd->toDateString()}.");
    }

    private function generatePayoutNumber(): string
    {
        $sequence = str_pad((string) (Payout::count() + 1), 5, '0', STR_PAD_LEFT);
        return 'PO-' . now()->format('Y') . '-' . $sequence;
    }
}
