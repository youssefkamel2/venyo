<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\LoggingService;

/**
 * Job to check for failed jobs and log their status.
 * Intended to run frequently (e.g., every 30 seconds) to provide visibility.
 */
class CheckFailedJobsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        //
    }

    public function handle(): void
    {
        $traceId = LoggingService::getTraceId();
        $startTime = microtime(true);
        $jobName = 'CheckFailedJobsJob';

        $msg = "[{$jobName}] Checking for failed jobs...";
        echo $msg . PHP_EOL;

        try {
            $failedCount = DB::table('failed_jobs')->count();

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            if ($failedCount > 0) {
                $warningMsg = "[{$jobName}] WARNING: Found {$failedCount} failed job(s)! Duration: {$duration}ms";
                echo $warningMsg . PHP_EOL;

                // Log detailed warning to queue log
                Log::channel('queue')->warning($warningMsg, [
                    'trace_id' => $traceId,
                    'failed_count' => $failedCount,
                    'duration_ms' => $duration
                ]);
            } else {
                $successMsg = "[{$jobName}] Status OK. No failed jobs. Duration: {$duration}ms";
                echo $successMsg . PHP_EOL;

                Log::channel('queue')->info($successMsg, [
                    'trace_id' => $traceId,
                    'failed_count' => 0,
                    'duration_ms' => $duration
                ]);
            }

        } catch (\Exception $e) {
            $errorMsg = "[{$jobName}] Error checking failed jobs: " . $e->getMessage();
            echo $errorMsg . PHP_EOL;

            Log::channel('queue')->error($errorMsg, [
                'trace_id' => $traceId,
                'error' => $e->getMessage()
            ]);
        }
    }
}
