<?php

namespace App\Traits;

use App\Services\HashidService;

trait HasHashedId
{
    public function getHashedIdAttribute(): string
    {
        return app(HashidService::class)->encode($this->id);
    }

    public static function decodeId(string $hash): ?int
    {
        return app(HashidService::class)->decode($hash);
    }
}
