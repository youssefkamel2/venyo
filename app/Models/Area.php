<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Traits\HasHashedId;

class Area extends Model
{
    use HasHashedId;
    protected $fillable = ['name_en', 'name_ar', 'slug', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function subAreas(): HasMany
    {
        return $this->hasMany(SubArea::class);
    }

    public function restaurants(): HasMany
    {
        return $this->hasMany(Restaurant::class);
    }
}
