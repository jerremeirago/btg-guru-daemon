<?php

namespace App\Http\Middleware;

use App\Services\Monitoring\MetricsService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class TrackCacheMetrics
{
    /**
     * The metrics service instance.
     *
     * @var \App\Services\Monitoring\MetricsService
     */
    protected MetricsService $metricsService;

    /**
     * Create a new middleware instance.
     *
     * @param \App\Services\Monitoring\MetricsService $metricsService
     * @return void
     */
    public function __construct(MetricsService $metricsService)
    {
        $this->metricsService = $metricsService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Override the Cache facade's get method to track hits and misses
        Cache::macro('getWithMetrics', function ($key, $default = null) {
            $value = Cache::get($key, null);
            
            if ($value === null) {
                app(MetricsService::class)->incrementCacheMisses();
                return $default;
            }
            
            app(MetricsService::class)->incrementCacheHits();
            return $value;
        });

        return $next($request);
    }
}
