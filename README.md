# BTS Guru Daemon Service

[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20.svg?style=flat&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4.svg?style=flat&logo=php&logoColor=white)](https://php.net)
[![Redis](https://img.shields.io/badge/Redis-6.x-DC382D.svg?style=flat&logo=redis&logoColor=white)](https://redis.io)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-14.x-336791.svg?style=flat&logo=postgresql&logoColor=white)](https://www.postgresql.org)

A high-performance real-time sports data streaming service built with Laravel 12. BTS Guru Daemon provides instant score updates and sports statistics via WebSockets while intelligently managing API consumption.

## Features

- **Real-time Updates**: Instant sports data delivery via WebSockets
- **Intelligent Caching**: Optimized Redis caching with sport-specific TTLs
- **Background Processing**: Continuous data polling with Laravel queues
- **API Authentication**: Secure API access with Laravel Sanctum
- **Scalable Architecture**: Designed for high-traffic and concurrent connections
- **Comprehensive Monitoring**: Queue and performance monitoring with Laravel Horizon

## Tech Stack

- **Backend**: Laravel 12, PHP 8.2+
- **Database**: PostgreSQL
- **Caching & Queues**: Redis
- **WebSockets**: Laravel Reverb
- **Authentication**: Laravel Sanctum
- **Monitoring**: Laravel Horizon

## Getting Started

### Prerequisites

- PHP 8.2 or higher
- Composer
- Redis server
- PostgreSQL database

### Installation

1. Clone the repository

```bash
git clone https://github.com/your-username/bts-daemon-guru.git
cd bts-daemon-guru
```

2. Install dependencies

```bash
composer install
```

3. Set up environment variables

```bash
cp .env.example .env
php artisan key:generate
```

4. Configure your database and Redis connections in the `.env` file

5. Run migrations

```bash
php artisan migrate
```

6. Start the development server

```bash
php artisan serve
```

### Running Background Workers

Start the queue worker to process background jobs:

```bash
php artisan queue:work
```

For production environments, use Supervisor or one of these methods to keep the worker running:

**Using Supervisor (Recommended):**
Create a configuration file and run Supervisor to manage the process.

**Using nohup:**
```bash
nohup php artisan queue:work > storage/logs/queue-worker.log 2>&1 &
```

**Using Screen:**
```bash
screen -S queue-worker
php artisan queue:work
# Detach with Ctrl+A followed by D
```

### WebSocket Server

Start the Laravel Reverb WebSocket server:

```bash
php artisan reverb:start
```

## API Documentation

API documentation is available at `/docs/api` when running the application.

### WebSocket Channels

Clients can subscribe to the following channel patterns:

- `sports.{sport}.leagues.{league}.matches.{match}`

Example: `sports.football.leagues.premier-league.matches.123`

## Available Commands

### API Service Sync

The `api:sync` command makes requests to external APIs and can run in recurring mode:

```bash
# Run once
php artisan api:sync

# Run in recurring mode (every 3 seconds)
php artisan api:sync --recurring
```

See the [full command documentation](docs/api-service-sync-command.md) for more details.

## Monitoring

Access the Laravel Horizon dashboard to monitor queues and jobs:

```bash
# Start Horizon
php artisan horizon

# Access the dashboard at
http://your-app-url/horizon
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Acknowledgements

- [Laravel](https://laravel.com)
- [Laravel Reverb](https://reverb.laravel.com)
- [Laravel Horizon](https://laravel.com/docs/horizon)
- [Redis](https://redis.io)

## Required Environment Configuration
```
# API Configuration
RAPIDAPI_KEY=your_api_key
RAPIDAPI_HOST=v3.football.api-sports.io
RAPIDAPI_BASE_URL=https://v3.football.api-sports.io

# Redis Configuration
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1
REDIS_QUEUE_DB=2
REDIS_REVERB_DB=3

# WebSocket Configuration
REVERB_APP_ID=sports_app
REVERB_APP_KEY=your_app_key
REVERB_APP_SECRET=your_app_secret
REVERB_HOST=0.0.0.0
REVERB_PORT=8080
```

## Handling Rate Limits
- Implement token bucket algorithm for rate limiting
- Establish different rate limits based on user subscription tier
- Provide clear rate limit headers in API responses
- Implement graceful handling of RapidAPI rate limit errors

## Performance Optimization
- Use Octane with Swoole for improved performance
- Implement request batching to reduce database queries
- Use Redis sorted sets for leaderboards and rankings
- Compress WebSocket messages for reduced bandwidth

## Monitoring and Alerting
- Track WebSocket connection counts and disconnections
- Monitor queue sizes and job processing times
- Alert on API rate limit warnings
- Track data freshness and polling success rates

This roadmap provides a comprehensive guide to building a scalable, real-time sports data streaming service that effectively bypasses RapidAPI limitations while providing a reliable service to end-users.

## Technical Requirements

### 1. Setup & Configuration
- Initialize a new Laravel 12 project
- Set up a MySQL database for storing user credentials and API usage data
- Configure Redis for high-performance caching
- Set up environment variables for RapidAPI keys and endpoints
- Implement proper error handling and logging

### 2. Real-time Data Architecture
- Set up Laravel Reverb for WebSocket communication
- Design a real-time data broadcasting system
- Implement event-driven architecture for score updates
- Create data transformation pipeline for different sports
- Configure horizontal scaling for WebSocket servers

### 3. Background Processing
- Implement Laravel queues for continuous data fetching
- Create workers that poll RapidAPI at optimal intervals
- Set up event listeners for score changes
- Design a change detection algorithm for sports data
- Implement error recovery and retry mechanisms

### 4. Scheduled Data Fetching
- Implement Laravel command scripts for fetching data from RapidAPI
- Configure cron jobs to run these commands at optimal intervals
- Store fetched data in the database for quick retrieval
- Implement data refresh strategies to keep information current

### 5. Monitoring & Analytics
- Track API usage per user
- Monitor cache hit rates and optimize accordingly
- Log errors and unusual access patterns
- Implement alerting for when RapidAPI limits are approaching

## Implementation Steps

### Step 1: Initial Setup
1. Create a new Laravel 12 project
2. Configure database connections
3. Set up basic authentication scaffold
4. Create initial migrations for database structure
5. Install and configure Redis

### Step 2: RapidAPI Sports Data Integration
1. Research available sports data endpoints on RapidAPI
2. Create service classes for each sports API endpoint
3. Implement HTTP client for making requests to RapidAPI
4. Design data normalization for consistent format across different sports
5. Add error handling and retry logic

### Step 3: Real-time Infrastructure with Laravel Reverb
1. Install and configure Laravel Reverb for WebSockets
2. Set up event broadcasting configuration
3. Create event classes for score updates and game status changes
4. Implement authorization for WebSocket connections
5. Configure Reverb for production scaling

### Step 4: Background Processing System
1. Configure Laravel queues with Redis
2. Create queue workers for continuous data polling
3. Implement scoring change detection algorithms
4. Set up incremental updates to minimize data transfer
5. Design fault tolerance with supervisord for worker management

### Step 5: API Key & User Management
1. Create models and migrations for API keys
2. Implement API key generation and validation
3. Build user registration and authentication
4. Create dashboard for API usage monitoring
5. Implement WebSocket connection limits based on subscription tier

### Step 6: Redis Caching Architecture
1. Design Redis data structures for efficient sports data storage
2. Implement intelligent TTL based on game status
3. Create Redis pub/sub channels for internal communication
4. Configure Redis persistence and backup strategy
5. Optimize memory usage with appropriate serialization


## Environment Configuration

`.env` file additions:

```
# RapidAPI Configuration
RAPIDAPI_KEY=your_rapidapi_key_here
RAPIDAPI_BASE_URL=https://example.rapidapi.com

# API Proxy Configuration
API_CACHE_ENABLED=true
API_DEFAULT_CACHE_DURATION=3600
API_RATE_LIMIT=60
API_RATE_LIMIT_DURATION=60

# Redis Configuration
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_CACHE_DB=1
REDIS_CACHE_PREFIX=rapidapi_cache:
```

## Testing Instructions

1. Run unit tests: `php artisan test`
2. Test API endpoints using Postman or similar tool
3. Verify caching behavior by monitoring database and response times
4. Test rate limiting by making multiple requests in succession
5. Verify scheduled jobs by manually running them: `php artisan rapidapi:refresh`