<?php

namespace App\Notifications\Vendor;

use App\Notifications\BaseDatabaseBroadcastNotification;

class DocumentsRequested extends BaseDatabaseBroadcastNotification
{
    public function __construct(private readonly array $documentTypes = []) {}

    public function notificationType(): string
    {
        return 'documents_requested';
    }

    public function notificationData(object $notifiable): array
    {
        $list = implode(', ', $this->documentTypes);

        return [
            'title'   => 'Documents Required',
            'message' => 'Please upload the following documents to continue: ' . $list,
            'url'     => route('partner.dashboard'),
        ];
    }

    public function broadcastOn(): array
    {
        return [];
    }
}
