<?php

namespace App\Notifications\Marketer;

use App\Models\MarketerMonthlyQuotaProgress;
use App\Notifications\BaseDatabaseBroadcastNotification;
use Illuminate\Broadcasting\PrivateChannel;

class MonthlyQuotaWarning extends BaseDatabaseBroadcastNotification
{
    public function __construct(private readonly MarketerMonthlyQuotaProgress $progress) {}

    public function notificationType(): string
    {
        return 'marketer_monthly_quota_warning';
    }

    public function notificationData(object $notifiable): array
    {
        $remaining = max(0, $this->progress->quota_target - $this->progress->completed_count);

        return [
            'title' => 'Monthly Quota At Risk',
            'message' => "You still need {$remaining} more promotion(s) this month to avoid a penalty.",
            'promotion_category' => $this->progress->promotion_category,
            'year' => $this->progress->year,
            'month' => $this->progress->month,
            'quota_target' => $this->progress->quota_target,
            'completed_count' => $this->progress->completed_count,
        ];
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', 'push'];
    }

    public function toPush(object $notifiable): array
    {
        $data = $this->notificationData($notifiable);

        return [
            'title' => $data['title'],
            'body' => $data['message'],
            'data' => [
                'screen' => 'monthly_quota',
                'type' => class_basename(static::class),
            ],
        ];
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('marketer.' . $this->progress->marketer_id)];
    }
}
