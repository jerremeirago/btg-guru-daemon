<?php

namespace App\Services\RapidApi;

use Carbon\Carbon;

class BaseballApiService extends RapidApiService
{
    /**
     * Get baseball matches for a specific date.
     *
     * @param int $day
     * @param int $month
     * @param int $year
     * @return array
     */
    public function getMatchesByDate(int $day, int $month, int $year): array
    {
        $endpoint = "api/baseball/matches/{$day}/{$month}/{$year}";
        $response = $this->makeRequest($endpoint);
        
        return $this->normalizeResponse($response);
    }

    /**
     * Get live baseball matches.
     *
     * @return array
     */
    public function getLiveMatches(): array
    {
        $endpoint = "api/baseball/matches/live";
        $response = $this->makeRequest(
            $endpoint, 
            [], 
            'GET', 
            config('services.rapidapi.cache.live_matches')
        );
        
        return $this->normalizeResponse($response);
    }

    /**
     * Get match details by ID.
     *
     * @param int $matchId
     * @return array
     */
    public function getMatchDetails(int $matchId): array
    {
        $endpoint = "api/baseball/match/{$matchId}";
        $response = $this->makeRequest($endpoint);
        
        return $this->normalizeResponse($response);
    }

    /**
     * Normalize baseball response data to a consistent format.
     *
     * @param array $data
     * @return array
     */
    protected function normalizeResponse(array $data): array
    {
        if (isset($data['error'])) {
            return $data;
        }

        $normalized = [];
        
        // Process matches if they exist in the response
        if (isset($data['response']) && is_array($data['response'])) {
            foreach ($data['response'] as $match) {
                $normalized[] = [
                    'id' => $match['id'] ?? null,
                    'rapidapi_id' => $match['id'] ?? null,
                    'league' => [
                        'id' => $match['league']['id'] ?? null,
                        'name' => $match['league']['name'] ?? null,
                        'country' => $match['league']['country'] ?? null,
                        'logo' => $match['league']['logo'] ?? null,
                    ],
                    'teams' => [
                        'home' => [
                            'id' => $match['teams']['home']['id'] ?? null,
                            'name' => $match['teams']['home']['name'] ?? null,
                            'logo' => $match['teams']['home']['logo'] ?? null,
                        ],
                        'away' => [
                            'id' => $match['teams']['away']['id'] ?? null,
                            'name' => $match['teams']['away']['name'] ?? null,
                            'logo' => $match['teams']['away']['logo'] ?? null,
                        ],
                    ],
                    'scores' => [
                        'home' => $match['scores']['home'] ?? null,
                        'away' => $match['scores']['away'] ?? null,
                    ],
                    'status' => [
                        'long' => $match['status']['long'] ?? null,
                        'short' => $match['status']['short'] ?? null,
                    ],
                    'date' => $match['date'] ?? null,
                    'time' => $match['time'] ?? null,
                    'timestamp' => $match['timestamp'] ?? null,
                    'venue' => [
                        'name' => $match['venue']['name'] ?? null,
                        'city' => $match['venue']['city'] ?? null,
                    ],
                    'has_updates' => false, // Will be set by change detection
                ];
            }
        }
        
        return [
            'data' => $normalized,
            'meta' => [
                'count' => count($normalized),
                'timestamp' => time(),
            ],
        ];
    }

    /**
     * Get matches for today.
     *
     * @return array
     */
    public function getTodayMatches(): array
    {
        $today = Carbon::now();
        return $this->getMatchesByDate(
            $today->day,
            $today->month,
            $today->year
        );
    }
}
