# BTS Guru Daemon Service - RapidAPI Integration

## Overview

This document outlines the integration with RapidAPI sports data providers, including caching strategies, polling mechanisms, and error handling.

## RapidAPI Sports Data Providers

The BTS Guru Daemon Service will integrate with the following RapidAPI sports data providers:

1. **API-Football** - Comprehensive football/soccer data
2. **API-Basketball** - Basketball data including NBA, FIBA, and European leagues
3. **API-Baseball** - MLB and other baseball leagues
4. **API-American Football** - NFL and college football data
5. **API-Hockey** - NHL and other hockey leagues

## Service Layer Architecture

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│             │     │             │     │             │     │             │
│  RapidAPI   │────▶│  HTTP Client│────▶│  Service    │────▶│  Redis      │
│  Endpoints  │     │  (Guzzle)   │     │  Layer      │     │  Cache      │
│             │     │             │     │             │     │             │
└─────────────┘     └─────────────┘     └─────────────┘     └─────────────┘
                                               │
                                               ▼
                                        ┌─────────────┐
                                        │             │
                                        │  Event      │
                                        │  Broadcasting│
                                        │             │
                                        └─────────────┘
```

## Service Classes

The service layer will consist of the following key classes:

### Base API Client

```php
namespace App\Services\RapidApi;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

abstract class BaseApiClient
{
    protected Client $client;
    protected string $baseUrl;
    protected string $apiKey;
    protected string $apiHost;
    protected int $defaultCacheTtl = 60; // Default TTL in seconds

    public function __construct()
    {
        $this->baseUrl = config('rapidapi.base_url');
        $this->apiKey = config('rapidapi.key');
        $this->apiHost = config('rapidapi.host');
        
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'X-RapidAPI-Key' => $this->apiKey,
                'X-RapidAPI-Host' => $this->apiHost,
                'Accept' => 'application/json',
            ],
        ]);
    }

    protected function get(string $endpoint, array $queryParams = [], int $cacheTtl = null): array
    {
        $cacheTtl = $cacheTtl ?? $this->defaultCacheTtl;
        $cacheKey = $this->generateCacheKey($endpoint, $queryParams);
        
        return Cache::remember($cacheKey, $cacheTtl, function () use ($endpoint, $queryParams) {
            try {
                $response = $this->client->get($endpoint, [
                    'query' => $queryParams,
                ]);
                
                return json_decode($response->getBody()->getContents(), true);
            } catch (GuzzleException $e) {
                Log::error('RapidAPI request failed', [
                    'endpoint' => $endpoint,
                    'params' => $queryParams,
                    'error' => $e->getMessage(),
                ]);
                
                throw $e;
            }
        });
    }

    protected function generateCacheKey(string $endpoint, array $params): string
    {
        $paramsString = json_encode($params);
        return "rapidapi:{$this->apiHost}:{$endpoint}:{$paramsString}";
    }
}
```

### Sport-Specific API Clients

Each sport will have its own API client that extends the base client:

```php
namespace App\Services\RapidApi;

class FootballApiClient extends BaseApiClient
{
    protected int $defaultCacheTtl = 60; // 60 seconds for live data
    
    public function getLiveMatches(): array
    {
        return $this->get('/fixtures', [
            'live' => 'all',
        ]);
    }
    
    public function getMatch(int $fixtureId): array
    {
        return $this->get('/fixtures', [
            'id' => $fixtureId,
        ], 30); // 30 seconds TTL for individual match data
    }
    
    public function getLeagues(string $country = null): array
    {
        return $this->get('/leagues', [
            'country' => $country,
        ], 3600); // 1 hour TTL for league data (changes infrequently)
    }
    
    public function getTeams(int $leagueId): array
    {
        return $this->get('/teams', [
            'league' => $leagueId,
        ], 86400); // 24 hours TTL for team data (changes very infrequently)
    }
}
```

## Polling Strategy

The service implements an intelligent polling strategy that adjusts frequency based on:

1. **Sport Type**: Different sports have different pace and scoring frequency
2. **Match Status**: Live matches are polled more frequently than scheduled or completed matches
3. **Match Period**: Critical periods (last minutes, overtime) are polled more frequently
4. **API Rate Limits**: Respects RapidAPI rate limits to avoid throttling

### Polling Frequencies

| Sport Type | Status | Period | Frequency |
|------------|--------|--------|-----------|
| Football | Live | Regular | 15 seconds |
| Football | Live | Last 10 min | 10 seconds |
| Basketball | Live | Regular | 10 seconds |
| Basketball | Live | Last 2 min | 5 seconds |
| Baseball | Live | Regular | 30 seconds |
| Baseball | Live | Last inning | 15 seconds |
| All | Scheduled | - | 5 minutes |
| All | Completed | - | 30 minutes |

## Caching Strategy

The caching strategy is designed to minimize API calls while maintaining data freshness:

### TTL (Time-To-Live) by Data Type

| Data Type | TTL | Rationale |
|-----------|-----|-----------|
| Live match data | 10-30 seconds | Needs to be fresh, but some buffer reduces load |
| Scheduled match data | 5-15 minutes | Changes infrequently |
| Completed match data | 1-24 hours | Final results rarely change |
| League data | 24 hours | Very stable information |
| Team data | 24-48 hours | Very stable information |
| Player data | 24 hours | Changes infrequently |

### Cache Invalidation

Cache invalidation occurs under the following conditions:

1. **TTL Expiration**: Standard expiration based on the TTL
2. **Match Status Change**: When a match changes status (e.g., from scheduled to live)
3. **Score Change**: When a score update is detected
4. **Manual Trigger**: Admin-initiated cache refresh for specific data

## Error Handling

The service implements robust error handling to manage RapidAPI failures:

### Retry Mechanism

```php
public function getWithRetry(string $endpoint, array $params = [], int $maxRetries = 3): array
{
    $attempt = 0;
    
    while ($attempt < $maxRetries) {
        try {
            return $this->get($endpoint, $params);
        } catch (GuzzleException $e) {
            $attempt++;
            $statusCode = $e->getCode();
            
            // Don't retry client errors except for rate limiting
            if ($statusCode >= 400 && $statusCode < 500 && $statusCode !== 429) {
                throw $e;
            }
            
            // Exponential backoff
            $delay = pow(2, $attempt);
            
            // Additional delay for rate limiting
            if ($statusCode === 429) {
                $delay += 10;
            }
            
            Log::warning("API request failed, retrying in {$delay} seconds", [
                'attempt' => $attempt,
                'max_retries' => $maxRetries,
                'endpoint' => $endpoint,
            ]);
            
            sleep($delay);
        }
    }
    
    // If we get here, all retries failed
    throw new \Exception("Failed to get data after {$maxRetries} attempts");
}
```

### Fallback Mechanisms

When RapidAPI is unavailable or rate-limited, the system implements the following fallbacks:

1. **Serve Cached Data**: Return the most recent cached data with a "stale" flag
2. **Reduced Polling**: Temporarily reduce polling frequency across all sports
3. **Graceful Degradation**: Prioritize high-profile matches and reduce updates for less important events
4. **User Notification**: Inform users of potential delays in data updates

## Data Normalization

The service normalizes data from different sports APIs into a consistent format:

```php
protected function normalizeMatch(array $apiData): array
{
    // Each sport client implements its own normalization logic
    return [
        'id' => $this->extractMatchId($apiData),
        'sport' => $this->getSportName(),
        'league' => [
            'id' => $this->extractLeagueId($apiData),
            'name' => $this->extractLeagueName($apiData),
        ],
        'teams' => [
            'home' => [
                'id' => $this->extractHomeTeamId($apiData),
                'name' => $this->extractHomeTeamName($apiData),
                'score' => $this->extractHomeScore($apiData),
            ],
            'away' => [
                'id' => $this->extractAwayTeamId($apiData),
                'name' => $this->extractAwayTeamName($apiData),
                'score' => $this->extractAwayScore($apiData),
            ],
        ],
        'status' => $this->mapStatus($apiData),
        'period' => $this->extractPeriod($apiData),
        'time' => $this->extractTime($apiData),
        'venue' => $this->extractVenue($apiData),
        'start_time' => $this->extractStartTime($apiData),
        'last_updated' => now()->toIso8601String(),
    ];
}
```

## Change Detection

The service implements a change detection algorithm to identify meaningful updates:

```php
public function detectChanges(array $oldData, array $newData): array
{
    $changes = [];
    
    // Check for score changes
    if ($oldData['teams']['home']['score'] !== $newData['teams']['home']['score'] ||
        $oldData['teams']['away']['score'] !== $newData['teams']['away']['score']) {
        $changes['score'] = [
            'old' => [
                'home' => $oldData['teams']['home']['score'],
                'away' => $oldData['teams']['away']['score'],
            ],
            'new' => [
                'home' => $newData['teams']['home']['score'],
                'away' => $newData['teams']['away']['score'],
            ],
        ];
    }
    
    // Check for status changes
    if ($oldData['status'] !== $newData['status']) {
        $changes['status'] = [
            'old' => $oldData['status'],
            'new' => $newData['status'],
        ];
    }
    
    // Check for period changes
    if ($oldData['period'] !== $newData['period']) {
        $changes['period'] = [
            'old' => $oldData['period'],
            'new' => $newData['period'],
        ];
    }
    
    return $changes;
}
```

## Monitoring and Logging

The service implements comprehensive monitoring and logging for RapidAPI interactions:

1. **Request Logging**: Log all API requests, responses, and cache hits/misses
2. **Rate Limit Tracking**: Monitor RapidAPI rate limit usage and remaining quota
3. **Error Tracking**: Log and alert on API errors and failures
4. **Performance Metrics**: Track response times and data freshness

## Configuration

The RapidAPI integration is configured in the `config/rapidapi.php` file:

```php
return [
    'key' => env('RAPIDAPI_KEY'),
    'host' => env('RAPIDAPI_HOST', 'v3.football.api-sports.io'),
    'base_url' => env('RAPIDAPI_BASE_URL', 'https://v3.football.api-sports.io'),
    
    'football' => [
        'host' => env('RAPIDAPI_FOOTBALL_HOST', 'v3.football.api-sports.io'),
        'base_url' => env('RAPIDAPI_FOOTBALL_BASE_URL', 'https://v3.football.api-sports.io'),
    ],
    
    'basketball' => [
        'host' => env('RAPIDAPI_BASKETBALL_HOST', 'v1.basketball.api-sports.io'),
        'base_url' => env('RAPIDAPI_BASKETBALL_BASE_URL', 'https://v1.basketball.api-sports.io'),
    ],
    
    // Additional sport configurations
    
    'polling' => [
        'football' => [
            'live' => env('POLLING_FOOTBALL_LIVE', 15),
            'scheduled' => env('POLLING_FOOTBALL_SCHEDULED', 300),
            'completed' => env('POLLING_FOOTBALL_COMPLETED', 1800),
        ],
        'basketball' => [
            'live' => env('POLLING_BASKETBALL_LIVE', 10),
            'scheduled' => env('POLLING_BASKETBALL_SCHEDULED', 300),
            'completed' => env('POLLING_BASKETBALL_COMPLETED', 1800),
        ],
        // Additional sport polling configurations
    ],
];
```
