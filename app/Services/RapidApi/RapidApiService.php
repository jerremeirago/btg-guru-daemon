<?php

namespace App\Services\RapidApi;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

abstract class RapidApiService
{
    /**
     * The RapidAPI host.
     *
     * @var string
     */
    protected string $apiHost;

    /**
     * The RapidAPI key.
     *
     * @var string
     */
    protected string $apiKey;

    /**
     * The base URL for the API.
     *
     * @var string
     */
    protected string $baseUrl;

    /**
     * Default cache TTL in seconds.
     *
     * @var int
     */
    protected int $defaultCacheTtl = 300; // 5 minutes

    /**
     * Create a new RapidAPI service instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->apiHost = config('services.rapidapi.host');
        $this->apiKey = config('services.rapidapi.key');
        $this->baseUrl = config('services.rapidapi.base_url');
    }

    /**
     * Make a request to the RapidAPI endpoint.
     *
     * @param string $endpoint
     * @param array $parameters
     * @param string $method
     * @param int|null $cacheTtl
     * @return array
     */
    protected function makeRequest(
        string $endpoint,
        array $parameters = [],
        string $method = 'GET',
        ?int $cacheTtl = null
    ): array {
        $url = $this->buildUrl($endpoint, $parameters);
        $cacheKey = $this->generateCacheKey($url);
        $ttl = $cacheTtl ?? $this->defaultCacheTtl;

        // Try to get from cache first
        if ($cachedResponse = Cache::get($cacheKey)) {
            return $cachedResponse;
        }

        try {
            $response = Http::withHeaders([
                'x-rapidapi-host' => $this->apiHost,
                'x-rapidapi-key' => $this->apiKey,
            ])->$method($url);

            if ($response->successful()) {
                $data = $response->json();
                
                // Cache the successful response
                Cache::put($cacheKey, $data, $ttl);
                
                // Log API usage for tracking
                $this->logApiUsage($endpoint, $method);
                
                return $data;
            }
            
            Log::error('RapidAPI request failed', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);
            
            return ['error' => 'API request failed', 'status' => $response->status()];
        } catch (\Exception $e) {
            Log::error('RapidAPI exception', [
                'endpoint' => $endpoint,
                'message' => $e->getMessage(),
            ]);
            
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Build the full URL for the API request.
     *
     * @param string $endpoint
     * @param array $parameters
     * @return string
     */
    protected function buildUrl(string $endpoint, array $parameters = []): string
    {
        // Handle path parameters (/:param1/:param2)
        $url = $this->baseUrl . '/' . $endpoint;
        
        // Handle query parameters (?param1=value&param2=value)
        if (!empty($parameters) && strpos($url, '?') === false) {
            $url .= '?' . http_build_query($parameters);
        }
        
        return $url;
    }

    /**
     * Generate a cache key for the request.
     *
     * @param string $url
     * @return string
     */
    protected function generateCacheKey(string $url): string
    {
        return 'rapidapi_cache:' . md5($url);
    }

    /**
     * Log API usage for tracking and monitoring.
     *
     * @param string $endpoint
     * @param string $method
     * @return void
     */
    protected function logApiUsage(string $endpoint, string $method): void
    {
        // This will be implemented to track API usage
        // Could store in database or increment Redis counters
    }

    /**
     * Normalize response data to a consistent format.
     *
     * @param array $data
     * @return array
     */
    abstract protected function normalizeResponse(array $data): array;
}
