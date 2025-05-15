# BTS Guru Daemon Service - WebSocket Implementation

## Overview

This document outlines the WebSocket implementation for the BTS Guru Daemon Service using Laravel Reverb. The WebSocket server provides real-time sports data updates to clients, bypassing the limitations of RapidAPI while maintaining data freshness.

## WebSocket Architecture

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│             │     │             │     │             │
│  Laravel    │────▶│    Redis    │────▶│   Reverb    │────▶ Clients
│  Events     │     │  Pub/Sub    │     │  WebSockets │
│             │     │             │     │             │
└─────────────┘     └─────────────┘     └─────────────┘
```

## Channel Structure

WebSocket channels follow a hierarchical naming convention to allow clients to subscribe to specific data streams:

- `sports.{sport_id}` - All updates for a specific sport
- `sports.{sport_id}.leagues.{league_id}` - All updates for a specific league
- `sports.{sport_id}.leagues.{league_id}.matches.{match_id}` - Updates for a specific match
- `sports.{sport_id}.teams.{team_id}` - Updates for a specific team

Examples:
- `sports.football` - All football updates
- `sports.basketball.leagues.nba` - All NBA basketball updates
- `sports.football.leagues.premier-league.matches.12345` - Updates for Premier League match #12345

## Channel Types

### Public Channels
- Available to all clients without authentication
- Provide basic score updates and match status changes
- Example: `sports.football.leagues.premier-league`

### Private Channels
- Require authentication via Laravel Sanctum
- Provide more detailed data and higher update frequency
- Example: `private-sports.basketball.leagues.nba.matches.12345`

### Presence Channels
- Track which users are subscribed to specific channels
- Useful for analytics and user activity tracking
- Example: `presence-sports.football.leagues.premier-league.matches.12345`

## Event Types

Events broadcast over WebSockets follow a consistent structure:

### Match Update Events
```json
{
  "event": "match.update",
  "data": {
    "match_id": 12345,
    "sport_id": "football",
    "league_id": "premier-league",
    "status": "live",
    "home_team": {
      "id": 101,
      "name": "Team A",
      "score": 2
    },
    "away_team": {
      "id": 102,
      "name": "Team B",
      "score": 1
    },
    "period": "2nd Half",
    "time": "75:00",
    "last_updated": "2025-05-14T01:35:00Z"
  }
}
```

### Match Event Events
```json
{
  "event": "match.event",
  "data": {
    "match_id": 12345,
    "event_id": 67890,
    "event_type": "goal",
    "team_id": 101,
    "player_name": "John Smith",
    "time": "74:30",
    "description": "Goal from penalty kick",
    "home_score": 2,
    "away_score": 1,
    "timestamp": "2025-05-14T01:34:30Z"
  }
}
```

### Match Status Change Events
```json
{
  "event": "match.status",
  "data": {
    "match_id": 12345,
    "previous_status": "scheduled",
    "new_status": "live",
    "timestamp": "2025-05-14T01:00:00Z"
  }
}
```

## Client Implementation

Clients can connect to the WebSocket server using standard WebSocket libraries or the Laravel Echo client library. Example client implementation using Laravel Echo:

```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: 'your_reverb_app_key',
    wsHost: window.location.hostname,
    wsPort: 8080,
    forceTLS: false,
    disableStats: true,
    enabledTransports: ['ws', 'wss'],
});

// Subscribe to public channel
window.Echo.channel('sports.football.leagues.premier-league')
    .listen('MatchUpdate', (e) => {
        console.log('Match update:', e);
    });

// Subscribe to private channel (requires authentication)
window.Echo.private('sports.football.leagues.premier-league.matches.12345')
    .listen('MatchEvent', (e) => {
        console.log('Match event:', e);
    });

// Subscribe to presence channel
window.Echo.join('presence-sports.football.leagues.premier-league.matches.12345')
    .here((users) => {
        console.log('Users currently watching:', users);
    })
    .joining((user) => {
        console.log('User joined:', user);
    })
    .leaving((user) => {
        console.log('User left:', user);
    })
    .listen('MatchUpdate', (e) => {
        console.log('Match update:', e);
    });
```

## Server Implementation

The WebSocket server is implemented using Laravel Reverb, which integrates with Laravel's event broadcasting system.

### Configuration

Configuration in `broadcasting.php`:

```php
'reverb' => [
    'driver' => 'reverb',
    'app_id' => env('REVERB_APP_ID', 'sports_app'),
    'app_key' => env('REVERB_APP_KEY'),
    'app_secret' => env('REVERB_APP_SECRET'),
    'host' => env('REVERB_HOST', '0.0.0.0'),
    'port' => env('REVERB_PORT', 8080),
    'options' => [
        'tls' => [
            'verify_peer' => false,
        ],
    ],
],
```

### Event Broadcasting

Events are broadcast from Laravel using the event broadcasting system:

```php
class MatchUpdate implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $match;

    public function __construct(Match $match)
    {
        $this->match = $match;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel("sports.{$this->match->sport->slug}"),
            new Channel("sports.{$this->match->sport->slug}.leagues.{$this->match->league->slug}"),
            new Channel("sports.{$this->match->sport->slug}.leagues.{$this->match->league->slug}.matches.{$this->match->id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'match.update';
    }

    public function broadcastWith(): array
    {
        return [
            'match_id' => $this->match->id,
            'sport_id' => $this->match->sport->slug,
            'league_id' => $this->match->league->slug,
            'status' => $this->match->status,
            'home_team' => [
                'id' => $this->match->homeTeam->id,
                'name' => $this->match->homeTeam->name,
                'score' => $this->match->home_score,
            ],
            'away_team' => [
                'id' => $this->match->awayTeam->id,
                'name' => $this->match->awayTeam->name,
                'score' => $this->match->away_score,
            ],
            'period' => $this->match->period,
            'time' => $this->match->time,
            'last_updated' => $this->match->last_updated->toIso8601String(),
        ];
    }
}
```

## Authentication

WebSocket authentication is handled through Laravel Sanctum, which provides token-based authentication for API clients.

### Channel Authorization

Channel authorization is implemented in the `routes/channels.php` file:

```php
Broadcast::channel('sports.{sport}.leagues.{league}.matches.{match}', function ($user, $sport, $league, $match) {
    return true; // Public channel, available to all
});

Broadcast::channel('private-sports.{sport}.leagues.{league}.matches.{match}', function ($user, $sport, $league, $match) {
    return $user->hasActiveSubscription(); // Only for subscribed users
});

Broadcast::channel('presence-sports.{sport}.leagues.{league}.matches.{match}', function ($user, $sport, $league, $match) {
    if ($user->hasActiveSubscription()) {
        return ['id' => $user->id, 'name' => $user->name];
    }
    
    return false;
});
```

## Scaling Considerations

- Multiple Reverb servers can be deployed behind a load balancer
- Redis pub/sub ensures all servers receive the same events
- Horizontal scaling allows handling thousands of concurrent connections
- Connection pooling reduces database load
- Monitoring WebSocket connections provides insights into system load
