<?php

namespace App\Services\Delivery;

use App\Jobs\CustomerDeliveredNotificationJob;
use App\Jobs\GenerateVendorPayoutsJob;
use App\Jobs\NotifyCustomerFailedDeliveryJob;
use App\Jobs\NotifyOperationsTeamJob;
use App\Models\DeliveryAgent;
use App\Models\DeliveryAgentEarning;
use App\Models\DeliveryAssignment;
use App\Models\ShipmentTrackingEvent;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class AssignmentService
{
    private const CUMULATIVE_FAIL_LIMIT = 3;

    public function __construct(
        private readonly OtpVerificationService  $otpService,
        private readonly ProofOfDeliveryService  $proofService,
        private readonly RtoService              $rtoService,
    ) {}

    // ── accept ────────────────────────────────────────────────────────────────

    /**
     * @throws \RuntimeException with key 'concurrent_assignment' if agent already has
     *         an active (accepted/picked_up) assignment — one-at-a-time dispatch model.
     */
    public function accept(DeliveryAssignment $assignment, DeliveryAgent $agent): void
    {
        if ($assignment->status !== DeliveryAssignment::STATUS_ASSIGNED) {
            throw new \DomainException('Assignment is not in assigned state.');
        }

        $hasActive = DeliveryAssignment::where('agent_id', $agent->id)
            ->whereIn('status', [
                DeliveryAssignment::STATUS_ACCEPTED,
                DeliveryAssignment::STATUS_PICKED_UP,
            ])
            ->exists();

        if ($hasActive) {
            throw new \RuntimeException('concurrent_assignment');
        }

        $assignment->update([
            'status'      => DeliveryAssignment::STATUS_ACCEPTED,
            'accepted_at' => now(),
        ]);
    }

    // ── pickup ────────────────────────────────────────────────────────────────

    public function pickup(DeliveryAssignment $assignment, float $latitude, float $longitude): void
    {
        if ($assignment->status !== DeliveryAssignment::STATUS_ACCEPTED) {
            throw new \DomainException('Assignment is not in accepted state.');
        }

        DB::transaction(function () use ($assignment, $latitude, $longitude) {
            $assignment->update([
                'status'           => DeliveryAssignment::STATUS_PICKED_UP,
                'picked_up_at'     => now(),
                'pickup_latitude'  => $latitude,
                'pickup_longitude' => $longitude,
            ]);

            if ($assignment->shipment) {
                $assignment->shipment->update([
                    'status'        => 'picked_up',
                    'picked_up_at'  => now(),
                ]);

                ShipmentTrackingEvent::create([
                    'shipment_id'  => $assignment->shipment_id,
                    'status'       => 'picked_up',
                    'description'  => 'Package picked up by delivery agent.',
                    'location'     => "{$latitude},{$longitude}",
                    'occurred_at'  => now(),
                ]);
            }

            $assignment->subOrder?->update(['status' => 'out_for_delivery']);
        });
    }

    // ── verifyOtp ─────────────────────────────────────────────────────────────

    /**
     * Verify the customer-provided OTP code.
     * Returns true on match, false on mismatch.
     *
     * @throws \RuntimeException('otp_locked') when lockout is reached
     */
    public function verifyOtp(DeliveryAssignment $assignment, string $code): bool
    {
        if ($assignment->status !== DeliveryAssignment::STATUS_PICKED_UP) {
            throw new \DomainException('Assignment is not in picked_up state.');
        }

        if ($this->otpService->isLocked($assignment)) {
            throw new \RuntimeException('otp_locked');
        }

        $matched = $this->otpService->verify($assignment, $code);

        if ($matched) {
            $assignment->update(['otp_verified' => true]);
        }

        return $matched;
    }

    // ── deliver ───────────────────────────────────────────────────────────────

    public function deliver(
        DeliveryAssignment $assignment,
        DeliveryAgent      $agent,
        string             $otpCode,
        UploadedFile       $proofImage,
        float              $latitude,
        float              $longitude,
        ?int               $codAmountCollectedCents,
    ): void {
        if ($assignment->status !== DeliveryAssignment::STATUS_PICKED_UP) {
            throw new \DomainException('Assignment is not in picked_up state.');
        }

        // Re-validate OTP (do not rely on the earlier /verify-otp call alone).
        if ($this->otpService->isLocked($assignment)) {
            throw new \RuntimeException('otp_locked');
        }

        $otpMatched = $this->otpService->verify($assignment, $otpCode);
        if (! $otpMatched) {
            throw new \RuntimeException('otp_invalid');
        }

        $assignment->load('subOrder.order');
        $order = $assignment->subOrder->order;
        $isCod = $order->payment_method === 'cod';

        if ($isCod && ($codAmountCollectedCents === null || $codAmountCollectedCents <= 0)) {
            throw new \DomainException('cod_amount_required');
        }

        $proofFileId = $this->proofService->store($assignment, $proofImage);

        DB::transaction(function () use (
            $assignment, $agent, $order, $isCod,
            $latitude, $longitude, $proofFileId, $codAmountCollectedCents
        ) {
            $assignment->update([
                'status'                      => DeliveryAssignment::STATUS_DELIVERED,
                'delivered_at'                => now(),
                'otp_verified'                => true,
                'proof_file_id'               => $proofFileId,
                'delivery_latitude'           => $latitude,
                'delivery_longitude'          => $longitude,
                'cod_amount_collected_cents'  => $isCod ? $codAmountCollectedCents : null,
            ]);

            if ($assignment->shipment) {
                $assignment->shipment->update([
                    'status'       => 'delivered',
                    'delivered_at' => now(),
                ]);

                ShipmentTrackingEvent::create([
                    'shipment_id' => $assignment->shipment_id,
                    'status'      => 'delivered',
                    'description' => 'Package delivered successfully.',
                    'location'    => "{$latitude},{$longitude}",
                    'occurred_at' => now(),
                ]);
            }

            $assignment->subOrder->update([
                'status'       => 'delivered',
                'delivered_at' => now(),
            ]);

            $currency = $order->currency ?? 'EGP';

            // Base delivery fee for the agent.
            DeliveryAgentEarning::create([
                'agent_id'                => $agent->id,
                'delivery_assignment_id'  => $assignment->id,
                'order_id'                => $order->id,
                'earning_type'            => 'base_fee',
                'amount_cents'            => $agent->per_delivery_fee_cents,
                'currency'                => $currency,
                'status'                  => 'pending',
            ]);

            // COD handling fee (separate from the cash physically collected).
            if ($isCod) {
                $codFee = $agent->zone?->cod_fee_cents ?? 0;
                if ($codFee > 0) {
                    DeliveryAgentEarning::create([
                        'agent_id'                => $agent->id,
                        'delivery_assignment_id'  => $assignment->id,
                        'order_id'                => $order->id,
                        'earning_type'            => 'cod_handling',
                        'amount_cents'            => $codFee,
                        'currency'                => $currency,
                        'status'                  => 'pending',
                    ]);
                }
            }

            // Start return window on order items.
            $assignment->subOrder->items()
                ->whereNull('return_eligible_until')
                ->update(['return_eligible_until' => now()->addDays(14)->toDateString()]);
        });

        CustomerDeliveredNotificationJob::dispatch($assignment->sub_order_id);
        GenerateVendorPayoutsJob::dispatch(
            Carbon::now()->startOfMonth(),
            Carbon::now()->endOfMonth()
        );
    }

    // ── fail ──────────────────────────────────────────────────────────────────

    public function fail(
        DeliveryAssignment $assignment,
        string             $failureReason,
        ?string            $failureNotes,
        float              $latitude,
        float              $longitude,
    ): void {
        if (! in_array($assignment->status, [
            DeliveryAssignment::STATUS_ACCEPTED,
            DeliveryAssignment::STATUS_PICKED_UP,
        ], true)) {
            throw new \DomainException('Assignment cannot be failed in its current state.');
        }

        DB::transaction(function () use (
            $assignment, $failureReason, $failureNotes, $latitude, $longitude
        ) {
            $assignment->update([
                'status'             => DeliveryAssignment::STATUS_FAILED,
                'failed_at'          => now(),
                'failure_reason'     => $failureReason,
                'failure_notes'      => $failureNotes,
                'delivery_latitude'  => $latitude,
                'delivery_longitude' => $longitude,
            ]);

            $cumulativeFails = DeliveryAssignment::where('shipment_id', $assignment->shipment_id)
                ->where('status', DeliveryAssignment::STATUS_FAILED)
                ->count();

            $assignment->load('subOrder.order');
            $isCodRefused = $failureReason === 'customer_refused'
                && $assignment->subOrder->order->payment_method === 'cod';

            $triggerRto = $isCodRefused || $cumulativeFails >= self::CUMULATIVE_FAIL_LIMIT;

            if ($triggerRto) {
                $this->rtoService->createReturnAssignment($assignment);
            }
        });

        NotifyCustomerFailedDeliveryJob::dispatch($assignment->sub_order_id);
        NotifyOperationsTeamJob::dispatch($assignment->id);
    }
}
