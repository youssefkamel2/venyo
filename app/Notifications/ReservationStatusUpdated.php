<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReservationStatusUpdated extends Notification
{
    use Queueable;

    protected $reservation;

    public function __construct(Reservation $reservation)
    {
        $this->reservation = $reservation;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'broadcast'];
    }

    public function toBroadcast(object $notifiable): array
    {
        return [
            'data' => [
                'reservation_id' => $this->reservation->id,
                'status' => $this->reservation->status,
                'message' => "Your reservation has been {$this->reservation->status}",
            ]
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $status = ucfirst($this->reservation->status);
        return (new MailMessage)
            ->subject("Reservation {$status}")
            ->line("Your reservation at {$this->reservation->restaurant->name_en} has been {$this->reservation->status}.")
            ->line("Date: " . \Carbon\Carbon::parse($this->reservation->reservation_date)->format('Y-m-d'))
            ->line("Time: {$this->reservation->reservation_time}")
            ->action('View My Reservations', url('/my-reservations'))
            ->line('Thank you for using Venyo!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'reservation_id' => $this->reservation->id,
            'restaurant_name' => $this->reservation->restaurant->name_en,
            'status' => $this->reservation->status,
            'message' => "Your reservation has been {$this->reservation->status}",
        ];
    }
}
