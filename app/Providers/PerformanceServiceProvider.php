<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Events\QueryExecuted;

class PerformanceServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        DB::listen(function (QueryExecuted $query) {
            // Log queries taking longer than 500ms
            if ($query->time > 500) {
                Log::channel('api')->warning('Slow Query Detected', [
                    'trace_id' => \App\Services\LoggingService::getTraceId(),
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time_ms' => $query->time,
                    'url' => request()->fullUrl(),
                ]);
            }
        });
    }
}
