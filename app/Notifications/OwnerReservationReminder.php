<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OwnerReservationReminder extends Notification
{
    use Queueable;

    protected $reservation;

    public function __construct(Reservation $reservation)
    {
        $this->reservation = $reservation;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toBroadcast(object $notifiable): array
    {
        return [
            'data' => [
                'reservation_id' => $this->reservation->id,
                'time' => $this->reservation->reservation_time,
                'message' => 'Upcoming reservation in 1 hour',
            ]
        ];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'reservation_id' => $this->reservation->id,
            'message' => 'Upcoming reservation in 1 hour',
        ];
    }
}
