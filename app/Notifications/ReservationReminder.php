<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReservationReminder extends Notification
{
    use Queueable;

    protected $reservation;

    public function __construct(Reservation $reservation)
    {
        $this->reservation = $reservation;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Upcoming Reservation Reminder')
            ->line("This is a reminder for your reservation at {$this->reservation->restaurant->name_en}.")
            ->line("Time: {$this->reservation->reservation_time}")
            ->line("Date: " . \Carbon\Carbon::parse($this->reservation->reservation_date)->format('Y-m-d'))
            ->action('View Details', url('/my-reservations'))
            ->line('We look forward to seeing you!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'reservation_id' => $this->reservation->id,
            'restaurant_name' => $this->reservation->restaurant->name_en,
            'message' => 'Upcoming reservation reminder',
        ];
    }
}
