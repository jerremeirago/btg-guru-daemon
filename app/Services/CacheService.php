<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CacheService
{
    /**
     * Cache TTL values in seconds.
     *
     * @var array
     */
    protected array $cacheTtl = [
        'default' => 300, // 5 minutes
        'live_matches' => 60, // 1 minute
        'upcoming_matches' => 600, // 10 minutes
        'completed_matches' => 3600, // 1 hour
        'standings' => 3600, // 1 hour
        'league_info' => 86400, // 24 hours
        'team_info' => 86400, // 24 hours
        'player_info' => 86400, // 24 hours
    ];
    
    /**
     * Cache key prefix.
     *
     * @var string
     */
    protected string $cachePrefix = 'sports_data:';
    
    /**
     * Get data from cache or execute the callback to retrieve it.
     *
     * @param string $key
     * @param callable $callback
     * @param string $type
     * @return mixed
     */
    public function remember(string $key, callable $callback, string $type = 'default')
    {
        $cacheKey = $this->buildCacheKey($key);
        $ttl = $this->getTtl($type);
        
        // Try to get from cache
        if (Cache::has($cacheKey)) {
            Log::debug("Cache hit for key: {$cacheKey}");
            return Cache::get($cacheKey);
        }
        
        // Execute callback to get data
        Log::debug("Cache miss for key: {$cacheKey}");
        $data = $callback();
        
        // Store in cache
        Cache::put($cacheKey, $data, $ttl);
        
        return $data;
    }
    
    /**
     * Store data in cache.
     *
     * @param string $key
     * @param mixed $data
     * @param string $type
     * @return bool
     */
    public function put(string $key, $data, string $type = 'default'): bool
    {
        $cacheKey = $this->buildCacheKey($key);
        $ttl = $this->getTtl($type);
        
        return Cache::put($cacheKey, $data, $ttl);
    }
    
    /**
     * Get data from cache.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        $cacheKey = $this->buildCacheKey($key);
        
        return Cache::get($cacheKey, $default);
    }
    
    /**
     * Remove data from cache.
     *
     * @param string $key
     * @return bool
     */
    public function forget(string $key): bool
    {
        $cacheKey = $this->buildCacheKey($key);
        
        return Cache::forget($cacheKey);
    }
    
    /**
     * Build a cache key with prefix.
     *
     * @param string $key
     * @return string
     */
    protected function buildCacheKey(string $key): string
    {
        return $this->cachePrefix . $key;
    }
    
    /**
     * Get TTL for a specific data type.
     *
     * @param string $type
     * @return int
     */
    protected function getTtl(string $type): int
    {
        return $this->cacheTtl[$type] ?? $this->cacheTtl['default'];
    }
    
    /**
     * Determine appropriate cache type based on match status.
     *
     * @param string|null $status
     * @return string
     */
    public function getMatchCacheType(?string $status): string
    {
        if (!$status) {
            return 'default';
        }
        
        $liveStatuses = ['LIVE', 'IN_PLAY', '1H', '2H', 'HT', 'ET', 'P', 'BT', 'SUSP'];
        $completedStatuses = ['FT', 'AET', 'PEN', 'AWD', 'WO', 'CANC', 'ABD', 'PST'];
        
        $status = strtoupper($status);
        
        if (in_array($status, $liveStatuses)) {
            return 'live_matches';
        } elseif (in_array($status, $completedStatuses)) {
            return 'completed_matches';
        } else {
            return 'upcoming_matches';
        }
    }
    
    /**
     * Clear all sports data cache.
     *
     * @return bool
     */
    public function clearAll(): bool
    {
        $cacheKeys = Cache::get('all_sports_cache_keys', []);
        
        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
        
        Cache::forget('all_sports_cache_keys');
        
        return true;
    }
    
    /**
     * Register a cache key for tracking.
     *
     * @param string $key
     * @return void
     */
    protected function registerCacheKey(string $key): void
    {
        $cacheKeys = Cache::get('all_sports_cache_keys', []);
        $cacheKeys[] = $key;
        Cache::put('all_sports_cache_keys', array_unique($cacheKeys), 86400 * 7); // 7 days
    }
}
