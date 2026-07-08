<?php

namespace App\Http\Controllers\Delivery\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Delivery\Assignment\AcceptAssignmentRequest;
use App\Http\Requests\Delivery\Assignment\DeliverRequest;
use App\Http\Requests\Delivery\Assignment\FailRequest;
use App\Http\Requests\Delivery\Assignment\PickupRequest;
use App\Http\Requests\Delivery\Assignment\VerifyOtpRequest;
use App\Http\Resources\Delivery\AssignmentDetailResource;
use App\Http\Resources\Delivery\AssignmentListResource;
use App\Http\Responses\ApiResponse;
use App\Models\DeliveryAgent;
use App\Models\DeliveryAssignment;
use App\Services\Delivery\AssignmentService;
use App\Services\Delivery\OtpVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    public function __construct(
        private readonly AssignmentService      $assignmentService,
        private readonly OtpVerificationService $otpService,
    ) {}

    // ── GET /assignments ──────────────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        /** @var DeliveryAgent $agent */
        $agent = auth('delivery_api')->user();

        $eagerLoad = ['subOrder.order', 'shipment.carrier'];

        // Active assignments are not date-scoped — an assignment accepted
        // yesterday but not yet delivered must still surface today.
        $activeAssignments = DeliveryAssignment::where('agent_id', $agent->id)
            ->whereIn('status', [
                DeliveryAssignment::STATUS_ASSIGNED,
                DeliveryAssignment::STATUS_ACCEPTED,
                DeliveryAssignment::STATUS_PICKED_UP,
            ])
            ->with($eagerLoad)
            ->orderByRaw("FIELD(status, 'assigned','accepted','picked_up')")
            ->get();

        $completedToday = DeliveryAssignment::where('agent_id', $agent->id)
            ->whereIn('status', [DeliveryAssignment::STATUS_DELIVERED, DeliveryAssignment::STATUS_FAILED])
            ->where(function ($q) {
                $q->whereDate('delivered_at', today())->orWhereDate('failed_at', today());
            })
            ->with($eagerLoad)
            ->orderByDesc('assigned_at')
            ->get();

        return ApiResponse::success([
            'active_assignments' => AssignmentListResource::collection($activeAssignments),
            'completed_today'    => AssignmentListResource::collection($completedToday),
        ]);
    }

    // ── GET /assignments/{id} ─────────────────────────────────────────────────

    public function show(DeliveryAssignment $assignment): JsonResponse
    {
        $this->authorizeAgent($assignment);

        $assignment->load(['subOrder.items', 'subOrder.order', 'shipment.carrier']);

        return ApiResponse::success(['assignment' => new AssignmentDetailResource($assignment)]);
    }

    // ── POST /assignments/{id}/accept ─────────────────────────────────────────

    public function accept(AcceptAssignmentRequest $request, DeliveryAssignment $assignment): JsonResponse
    {
        $this->authorizeAgent($assignment);

        /** @var DeliveryAgent $agent */
        $agent = auth('delivery_api')->user();

        try {
            $this->assignmentService->accept($assignment, $agent);
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'concurrent_assignment') {
                return ApiResponse::error(
                    'You already have an active assignment. Complete or fail it before accepting another.',
                    [],
                    422
                );
            }
            throw $e;
        } catch (\DomainException $e) {
            return ApiResponse::error($e->getMessage(), [], 422);
        }

        return $this->respondWithAssignment($assignment, 'Assignment accepted.');
    }

    // ── POST /assignments/{id}/picked-up ──────────────────────────────────────

    public function pickedUp(PickupRequest $request, DeliveryAssignment $assignment): JsonResponse
    {
        $this->authorizeAgent($assignment);

        try {
            $this->assignmentService->pickup(
                $assignment,
                (float) $request->latitude,
                (float) $request->longitude,
            );
        } catch (\DomainException $e) {
            return ApiResponse::error($e->getMessage(), [], 422);
        }

        return $this->respondWithAssignment($assignment, 'Package marked as picked up.');
    }

    // ── POST /assignments/{id}/verify-otp ─────────────────────────────────────

    public function verifyOtp(VerifyOtpRequest $request, DeliveryAssignment $assignment): JsonResponse
    {
        $this->authorizeAgent($assignment);

        try {
            $matched = $this->assignmentService->verifyOtp($assignment, $request->otp_code);
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'otp_locked') {
                return ApiResponse::error(
                    'Too many incorrect OTP attempts. This assignment has been flagged for support review.',
                    [],
                    423
                );
            }
            throw $e;
        } catch (\DomainException $e) {
            return ApiResponse::error($e->getMessage(), [], 422);
        }

        if (! $matched) {
            $assignment->refresh();
            $remaining = $this->otpService->remainingAttempts($assignment);
            return ApiResponse::error(
                "Incorrect OTP. {$remaining} attempt(s) remaining.",
                ['remaining_attempts' => $remaining],
                422
            );
        }

        $assignment->load(['subOrder.items', 'subOrder.order', 'shipment.carrier']);

        return ApiResponse::success([
            'verified'   => true,
            'assignment' => new AssignmentDetailResource($assignment),
        ], 'OTP verified.');
    }

    // ── POST /assignments/{id}/deliver ────────────────────────────────────────

    public function deliver(DeliverRequest $request, DeliveryAssignment $assignment): JsonResponse
    {
        $this->authorizeAgent($assignment);

        /** @var DeliveryAgent $agent */
        $agent = auth('delivery_api')->user();
        $agent->load('zone');

        try {
            $this->assignmentService->deliver(
                assignment:                  $assignment,
                agent:                       $agent,
                otpCode:                     $request->otp_code,
                proofImage:                  $request->file('proof_image'),
                latitude:                    (float) $request->latitude,
                longitude:                   (float) $request->longitude,
                codAmountCollectedCents:     $request->integer('cod_amount_collected_cents') ?: null,
            );
        } catch (\RuntimeException $e) {
            return match ($e->getMessage()) {
                'otp_locked'  => ApiResponse::error(
                    'Too many incorrect OTP attempts. Assignment flagged for support review.',
                    [],
                    423
                ),
                'otp_invalid' => ApiResponse::error(
                    'Incorrect OTP. Delivery cannot be confirmed.',
                    [],
                    422
                ),
                default       => throw $e,
            };
        } catch (\DomainException $e) {
            $message = match ($e->getMessage()) {
                'cod_amount_required' => 'COD amount collected is required for cash-on-delivery orders.',
                default               => $e->getMessage(),
            };
            return ApiResponse::error($message, [], 422);
        }

        return $this->respondWithAssignment($assignment, 'Delivery confirmed successfully.');
    }

    // ── POST /assignments/{id}/fail ───────────────────────────────────────────

    public function fail(FailRequest $request, DeliveryAssignment $assignment): JsonResponse
    {
        $this->authorizeAgent($assignment);

        try {
            $this->assignmentService->fail(
                assignment:     $assignment,
                failureReason:  $request->failure_reason,
                failureNotes:   $request->failure_notes,
                latitude:       (float) $request->latitude,
                longitude:      (float) $request->longitude,
            );
        } catch (\DomainException $e) {
            return ApiResponse::error($e->getMessage(), [], 422);
        }

        return $this->respondWithAssignment($assignment, 'Delivery marked as failed.');
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function authorizeAgent(DeliveryAssignment $assignment): void
    {
        /** @var DeliveryAgent $agent */
        $agent = auth('delivery_api')->user();

        abort_if($assignment->agent_id !== $agent->id, 403, 'Forbidden.');
    }

    private function respondWithAssignment(DeliveryAssignment $assignment, string $message): JsonResponse
    {
        $assignment->load(['subOrder.items', 'subOrder.order', 'shipment.carrier']);

        return ApiResponse::success(['assignment' => new AssignmentDetailResource($assignment)], $message);
    }
}
