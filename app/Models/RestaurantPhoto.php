<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RestaurantPhoto extends Model
{
    protected $fillable = ['restaurant_id', 'photo_path', 'is_cover', 'sort_order'];

    protected $casts = [
        'is_cover' => 'boolean',
    ];

    public function getUrlAttribute(): ?string
    {
        return $this->photo_path ? url(\Illuminate\Support\Facades\Storage::url($this->photo_path)) : null;
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }
}
