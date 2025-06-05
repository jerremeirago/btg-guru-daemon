<?php

namespace App\Events;

use App\Models\AflApiResponse;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Services\Afl\AflService;

class AflDataUpdate implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The AFL data that should be broadcast.
     *
     * @var \App\Models\AflApiResponse
     */
    public $aflData;

    public AflService $aflService;
    
    /**
     * Fresh response data to bypass any caching issues.
     *
     * @var array|null
     */
    public ?array $freshResponse = null;

    /**
     * Create a new event instance.
     */
    public function __construct(AflApiResponse $aflData, AflService $aflService)
    {
        $this->aflData = $aflData;
        $this->aflService = $aflService;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('sports.live.afl'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'afl.update';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        // If we have fresh response data, use it to hydrate the analyzer
        if ($this->freshResponse) {
            // Create a temporary analyzer with the fresh data
            $analyzer = app(\App\Services\Afl\Utils\Analyzer::class);
            $analyzer->hydrate($this->freshResponse);
            
            // Get scoreboard and standings directly from this analyzer
            $scoreboard = $analyzer->getTeamScores();
            $standings = $this->aflService->getTeamStandings(); // Still use service for standings
        } else {
            // Fall back to the service if no fresh data is available
            $scoreboard = $this->aflService->getScoreboard();
            $standings = $this->aflService->getTeamStandings();
        }

        return [
            'has_match_today' => has_match_today(),
            'data_available' => $this->aflData->response_code === 200,
            'updated_at' => $this->aflData->updated_at->toIso8601String(),
            'api_call_time' => $this->aflData->response_time,
            'response_code' => $this->aflData->response_code,
            'request_id' => $this->aflData->request_id,
            'fetch' => config('app.url') . '/api/v1/live/afl',
            'round' => get_current_round()['round'],
            'scoreboard' => $scoreboard,
            'standings' => $standings
        ];
    }
}
