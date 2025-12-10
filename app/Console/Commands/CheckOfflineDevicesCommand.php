<?php

namespace App\Console\Commands;

use App\Jobs\CheckOfflineDevices;
use Illuminate\Console\Command;

class CheckOfflineDevicesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'devices:check-offline';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for offline devices and update their status';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Dispatching offline device check job...');

        CheckOfflineDevices::dispatch();

        $this->info('Offline device check job has been dispatched successfully.');
        $this->comment('Devices that have not sent readings within the threshold will be marked as offline.');

        return $this::SUCCESS;
    }
}
