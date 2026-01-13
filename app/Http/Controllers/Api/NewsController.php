<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiLog;
use App\Services\NewsApiService;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    protected $newsService;

    public function __construct(NewsApiService $newsService)
    {
        $this->newsService = $newsService;
    }

    public function getHeadlines(Request $request)
    {
        // Log the API request
        $log = ApiLog::create([
            'endpoint' => $request->path(),
            'method' => $request->method(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'parameters' => $request->all(),
        ]);

        // Get cached or fresh data
        $country = $request->get('country', 'us');
        $data = $this->newsService->getTopHeadlines($country);

        // Update log with response status
        $log->update([
            'response_code' => $data['status'] === 'ok' ? 200 : 500,
            'response_size' => strlen(json_encode($data))
        ]);

        return response()->json($data);
    }

    /**
     * Get API logs with statistics
     */
    public function getLogs(Request $request)
    {
        $logs = ApiLog::orderBy('created_at', 'desc')
            ->limit($request->get('limit', 20))
            ->get();

        $stats = [
            'total' => ApiLog::count(),
            'today' => ApiLog::whereDate('created_at', today())->count(),
            'success_rate' => ApiLog::where('response_code', 200)->count() / max(ApiLog::count(), 1) * 100,
        ];

        return response()->json([
            'logs' => $logs,
            'stats' => $stats,
        ]);
    }
}
