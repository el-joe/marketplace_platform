<?php

namespace App\Notifications\DeliveryAgent;

use App\Models\DeliveryAssignment;
use App\Notifications\BaseDatabaseBroadcastNotification;
use Illuminate\Broadcasting\PrivateChannel;

class NewDeliveryAssigned extends BaseDatabaseBroadcastNotification
{
    public function __construct(private readonly DeliveryAssignment $assignment) {}

    public function notificationType(): string
    {
        return 'new_delivery_assigned';
    }

    public function notificationData(object $notifiable): array
    {
        return [
            'title'         => 'تم تعيين توصيل جديد لك',
            'message'       => 'لديك طلب توصيل جديد — رقم الشحنة #' . $this->assignment->shipment_id . '. يرجى الاطلاع عليه.',
            'url'           => route('delivery.assignments.show', $this->assignment->id),
            'assignment_id' => $this->assignment->id,
            'shipment_id'   => $this->assignment->shipment_id,
            'assigned_at'   => $this->assignment->assigned_at?->toIso8601String(),
        ];
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('delivery-agent.' . $this->assignment->agent_id)];
    }
}
