<?php

namespace App\Services\RapidApi;

use Carbon\Carbon;

class BasketballApiService extends RapidApiService
{
    /**
     * Get basketball matches for a specific date.
     *
     * @param int $day
     * @param int $month
     * @param int $year
     * @return array
     */
    public function getMatchesByDate(int $day, int $month, int $year): array
    {
        $endpoint = "api/basketball/matches/{$day}/{$month}/{$year}";
        $response = $this->makeRequest($endpoint);
        
        return $this->normalizeResponse($response);
    }

    /**
     * Get live basketball matches.
     *
     * @return array
     */
    public function getLiveMatches(): array
    {
        $endpoint = "api/basketball/matches/live";
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
        $endpoint = "api/basketball/match/{$matchId}";
        $response = $this->makeRequest($endpoint);
        
        return $this->normalizeResponse($response);
    }

    /**
     * Get basketball leagues.
     *
     * @return array
     */
    public function getLeagues(): array
    {
        $endpoint = "api/basketball/leagues";
        $response = $this->makeRequest(
            $endpoint, 
            [], 
            'GET', 
            config('services.rapidapi.cache.standings')
        );
        
        return $this->normalizeLeaguesResponse($response);
    }

    /**
     * Get league standings.
     *
     * @param int $leagueId
     * @return array
     */
    public function getLeagueStandings(int $leagueId): array
    {
        $endpoint = "api/basketball/{$leagueId}/standings";
        $response = $this->makeRequest(
            $endpoint, 
            [], 
            'GET', 
            config('services.rapidapi.cache.standings')
        );
        
        return $this->normalizeStandingsResponse($response);
    }

    /**
     * Get team information.
     *
     * @param int $teamId
     * @return array
     */
    public function getTeamInfo(int $teamId): array
    {
        $endpoint = "api/basketball/team/{$teamId}";
        $response = $this->makeRequest(
            $endpoint, 
            [], 
            'GET', 
            config('services.rapidapi.cache.team_info')
        );
        
        return $this->normalizeTeamResponse($response);
    }

    /**
     * Get player information.
     *
     * @param int $playerId
     * @return array
     */
    public function getPlayerInfo(int $playerId): array
    {
        $endpoint = "api/basketball/player/{$playerId}";
        $response = $this->makeRequest($endpoint);
        
        return $this->normalizePlayerResponse($response);
    }

    /**
     * Normalize basketball response data to a consistent format.
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
                    'date' => $match['date'] ?? null,
                    'time' => $match['time'] ?? null,
                    'timestamp' => $match['timestamp'] ?? null,
                    'timezone' => $match['timezone'] ?? null,
                    'stage' => $match['stage'] ?? null,
                    'week' => $match['week'] ?? null,
                    'status' => [
                        'long' => $match['status']['long'] ?? null,
                        'short' => $match['status']['short'] ?? null,
                        'timer' => $match['status']['timer'] ?? null,
                    ],
                    'league' => [
                        'id' => $match['league']['id'] ?? null,
                        'name' => $match['league']['name'] ?? null,
                        'type' => $match['league']['type'] ?? null,
                        'logo' => $match['league']['logo'] ?? null,
                        'season' => $match['league']['season'] ?? null,
                    ],
                    'country' => [
                        'name' => $match['country']['name'] ?? null,
                        'code' => $match['country']['code'] ?? null,
                        'flag' => $match['country']['flag'] ?? null,
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
                        'home' => [
                            'quarter_1' => $match['scores']['home']['quarter_1'] ?? null,
                            'quarter_2' => $match['scores']['home']['quarter_2'] ?? null,
                            'quarter_3' => $match['scores']['home']['quarter_3'] ?? null,
                            'quarter_4' => $match['scores']['home']['quarter_4'] ?? null,
                            'over_time' => $match['scores']['home']['over_time'] ?? null,
                            'total' => $match['scores']['home']['total'] ?? null,
                        ],
                        'away' => [
                            'quarter_1' => $match['scores']['away']['quarter_1'] ?? null,
                            'quarter_2' => $match['scores']['away']['quarter_2'] ?? null,
                            'quarter_3' => $match['scores']['away']['quarter_3'] ?? null,
                            'quarter_4' => $match['scores']['away']['quarter_4'] ?? null,
                            'over_time' => $match['scores']['away']['over_time'] ?? null,
                            'total' => $match['scores']['away']['total'] ?? null,
                        ],
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
     * Normalize leagues response data.
     *
     * @param array $data
     * @return array
     */
    protected function normalizeLeaguesResponse(array $data): array
    {
        if (isset($data['error'])) {
            return $data;
        }

        $normalized = [];
        
        if (isset($data['response']) && is_array($data['response'])) {
            foreach ($data['response'] as $league) {
                $normalized[] = [
                    'id' => $league['id'] ?? null,
                    'rapidapi_id' => $league['id'] ?? null,
                    'name' => $league['name'] ?? null,
                    'type' => $league['type'] ?? null,
                    'logo' => $league['logo'] ?? null,
                    'country' => [
                        'name' => $league['country']['name'] ?? null,
                        'code' => $league['country']['code'] ?? null,
                        'flag' => $league['country']['flag'] ?? null,
                    ],
                    'seasons' => $league['seasons'] ?? [],
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
     * Normalize standings response data.
     *
     * @param array $data
     * @return array
     */
    protected function normalizeStandingsResponse(array $data): array
    {
        if (isset($data['error'])) {
            return $data;
        }

        $normalized = [];
        
        if (isset($data['response']) && is_array($data['response'])) {
            foreach ($data['response'] as $standing) {
                $normalizedStanding = [
                    'league' => [
                        'id' => $standing['league']['id'] ?? null,
                        'name' => $standing['league']['name'] ?? null,
                        'type' => $standing['league']['type'] ?? null,
                        'logo' => $standing['league']['logo'] ?? null,
                        'season' => $standing['league']['season'] ?? null,
                    ],
                    'country' => [
                        'name' => $standing['country']['name'] ?? null,
                        'code' => $standing['country']['code'] ?? null,
                        'flag' => $standing['country']['flag'] ?? null,
                    ],
                    'standings' => [],
                ];
                
                if (isset($standing['standings']) && is_array($standing['standings'])) {
                    foreach ($standing['standings'] as $team) {
                        $normalizedStanding['standings'][] = [
                            'position' => $team['position'] ?? null,
                            'stage' => $team['stage'] ?? null,
                            'group' => $team['group'] ?? null,
                            'team' => [
                                'id' => $team['team']['id'] ?? null,
                                'name' => $team['team']['name'] ?? null,
                                'logo' => $team['team']['logo'] ?? null,
                            ],
                            'games' => $team['games'] ?? [],
                            'points' => $team['points'] ?? [],
                            'form' => $team['form'] ?? null,
                        ];
                    }
                }
                
                $normalized[] = $normalizedStanding;
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
     * Normalize team response data.
     *
     * @param array $data
     * @return array
     */
    protected function normalizeTeamResponse(array $data): array
    {
        if (isset($data['error'])) {
            return $data;
        }

        $normalized = [];
        
        if (isset($data['response']) && is_array($data['response'])) {
            foreach ($data['response'] as $team) {
                $normalized[] = [
                    'id' => $team['id'] ?? null,
                    'rapidapi_id' => $team['id'] ?? null,
                    'name' => $team['name'] ?? null,
                    'logo' => $team['logo'] ?? null,
                    'country' => [
                        'name' => $team['country']['name'] ?? null,
                        'code' => $team['country']['code'] ?? null,
                        'flag' => $team['country']['flag'] ?? null,
                    ],
                    'founded' => $team['founded'] ?? null,
                    'national' => $team['national'] ?? false,
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
     * Normalize player response data.
     *
     * @param array $data
     * @return array
     */
    protected function normalizePlayerResponse(array $data): array
    {
        if (isset($data['error'])) {
            return $data;
        }

        $normalized = [];
        
        if (isset($data['response']) && is_array($data['response'])) {
            foreach ($data['response'] as $player) {
                $normalized[] = [
                    'id' => $player['id'] ?? null,
                    'rapidapi_id' => $player['id'] ?? null,
                    'name' => $player['name'] ?? null,
                    'firstname' => $player['firstname'] ?? null,
                    'lastname' => $player['lastname'] ?? null,
                    'birth' => [
                        'date' => $player['birth']['date'] ?? null,
                        'country' => $player['birth']['country'] ?? null,
                    ],
                    'nationality' => $player['nationality'] ?? null,
                    'height' => $player['height'] ?? null,
                    'weight' => $player['weight'] ?? null,
                    'team' => [
                        'id' => $player['team']['id'] ?? null,
                        'name' => $player['team']['name'] ?? null,
                        'logo' => $player['team']['logo'] ?? null,
                    ],
                    'games' => $player['games'] ?? [],
                    'points' => $player['points'] ?? [],
                    'rebounds' => $player['rebounds'] ?? [],
                    'assists' => $player['assists'] ?? [],
                    'statistics' => $player['statistics'] ?? [],
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
