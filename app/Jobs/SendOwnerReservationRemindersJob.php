<?php

namespace App\Jobs;

use App\Models\Reservation;
use App\Notifications\OwnerReservationReminder;
use App\Services\LoggingService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job to send reminder to restaurant owners 1 hour before reservation.
 * 
 * Runs: Every 5 minutes
 * Reminds: Reservations happening in ~1 hour (55-65 min window)
 * Notifies: Sends Pusher notification to restaurant owner dashboard
 */
class SendOwnerReservationRemindersJob implements ShouldQueue
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
        $jobName = 'SendOwnerReservationRemindersJob';

        $msg = "[{$jobName}] Running...";
        echo $msg . PHP_EOL;
        Log::channel('queue')->info($msg, [
            'trace_id' => $traceId,
            'started_at' => now()->toIso8601String(),
        ]);

        try {
            $now = now();

            // Get accepted reservations happening in ~1 hour
            // We use a slightly wider window (50-70 mins) to make sure we don't miss anything,
            // but we filter precisely below.
            $reservations = Reservation::where('status', 'accepted')
                ->where('reservation_date', $now->toDateString())
                ->with(['user', 'restaurant.owner'])
                ->get()
                ->filter(function ($reservation) use ($now) {
                    $reservationDateTime = Carbon::parse(
                        $reservation->reservation_date->format('Y-m-d') . ' ' . $reservation->reservation_time
                    );
                    $minutesUntil = $now->diffInMinutes($reservationDateTime, false);

                    // Match window: 55 to 65 minutes
                    return $minutesUntil >= 55 && $minutesUntil <= 65;
                });

            $remindersSent = 0;

            foreach ($reservations as $reservation) {
                if (!$reservation->restaurant->owner) {
                    Log::channel('queue')->warning("[{$jobName}] No owner for restaurant #{$reservation->restaurant_id}", [
                        'reservation_id' => $reservation->id
                    ]);
                    continue;
                }

                try {
                    $reservation->restaurant->owner->notify(new OwnerReservationReminder($reservation));
                    $remindersSent++;

                    Log::channel('queue')->info("[{$jobName}] Sent reminder to owner #{$reservation->restaurant->owner->id}", [
                        'reservation_id' => $reservation->id,
                        'reservation_time' => $reservation->reservation_time,
                    ]);
                } catch (\Exception $e) {
                    Log::channel('queue')->error("[{$jobName}] Failed to send reminder", [
                        'reservation_id' => $reservation->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            $msg = "[{$jobName}] Completed. Reminders sent: {$remindersSent}. Duration: {$duration}ms";
            echo $msg . PHP_EOL;

            Log::channel('queue')->info($msg, [
                'trace_id' => $traceId,
                'reminders_sent' => $remindersSent,
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
