<?php

namespace App\Services\Afl\Utils\Traits;

use Illuminate\Support\Collection;

trait EventAnalysis
{
    /**
     * Get all scoring events from all matches
     */
    public function getAllScoringEvents(): Collection
    {
        return $this->matches->flatMap(function ($match) {
            if (!isset($match['events']['event'])) {
                return collect();
            }

            return collect($match['events']['event'])
                ->whereIn('@type', ['goal', 'behind'])
                ->map(function ($event) use ($match) {
                    return array_merge($event, [
                        'match_id' => $match['@id'],
                        'home_team' => $match['localteam']['@name'],
                        'away_team' => $match['visitorteam']['@name'],
                        'venue' => $match['@venue'],
                        'date' => $match['@date'],
                        'team_name' => $event['@team'] === 'hometeam'
                            ? $match['localteam']['@name']
                            : $match['visitorteam']['@name']
                    ]);
                });
        });
    }

    /**
     * Get all goals scored
     */
    public function getAllGoals(): Collection
    {
        return $this->getAllScoringEvents()->where('@type', 'goal');
    }

    /**
     * Get all behinds scored
     */
    public function getAllBehinds(): Collection
    {
        return $this->getAllScoringEvents()->where('@type', 'behind');
    }

    /**
     * Get goals by quarter
     */
    public function getGoalsByQuarter(): Collection
    {
        return $this->getAllGoals()->groupBy('@period')->map(function ($quarterGoals, $quarter) {
            return [
                'quarter' => $quarter,
                'total_goals' => $quarterGoals->count(),
                'goals' => $quarterGoals->values()
            ];
        });
    }

    /**
     * Get scoring timeline for all matches
     */
    public function getScoringTimeline(): Collection
    {
        return $this->getAllScoringEvents()
            ->sortBy(['match_id', '@period', '@minute'])
            ->map(function ($event) {
                return [
                    'match_id' => $event['match_id'],
                    'team' => $event['team_name'],
                    'player' => $event['@player'] ?: 'Unknown',
                    'type' => $event['@type'],
                    'quarter' => $event['@period'],
                    'minute' => $event['@minute'],
                    'venue' => $event['venue'],
                    'timestamp' => "Q{$event['@period']} {$event['@minute']}min"
                ];
            });
    }

    /**
     * Get multiple goal scorers
     */
    public function getMultipleGoalScorers(): Collection
    {
        return $this->getAllGoals()
            ->where('@player', '!=', '')
            ->groupBy('@player')
            ->filter(function ($playerGoals) {
                return $playerGoals->count() > 1;
            })
            ->map(function ($playerGoals, $playerName) {
                return [
                    'player' => $playerName,
                    'total_goals' => $playerGoals->count(),
                    'teams' => $playerGoals->pluck('team_name')->unique()->values(),
                    'matches' => $playerGoals->pluck('match_id')->unique()->count(),
                    'goals_by_quarter' => $playerGoals->groupBy('@period')->map->count(),
                    'goals' => $playerGoals->values()
                ];
            })
            ->sortByDesc('total_goals');
    }

    /**
     * Get fastest goals (early in quarters)
     */
    public function getFastestGoals(int $maxMinute = 5): Collection
    {
        return $this->getAllGoals()
            ->filter(function ($goal) use ($maxMinute) {
                return (int) $goal['@minute'] <= $maxMinute;
            })
            ->sortBy('@minute')
            ->map(function ($goal) {
                return [
                    'player' => $goal['@player'] ?: 'Unknown',
                    'team' => $goal['team_name'],
                    'quarter' => $goal['@period'],
                    'minute' => $goal['@minute'],
                    'match' => $goal['home_team'] . ' vs ' . $goal['away_team'],
                    'venue' => $goal['venue']
                ];
            });
    }

    /**
     * Get scoring trends by time periods
     */
    public function getScoringTrends(): Collection
    {
        $timeRanges = [
            'early' => [1, 7],
            'mid' => [8, 22],
            'late' => [23, 35]
        ];

        return collect($timeRanges)->map(function ($range, $period) {
            $goals = $this->getAllGoals()->filter(function ($goal) use ($range) {
                $minute = (int) $goal['@minute'];
                return $minute >= $range[0] && $minute <= $range[1];
            });

            return [
                'period' => $period,
                'time_range' => "{$range[0]}-{$range[1]} minutes",
                'total_goals' => $goals->count(),
                'average_per_match' => $this->matches->count() > 0
                    ? round($goals->count() / $this->matches->count(), 2)
                    : 0,
                'top_scorers' => $goals->where('@player', '!=', '')
                    ->groupBy('@player')
                    ->map->count()
                    ->sortDesc()
                    ->take(3)
            ];
        });
    }

    /**
     * Get team scoring patterns
     */
    public function getTeamScoringPatterns(string $teamName): array
    {
        $teamGoals = $this->getAllGoals()->where('team_name', $teamName);
        $teamBehinds = $this->getAllBehinds()->where('team_name', $teamName);

        return [
            'team' => $teamName,
            'total_goals' => $teamGoals->count(),
            'total_behinds' => $teamBehinds->count(),
            'goal_accuracy' => $this->calculateScoringAccuracy($teamGoals->count(), $teamBehinds->count()),
            'goals_by_quarter' => $teamGoals->groupBy('@period')->map->count()->sortKeys(),
            'behinds_by_quarter' => $teamBehinds->groupBy('@period')->map->count()->sortKeys(),
            'top_goal_scorers' => $teamGoals->where('@player', '!=', '')
                ->groupBy('@player')
                ->map->count()
                ->sortDesc()
                ->take(5),
            'scoring_timeline' => $teamGoals->sortBy(['@period', '@minute'])
                ->map(function ($goal) {
                    return [
                        'player' => $goal['@player'] ?: 'Unknown',
                        'quarter' => $goal['@period'],
                        'minute' => $goal['@minute'],
                        'match' => $goal['home_team'] . ' vs ' . $goal['away_team']
                    ];
                })
        ];
    }

    /**
     * Get match scoring summary
     */
    public function getMatchScoringEvents(string $matchId): Collection
    {
        $match = $this->matches->where('@id', $matchId)->first();

        if (!$match || !isset($match['events']['event'])) {
            return collect();
        }

        return collect($match['events']['event'])
            ->whereIn('@type', ['goal', 'behind'])
            ->sortBy(['@period', '@minute'])
            ->map(function ($event) use ($match) {
                return [
                    'team' => $event['@team'] === 'hometeam'
                        ? $match['localteam']['@name']
                        : $match['visitorteam']['@name'],
                    'player' => $event['@player'] ?: 'Unknown',
                    'type' => $event['@type'],
                    'quarter' => $event['@period'],
                    'minute' => $event['@minute'],
                    'timestamp' => "Q{$event['@period']} {$event['@minute']}min"
                ];
            });
    }

    /**
     * Calculate scoring accuracy
     */
    protected function calculateScoringAccuracy(int $goals, int $behinds): float
    {
        $totalShots = $goals + $behinds;
        return $totalShots > 0 ? round(($goals / $totalShots) * 100, 2) : 0;
    }

    /**
     * Get quarter-by-quarter momentum shifts
     */
    public function getMomentumShifts(): Collection
    {
        return $this->matches->map(function ($match) {
            $events = $this->getMatchScoringEvents($match['@id']);
            $homeTeam = $match['localteam']['@name'];
            $awayTeam = $match['visitorteam']['@name'];

            $quarterMomentum = collect([1, 2, 3, 4])->map(function ($quarter) use ($events, $homeTeam, $awayTeam) {
                $quarterEvents = $events->where('quarter', $quarter);
                $homeScore = $quarterEvents->where('team', $homeTeam)->count() * 6; // Simplified scoring
                $awayScore = $quarterEvents->where('team', $awayTeam)->count() * 6;

                return [
                    'quarter' => $quarter,
                    'home_score' => $homeScore,
                    'away_score' => $awayScore,
                    'momentum' => $homeScore > $awayScore ? $homeTeam : ($awayScore > $homeScore ? $awayTeam : 'Even')
                ];
            });

            return [
                'match' => $homeTeam . ' vs ' . $awayTeam,
                'match_id' => $match['@id'],
                'quarter_momentum' => $quarterMomentum
            ];
        });
    }
}
