<?php

namespace App\Services\RapidApi;

use Carbon\Carbon;

class FootballApiService extends RapidApiService
{
    /**
     * Get football matches for a specific date.
     *
     * @param int $day
     * @param int $month
     * @param int $year
     * @return array
     */
    public function getMatchesByDate(int $day, int $month, int $year): array
    {
        $endpoint = "api/football/matches/{$day}/{$month}/{$year}";
        $response = $this->makeRequest($endpoint);
        
        return $this->normalizeResponse($response);
    }

    /**
     * Get live football matches.
     *
     * @return array
     */
    public function getLiveMatches(): array
    {
        $endpoint = "api/football/matches/live";
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
        $endpoint = "api/football/match/{$matchId}";
        $response = $this->makeRequest($endpoint);
        
        return $this->normalizeResponse($response);
    }

    /**
     * Get league information.
     *
     * @return array
     */
    public function getLeagues(): array
    {
        $endpoint = "api/football/leagues";
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
        $endpoint = "api/football/{$leagueId}/standings";
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
        $endpoint = "api/football/team/{$teamId}";
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
        $endpoint = "api/player/{$playerId}";
        $response = $this->makeRequest($endpoint);
        
        return $this->normalizePlayerResponse($response);
    }

    /**
     * Get player's previous matches.
     *
     * @param int $playerId
     * @param int $page
     * @return array
     */
    public function getPlayerPreviousMatches(int $playerId, int $page = 0): array
    {
        $endpoint = "api/player/{$playerId}/matches/previous/{$page}";
        $response = $this->makeRequest($endpoint);
        
        return $this->normalizeResponse($response);
    }

    /**
     * Normalize football response data to a consistent format.
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
                    'id' => $match['fixture']['id'] ?? null,
                    'rapidapi_id' => $match['fixture']['id'] ?? null,
                    'league' => [
                        'id' => $match['league']['id'] ?? null,
                        'name' => $match['league']['name'] ?? null,
                        'country' => $match['league']['country'] ?? null,
                        'logo' => $match['league']['logo'] ?? null,
                        'season' => $match['league']['season'] ?? null,
                        'round' => $match['league']['round'] ?? null,
                    ],
                    'teams' => [
                        'home' => [
                            'id' => $match['teams']['home']['id'] ?? null,
                            'name' => $match['teams']['home']['name'] ?? null,
                            'logo' => $match['teams']['home']['logo'] ?? null,
                            'winner' => $match['teams']['home']['winner'] ?? null,
                        ],
                        'away' => [
                            'id' => $match['teams']['away']['id'] ?? null,
                            'name' => $match['teams']['away']['name'] ?? null,
                            'logo' => $match['teams']['away']['logo'] ?? null,
                            'winner' => $match['teams']['away']['winner'] ?? null,
                        ],
                    ],
                    'goals' => [
                        'home' => $match['goals']['home'] ?? null,
                        'away' => $match['goals']['away'] ?? null,
                    ],
                    'score' => $match['score'] ?? [],
                    'fixture' => [
                        'id' => $match['fixture']['id'] ?? null,
                        'referee' => $match['fixture']['referee'] ?? null,
                        'timezone' => $match['fixture']['timezone'] ?? null,
                        'date' => $match['fixture']['date'] ?? null,
                        'timestamp' => $match['fixture']['timestamp'] ?? null,
                        'venue' => [
                            'id' => $match['fixture']['venue']['id'] ?? null,
                            'name' => $match['fixture']['venue']['name'] ?? null,
                            'city' => $match['fixture']['venue']['city'] ?? null,
                        ],
                        'status' => [
                            'long' => $match['fixture']['status']['long'] ?? null,
                            'short' => $match['fixture']['status']['short'] ?? null,
                            'elapsed' => $match['fixture']['status']['elapsed'] ?? null,
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
                    'id' => $league['league']['id'] ?? null,
                    'rapidapi_id' => $league['league']['id'] ?? null,
                    'name' => $league['league']['name'] ?? null,
                    'type' => $league['league']['type'] ?? null,
                    'logo' => $league['league']['logo'] ?? null,
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
            foreach ($data['response'] as $standingGroup) {
                if (isset($standingGroup['league']['standings']) && is_array($standingGroup['league']['standings'])) {
                    foreach ($standingGroup['league']['standings'] as $standingList) {
                        $standingData = [
                            'league' => [
                                'id' => $standingGroup['league']['id'] ?? null,
                                'name' => $standingGroup['league']['name'] ?? null,
                                'country' => $standingGroup['league']['country'] ?? null,
                                'logo' => $standingGroup['league']['logo'] ?? null,
                                'season' => $standingGroup['league']['season'] ?? null,
                            ],
                            'standings' => [],
                        ];
                        
                        foreach ($standingList as $team) {
                            $standingData['standings'][] = [
                                'rank' => $team['rank'] ?? null,
                                'team' => [
                                    'id' => $team['team']['id'] ?? null,
                                    'name' => $team['team']['name'] ?? null,
                                    'logo' => $team['team']['logo'] ?? null,
                                ],
                                'points' => $team['points'] ?? null,
                                'goalsDiff' => $team['goalsDiff'] ?? null,
                                'group' => $team['group'] ?? null,
                                'form' => $team['form'] ?? null,
                                'status' => $team['status'] ?? null,
                                'description' => $team['description'] ?? null,
                                'all' => $team['all'] ?? [],
                                'home' => $team['home'] ?? [],
                                'away' => $team['away'] ?? [],
                            ];
                        }
                        
                        $normalized[] = $standingData;
                    }
                }
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
                    'team' => [
                        'id' => $team['team']['id'] ?? null,
                        'rapidapi_id' => $team['team']['id'] ?? null,
                        'name' => $team['team']['name'] ?? null,
                        'code' => $team['team']['code'] ?? null,
                        'country' => $team['team']['country'] ?? null,
                        'founded' => $team['team']['founded'] ?? null,
                        'national' => $team['team']['national'] ?? false,
                        'logo' => $team['team']['logo'] ?? null,
                    ],
                    'venue' => $team['venue'] ?? [],
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
                    'player' => [
                        'id' => $player['player']['id'] ?? null,
                        'rapidapi_id' => $player['player']['id'] ?? null,
                        'name' => $player['player']['name'] ?? null,
                        'firstname' => $player['player']['firstname'] ?? null,
                        'lastname' => $player['player']['lastname'] ?? null,
                        'age' => $player['player']['age'] ?? null,
                        'birth' => $player['player']['birth'] ?? [],
                        'nationality' => $player['player']['nationality'] ?? null,
                        'height' => $player['player']['height'] ?? null,
                        'weight' => $player['player']['weight'] ?? null,
                        'injured' => $player['player']['injured'] ?? false,
                        'photo' => $player['player']['photo'] ?? null,
                    ],
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
