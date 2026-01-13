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
            
            // The library returns an object, we need to convert it to array for consistency
            // or just use it as object. The previous code expected array access $data['status'].
            // Let's coerce it to array to be safe and consistent with previous behavior.
            $data = (array) $response;
            
            // When cast to array, the properties become keys. 
            // However, nested objects (like articles) might still be objects.
            // A simple json decode/encode is the cleanest way to ensure full array structure if needed,
            // but let's see. The frontend expects 'status', 'articles'.
            // The library returns the raw JSON decoded as object.
            
            // Let's do a full conversion to ensure deep array structure if the frontend relies on arrays
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
