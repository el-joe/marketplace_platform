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

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', 'push'];
    }

    public function toPush(object $notifiable): array
    {
        $data = $this->notificationData($notifiable);

        return [
            'title' => $data['title'],
            'body'  => $data['message'],
            'data'  => [
                'screen' => 'sample_detail',
                'id'     => $this->sampleRequest->id,
                'type'   => class_basename(static::class),
            ],
        ];
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('marketer.' . $this->sampleRequest->marketer_id)];
    }
}
