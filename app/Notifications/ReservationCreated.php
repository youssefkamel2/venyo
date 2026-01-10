<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Notification sent to Restaurant Owner when a new reservation is created.
 * 
 * Channels: Pusher broadcast only (for in-app dashboard notification)
 * Recipient: RestaurantOwner
 */
class ReservationCreated extends Notification
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
        Log::channel('api')->info('Sending ReservationCreated notification', [
            'notification_type' => 'ReservationCreated',
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
        return new BroadcastMessage([
            'type' => 'reservation.created',
            'reservation_id' => $this->reservation->id,
            'user_name' => $this->reservation->user->name,
            'user_phone' => $this->reservation->user->phone,
            'guests_count' => $this->reservation->guests_count,
            'reservation_date' => $this->reservation->reservation_date->format('Y-m-d'),
            'reservation_time' => $this->reservation->reservation_time,
            'occasion' => $this->reservation->occasion,
            'message' => 'New reservation received from ' . $this->reservation->user->name,
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
        return 'reservation.created';
    }
}
