<?php

namespace App\Console;

use App\Jobs\AutoCancelPendingReservationsJob;
use App\Jobs\CleanupExpiredLocksJob;
use App\Jobs\SendOwnerReservationRemindersJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Owner 1-hour reminders (Check every 5 minutes, dispatched to queue)
        $schedule->job(new SendOwnerReservationRemindersJob())
            ->everyMinute()
            ->name('owner-reservation-reminders')
            ->withoutOverlapping();

        // Auto-cancel abandoned reservations (Check every 5 minutes to catch 5-min expirations)
        $schedule->job(new AutoCancelPendingReservationsJob())
            ->everyMinute()
            ->name('auto-cancel-reservations')
            ->withoutOverlapping();

        // Cleanup expired locks (Check every 5 minutes, dispatched to queue)
        $schedule->job(new CleanupExpiredLocksJob())
            ->everyMinute()
            ->name('cleanup-expired-locks')
            ->withoutOverlapping();

        // Monitor Failed Jobs (Run every 30 seconds)
        // This command runs the check, sleeps for 30s, and runs it again.
        $schedule->command('monitor:failed-jobs')
            ->everyMinute()
            ->runInBackground();

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

