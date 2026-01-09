<?php

namespace App\Jobs;

use App\Models\Reservation;
use App\Notifications\ReservationReminder;
use App\Services\LoggingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendReservationRemindersJob implements ShouldQueue
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

        // Get reservations that are coming up in 2 hours
        $reminders = Reservation::where('status', 'accepted')
            ->where('reservation_date', now()->toDateString())
            ->whereRaw('ABS(TIMESTAMPDIFF(HOUR, CONCAT(reservation_date, " ", reservation_time), NOW())) = 2')
            ->get();

        foreach ($reminders as $reservation) {
            $reservation->user->notify(new ReservationReminder($reservation));

            Log::channel('api')->info('Sent reservation reminder', [
                'trace_id' => $traceId,
                'reservation_id' => $reservation->id,
                'user_id' => $reservation->user_id,
            ]);
        }

        Log::channel('api')->info('Reservation reminders job completed', [
            'trace_id' => $traceId,
            'reminders_sent' => $reminders->count(),
        ]);
    }
}
