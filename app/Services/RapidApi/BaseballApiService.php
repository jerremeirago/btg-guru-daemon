<?php

namespace App\Services\RapidApi;

use App\Models\League;
use App\Models\Team;
use App\Models\SportMatch;
use App\Services\CacheService;
use App\Services\ChangeDetectionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BaseballApiService extends RapidApiService
{
    /**
     * The cache service instance.
     *
     * @var \App\Services\CacheService
     */
    protected CacheService $cacheService;

    /**
     * The change detection service instance.
     *
     * @var \App\Services\ChangeDetectionService
     */
    protected ChangeDetectionService $changeDetectionService;

    /**
     * Create a new baseball API service instance.
     *
     * @param \App\Services\CacheService $cacheService
     * @param \App\Services\ChangeDetectionService $changeDetectionService
     * @return void
     */
    public function __construct(CacheService $cacheService, ChangeDetectionService $changeDetectionService)
    {
        parent::__construct();
        $this->cacheService = $cacheService;
        $this->changeDetectionService = $changeDetectionService;
    }

    /**
     * Get the sport type for this service.
     *
     * @return string
     */
    protected function getSportType(): string
    {
        return 'baseball';
    }

    /**
     * Get baseball matches for a specific date.
     *
     * @param int $day
     * @param int $month
     * @param int $year
     * @param bool $bypassCache Whether to bypass cache and fetch fresh data
     * @return array
     */
    public function getMatchesByDate(int $day, int $month, int $year, bool $bypassCache = false): array
    {
        $cacheKey = "baseball:matches:{$day}:{$month}:{$year}";

        // Function to fetch fresh data from API
        $fetchDataCallback = function () use ($day, $month, $year) {
            $endpoint = "api/baseball/matches/{$day}/{$month}/{$year}";
            $response = $this->makeRequest($endpoint);

            // Normalize the response data
            $normalizedData = $this->normalizeResponse($response);

            // Process each match for change detection
            if (!empty($normalizedData['data'])) {
                $processedMatches = [];

                foreach ($normalizedData['data'] as $match) {
                    // Detect changes in match data
                    $processedMatch = $this->changeDetectionService->detectChanges($match);
                    $processedMatches[] = $processedMatch;
                }

                $normalizedData['data'] = $processedMatches;
            }

            // Store the data in the database
            $this->storeMatchData($normalizedData);

            return $normalizedData;
        };

        // If bypassing cache, fetch fresh data directly
        if ($bypassCache) {
            return $fetchDataCallback();
        }

        // Otherwise use cache with the callback
        return $this->cacheService->remember($cacheKey, $fetchDataCallback, 'upcoming_matches');
    }

    /**
     * Get live baseball matches.
     *
     * @return array
     */
    public function getLiveMatches(): array
    {
        $cacheKey = "baseball:matches:live";

        return $this->cacheService->remember($cacheKey, function () {
            $endpoint = "api/baseball/matches/live";
            $response = $this->makeRequest($endpoint);

            // Normalize the response data
            $normalizedData = $this->normalizeResponse($response);

            // Process each match for change detection
            if (!empty($normalizedData['data'])) {
                $processedMatches = [];

                foreach ($normalizedData['data'] as $match) {
                    // Detect changes in match data
                    $processedMatch = $this->changeDetectionService->detectChanges($match);
                    $processedMatches[] = $processedMatch;

                    // If there are updates, broadcast them via WebSockets
                    if ($processedMatch['has_updates'] && !empty($processedMatch['changes'])) {
                        $this->broadcastMatchUpdate($processedMatch);
                    }
                }

                $normalizedData['data'] = $processedMatches;
            }

            // Store the data in the database
            $this->storeMatchData($normalizedData);

            return $normalizedData;
        }, 'live_matches');
    }

    /**
     * Broadcast match updates via WebSockets.
     *
     * @param array $match
     * @return void
     */
    protected function broadcastMatchUpdate(array $match): void
    {
        try {
            // This will be implemented with Laravel Reverb
            // For now, just log the update
            Log::info('Match update detected', [
                'match_id' => $match['rapidapi_id'],
                'sport' => $this->sportType,
                'changes' => $match['changes'] ?? [],
                'timestamp' => now()->toDateTimeString(),
            ]);

            // In a future implementation, we would broadcast the event:
            // event(new MatchUpdateEvent($match));
        } catch (\Exception $e) {
            Log::error('Failed to broadcast match update', [
                'match_id' => $match['rapidapi_id'] ?? null,
                'error' => $e->getMessage(),
            ]);
        }
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

        $normalizedData = $this->normalizeResponse($response);
        $this->storeMatchData($normalizedData);

        return $normalizedData;
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

        // Process matches from the 'events' array in the actual API response format
        if (isset($data['events']) && is_array($data['events'])) {
            foreach ($data['events'] as $match) {
                $homeTeam = $match['homeTeam'] ?? [];
                $awayTeam = $match['awayTeam'] ?? [];
                $homeScore = $match['homeScore'] ?? [];
                $awayScore = $match['awayScore'] ?? [];
                $tournament = $match['tournament'] ?? [];
                $status = $match['status'] ?? [];
                
                $normalized[] = [
                    'id' => $match['id'] ?? null,
                    'rapidapi_id' => $match['id'] ?? null,
                    'sport_type' => $this->sportType,
                    'league' => [
                        'id' => $tournament['id'] ?? null,
                        'name' => $tournament['name'] ?? null,
                        'country' => $tournament['category']['name'] ?? null,
                        'logo' => $tournament['category']['flag'] ?? null,
                    ],
                    'home_team' => [
                        'id' => $homeTeam['id'] ?? null,
                        'name' => $homeTeam['name'] ?? null,
                        'logo' => $homeTeam['logo'] ?? null,
                    ],
                    'away_team' => [
                        'id' => $awayTeam['id'] ?? null,
                        'name' => $awayTeam['name'] ?? null,
                        'logo' => $awayTeam['logo'] ?? null,
                    ],
                    'home_score' => $homeScore['current'] ?? 0,
                    'away_score' => $awayScore['current'] ?? 0,
                    'status' => $status['type'] ?? 'unknown',
                    'date' => date('Y-m-d', $match['startTimestamp'] ?? time()),
                    'time' => date('H:i:s', $match['startTimestamp'] ?? time()),
                    'timestamp' => $match['startTimestamp'] ?? time(),
                    'venue' => [
                        'name' => $match['venue']['stadium']['name'] ?? null,
                        'city' => $match['venue']['city']['name'] ?? null,
                    ],
                    'has_updates' => false, // Will be set by change detection
                    'slug' => $match['slug'] ?? null,
                    'periods' => $match['periods'] ?? [],
                    'winner_code' => $match['winnerCode'] ?? null,
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
     * Store match data in the database.
     *
     * @param array $normalizedData
     * @return void
     */
    protected function storeMatchData(array $normalizedData): void
    {
        if (empty($normalizedData['data'])) {
            return;
        }

        try {
            DB::beginTransaction();
            
            foreach ($normalizedData['data'] as $matchData) {
                // Store league data
                $leagueId = null;
                if (isset($matchData['league']['id'])) {
                    $league = [
                        'rapidapi_id' => $matchData['league']['id'],
                        'sport_type' => $this->sportType,
                        'name' => $matchData['league']['name'] ?? '',
                        'country' => $matchData['league']['country'] ?? null,
                        'logo_url' => $matchData['league']['logo'] ?? null,
                        'is_active' => true,
                    ];

                    $leagueModel = League::updateOrCreate(
                        ['rapidapi_id' => $matchData['league']['id']],
                        $league
                    );
                    
                    $leagueId = $leagueModel->id;
                }

                // Store home team data
                $homeTeamId = null;
                if (isset($matchData['home_team']['id'])) {
                    $homeTeam = [
                        'rapidapi_id' => $matchData['home_team']['id'],
                        'sport_type' => $this->sportType,
                        'name' => $matchData['home_team']['name'] ?? '',
                        'logo_url' => $matchData['home_team']['logo'] ?? null,
                        'is_active' => true,
                    ];

                    $homeTeamModel = Team::updateOrCreate(
                        ['rapidapi_id' => $matchData['home_team']['id']],
                        $homeTeam
                    );

                    $homeTeamId = $homeTeamModel->id;
                }

                // Store away team data
                $awayTeamId = null;
                if (isset($matchData['away_team']['id'])) {
                    $awayTeam = [
                        'rapidapi_id' => $matchData['away_team']['id'],
                        'sport_type' => $this->sportType,
                        'name' => $matchData['away_team']['name'] ?? '',
                        'logo_url' => $matchData['away_team']['logo'] ?? null,
                        'is_active' => true,
                    ];

                    $awayTeamModel = Team::updateOrCreate(
                        ['rapidapi_id' => $matchData['away_team']['id']],
                        $awayTeam
                    );

                    $awayTeamId = $awayTeamModel->id;
                }

                // Store match data
                if (isset($matchData['id'])) {
                    $match = [
                        'rapidapi_id' => $matchData['id'],
                        'sport_type' => $this->sportType,
                        'league_id' => $leagueId,
                        'home_team_id' => $homeTeamId,
                        'away_team_id' => $awayTeamId,
                        'status_short' => $matchData['status'] ?? 'unknown',
                        'status_long' => $matchData['status'] ?? 'unknown',
                        'home_score' => $matchData['home_score'] ?? 0,
                        'away_score' => $matchData['away_score'] ?? 0,
                        'match_date' => isset($matchData['date']) ? Carbon::parse($matchData['date']) : null,
                        'timestamp' => $matchData['timestamp'] ?? null,
                        'venue_name' => $matchData['venue']['name'] ?? null,
                        'venue_city' => $matchData['venue']['city'] ?? null,
                        'additional_data' => json_encode([
                            'league' => $matchData['league'] ?? null,
                            'home_team' => $matchData['home_team'] ?? null,
                            'away_team' => $matchData['away_team'] ?? null,
                            'slug' => $matchData['slug'] ?? null,
                            'periods' => $matchData['periods'] ?? null,
                            'winner_code' => $matchData['winner_code'] ?? null,
                        ]),
                        'has_updates' => $matchData['has_updates'] ?? false,
                    ];

                    SportMatch::updateOrCreate(
                        ['rapidapi_id' => $matchData['id']],
                        $match
                    );
                }
            }
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to store baseball match data: ' . $e->getMessage(), [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
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
