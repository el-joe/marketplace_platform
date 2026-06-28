<?php

namespace App\Notifications\Customer;

use App\Models\TravelBooking;
use Illuminate\Broadcasting\PrivateChannel;

class TravelBookingCancelled extends BaseCustomerNotification
{
    public function __construct(
        private readonly TravelBooking $booking,
        private readonly string $cancelledBy = 'agency',
    ) {}

    public function notificationType(): string
    {
        return 'travel_booking_cancelled';
    }

    public function notificationData(object $notifiable): array
    {
        $this->booking->loadMissing('package:id,title');

        $byWhom = match ($this->cancelledBy) {
            'customer' => 'at your request',
            'agency'   => 'by the travel agency',
            default    => '',
        };

        return [
            'title'          => 'Booking Cancelled',
            'message'        => "Your travel booking #{$this->booking->booking_number} for \"{$this->booking->package->title}\" has been cancelled {$byWhom}.",
            'url'            => route('customer.account.travel-bookings.show', $this->booking->id),
            'booking_id'     => $this->booking->id,
            'booking_number' => $this->booking->booking_number,
            'package_title'  => $this->booking->package->title,
            'cancelled_by'   => $this->cancelledBy,
        ];
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('customer.' . $this->booking->customer_id)];
    }
}
