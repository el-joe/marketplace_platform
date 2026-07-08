<?php

namespace App\Http\Controllers\Api\TravelAgencyPortal;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\TravelAgency;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    private function agencyId(): string
    {
        return Auth::guard('travel_agencies')->id();
    }

    // GET /api/travel-agency/v1/notifications
    public function index(Request $request): JsonResponse
    {
        $agencyId = $this->agencyId();

        $notifications = DB::table('notifications')
            ->where('notifiable_type', TravelAgency::class)
            ->where('notifiable_id', $agencyId)
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 20));

        return ApiResponse::success([
            'items' => collect($notifications->items())->map(fn ($n) => $this->formatRow($n)),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
                'unread_count' => $this->getUnreadCount($agencyId),
            ],
        ]);
    }

    // GET /api/travel-agency/v1/notifications/recent
    public function recent(): JsonResponse
    {
        $agencyId = $this->agencyId();

        $items = DB::table('notifications')
            ->where('notifiable_type', TravelAgency::class)
            ->where('notifiable_id', $agencyId)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn ($n) => $this->formatRow($n));

        return ApiResponse::success([
            'count' => $this->getUnreadCount($agencyId),
            'items' => $items,
        ]);
    }

    // GET /api/travel-agency/v1/notifications/unread-count
    public function unreadCount(): JsonResponse
    {
        return ApiResponse::success(['unread_count' => $this->getUnreadCount($this->agencyId())]);
    }

    // POST /api/travel-agency/v1/notifications/{id}/read
    public function markRead(string $id): JsonResponse
    {
        DB::table('notifications')
            ->where('id', $id)
            ->where('notifiable_type', TravelAgency::class)
            ->where('notifiable_id', $this->agencyId())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return ApiResponse::success(null, 'Marked as read.');
    }

    // POST /api/travel-agency/v1/notifications/mark-all-read
    public function markAllRead(): JsonResponse
    {
        DB::table('notifications')
            ->where('notifiable_type', TravelAgency::class)
            ->where('notifiable_id', $this->agencyId())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return ApiResponse::success(null, 'All marked as read.');
    }

    private function getUnreadCount(string $agencyId): int
    {
        return DB::table('notifications')
            ->where('notifiable_type', TravelAgency::class)
            ->where('notifiable_id', $agencyId)
            ->whereNull('read_at')
            ->count();
    }

    private function formatRow(object $n): array
    {
        $data = is_string($n->data) ? json_decode($n->data, true) : (array) $n->data;

        return [
            'id' => $n->id,
            'type' => $n->type,
            'type_short' => class_basename($n->type),
            'title' => $data['title'] ?? 'Notification',
            'message' => $data['message'] ?? '',
            'url' => $data['url'] ?? null,
            'read' => ! is_null($n->read_at),
            'created_at' => $n->created_at,
        ];
    }
}
