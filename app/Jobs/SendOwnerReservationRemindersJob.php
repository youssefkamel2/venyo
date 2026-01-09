<?php

namespace App\Jobs;

use App\Models\Reservation;
use App\Notifications\OwnerReservationReminder;
use App\Services\LoggingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendOwnerReservationRemindersJob implements ShouldQueue
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

        // Get reservations that are coming up in ~1 hour (55-65 min window)
        $reminders = Reservation::where('status', 'accepted')
            ->where('reservation_date', now()->toDateString())
            ->whereRaw('ABS(TIMESTAMPDIFF(MINUTE, CONCAT(reservation_date, " ", reservation_time), NOW())) <= 65')
            ->whereRaw('ABS(TIMESTAMPDIFF(MINUTE, CONCAT(reservation_date, " ", reservation_time), NOW())) >= 55')
            ->get();

        foreach ($reminders as $reservation) {
            $reservation->restaurant->owner->notify(new OwnerReservationReminder($reservation));

            Log::channel('api')->info('Sent owner reservation reminder', [
                'trace_id' => $traceId,
                'reservation_id' => $reservation->id,
                'owner_id' => $reservation->restaurant->owner_id,
            ]);
        }

        Log::channel('api')->info('Owner reminders job completed', [
            'trace_id' => $traceId,
            'reminders_sent' => $reminders->count(),
        ]);
    }
}
