<?php

namespace App\Jobs;

use App\Models\Reservation;
use App\Notifications\ReservationStatusUpdated;
use App\Services\LoggingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job to auto-cancel pending reservations that haven't been approved.
 * 
 * Runs: Every 5 minutes
 * Cancels: Reservations pending for more than 5 minutes
 * Notifies: Sends email to customer about cancellation
 */
class AutoCancelPendingReservationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct()
    {
        //
    }

    public function handle(): void
    {
        $traceId = LoggingService::getTraceId();
        $startTime = microtime(true);
        $jobName = 'AutoCancelPendingReservationsJob';

        $msg = "[{$jobName}] Running...";
        echo $msg . PHP_EOL;
        Log::channel('queue')->info($msg, [
            'trace_id' => $traceId,
            'started_at' => now()->toIso8601String(),
        ]);

        try {
            // Cancel reservations pending for more than 5 minutes
            $cutoffTime = now()->subMinutes(5);

            $reservations = Reservation::where('status', 'pending')
                ->where('created_at', '<', $cutoffTime)
                ->with(['user', 'restaurant'])
                ->get();

            $canceledCount = 0;
            $emailsSent = 0;

            foreach ($reservations as $reservation) {
                $reservation->update(['status' => 'canceled']);
                $canceledCount++;

                Log::channel('queue')->info("[{$jobName}] Canceled reservation #{$reservation->id}", [
                    'trace_id' => $traceId,
                    'reservation_id' => $reservation->id,
                    'user_id' => $reservation->user_id,
                    'restaurant_id' => $reservation->restaurant_id,
                    'was_pending_for_minutes' => $reservation->created_at->diffInMinutes(now()),
                ]);

                // Send email to customer
                try {
                    $reservation->user->notify(new ReservationStatusUpdated($reservation));
                    $emailsSent++;
                } catch (\Exception $e) {
                    Log::channel('queue')->error("[{$jobName}] Failed to send email for #{ $reservation->id}", [
                        'trace_id' => $traceId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            $msg = "[{$jobName}] Completed. Canceled: {$canceledCount}. Emails: {$emailsSent}. Duration: {$duration}ms";
            echo $msg . PHP_EOL;

            Log::channel('queue')->info($msg, [
                'trace_id' => $traceId,
                'reservations_canceled' => $canceledCount,
                'emails_sent' => $emailsSent,
                'duration_ms' => $duration,
            ]);

        } catch (\Exception $e) {
            Log::channel('queue')->error("[{$jobName}] Failed: " . $e->getMessage(), [
                'trace_id' => $traceId,
                'trace' => $e->getTraceAsString(),
            ]);
            echo "[{$jobName}] Failed: " . $e->getMessage() . PHP_EOL;
            throw $e;
        }
    }
}
