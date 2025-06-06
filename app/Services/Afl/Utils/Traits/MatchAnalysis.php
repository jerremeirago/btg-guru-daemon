<?php

namespace App\Services\Afl\Utils\Traits;

use Illuminate\Support\Collection;

trait MatchAnalysis
{
    /**
     * Get all team scores with match details, usually used in showing
     *rscoreboard
     */
    public function getTeamScores(): Collection
    {
        return $this->matches->map(function ($match) {
            $homeScore = (int) $match['localteam']['@score'];
            $awayScore = (int) $match['visitorteam']['@score'];

            $matchDate = \Carbon\Carbon::parse($match['@date']);

            return [
                'match_id' => $match['@id'],
                'venue' => $match['@venue'],
                'date' => $matchDate->format('d.m.Y'),
                'time' => $match['@time'] ?? $matchDate->format('H:i'),
                'status' => $match['@status'],
                'home_team' => $match['localteam']['@name'],
                'home_score' => $homeScore,
                'away_team' => $match['visitorteam']['@name'],
                'away_score' => $awayScore,
                'total_score' => $homeScore + $awayScore,
                'margin' => abs($homeScore - $awayScore),
                'winner' => $homeScore > $awayScore ? $match['localteam']['@name'] : $match['visitorteam']['@name'],
                'home_goals' => (int) $match['localteam']['@goals'],
                'home_behinds' => (int) $match['localteam']['@behinds'],
                'away_goals' => (int) $match['visitorteam']['@goals'],
                'away_behinds' => (int) $match['visitorteam']['@behinds'],
            ];
        });
    }

    /**
     * Get highest scoring matches
     */
    public function getHighestScoringMatches(): Collection
    {
        return $this->getTeamScores()->sortByDesc('total_score');
    }

    /**
     * Get matches with biggest winning margins
     */
    public function getBiggestWins(): Collection
    {
        return $this->getTeamScores()->sortByDesc('margin');
    }

    /**
     * Get closest matches (smallest margins)
     */
    public function getClosestMatches(): Collection
    {
        return $this->getTeamScores()->sortBy('margin');
    }

    /**
     * Get quarter-by-quarter breakdown for all matches
     */
    public function getQuarterBreakdown(): Collection
    {
        return $this->matches->map(function ($match) {
            return [
                'match' => $match['localteam']['@name'] . ' vs ' . $match['visitorteam']['@name'],
                'match_id' => $match['@id'],
                'quarters' => collect($match['quarters']['quarter'])->map(function ($quarter) {
                    return [
                        'quarter' => $quarter['@name'],
                        'home_goals' => (int) $quarter['@homeGoals'],
                        'home_behinds' => (int) $quarter['@homeBehinds'],
                        'home_points' => (int) $quarter['@homePoints'],
                        'away_goals' => (int) $quarter['@awayGoals'],
                        'away_behinds' => (int) $quarter['@awayBehinds'],
                        'away_points' => (int) $quarter['@awayPoints'],
                        'home_quarter_score' => (int) $quarter['@homePoints'] -
                            (isset($prevQuarter) ? (int) $prevQuarter['@homePoints'] : 0),
                        'away_quarter_score' => (int) $quarter['@awayPoints'] -
                            (isset($prevQuarter) ? (int) $prevQuarter['@awayPoints'] : 0),
                    ];
                })
            ];
        });
    }

    /**
     * Get matches by status
     */
    public function getFullTimeMatches(): Collection
    {
        return $this->filterByStatus('Full Time');
    }

    /**
     * Get live matches
     */
    public function getLiveMatches(): Collection
    {
        return $this->matches->filter(function ($match) {
            return $match['@status'] !== 'Full Time';
        });
    }

    /**
     * Get matches by venue with statistics
     */
    public function getMatchesByVenue(): Collection
    {
        return $this->matches->groupBy('@venue')->map(function ($venueMatches, $venue) {
            $scores = $venueMatches->map(function ($match) {
                return (int) $match['localteam']['@score'] + (int) $match['visitorteam']['@score'];
            });

            return [
                'venue' => $venue,
                'match_count' => $venueMatches->count(),
                'average_total_score' => round($scores->avg(), 2),
                'highest_score' => $scores->max(),
                'lowest_score' => $scores->min(),
                'matches' => $venueMatches->values()
            ];
        });
    }

    /**
     * Get match results summary
     */
    public function getMatchSummary(): array
    {
        $teamScores = $this->getTeamScores();

        return [
            'total_matches' => $this->matches->count(),
            'completed_matches' => $this->getFullTimeMatches()->count(),
            'live_matches' => $this->getLiveMatches()->count(),
            'average_total_score' => round($teamScores->avg('total_score'), 2),
            'highest_total_score' => $teamScores->max('total_score'),
            'average_margin' => round($teamScores->avg('margin'), 2),
            'biggest_margin' => $teamScores->max('margin'),
            'venues_count' => $this->matches->pluck('@venue')->unique()->count(),
        ];
    }
}
