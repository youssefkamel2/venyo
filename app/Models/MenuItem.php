<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasHashedId;
use Illuminate\Support\Facades\Storage;

class MenuItem extends Model
{
    use HasHashedId;

    protected $fillable = [
        'menu_category_id',
        'name_en',
        'name_ar',
        'description_en',
        'description_ar',
        'price',
        'image_url',
        'course',
        'is_available'
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'price' => 'decimal:2'
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(MenuCategory::class, 'menu_category_id');
    }

    public function getUrlAttribute(): ?string
    {
        return $this->image_url ? url(Storage::url($this->image_url)) : null;
    }
}
