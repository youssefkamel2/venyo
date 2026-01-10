<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Reminder notification sent to Restaurant Owner 1 hour before reservation.
 * 
 * Channels: Pusher broadcast only (for in-app dashboard notification)
 * Recipient: RestaurantOwner
 */
class OwnerReservationReminder extends Notification
{
    use Queueable;

    protected Reservation $reservation;

    public function __construct(Reservation $reservation)
    {
        $this->reservation = $reservation;
    }

    /**
     * Only broadcast to Pusher for in-app notification.
     */
    public function via(object $notifiable): array
    {
        Log::channel('api')->info('Sending OwnerReservationReminder notification', [
            'notification_type' => 'OwnerReservationReminder',
            'recipient_type' => get_class($notifiable),
            'recipient_id' => $notifiable->id,
            'reservation_id' => $this->reservation->id,
            'channels' => ['broadcast'],
        ]);

        return ['broadcast'];
    }

    /**
     * Broadcast data for Pusher.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        $time = \Carbon\Carbon::parse($this->reservation->reservation_time)->format('g:i A');

        return new BroadcastMessage([
            'type' => 'reservation.reminder',
            'reservation_id' => $this->reservation->id,
            'user_name' => $this->reservation->user->name,
            'guests_count' => $this->reservation->guests_count,
            'reservation_time' => $time,
            'message' => "Reminder: {$this->reservation->user->name} has a reservation at {$time} (in ~1 hour)",
            'created_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Define the broadcast channel name.
     */
    public function broadcastOn(): array
    {
        return ['private-restaurant.' . $this->reservation->restaurant_id];
    }

    /**
     * Define the broadcast event name.
     */
    public function broadcastAs(): string
    {
        return 'reservation.reminder';
    }
}
