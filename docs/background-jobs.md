# BTS Guru Daemon Service - Background Jobs

## Overview

This document outlines the background job architecture for the BTS Guru Daemon Service, which is responsible for continuously fetching sports data from RapidAPI, processing it, and broadcasting updates via WebSockets.

## Job Architecture

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│             │     │             │     │             │     │             │
│  Scheduler  │────▶│  Queue      │────▶│  Workers    │────▶│  Event      │
│             │     │             │     │             │     │  Broadcasting│
│             │     │             │     │             │     │             │
└─────────────┘     └─────────────┘     └─────────────┘     └─────────────┘
```

## Job Types

### 1. Sport Data Polling Jobs

These jobs are responsible for fetching data from RapidAPI at regular intervals:

- `PollLiveFootballMatchesJob`: Fetches live football match data
- `PollLiveBasketballMatchesJob`: Fetches live basketball match data
- `PollScheduledMatchesJob`: Fetches upcoming scheduled matches
- `PollCompletedMatchesJob`: Updates data for recently completed matches

### 2. Data Processing Jobs

These jobs process and normalize the data fetched from RapidAPI:

- `ProcessMatchDataJob`: Processes match data and detects changes
- `UpdateMatchStatusJob`: Updates match status when it changes
- `ProcessMatchEventsJob`: Processes match events (goals, cards, etc.)

### 3. Broadcasting Jobs

These jobs are responsible for broadcasting updates to WebSocket clients:

- `BroadcastMatchUpdateJob`: Broadcasts match updates to subscribers
- `BroadcastMatchEventJob`: Broadcasts match events to subscribers
- `BroadcastStatusChangeJob`: Broadcasts match status changes

## Queue Configuration

The application uses Redis for queue processing with multiple queues for different job types:

- `polling`: For data polling jobs
- `processing`: For data processing jobs
- `broadcasting`: For event broadcasting jobs

Configuration in `config/queue.php`:

```php
'redis' => [
    'driver' => 'redis',
    'connection' => 'queue',
    'queue' => env('REDIS_QUEUE', 'default'),
    'retry_after' => 90,
    'block_for' => null,
],
```

## Job Scheduling

Jobs are scheduled using Laravel's scheduler in `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Football polling
    $schedule->job(new PollLiveFootballMatchesJob())->everyFifteenSeconds();
    $schedule->job(new PollScheduledFootballMatchesJob())->everyFiveMinutes();
    $schedule->job(new PollCompletedFootballMatchesJob())->everyThirtyMinutes();
    
    // Basketball polling
    $schedule->job(new PollLiveBasketballMatchesJob())->everyTenSeconds();
    $schedule->job(new PollScheduledBasketballMatchesJob())->everyFiveMinutes();
    $schedule->job(new PollCompletedBasketballMatchesJob())->everyThirtyMinutes();
    
    // Other sports...
    
    // Maintenance jobs
    $schedule->job(new CleanupOldMatchDataJob())->daily();
    $schedule->job(new UpdateLeagueDataJob())->daily();
    $schedule->job(new UpdateTeamDataJob())->daily();
}
```

## Job Implementation

### Example: Poll Live Football Matches Job

```php
namespace App\Jobs;

use App\Services\RapidApi\FootballApiClient;
use App\Services\DataProcessors\FootballDataProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PollLiveFootballMatchesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 30;
    
    public function __construct()
    {
        $this->onQueue('polling');
    }
    
    public function handle(FootballApiClient $apiClient, FootballDataProcessor $dataProcessor)
    {
        try {
            Log::info('Polling live football matches');
            
            // Fetch live matches from API
            $liveMatches = $apiClient->getLiveMatches();
            
            // Process each match
            foreach ($liveMatches['response'] as $match) {
                ProcessMatchDataJob::dispatch($match, 'football')
                    ->onQueue('processing');
            }
            
            Log::info('Completed polling live football matches', [
                'count' => count($liveMatches['response']),
            ]);
        } catch (\Exception $e) {
            Log::error('Error polling live football matches', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw $e;
        }
    }
    
    public function failed(\Throwable $exception)
    {
        Log::error('Live football polling job failed', [
            'error' => $exception->getMessage(),
        ]);
    }
}
```

### Example: Process Match Data Job

```php
namespace App\Jobs;

use App\Events\MatchUpdate;
use App\Events\MatchStatusChange;
use App\Models\Match;
use App\Services\DataProcessors\DataProcessorFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessMatchDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $matchData;
    protected $sportType;
    
    public $tries = 3;
    public $timeout = 30;
    
    public function __construct(array $matchData, string $sportType)
    {
        $this->matchData = $matchData;
        $this->sportType = $sportType;
        $this->onQueue('processing');
    }
    
    public function handle(DataProcessorFactory $processorFactory)
    {
        try {
            // Get the appropriate data processor for this sport
            $processor = $processorFactory->getProcessor($this->sportType);
            
            // Normalize the data
            $normalizedData = $processor->normalize($this->matchData);
            
            // Find or create the match in the database
            $match = Match::firstOrNew([
                'rapidapi_id' => $normalizedData['id'],
                'sport_id' => $normalizedData['sport_id'],
            ]);
            
            // Check for changes if the match already exists
            $isNewMatch = !$match->exists;
            $changes = [];
            
            if (!$isNewMatch) {
                $changes = $processor->detectChanges($match->toArray(), $normalizedData);
            }
            
            // Update the match with new data
            foreach ($normalizedData as $key => $value) {
                $match->{$key} = $value;
            }
            
            $match->last_updated = now();
            $match->save();
            
            // Process any detected changes
            if (!empty($changes)) {
                $this->processChanges($match, $changes);
            } elseif ($isNewMatch) {
                // Broadcast the new match
                BroadcastMatchUpdateJob::dispatch($match)
                    ->onQueue('broadcasting');
            }
            
            Log::info('Processed match data', [
                'match_id' => $match->id,
                'sport' => $this->sportType,
                'changes' => $changes,
            ]);
        } catch (\Exception $e) {
            Log::error('Error processing match data', [
                'error' => $e->getMessage(),
                'sport' => $this->sportType,
                'match_data' => $this->matchData,
            ]);
            
            throw $e;
        }
    }
    
    protected function processChanges(Match $match, array $changes)
    {
        // Handle score changes
        if (isset($changes['score'])) {
            // Process score change events
            ProcessMatchEventsJob::dispatch($match, 'score_change', $changes['score'])
                ->onQueue('processing');
            
            // Broadcast score update
            BroadcastMatchUpdateJob::dispatch($match)
                ->onQueue('broadcasting');
        }
        
        // Handle status changes
        if (isset($changes['status'])) {
            // Broadcast status change
            BroadcastStatusChangeJob::dispatch(
                $match,
                $changes['status']['old'],
                $changes['status']['new']
            )->onQueue('broadcasting');
        }
        
        // Handle period changes
        if (isset($changes['period'])) {
            // Broadcast period change
            BroadcastMatchUpdateJob::dispatch($match)
                ->onQueue('broadcasting');
        }
    }
    
    public function failed(\Throwable $exception)
    {
        Log::error('Match data processing job failed', [
            'error' => $exception->getMessage(),
            'sport' => $this->sportType,
        ]);
    }
}
```

### Example: Broadcast Match Update Job

```php
namespace App\Jobs;

use App\Events\MatchUpdate;
use App\Models\Match;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BroadcastMatchUpdateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $match;
    
    public $tries = 3;
    public $timeout = 15;
    
    public function __construct(Match $match)
    {
        $this->match = $match;
        $this->onQueue('broadcasting');
    }
    
    public function handle()
    {
        try {
            // Load relationships if they're not already loaded
            if (!$this->match->relationLoaded('sport')) {
                $this->match->load('sport');
            }
            
            if (!$this->match->relationLoaded('league')) {
                $this->match->load('league');
            }
            
            if (!$this->match->relationLoaded('homeTeam')) {
                $this->match->load('homeTeam');
            }
            
            if (!$this->match->relationLoaded('awayTeam')) {
                $this->match->load('awayTeam');
            }
            
            // Broadcast the match update event
            event(new MatchUpdate($this->match));
            
            Log::info('Broadcasted match update', [
                'match_id' => $this->match->id,
                'sport' => $this->match->sport->name,
                'status' => $this->match->status,
            ]);
        } catch (\Exception $e) {
            Log::error('Error broadcasting match update', [
                'error' => $e->getMessage(),
                'match_id' => $this->match->id,
            ]);
            
            throw $e;
        }
    }
    
    public function failed(\Throwable $exception)
    {
        Log::error('Match update broadcasting job failed', [
            'error' => $exception->getMessage(),
            'match_id' => $this->match->id,
        ]);
    }
}
```

## Worker Configuration

Workers are managed using Laravel Horizon, which provides a dashboard for monitoring queue processing. The configuration is defined in `config/horizon.php`:

```php
'environments' => [
    'production' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['polling', 'processing', 'broadcasting'],
            'balance' => 'auto',
            'processes' => 10,
            'tries' => 3,
        ],
    ],
    
    'local' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['polling', 'processing', 'broadcasting'],
            'balance' => 'auto',
            'processes' => 3,
            'tries' => 3,
        ],
    ],
],
```

## Supervisor Configuration

In production, worker processes are managed by Supervisor to ensure they remain running. Example Supervisor configuration:

```ini
[program:bts-daemon-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan horizon
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/path/to/horizon.log
stopwaitsecs=3600
```

## Scaling Strategy

The background job system is designed to scale horizontally:

1. **Auto-scaling Workers**: Horizon automatically scales worker processes based on queue load
2. **Queue Prioritization**: Critical jobs (like live match updates) are processed first
3. **Job Batching**: Related jobs are batched together for efficiency
4. **Rate Limiting**: Jobs are rate-limited to prevent overwhelming RapidAPI
5. **Failure Handling**: Failed jobs are retried with exponential backoff

## Monitoring and Alerting

The background job system includes comprehensive monitoring:

1. **Horizon Dashboard**: Real-time monitoring of queue processing
2. **Failed Job Monitoring**: Alerts for repeatedly failing jobs
3. **Queue Size Monitoring**: Alerts for growing queue backlogs
4. **Worker Health Checks**: Monitoring of worker process health
5. **Job Timing**: Tracking of job execution times for performance optimization

## Command Line Tools

The application includes command-line tools for managing the background job system:

```bash
# Start the Horizon worker process
php artisan horizon

# Pause all job processing
php artisan horizon:pause

# Resume job processing
php artisan horizon:continue

# Terminate the Horizon process
php artisan horizon:terminate

# View current Horizon status
php artisan horizon:status

# Clear all failed jobs
php artisan queue:flush

# Retry all failed jobs
php artisan queue:retry all

# Retry a specific failed job
php artisan queue:retry job-id
```
