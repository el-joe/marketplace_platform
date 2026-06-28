<?php

namespace App\Notifications\Marketer;

use App\Models\MarketerSampleRequest;
use App\Notifications\BaseDatabaseBroadcastNotification;
use Illuminate\Broadcasting\PrivateChannel;

class SampleRequestApproved extends BaseDatabaseBroadcastNotification
{
    public function __construct(private readonly MarketerSampleRequest $sampleRequest) {}

    public function notificationType(): string
    {
        return 'marketer_sample_request_approved';
    }

    public function notificationData(object $notifiable): array
    {
        return [
            'title'             => 'Sample Request Approved',
            'message'           => 'Your sample request has been approved. The vendor will prepare and dispatch your samples shortly.',
            'url'               => route('marketer.samples.index'),
            'sample_request_id' => $this->sampleRequest->id,
        ];
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('marketer.' . $this->sampleRequest->marketer_id)];
    }
}
