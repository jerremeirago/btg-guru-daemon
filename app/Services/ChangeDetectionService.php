<?php

namespace App\Services;

use App\Models\SportMatch;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ChangeDetectionService
{
    /**
     * Cache key prefix for previous match states.
     *
     * @var string
     */
    protected string $cacheKeyPrefix = 'match_previous_state:';
    
    /**
     * Cache TTL for previous match states in seconds.
     *
     * @var int
     */
    protected int $cacheTtl = 86400; // 24 hours
    
    /**
     * Detect changes in match data.
     *
     * @param array $matchData
     * @return array
     */
    public function detectChanges(array $matchData): array
    {
        if (empty($matchData['rapidapi_id'])) {
            return $matchData;
        }
        
        $cacheKey = $this->cacheKeyPrefix . $matchData['rapidapi_id'];
        $previousState = Cache::get($cacheKey);
        
        // First time seeing this match, no changes to detect
        if (!$previousState) {
            Cache::put($cacheKey, $matchData, $this->cacheTtl);
            $matchData['has_updates'] = false;
            return $matchData;
        }
        
        // Detect changes
        $changes = $this->getChanges($previousState, $matchData);
        
        // Update cache with new state
        Cache::put($cacheKey, $matchData, $this->cacheTtl);
        
        // Set has_updates flag and include changes
        $matchData['has_updates'] = !empty($changes);
        $matchData['changes'] = $changes;
        
        // Log significant changes
        if (!empty($changes)) {
            $this->logChanges($matchData['rapidapi_id'], $changes);
        }
        
        return $matchData;
    }
    
    /**
     * Get changes between previous and current match states.
     *
     * @param array $previousState
     * @param array $currentState
     * @return array
     */
    protected function getChanges(array $previousState, array $currentState): array
    {
        $changes = [];
        
        // Check score changes
        if ($this->hasScoreChanged($previousState, $currentState)) {
            $changes['score'] = [
                'previous' => [
                    'home' => $previousState['scores']['home'] ?? null,
                    'away' => $previousState['scores']['away'] ?? null,
                ],
                'current' => [
                    'home' => $currentState['scores']['home'] ?? null,
                    'away' => $currentState['scores']['away'] ?? null,
                ],
            ];
        }
        
        // Check status changes
        if ($this->hasStatusChanged($previousState, $currentState)) {
            $changes['status'] = [
                'previous' => [
                    'short' => $previousState['status']['short'] ?? null,
                    'long' => $previousState['status']['long'] ?? null,
                ],
                'current' => [
                    'short' => $currentState['status']['short'] ?? null,
                    'long' => $currentState['status']['long'] ?? null,
                ],
            ];
        }
        
        return $changes;
    }
    
    /**
     * Check if score has changed.
     *
     * @param array $previousState
     * @param array $currentState
     * @return bool
     */
    protected function hasScoreChanged(array $previousState, array $currentState): bool
    {
        $prevHomeScore = $previousState['scores']['home'] ?? null;
        $prevAwayScore = $previousState['scores']['away'] ?? null;
        $currHomeScore = $currentState['scores']['home'] ?? null;
        $currAwayScore = $currentState['scores']['away'] ?? null;
        
        return $prevHomeScore !== $currHomeScore || $prevAwayScore !== $currAwayScore;
    }
    
    /**
     * Check if status has changed.
     *
     * @param array $previousState
     * @param array $currentState
     * @return bool
     */
    protected function hasStatusChanged(array $previousState, array $currentState): bool
    {
        $prevStatus = $previousState['status']['short'] ?? null;
        $currStatus = $currentState['status']['short'] ?? null;
        
        return $prevStatus !== $currStatus;
    }
    
    /**
     * Log changes for monitoring.
     *
     * @param int|string $matchId
     * @param array $changes
     * @return void
     */
    protected function logChanges($matchId, array $changes): void
    {
        Log::info("Match {$matchId} changes detected", [
            'match_id' => $matchId,
            'changes' => $changes,
            'timestamp' => now()->toDateTimeString(),
        ]);
        
        // Update the match in the database to mark it as having updates
        $match = SportMatch::where('rapidapi_id', $matchId)->first();
        if ($match) {
            $match->has_updates = true;
            $match->additional_data = array_merge(
                (array) $match->additional_data, 
                ['last_change' => $changes]
            );
            $match->save();
        }
    }
    
    /**
     * Process a batch of matches to detect changes.
     *
     * @param array $matches
     * @return array
     */
    public function processBatch(array $matches): array
    {
        $processedMatches = [];
        
        foreach ($matches as $match) {
            $processedMatches[] = $this->detectChanges($match);
        }
        
        return $processedMatches;
    }
}
