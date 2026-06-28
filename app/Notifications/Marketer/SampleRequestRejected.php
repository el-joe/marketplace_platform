<?php

namespace App\Notifications\Marketer;

use App\Models\MarketerSampleRequest;
use App\Notifications\BaseDatabaseBroadcastNotification;
use Illuminate\Broadcasting\PrivateChannel;

class SampleRequestRejected extends BaseDatabaseBroadcastNotification
{
    public function __construct(private readonly MarketerSampleRequest $sampleRequest) {}

    public function notificationType(): string
    {
        return 'marketer_sample_request_rejected';
    }

    public function notificationData(object $notifiable): array
    {
        return [
            'title'             => 'Sample Request Not Approved',
            'message'           => 'Your sample request was not approved.' . ($this->sampleRequest->rejection_reason ? ' Reason: ' . $this->sampleRequest->rejection_reason : ''),
            'url'               => route('marketer.samples.index'),
            'sample_request_id' => $this->sampleRequest->id,
        ];
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('marketer.' . $this->sampleRequest->marketer_id)];
    }
}
