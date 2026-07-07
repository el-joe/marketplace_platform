<?php

namespace App\Http\Controllers\Marketer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Marketer\Support\SupportTicketMessageRequest;
use App\Http\Requests\Marketer\Support\SupportTicketRateRequest;
use App\Http\Requests\Marketer\Support\SupportTicketStoreRequest;
use App\Http\Resources\Marketer\SupportTicketResource;
use App\Http\Responses\ApiResponse;
use App\Models\SupportTicket;
use App\Services\Marketer\SupportTicketService;
use Illuminate\Http\JsonResponse;

class SupportTicketController extends Controller
{
    public function __construct(private readonly SupportTicketService $ticketService) {}

    public function index(): JsonResponse
    {
        $marketer  = auth('marketer_api')->user();
        $paginator = $this->ticketService->list($marketer);

        return ApiResponse::paginated($paginator, SupportTicketResource::class);
    }

    public function store(SupportTicketStoreRequest $request): JsonResponse
    {
        $marketer = auth('marketer_api')->user();
        $ticket   = $this->ticketService->store($marketer, $request->validated());

        return ApiResponse::success(new SupportTicketResource($ticket), 'Ticket created.', 201);
    }

    public function show(string $ticketNumber): JsonResponse
    {
        $marketer = auth('marketer_api')->user();
        $ticket   = $this->ticketService->findForMarketer($marketer, $ticketNumber);

        if (!$ticket) {
            return ApiResponse::error('Ticket not found.', [], 404);
        }

        return ApiResponse::success(new SupportTicketResource($ticket));
    }

    public function addMessage(SupportTicketMessageRequest $request, string $ticketNumber): JsonResponse
    {
        $marketer = auth('marketer_api')->user();
        $ticket   = $this->resolveTicket($marketer, $ticketNumber);

        if (!$ticket) {
            return ApiResponse::error('Ticket not found.', [], 404);
        }

        $message = $this->ticketService->addMessage($marketer, $ticket, $request->validated());

        return ApiResponse::success([
            'id'         => $message->id,
            'message'    => $message->message,
            'created_at' => $message->created_at?->toIso8601String(),
        ], 'Message sent.', 201);
    }

    public function rate(SupportTicketRateRequest $request, string $ticketNumber): JsonResponse
    {
        $marketer = auth('marketer_api')->user();
        $ticket   = $this->resolveTicket($marketer, $ticketNumber);

        if (!$ticket) {
            return ApiResponse::error('Ticket not found.', [], 404);
        }

        $ticket = $this->ticketService->rate($marketer, $ticket, $request->validated());

        return ApiResponse::success(new SupportTicketResource($ticket), 'Thank you for your feedback.');
    }

    private function resolveTicket($marketer, string $ticketNumber): ?SupportTicket
    {
        return SupportTicket::where('ticket_number', $ticketNumber)
            ->where('requester_user_id', $marketer->id)
            ->where('requester_role', 'marketer')
            ->first();
    }
}
