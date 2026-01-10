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
        $startTime = microtime(true);
        $jobName = 'CleanupExpiredLocksJob';

        $msg = "[{$jobName}] Running...";
        echo $msg . PHP_EOL;
        Log::channel('queue')->info($msg, ['trace_id' => $traceId]);

        $expiredLocks = Reservation::where('status', 'hold')
            ->whereNotNull('locked_until')
            ->where('locked_until', '<', now())
            ->get();

        $count = $expiredLocks->count();

        if ($count > 0) {
            foreach ($expiredLocks as $lock) {
                $lock->update(['status' => 'canceled']);
                Log::channel('queue')->info("[{$jobName}] Released lock for reservation #{$lock->id}", [
                    'trace_id' => $traceId,
                    'reservation_id' => $lock->id,
                    'locked_until' => $lock->locked_until,
                ]);
            }
        }

        $duration = round((microtime(true) - $startTime) * 1000, 2);

        $msg = "[{$jobName}] Completed. Released: {$count}. Duration: {$duration}ms";
        echo $msg . PHP_EOL;

        Log::channel('queue')->info($msg, [
            'trace_id' => $traceId,
            'released_count' => $count,
            'duration_ms' => $duration,
        ]);
    }
}
