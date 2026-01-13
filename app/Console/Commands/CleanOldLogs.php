<?php

namespace App\Console\Commands;

use App\Models\ApiLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanOldLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logs:clean';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete logs older than 30 days';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $count = ApiLog::where('created_at', '<', now()->subDays(30))->delete();
        // for demonstration purposes only
        // $count = ApiLog::where('created_at', '<', now()->subMinutes(5))->delete();

        $this->info("Successfully deleted {$count} old logs.");

        Log::info("Cron Job: Deleted {$count} old logs.");

        return Command::SUCCESS;
    }
}
