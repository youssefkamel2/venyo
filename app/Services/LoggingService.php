<?php

namespace App\Services;

use Illuminate\Support\Str;

class LoggingService
{
    protected static $traceId;

    public static function getTraceId(): string
    {
        if (!static::$traceId) {
            static::$traceId = (string) Str::uuid();
        }

        return static::$traceId;
    }
}
