<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'name_en', 'name_ar', 'slug', 'description_en', 'description_ar',
        'price', 'duration_days', 'features', 'is_promoted_included',
        'max_photos', 'is_active', 'sort_order'
    ];

    protected $casts = [
        'features' => 'array',
        'is_promoted_included' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
