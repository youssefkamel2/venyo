<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Notification sent to Customer when their reservation status changes.
 * 
 * Channels: Email only (no Pusher for customers)
 * Recipient: User (Customer)
 * Triggers: Restaurant accepts/rejects, auto-cancel
 */
class ReservationStatusUpdated extends Notification
{
    use Queueable;

    protected Reservation $reservation;

    public function __construct(Reservation $reservation)
    {
        $this->reservation = $reservation;
    }

    /**
     * Only email for customers - no Pusher needed.
     */
    public function via(object $notifiable): array
    {
        Log::channel('api')->info('Sending ReservationStatusUpdated notification', [
            'notification_type' => 'ReservationStatusUpdated',
            'recipient_type' => get_class($notifiable),
            'recipient_id' => $notifiable->id,
            'reservation_id' => $this->reservation->id,
            'new_status' => $this->reservation->status,
            'channels' => ['mail'],
        ]);

        return ['mail'];
    }

    /**
     * Email notification to customer.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $status = ucfirst($this->reservation->status);
        $restaurantName = $this->reservation->restaurant->name_en;
        $date = $this->reservation->reservation_date->format('l, F j, Y');
        $time = \Carbon\Carbon::parse($this->reservation->reservation_time)->format('g:i A');

        $mailMessage = (new MailMessage)
            ->subject("Reservation {$status} - {$restaurantName}")
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line("Your reservation at **{$restaurantName}** has been **{$this->reservation->status}**.")
            ->line("📅 Date: {$date}")
            ->line("🕐 Time: {$time}")
            ->line("👥 Guests: {$this->reservation->guests_count}");

        if ($this->reservation->status === 'accepted') {
            $mailMessage->line('We look forward to seeing you!')
                ->action('View Reservation', url('/my-reservations'));
        } elseif ($this->reservation->status === 'rejected') {
            $mailMessage->line('We apologize for any inconvenience. Please try booking a different time.')
                ->action('Make New Reservation', url('/restaurants/' . $this->reservation->restaurant->slug));
        } elseif ($this->reservation->status === 'canceled') {
            $mailMessage->line('Your reservation was automatically canceled as the restaurant did not respond in time.')
                ->action('Make New Reservation', url('/restaurants/' . $this->reservation->restaurant->slug));
        }

        $mailMessage->line('Thank you for using Venyo!');

        return $mailMessage;
    }
}
