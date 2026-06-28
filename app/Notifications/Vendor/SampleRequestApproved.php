<?php

namespace App\Notifications\Vendor;

use App\Models\MarketerSampleRequest;
use App\Notifications\BaseDatabaseBroadcastNotification;

class SampleRequestApproved extends BaseDatabaseBroadcastNotification
{
    public function __construct(private readonly MarketerSampleRequest $sampleRequest) {}

    public function notificationType(): string
    {
        return 'sample_request_approved';
    }

    public function notificationData(object $notifiable): array
    {
        return [
            'title'             => 'Sample Request Approved',
            'message'           => "You have approved a marketer's sample request. Please prepare and dispatch the sample.",
            'url'               => route('partner.marketer-samples.index'),
            'sample_request_id' => $this->sampleRequest->id,
        ];
    }

    public function broadcastOn(): array
    {
        return [];
    }
}
