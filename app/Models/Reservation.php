<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

use App\Traits\HasHashedId;

class Reservation extends Model
{
    use HasHashedId;
    protected $fillable = [
        'user_id',
        'restaurant_id',
        'time_slot_id',
        'reservation_date',
        'reservation_time',
        'guests_count',
        'occasion',
        'special_request',
        'subscribe_newsletter',
        'status',
        'locked_until',
        'completed_at'
    ];

    protected $casts = [
        'reservation_date' => 'date',
        'subscribe_newsletter' => 'boolean',
        'locked_until' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function timeSlot(): BelongsTo
    {
        return $this->belongsTo(TimeSlot::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function customerReview(): HasOne
    {
        return $this->hasOne(CustomerReview::class);
    }
}
