<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReservationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Eager load orders with items if not already loaded
        $orders = $this->relationLoaded('orders')
            ? $this->orders
            : $this->orders()->with('items.menuItem')->get();

        $preorderTotal = $orders->sum('total');

        return [
            'id' => $this->hashed_id,
            'reservation_date' => $this->reservation_date->format('Y-m-d'),
            'formatted_date' => $this->reservation_date->format('D, M j, Y'),
            'reservation_time' => $this->reservation_time,
            'formatted_time' => \Carbon\Carbon::parse($this->reservation_time)->format('g:i A'),
            'guests_count' => $this->guests_count,
            'occasion' => $this->occasion,
            'special_request' => $this->special_request,
            'dietary_preferences' => $this->dietary_preferences,
            'status' => $this->status,
            'review' => $this->review ? new ReviewResource($this->review) : null,
            'has_review' => $this->review()->exists(),
            'locked_until' => $this->locked_until ? $this->locked_until->toIso8601String() : null,
            'restaurant' => new RestaurantResource($this->whenLoaded('restaurant')),
            'user' => [
                'id' => $this->user->hashed_id ?? null,
                'name' => $this->user->name ?? null,
            ],
            'time_slot' => [
                'id' => $this->timeSlot->hashed_id ?? null,
                'start_time' => $this->timeSlot->start_time ?? null,
            ],
            'orders' => OrderResource::collection($orders->load('items.menuItem')),
            'preorder_total' => (float) $preorderTotal,
        ];
    }
}
