<?php

namespace App\Services;

use Illuminate\Support\Facades\Broadcast;

class PushNotificationService
{
    /**
     * Broadcast a message to a specific channel.
     *
     * @param string $channel
     * @param string $event
     * @param array $data
     * @return void
     */
    public function broadcast(string $channel, string $event, array $data): void
    {
        Broadcast::on($channel)->as($event)->with($data)->broadcast();
    }

    /**
     * Notify a specific user.
     *
     * @param int $userId
     * @param string $title
     * @param string $body
     * @param array $extraData
     * @return void
     */
    public function notifyUser(int $userId, string $title, string $body, array $extraData = []): void
    {
        $this->broadcast("private-customer.{$userId}", 'notification.received', [
            'title' => $title,
            'body' => $body,
            'data' => $extraData,
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    /**
     * Notify a restaurant owner.
     *
     * @param int $restaurantId
     * @param string $title
     * @param string $body
     * @param array $extraData
     * @return void
     */
    public function notifyRestaurant(int $restaurantId, string $title, string $body, array $extraData = []): void
    {
        $this->broadcast("private-restaurant.{$restaurantId}", 'notification.received', [
            'title' => $title,
            'body' => $body,
            'data' => $extraData,
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    /**
     * Notify all system admins.
     *
     * @param string $title
     * @param string $body
     * @param array $extraData
     * @return void
     */
    public function notifyAdmins(string $title, string $body, array $extraData = []): void
    {
        $this->broadcast('private-admin.all', 'notification.received', [
            'title' => $title,
            'body' => $body,
            'data' => $extraData,
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    /**
     * Broadcast a promotional message to all users.
     *
     * @param string $title
     * @param string $body
     * @param array $extraData
     * @return void
     */
    public function broadcastPromotion(string $title, string $body, array $extraData = []): void
    {
        $this->broadcast('public.promotions', 'promotion.received', [
            'title' => $title,
            'body' => $body,
            'data' => $extraData,
            'timestamp' => now()->toDateTimeString(),
        ]);
    }
}
