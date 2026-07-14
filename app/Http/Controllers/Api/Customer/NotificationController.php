<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Customer\NotificationResource;
use App\Http\Responses\ApiResponse;
use App\Models\DeviceToken;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Notification data contract.
 *
 * Every notification stored in `notifications.data` MUST have these top-level keys:
 * {
 *   "title_en": string,
 *   "title_ar": string,
 *   "body_en": string,
 *   "body_ar": string,
 *   "action_type": "order"|"product"|"return"|"dispute"|"wallet"|"general",
 *   "action_id": string|null,
 *   "icon": "order"|"wallet"|"bell"|"gift"|"shield"
 * }
 */
class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $customer = auth('customer')->user();

        $paginator = Notification::query()
            ->where('notifiable_type', $customer::class)
            ->where('notifiable_id', $customer->getKey())
            ->where('channel', 'database')
            ->orderByDesc('created_at')
            ->paginate(20);

        $unreadCount = Notification::query()
            ->where('notifiable_type', $customer::class)
            ->where('notifiable_id', $customer->getKey())
            ->where('channel', 'database')
            ->whereNull('read_at')
            ->count();

        return ApiResponse::paginated($paginator, NotificationResource::class, [
            'unread_count' => $unreadCount,
        ]);
    }

    public function unreadCount(): JsonResponse
    {
        $customer = auth('customer')->user();

        $count = Notification::query()
            ->where('notifiable_type', $customer::class)
            ->where('notifiable_id', $customer->getKey())
            ->where('channel', 'database')
            ->whereNull('read_at')
            ->count();

        return ApiResponse::success(['unread_count' => $count]);
    }

    public function markAsRead(string $id): JsonResponse
    {
        $customer = auth('customer')->user();

        $notification = Notification::query()
            ->where('id', $id)
            ->where('notifiable_type', $customer::class)
            ->where('notifiable_id', $customer->getKey())
            ->where('channel', 'database')
            ->first();

        if (!$notification) {
            return ApiResponse::error('Notification not found.', [], 404);
        }

        if ($notification->read_at === null) {
            $notification->update(['read_at' => now()]);
        }

        return ApiResponse::success(new NotificationResource($notification));
    }

    public function markAllAsRead(): JsonResponse
    {
        $customer = auth('customer')->user();

        Notification::query()
            ->where('notifiable_type', $customer::class)
            ->where('notifiable_id', $customer->getKey())
            ->where('channel', 'database')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return ApiResponse::success(null, 'All notifications marked as read.');
    }

    public function registerDevice(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => ['required', 'string', 'max:255'],
            'platform' => ['required', 'string', 'in:ios,android'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed.', $validator->errors()->toArray());
        }

        $customer = auth('customer')->user();
        $data = $validator->validated();

        DeviceToken::updateOrCreate(
            [
                'tokenable_type' => $customer::class,
                'tokenable_id' => $customer->getKey(),
                'token' => $data['token'],
            ],
            [
                'platform' => $data['platform'],
                'is_active' => true,
                'last_used_at' => now(),
            ]
        );

        return ApiResponse::success(null, 'Device registered.');
    }

    public function removeDevice(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed.', $validator->errors()->toArray());
        }

        $customer = auth('customer')->user();

        DeviceToken::query()
            ->where('tokenable_type', $customer::class)
            ->where('tokenable_id', $customer->getKey())
            ->where('token', $validator->validated()['token'])
            ->update(['is_active' => false]);

        return ApiResponse::success(null, 'Device removed.');
    }
}
