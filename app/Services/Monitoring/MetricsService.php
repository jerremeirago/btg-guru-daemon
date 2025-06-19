<?php

namespace App\Services\Monitoring;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class MetricsService
{
    /**
     * Get API metrics for Prometheus.
     *
     * @return array
     */
    public function getApiMetrics(): array
    {
        try {
            // Get API request metrics from the database
            $apiMetrics = DB::table('api_requests')
                ->select(
                    'endpoint',
                    'sport_type',
                    'success',
                    DB::raw('COUNT(*) as total_requests'),
                    DB::raw('AVG(response_time_ms) as avg_response_time'),
                    DB::raw('MAX(response_time_ms) as max_response_time'),
                    DB::raw('SUM(CASE WHEN success = true THEN 1 ELSE 0 END) as successful_requests'),
                    DB::raw('SUM(CASE WHEN success = false THEN 1 ELSE 0 END) as failed_requests')
                )
                ->whereDate('created_at', '>=', now()->subDay())
                ->groupBy('endpoint', 'sport_type', 'success')
                ->get()
                ->toArray();

            return $apiMetrics;
        } catch (\Exception $e) {
            Log::error('Failed to collect API metrics: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get queue metrics for Prometheus.
     *
     * @return array
     */
    public function getQueueMetrics(): array
    {
        try {
            // Get queue metrics from Redis
            $queueMetrics = [
                'pending_jobs' => Redis::connection('queue')->llen('queues:default'),
                'failed_jobs' => DB::table('failed_jobs')->count(),
            ];

            return $queueMetrics;
        } catch (\Exception $e) {
            Log::error('Failed to collect queue metrics: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get cache metrics for Prometheus.
     *
     * @return array
     */
    public function getCacheMetrics(): array
    {
        try {
            // Get cache hit rate (this is an approximation)
            $cacheHits = Cache::get('metrics:cache_hits', 0);
            $cacheMisses = Cache::get('metrics:cache_misses', 0);
            $hitRate = $cacheHits + $cacheMisses > 0 ? 
                ($cacheHits / ($cacheHits + $cacheMisses)) * 100 : 0;

            return [
                'cache_hits' => $cacheHits,
                'cache_misses' => $cacheMisses,
                'hit_rate' => $hitRate,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to collect cache metrics: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get database metrics for Prometheus.
     *
     * @return array
     */
    public function getDatabaseMetrics(): array
    {
        try {
            // Get database metrics
            $dbMetrics = [
                'total_leagues' => DB::table('leagues')->count(),
                'total_teams' => DB::table('teams')->count(),
                'total_sport_matches' => DB::table('sport_matches')->count(),
                'total_players' => DB::table('players')->count(),
            ];

            return $dbMetrics;
        } catch (\Exception $e) {
            Log::error('Failed to collect database metrics: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get system metrics for Prometheus.
     *
     * @return array
     */
    public function getSystemMetrics(): array
    {
        try {
            // Get system metrics
            $systemMetrics = [
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true),
            ];

            return $systemMetrics;
        } catch (\Exception $e) {
            Log::error('Failed to collect system metrics: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Increment cache hit counter.
     *
     * @return void
     */
    public function incrementCacheHits(): void
    {
        try {
            $hits = Cache::get('metrics:cache_hits', 0);
            Cache::put('metrics:cache_hits', $hits + 1, now()->addDay());
        } catch (\Exception $e) {
            Log::error('Failed to increment cache hits: ' . $e->getMessage());
        }
    }

    /**
     * Increment cache miss counter.
     *
     * @return void
     */
    public function incrementCacheMisses(): void
    {
        try {
            $misses = Cache::get('metrics:cache_misses', 0);
            Cache::put('metrics:cache_misses', $misses + 1, now()->addDay());
        } catch (\Exception $e) {
            Log::error('Failed to increment cache misses: ' . $e->getMessage());
        }
    }
}
