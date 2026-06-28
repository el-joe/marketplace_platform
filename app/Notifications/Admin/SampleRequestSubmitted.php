<?php

namespace App\Notifications\Admin;

use App\Models\MarketerSampleRequest;
use App\Notifications\BaseDatabaseBroadcastNotification;
use Illuminate\Broadcasting\PrivateChannel;

class SampleRequestSubmitted extends BaseDatabaseBroadcastNotification
{
    public function __construct(private readonly MarketerSampleRequest $sampleRequest) {}

    public function notificationType(): string
    {
        return 'sample_request_submitted';
    }

    public function notificationData(object $notifiable): array
    {
        return [
            'title'             => 'Sample Request Submitted',
            'message'           => "A marketer has submitted a sample request for campaign \"{$this->sampleRequest->campaign?->name}\" and requires fulfillment.",
            'sample_request_id' => $this->sampleRequest->id,
            'link'              => route('admin.marketers.samples.show', $this->sampleRequest->id),
        ];
    }

    public function broadcastOn(mixed $notifiable = null): array
    {
        if (! $notifiable) {
            return [];
        }

        return [new PrivateChannel('admin.' . $notifiable->id)];
    }
}
