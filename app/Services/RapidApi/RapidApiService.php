<?php

namespace App\Services\RapidApi;

use App\Models\ApiRequest;
use App\Services\RetryService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

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
     * The sport type for this service.
     *
     * @var string
     */
    protected string $sportType;
    
    /**
     * The retry service instance.
     *
     * @var \App\Services\RetryService
     */
    protected RetryService $retryService;

    /**
     * Default cache TTL in seconds.
     *
     * @var int
     */
    protected int $defaultCacheTtl = 300; // 5 minutes
    
    /**
     * HTTP status codes that should trigger a retry.
     *
     * @var array
     */
    protected array $retryStatusCodes = [
        429, // Too Many Requests
        500, // Internal Server Error
        502, // Bad Gateway
        503, // Service Unavailable
        504, // Gateway Timeout
    ];

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
        $this->sportType = $this->getSportType();
        $this->retryService = new RetryService(
            config('services.rapidapi.retry.max_attempts', 3),
            config('services.rapidapi.retry.base_delay_ms', 1000),
            config('services.rapidapi.retry.max_delay_ms', 10000)
        );
    }

    /**
     * Get the sport type for this service.
     *
     * @return string
     */
    abstract protected function getSportType(): string;

    /**
     * Make a request to the RapidAPI endpoint with retry logic.
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
            // Use retry service for the API request
            return $this->retryService->execute(
                function (int $attempt) use ($endpoint, $url, $method, $parameters, $cacheKey, $ttl) {
                    $startTime = microtime(true);
                    $statusCode = null;
                    $success = false;
                    $errorMessage = null;
                    
                    // If this is a retry attempt, add a small delay to avoid hitting rate limits
                    if ($attempt > 1) {
                        Log::info("Retry attempt {$attempt} for endpoint: {$endpoint}");
                    }
                    
                    try {
                        $response = Http::withHeaders([
                            'x-rapidapi-host' => $this->apiHost,
                            'x-rapidapi-key' => $this->apiKey,
                        ])->timeout(30)->$method($url);
                        
                        $statusCode = $response->status();
                        
                        // Check if we need to retry based on status code
                        if (in_array($statusCode, $this->retryStatusCodes)) {
                            throw new RequestException($response);
                        }
                        
                        if ($response->successful()) {
                            $data = $response->json();
                            $success = true;
                            
                            // Cache the successful response
                            Cache::put($cacheKey, $data, $ttl);
                            
                            // Log API usage for tracking
                            $this->logApiUsage(
                                $endpoint, 
                                $method, 
                                $parameters, 
                                $statusCode, 
                                $success, 
                                null, 
                                $startTime, 
                                $response->headers()
                            );
                            
                            return $data;
                        }
                        
                        $errorMessage = 'API request failed with status: ' . $statusCode;
                        Log::error('RapidAPI request failed', [
                            'endpoint' => $endpoint,
                            'status' => $statusCode,
                            'response' => $response->body(),
                            'attempt' => $attempt,
                        ]);
                        
                        // Log the failed request
                        $this->logApiUsage(
                            $endpoint, 
                            $method, 
                            $parameters, 
                            $statusCode, 
                            $success, 
                            $errorMessage, 
                            $startTime, 
                            $response->headers()
                        );
                        
                        return ['error' => $errorMessage, 'status' => $statusCode];
                    } catch (RequestException $e) {
                        // This will be caught by the retry service and retried if needed
                        throw $e;
                    } catch (\Exception $e) {
                        $errorMessage = $e->getMessage();
                        Log::error('RapidAPI exception', [
                            'endpoint' => $endpoint,
                            'message' => $errorMessage,
                            'attempt' => $attempt,
                        ]);
                        
                        // Log the exception
                        $this->logApiUsage(
                            $endpoint, 
                            $method, 
                            $parameters, 
                            $statusCode, 
                            $success, 
                            $errorMessage, 
                            $startTime
                        );
                        
                        // Throw the exception to trigger retry if needed
                        throw $e;
                    }
                },
                // Define which exceptions should trigger a retry
                function (\Throwable $e) {
                    // Retry on network errors and specific HTTP status codes
                    if ($e instanceof RequestException) {
                        $statusCode = $e->getCode();
                        return in_array($statusCode, $this->retryStatusCodes);
                    }
                    
                    // Retry on connection errors, timeouts, etc.
                    return $e instanceof \GuzzleHttp\Exception\ConnectException ||
                           $e instanceof \GuzzleHttp\Exception\RequestException;
                },
                // Log retry attempts
                function (\Throwable $e, int $attempt, int $delayMs) use ($endpoint) {
                    Log::warning("Retrying {$endpoint} (attempt {$attempt}) after {$delayMs}ms", [
                        'exception' => $e->getMessage(),
                        'endpoint' => $endpoint,
                    ]);
                }
            );
        } catch (\Exception $e) {
            // After all retries have failed
            Log::error('All retry attempts failed for RapidAPI request', [
                'endpoint' => $endpoint,
                'message' => $e->getMessage(),
            ]);
            
            return [
                'error' => 'API request failed after multiple retry attempts: ' . $e->getMessage(),
                'status' => $e instanceof RequestException ? $e->getCode() : 500,
            ];
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
     * @param array $parameters
     * @param int|null $statusCode
     * @param bool $success
     * @param string|null $errorMessage
     * @param float $startTime
     * @param array $responseHeaders
     * @return void
     */
    protected function logApiUsage(
        string $endpoint, 
        string $method, 
        array $parameters = [], 
        ?int $statusCode = null, 
        bool $success = true, 
        ?string $errorMessage = null, 
        float $startTime = 0, 
        array $responseHeaders = []
    ): void {
        try {
            $responseTime = $startTime > 0 ? round((microtime(true) - $startTime) * 1000) : null;
            
            // Store API request in database
            ApiRequest::create([
                'endpoint' => $endpoint,
                'method' => $method,
                'sport_type' => $this->sportType,
                'status_code' => $statusCode,
                'success' => $success,
                'error_message' => $errorMessage,
                'response_time_ms' => $responseTime,
                'request_params' => $parameters,
                'response_headers' => $responseHeaders,
            ]);
        } catch (\Exception $e) {
            // Log the error but don't let it break the API functionality
            Log::error('Failed to log API request: ' . $e->getMessage(), [
                'endpoint' => $endpoint,
                'method' => $method,
                'sport_type' => $this->sportType ?? 'unknown',
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Store entities in the database.
     *
     * @param string $modelClass
     * @param array $entities
     * @param array $uniqueKeys
     * @param array $updateColumns
     * @return void
     */
    protected function storeEntities(string $modelClass, array $entities, array $uniqueKeys, array $updateColumns): void
    {
        if (empty($entities)) {
            return;
        }

        // Use chunking for large datasets
        foreach (array_chunk($entities, 100) as $chunk) {
            DB::transaction(function () use ($modelClass, $chunk, $uniqueKeys, $updateColumns) {
                $modelClass::upsert($chunk, $uniqueKeys, $updateColumns);
            });
        }
    }

    /**
     * Normalize response data to a consistent format.
     *
     * @param array $data
     * @return array
     */
    abstract protected function normalizeResponse(array $data): array;
}
