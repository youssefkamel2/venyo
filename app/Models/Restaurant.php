<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

use App\Traits\HasHashedId;

class Restaurant extends Model implements HasMedia
{
    use InteractsWithMedia, HasHashedId;

    protected $fillable = [
        'owner_id',
        'name_en',
        'name_ar',
        'slug',
        'description_en',
        'description_ar',
        'area_id',
        'sub_area_id',
        'restaurant_type_id',
        'cuisine_type_id',
        'address',
        'google_maps_link',
        'menu_link',
        'phone',
        'opening_time',
        'closing_time',
        'is_reservable',
        'is_promoted',
        'is_active',
        'is_profile_complete'
    ];

    protected $casts = [
        'is_reservable' => 'boolean',
        'is_promoted' => 'boolean',
        'is_active' => 'boolean',
        'is_profile_complete' => 'boolean',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(RestaurantOwner::class, 'owner_id');
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function subArea(): BelongsTo
    {
        return $this->belongsTo(SubArea::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(RestaurantType::class, 'restaurant_type_id');
    }

    public function cuisine(): BelongsTo
    {
        return $this->belongsTo(CuisineType::class, 'cuisine_type_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(RestaurantPhoto::class);
    }

    public function slots(): HasMany
    {
        return $this->hasMany(TimeSlot::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function customerReviews(): HasMany
    {
        return $this->hasMany(CustomerReview::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)->where('status', 'active')->where('expires_at', '>', now());
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }
}
