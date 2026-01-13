<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ClearNewsCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:clear-news';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear the cached news API responses for cache invalidation';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Clear all news-related cache keys
        // Using pattern-based clearing for news headlines
        $countries = ['us', 'gb', 'ca', 'au', 'in', 'de', 'fr'];
        $clearedCount = 0;

        foreach ($countries as $country) {
            // Updated to match the new v3 key from NewsApiService
            $key = "news.headlines.v3.{$country}";
            if (Cache::has($key)) {
                Cache::forget($key);
                $clearedCount++;
            }
        }

        $this->info("News cache cleared successfully. {$clearedCount} cache entries removed.");
        Log::info("Cron Job: News cache cleared. {$clearedCount} entries removed.");

        return Command::SUCCESS;
    }
}
