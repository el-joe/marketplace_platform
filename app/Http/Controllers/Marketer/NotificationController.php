<?php

namespace App\Http\Controllers\Marketer;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Marketer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    private function marketer(): Marketer
    {
        /** @var Marketer $marketer */
        $marketer = auth('marketer_api')->user();
        return $marketer;
    }

    // GET /api/marketer/v1/notifications
    public function index(Request $request): JsonResponse
    {
        $marketer = $this->marketer();

        $notifications = DB::table('notifications')
            ->where('notifiable_type', Marketer::class)
            ->where('notifiable_id', $marketer->id)
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 20));

        return ApiResponse::success([
            'items' => collect($notifications->items())->map(fn ($n) => [
                'id'         => $n->id,
                'type'       => $n->type,
                'type_short' => class_basename($n->type),
                'data'       => json_decode($n->data, true),
                'channel'    => $n->channel,
                'read_at'    => $n->read_at,
                'created_at' => $n->created_at,
            ]),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page'    => $notifications->lastPage(),
                'per_page'     => $notifications->perPage(),
                'total'        => $notifications->total(),
                'unread_count' => $this->getUnreadCount($marketer->id),
            ],
        ]);
    }

    // GET /api/marketer/v1/notifications/unread-count
    public function unreadCount(): JsonResponse
    {
        $marketer = $this->marketer();

        return ApiResponse::success(['unread_count' => $this->getUnreadCount($marketer->id)]);
    }

    // PUT /api/marketer/v1/notifications/{id}/read
    public function markRead(string $id): JsonResponse
    {
        $marketer = $this->marketer();

        DB::table('notifications')
            ->where('id', $id)
            ->where('notifiable_type', Marketer::class)
            ->where('notifiable_id', $marketer->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return ApiResponse::success(null, 'Marked as read.');
    }

    // PUT /api/marketer/v1/notifications/read-all
    public function markAllRead(): JsonResponse
    {
        $marketer = $this->marketer();

        DB::table('notifications')
            ->where('notifiable_type', Marketer::class)
            ->where('notifiable_id', $marketer->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return ApiResponse::success(null, 'All marked as read.');
    }

    private function getUnreadCount(string $marketerId): int
    {
        return DB::table('notifications')
            ->where('notifiable_type', Marketer::class)
            ->where('notifiable_id', $marketerId)
            ->whereNull('read_at')
            ->count();
    }
}
