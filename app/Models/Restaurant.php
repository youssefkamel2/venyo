<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

use App\Traits\HasHashedId;

class Restaurant extends Model implements HasMedia
{
    use InteractsWithMedia, HasHashedId, LogsActivity;

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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name_en',
                'name_ar',
                'description_en',
                'description_ar',
                'area_id',
                'sub_area_id',
                'restaurant_type_id',
                'cuisine_type_id',
                'address',
                'phone',
                'opening_time',
                'closing_time',
                'is_reservable',
                'is_promoted',
                'is_active',
                'is_profile_complete'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Restaurant has been {$eventName}");
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')
            ->singleFile();

        $this->addMediaCollection('photos');

        $this->addMediaCollection('menu');
    }

    public function registerMediaConversions(\Spatie\MediaLibrary\MediaCollections\Models\Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(300)
            ->height(200)
            ->sharpen(10);

        $this->addMediaConversion('medium')
            ->width(800)
            ->height(600);

        $this->addMediaConversion('large')
            ->width(1200)
            ->height(800);
    }
}
