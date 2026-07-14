<?php

namespace App\Notifications\Customer;

use Illuminate\Broadcasting\PrivateChannel;

class SendOtpNotification extends BaseCustomerNotification
{
    public function __construct(
        private readonly string $customerId,
        private readonly string $otp,
        private readonly string $purpose,
    ) {}

    public function notificationType(): string
    {
        return 'security_otp';
    }

    public function notificationData(object $notifiable): array
    {
        return [
            'title' => 'Your verification code',
            'message' => "Your {$this->purpose} code is {$this->otp}. It expires in 15 minutes.",
        ];
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('customer.' . $this->customerId)];
    }
}
