<?php

namespace App\Notifications\Marketer;

use App\Models\TravelAgencyCampaignInvitation;
use App\Notifications\BaseDatabaseBroadcastNotification;
use Illuminate\Broadcasting\PrivateChannel;

class TravelAgencyCampaignInvitationReceived extends BaseDatabaseBroadcastNotification
{
    public function __construct(private readonly TravelAgencyCampaignInvitation $invitation) {}

    public function notificationType(): string
    {
        return 'travel_agency_campaign_invitation_received';
    }

    public function notificationData(object $notifiable): array
    {
        $offer      = $this->invitation->offer;
        $agencyName = $offer->agency->name;
        $commission = number_format($offer->offered_commission_rate, 0);
        $deadline   = $offer->invitation_deadline
            ? $offer->invitation_deadline->format('d M Y')
            : 'N/A';

        return [
            'title'         => 'New Campaign Invitation',
            'message'       => "{$agencyName} has invited you to promote their travel packages! Commission: {$commission}% per confirmed booking. Deadline: {$deadline}.",
            'url'           => route('marketer.invitations.show', $this->invitation->id),
            'invitation_id' => $this->invitation->id,
            'offer_id'      => $this->invitation->travel_agency_campaign_offer_id,
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
                'screen' => 'invitation_detail',
                'id'     => $this->invitation->id,
                'type'   => class_basename(static::class),
            ],
        ];
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('marketer.' . $this->invitation->marketer_id)];
    }
}
