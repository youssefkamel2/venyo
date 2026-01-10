<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\CheckFailedJobsJob;

class MonitorFailedJobsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitor:failed-jobs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the failed jobs check twice in a minute (every 30 seconds)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        \Illuminate\Support\Facades\Log::channel('queue')->info('[MonitorFailedJobsCommand] Starting 30s monitoring loop...');

        // First run
        dispatch(new CheckFailedJobsJob());

        // Wait 30 seconds
        sleep(30);

        // Second run
        dispatch(new CheckFailedJobsJob());
    }
}
