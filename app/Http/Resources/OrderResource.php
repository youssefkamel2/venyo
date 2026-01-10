<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->hashed_id,
            'order_number' => $this->order_number,
            'total' => (float) $this->total,
            'created_at' => $this->created_at->toIso8601String(),
            'formatted_date' => $this->created_at->format('M j, Y g:i A'),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
