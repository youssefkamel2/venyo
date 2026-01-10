<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->menuItem->name_en, // Shortcut for easy frontend display
            'quantity' => $this->quantity,
            'price' => (float) $this->price,
            'total' => (float) ($this->price * $this->quantity),
        ];
    }
}
