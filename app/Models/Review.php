<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $fillable = ['user_id', 'restaurant_id', 'reservation_id', 'rating', 'comment', 'is_visible'];

    protected $casts = [
        'is_visible' => 'boolean',
    ];

    protected static function booted()
    {
        static::created(function ($review) {
            $review->syncRestaurantStats();
        });

        static::updated(function ($review) {
            $review->syncRestaurantStats();
        });

        static::deleted(function ($review) {
            $review->syncRestaurantStats();
        });
    }

    public function syncRestaurantStats()
    {
        $restaurant = $this->restaurant;
        if ($restaurant) {
            $stats = static::where('restaurant_id', $restaurant->id)
                ->where('is_visible', true)
                ->selectRaw('COUNT(*) as count, AVG(rating) as avg_rating')
                ->first();

            $restaurant->update([
                'reviews_count' => $stats->count ?? 0,
                'rating' => round($stats->avg_rating ?? 0, 1),
            ]);
        }
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }
}
