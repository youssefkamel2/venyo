<?php

namespace App\Jobs;

use App\Models\Reservation;
use App\Services\LoggingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CleanupExpiredLocksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 30;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $traceId = LoggingService::getTraceId();

        $cleanedCount = Reservation::where('status', 'hold')
            ->whereNotNull('locked_until')
            ->where('locked_until', '<', now())
            ->update(['status' => 'canceled']);

        if ($cleanedCount > 0) {
            Log::channel('api')->info('Cleaned up expired reservation locks', [
                'trace_id' => $traceId,
                'cleaned_count' => $cleanedCount,
            ]);
        }
    }
}
