<?php

namespace App\Http\Controllers\Delivery;

use App\Http\Controllers\Controller;
use App\Models\DeliveryAgent;
use App\Models\DeliveryAssignment;
use App\Models\DeliveryAgentEarning;
use App\Models\PaymentTransaction;
use App\Services\FileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AssignmentController extends Controller
{
    public function __construct(private readonly FileService $fileService)
    {
    }

    /** Today's assignments grouped by status. */
    public function index(): View
    {
        /** @var DeliveryAgent $agent */
        $agent = Auth::guard('delivery')->user();

        $assignments = DeliveryAssignment::where('agent_id', $agent->id)
            ->whereDate('assigned_at', today())
            ->with(['subOrder.order', 'subOrder.items', 'shipment'])
            ->orderByRaw("FIELD(status, 'assigned','accepted','picked_up','delivered','failed')")
            ->get();

        $pending = $assignments->whereIn('status', ['assigned', 'accepted', 'picked_up']);
        $completed = $assignments->where('status', DeliveryAssignment::STATUS_DELIVERED);
        $failed = $assignments->where('status', DeliveryAssignment::STATUS_FAILED);

        return view('delivery.assignments.index', compact('pending', 'completed', 'failed'));
    }

    /** Full order details for one assignment. */
    public function show(DeliveryAssignment $assignment): View
    {
        $this->authorizeAssignment($assignment);

        $assignment->load([
            'subOrder.order.customer',
            'subOrder.items.productVariant.product',
            'shipment',
        ]);

        return view('delivery.assignments.show', compact('assignment'));
    }

    /** Agent accepts the assignment (assigned → accepted). */
    public function accept(DeliveryAssignment $assignment): JsonResponse
    {
        $this->authorizeAssignment($assignment);

        if ($assignment->status !== DeliveryAssignment::STATUS_ASSIGNED) {
            return response()->json(['message' => 'Assignment cannot be accepted in its current state.'], 422);
        }

        $assignment->update([
            'status' => DeliveryAssignment::STATUS_ACCEPTED,
            'accepted_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Assignment accepted.']);
    }

    /** Agent marks item as picked up (accepted → picked_up). */
    public function pickedUp(Request $request, DeliveryAssignment $assignment): JsonResponse
    {
        $this->authorizeAssignment($assignment);

        if ($assignment->status !== DeliveryAssignment::STATUS_ACCEPTED) {
            return response()->json(['message' => 'Assignment is not in accepted state.'], 422);
        }

        $validated = $request->validate([
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $assignment->update([
            'status' => DeliveryAssignment::STATUS_PICKED_UP,
            'picked_up_at' => now(),
            'pickup_latitude' => $validated['latitude'] ?? null,
            'pickup_longitude' => $validated['longitude'] ?? null,
        ]);

        // Update shipment status
        if ($assignment->shipment) {
            $assignment->shipment->update([
                'status' => 'picked_up',
                'picked_up_at' => now(),
            ]);
        }

        // Transition sub-order status
        if ($assignment->subOrder) {
            $assignment->subOrder->update(['status' => 'out_for_delivery']);
        }

        return response()->json(['success' => true, 'message' => 'Marked as picked up.']);
    }

    /** Agent delivers — validates OTP, stores proof, updates all related records. */
    public function deliver(Request $request, DeliveryAssignment $assignment): JsonResponse
    {
        $this->authorizeAssignment($assignment);

        if ($assignment->status !== DeliveryAssignment::STATUS_PICKED_UP) {
            return response()->json(['message' => 'Assignment is not in picked-up state.'], 422);
        }

        $assignment->load('subOrder.order');
        $order = $assignment->subOrder?->order;
        $isCod = $order && $order->payment_method === 'cod';

        $validated = $request->validate([
            'otp_code'             => ['required', 'digits:6'],
            'proof_image'          => ['nullable', 'image', 'max:5120'],
            'latitude'             => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'            => ['nullable', 'numeric', 'between:-180,180'],
            'cod_amount_collected' => $isCod
                ? ['required', 'integer', 'min:1']
                : ['nullable', 'integer', 'min:1'],
            'discrepancy_note'     => ['nullable', 'string', 'max:500'],
        ]);

        // OTP validation (max 3 attempts)
        if ($assignment->otp_attempts >= 3) {
            return response()->json(['message' => 'Too many OTP attempts. Contact support.'], 422);
        }

        $expectedOtp = $assignment->delivery_otp
            ?? ($assignment->shipment?->delivery_otp ?? null);

        if ($expectedOtp && $validated['otp_code'] !== (string) $expectedOtp) {
            $assignment->increment('otp_attempts');
            $remaining = 3 - $assignment->fresh()->otp_attempts;

            return response()->json([
                'message' => "Invalid OTP. {$remaining} attempt(s) remaining.",
                'remaining' => $remaining,
            ], 422);
        }

        // COD amount validation — expected = order total (which includes cod_fee)
        if ($isCod && $order) {
            $expectedCents = (int) $order->total;
            $collectedCents = (int) $validated['cod_amount_collected'];
            $diffCents = abs($collectedCents - $expectedCents);
            $diffPct = $expectedCents > 0 ? ($diffCents / $expectedCents) : 0;

            if ($diffCents > 5 && $diffPct > 0.05) {
                // More than 5% discrepancy — require a note
                if (empty($validated['discrepancy_note'])) {
                    $expectedFormatted = number_format($expectedCents / 100, 2);
                    $collectedFormatted = number_format($collectedCents / 100, 2);
                    return response()->json([
                        'message' => "المبلغ المُدخل ({$collectedFormatted}) لا يتطابق مع المبلغ المتوقع ({$expectedFormatted}). يرجى التأكد.",
                        'requires_discrepancy_note' => true,
                        'expected_cents' => $expectedCents,
                        'collected_cents' => $collectedCents,
                    ], 422);
                }
            }
        }

        DB::transaction(function () use ($assignment, $validated, $request, $order, $isCod) {
            $proofFileId = null;

            if ($request->hasFile('proof_image')) {
                $file = $this->fileService->store(
                    $request->file('proof_image'),
                    DeliveryAssignment::class,
                    $assignment->id,
                    'delivery_proof'
                );
                $proofFileId = $file->id;
            }

            $assignmentData = [
                'status'             => DeliveryAssignment::STATUS_DELIVERED,
                'delivered_at'       => now(),
                'otp_verified'       => true,
                'proof_file_id'      => $proofFileId,
                'delivery_latitude'  => $validated['latitude'] ?? null,
                'delivery_longitude' => $validated['longitude'] ?? null,
            ];

            if ($isCod && isset($validated['cod_amount_collected'])) {
                $assignmentData['cod_amount_collected_cents'] = (int) $validated['cod_amount_collected'];
            }

            $assignment->update($assignmentData);

            if ($assignment->shipment) {
                $assignment->shipment->update([
                    'status'       => 'delivered',
                    'delivered_at' => now(),
                ]);
            }

            if ($assignment->subOrder) {
                $assignment->subOrder->update([
                    'status'       => 'delivered',
                    'delivered_at' => now(),
                ]);
            }

            // Virtual capture for COD — cash physically changed hands
            if ($isCod && $order) {
                $order->update(['payment_status' => 'captured']);

                PaymentTransaction::where('order_id', $order->id)
                    ->where('gateway', 'cod')
                    ->where('status', 'pending')
                    ->update(['status' => 'succeeded', 'processed_at' => now()]);
            }

            $assignment->agent?->increment('total_deliveries');

            $agent = Auth::guard('delivery')->user();
            $currency = $order?->currency ?? 'USD';

            DeliveryAgentEarning::create([
                'agent_id'                => $agent->id,
                'delivery_assignment_id'  => $assignment->id,
                'order_id'                => $assignment->subOrder?->order_id,
                'earning_type'            => 'base_fee',
                'amount_cents'            => $agent->per_delivery_fee_cents,
                'currency'                => $currency,
                'status'                  => 'pending',
            ]);

            // COD handling bonus — the agent earns the cod_fee the customer paid
            if ($isCod && $order && $order->cod_fee > 0) {
                DeliveryAgentEarning::create([
                    'agent_id'                => $agent->id,
                    'delivery_assignment_id'  => $assignment->id,
                    'order_id'                => $assignment->subOrder?->order_id,
                    'earning_type'            => 'cod_handling',
                    'amount_cents'            => (int) $order->cod_fee,
                    'currency'                => $currency,
                    'status'                  => 'pending',
                    'notes'                   => $validated['discrepancy_note'] ?? null,
                ]);
            }
        });

        return response()->json(['success' => true, 'message' => 'Delivery confirmed!']);
    }

    /** Agent marks delivery as failed. */
    public function fail(Request $request, DeliveryAssignment $assignment): JsonResponse
    {
        $this->authorizeAssignment($assignment);

        if (
            !in_array($assignment->status, [
                DeliveryAssignment::STATUS_ACCEPTED,
                DeliveryAssignment::STATUS_PICKED_UP,
            ])
        ) {
            return response()->json(['message' => 'Cannot fail assignment in current state.'], 422);
        }

        $validated = $request->validate([
            'failure_reason' => ['required', 'string', 'max:255'],
            'failure_notes' => ['nullable', 'string', 'max:1000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $assignment->update([
            'status' => DeliveryAssignment::STATUS_FAILED,
            'failed_at' => now(),
            'failure_reason' => $validated['failure_reason'],
            'failure_notes' => $validated['failure_notes'] ?? null,
            'delivery_latitude' => $validated['latitude'] ?? null,
            'delivery_longitude' => $validated['longitude'] ?? null,
        ]);

        return response()->json(['success' => true, 'message' => 'Assignment marked as failed.']);
    }

    /** Ensures the assignment belongs to the authenticated agent. */
    private function authorizeAssignment(DeliveryAssignment $assignment): void
    {
        /** @var DeliveryAgent $agent */
        $agent = Auth::guard('delivery')->user();

        abort_if($assignment->agent_id !== $agent->id, 403, 'Forbidden.');
    }
}
