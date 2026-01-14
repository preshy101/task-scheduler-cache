<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use jcobhams\NewsApi\NewsApi;

class NewsApiService
{
    protected $newsApi;

    public function __construct()
    {
        $this->newsApi = new NewsApi(config('services.newsapi.key'));
    }

    public function getTopHeadlines($country = 'us')
    {
        // Use v3 key to invalidate potentially bad existing cache from previous implementation
        $cacheKey = "news.headlines.v3.{$country}";

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            // $q, $sources, $country, $category, $page_size, $page
            $response = $this->newsApi->getTopHeadlines(null, null, $country);

            $data = (array) $response;

            // conversion to associative array
            $data = json_decode(json_encode($response), true);

            // Only cache successful responses
            if (($data['status'] ?? '') === 'ok') {
                Cache::put($cacheKey, $data, 3600);
            } else {
                \Illuminate\Support\Facades\Log::error("NewsAPI Error for {$country}: " . json_encode($data));
            }

            return $data;

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("NewsAPI Request Exception: " . $e->getMessage());

            // Return error structure compatible with frontend
            return [
                'status' => 'error',
                'message' => 'Service temporarily unavailable: ' . $e->getMessage()
            ];
        }
    }
}
