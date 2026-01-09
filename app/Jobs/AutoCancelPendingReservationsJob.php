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

class AutoCancelPendingReservationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

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
        $now = now();

        $reservations = Reservation::where('status', 'pending')
            ->where(function ($query) use ($now) {
                $query->where('created_at', '<', $now->subHours(24))
                    ->orWhere(function ($q) use ($now) {
                        $q->where('reservation_date', '<', $now->toDateString())
                            ->orWhere(function ($sq) use ($now) {
                                $sq->where('reservation_date', $now->toDateString())
                                    ->where('reservation_time', '<', $now->toTimeString());
                            });
                    });
            })
            ->get();

        foreach ($reservations as $reservation) {
            $reservation->update(['status' => 'canceled']);

            Log::channel('api')->info('Auto-canceled reservation', [
                'trace_id' => $traceId,
                'reservation_id' => $reservation->id,
                'reason' => 'No response from restaurant',
            ]);

            // Send notification to user
            $reservation->user->notify(new \App\Notifications\ReservationStatusUpdated($reservation));
        }

        Log::channel('api')->info('Auto-cancel job completed', [
            'trace_id' => $traceId,
            'canceled_count' => $reservations->count(),
        ]);
    }
}
