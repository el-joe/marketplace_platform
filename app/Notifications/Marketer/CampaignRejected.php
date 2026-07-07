<?php

namespace App\Notifications\Marketer;

use App\Models\ClassifiedListing;
use App\Models\MarketerCampaign;
use App\Models\TravelPackage;
use App\Notifications\BaseDatabaseBroadcastNotification;
use Illuminate\Broadcasting\PrivateChannel;

class CampaignRejected extends BaseDatabaseBroadcastNotification
{
    public function __construct(
        private readonly MarketerCampaign $campaign,
        private readonly string $reason = '',
    ) {}

    public function notificationType(): string
    {
        return 'marketer_campaign_rejected';
    }

    public function notificationData(object $notifiable): array
    {
        $entityName = $this->resolveEntityName();

        return [
            'title'       => 'Campaign Not Approved',
            'message'     => "Your campaign \"{$this->campaign->name}\"" . ($entityName ? " promoting {$entityName}" : '') . ' was not approved.' . ($this->reason ? ' Reason: ' . $this->reason : ''),
            'url'         => route('marketer.campaigns.index'),
            'campaign_id' => $this->campaign->id,
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
                'screen' => 'campaign_detail',
                'id'     => $this->campaign->id,
                'type'   => class_basename(static::class),
            ],
        ];
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('marketer.' . $this->campaign->marketer_id)];
    }

    private function resolveEntityName(): ?string
    {
        $campaignable = $this->campaign->campaignable;

        if ($campaignable === null) {
            return null;
        }

        return match (true) {
            $campaignable instanceof ClassifiedListing => $campaignable->title_en,
            $campaignable instanceof TravelPackage     => $campaignable->title_en,
            default                                    => $campaignable->store_name ?? $campaignable->name ?? null,
        };
    }
}
