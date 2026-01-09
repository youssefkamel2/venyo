<?php

namespace App\Console;

use App\Jobs\AutoCancelPendingReservationsJob;
use App\Jobs\CleanupExpiredLocksJob;
use App\Jobs\SendOwnerReservationRemindersJob;
use App\Jobs\SendReservationRemindersJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Reservation Reminders (Check every hour, dispatched to queue)
        $schedule->job(new SendReservationRemindersJob())
            ->hourly()
            ->name('reservation-reminders')
            ->withoutOverlapping();

        // Owner 1-hour reminders (Check every 5 minutes, dispatched to queue)
        $schedule->job(new SendOwnerReservationRemindersJob())
            ->everyFiveMinutes()
            ->name('owner-reservation-reminders')
            ->withoutOverlapping();

        // Auto-cancel abandoned reservations (Check every hour, dispatched to queue)
        $schedule->job(new AutoCancelPendingReservationsJob())
            ->hourly()
            ->name('auto-cancel-reservations')
            ->withoutOverlapping();

        // Cleanup expired locks (Check every 5 minutes, dispatched to queue)
        $schedule->job(new CleanupExpiredLocksJob())
            ->everyFiveMinutes()
            ->name('cleanup-expired-locks')
            ->withoutOverlapping();

        // Clean up old activity logs (weekly)
        $schedule->command('activitylog:clean')
            ->weekly()
            ->name('clean-activity-logs');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}

