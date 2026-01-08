<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Reservation Reminders (Check every hour)
        $schedule->call(function () {
            $reminders = \App\Models\Reservation::where('status', 'accepted')
                ->where('reservation_date', now()->toDateString())
                ->whereRaw('ABS(TIMESTAMPDIFF(HOUR, CONCAT(reservation_date, " ", reservation_time), NOW())) = 2')
                ->get();

            foreach ($reminders as $reservation) {
                $reservation->user->notify(new \App\Notifications\ReservationReminder($reservation));
            }
        })->hourly();

        // Owner 1-hour reminders (Pusher)
        $schedule->call(function () {
            $reminders = \App\Models\Reservation::where('status', 'accepted')
                ->where('reservation_date', now()->toDateString())
                ->whereRaw('ABS(TIMESTAMPDIFF(MINUTE, CONCAT(reservation_date, " ", reservation_time), NOW())) <= 65')
                ->whereRaw('ABS(TIMESTAMPDIFF(MINUTE, CONCAT(reservation_date, " ", reservation_time), NOW())) >= 55')
                ->get();

            foreach ($reminders as $reservation) {
                $reservation->restaurant->owner->notify(new \App\Notifications\OwnerReservationReminder($reservation));
            }
        })->everyFiveMinutes();

        // Auto-cancel abandoned reservations
        $schedule->call(function () {
            (new \App\Services\ReservationService())->autoCancelPending();
        })->hourly();

        // Cleanup expired locks
        $schedule->call(function () {
            (new \App\Services\ReservationService())->cleanupLocks();
        })->everyFiveMinutes();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
