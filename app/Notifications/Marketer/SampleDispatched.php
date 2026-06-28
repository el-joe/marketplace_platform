<?php

namespace App\Notifications\Marketer;

use App\Models\MarketerSampleRequest;
use App\Notifications\BaseDatabaseBroadcastNotification;
use Illuminate\Broadcasting\PrivateChannel;

class SampleDispatched extends BaseDatabaseBroadcastNotification
{
    public function __construct(private readonly MarketerSampleRequest $sampleRequest) {}

    public function notificationType(): string
    {
        return 'marketer_sample_dispatched';
    }

    public function notificationData(object $notifiable): array
    {
        return [
            'title'             => 'Samples Dispatched',
            'message'           => 'Your samples have been dispatched by the vendor and are on their way to you.',
            'url'               => route('marketer.samples.index'),
            'sample_request_id' => $this->sampleRequest->id,
        ];
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('marketer.' . $this->sampleRequest->marketer_id)];
    }
}
