<?php

namespace App\Console\Commands;

use App\Jobs\ExecuteScheduledReportsJob;
use Illuminate\Console\Command;

class ExecuteScheduledReports extends Command
{
    protected $signature = 'scheduled-reports:execute';
    protected $description = 'Execute all due scheduled reports';

    public function handle(): int
    {
        $this->info('Dispatching scheduled reports execution job...');

        ExecuteScheduledReportsJob::dispatch();

        $this->info('Job dispatched successfully');

        return self::SUCCESS;
    }
}
