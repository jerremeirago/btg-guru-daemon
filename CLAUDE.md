# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Common Development Commands

### Docker Environment (Primary Development Method)
```bash
# Start the Docker containers (PostgreSQL + Laravel app)
docker compose up -d

# SSH into the app container for development
composer run ssh-php               # Uses: docker compose exec app sh

# Run artisan commands inside Docker
composer run artisan migrate       # Uses: docker compose exec app php artisan migrate
composer run artisan queue:work    # Uses: docker compose exec app php artisan queue:work

# Alternative: Direct Docker commands
docker compose exec app php artisan migrate
docker compose exec app php artisan serve
docker compose exec app sh

# Stop Docker containers
docker compose down
```

#### Docker Services and Ports
- **Laravel App**: `http://localhost` (port 80)
- **WebSocket (Reverb)**: `ws://localhost:8080` (port 8080)
- **Vite Dev Server**: `http://localhost:5173` (port 5173)
- **PostgreSQL**: `localhost:5432` (port 5432)

#### Docker Composer Shortcuts
- `composer run ssh-php` - SSH into app container
- `composer run artisan <command>` - Run artisan commands in container
- `composer run php-dump` - Run composer dump-autoload in container

### Building and Development (Inside Docker Container)
```bash
# After ssh-ing into container with: composer run ssh-php

# Install PHP dependencies
composer install

# Install Node.js dependencies  
npm install

# Development server with hot reload (starts web server, queue, logs, and vite)
composer run dev

# Alternative: Start individual services
php artisan serve                    # Web server (port 80)
php artisan queue:work              # Background jobs
php artisan reverb:start            # WebSocket server (port 8080)
npm run dev                         # Frontend assets (port 5173)

# Production build
npm run build
```

### Testing and Code Quality (Inside Docker Container)
```bash
# Run all tests (uses Pest testing framework)
php artisan test
composer run test                   # Alternative method

# Code formatting (Laravel Pint)
./vendor/bin/pint                   # Format all files
./vendor/bin/pint --test           # Check formatting without fixing

# Run single test file
php artisan test tests/Feature/ExampleTest.php

# Run tests with coverage
php artisan test --coverage

# From host machine (using composer scripts)
composer run artisan test
```

### Database Operations (Inside Docker Container)
```bash
# Run migrations
php artisan migrate

# Rollback migrations
php artisan migrate:rollback

# Fresh migration with seeding
php artisan migrate:fresh --seed

# Create new migration
php artisan make:migration create_example_table

# From host machine
composer run artisan migrate
```

### Sports Data Commands (Inside Docker Container)
```bash
# Fetch AFL data (one-time)
php artisan api:afl

# Fetch AFL data (recurring mode - runs continuously)
php artisan api:afl --recurring

# Other sports data commands
php artisan api:afl:schedules       # AFL schedules
php artisan api:afl:standings       # AFL standings
php artisan api:broadcast:afl       # Broadcast AFL data via WebSocket

# Service management (inside container)
./start-services.sh                # Start all daemon services (default)
./start-services.sh start          # Start all daemon services
./start-services.sh stop           # Stop all daemon services
./start-services.sh restart        # Restart all daemon services
./start-services.sh status         # Check service status

# The start-services.sh script manages these background services:
# - Reverb WebSocket server (php artisan reverb:start)
# - AFL data fetching (php artisan api:afl --recurring)
# - AFL standings (php artisan api:afl:standing --recurring)
# - Horizon queue monitor (php artisan horizon)
# All services run in background with PID tracking and logging

# From host machine
composer run artisan api:afl
```

### Queue and Background Jobs (Inside Docker Container)
```bash
# Process queue jobs
php artisan queue:work

# Process specific queue
php artisan queue:work --queue=broadcasts

# Monitor queues with Horizon
php artisan horizon

# Clear failed jobs
php artisan queue:flush
```

### Caching and Performance (Inside Docker Container)
```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Architecture Overview

### Core Architecture Pattern
BTS Guru Daemon is a **Service-Oriented Architecture** with **Event-Driven Real-time Broadcasting** designed as a sports data collection and distribution daemon focused on Australian Football League (AFL) data.

### Key Architectural Components

#### API Layer (`app/Services/`)
- **ApiDriverHandler**: Central HTTP client with configurable timeouts
- **AflService**: Primary AFL data orchestrator 
- **API Drivers**: Abstracted providers (GoalServe, RapidAPI)
- **CacheService**: Redis-based data caching
- **ChangeDetectionService**: Data change monitoring
- **RetryService**: Fault tolerance for API calls

#### AFL Analytics Engine (`app/Services/Afl/Utils/`)
Sophisticated sports analytics using trait-based composition:
- **TeamAnalysis**: Performance metrics, head-to-head records
- **MatchAnalysis**: Match-specific statistics 
- **PlayerAnalysis**: Player performance tracking
- **EventAnalysis**: Game events and timeline
- **ScheduleAnalysis**: Fixture and scheduling logic

#### Real-time Broadcasting
- **Laravel Reverb**: WebSocket server (port 8080)
- **Event System**: `AflDataUpdate` broadcasts to `sports.live.afl` channel
- **Fresh Data Pipeline**: Cache-bypassing for real-time accuracy

#### Background Processing
- **AflLiveDataSyncJob**: Self-dispatching recursive job for continuous data fetching
- **Intelligent Scheduling**: 2-second intervals during match days, 60-second otherwise
- **Laravel Horizon**: Queue monitoring and management

### Database Models and Relationships

#### Core Models
- **AflApiResponse**: API response storage with UUID keys and JSON data
- **League/Team/SportMatch**: Hierarchical sports data structure
- **Standing/Player**: Statistics and rankings
- **ApiRequest**: Request logging and monitoring

Key Features:
- UUID-based primary keys for distributed systems
- JSON columns for flexible API response storage
- Automated round/date population via model events
- Strategic indexing for performance

### API Structure (`routes/api.php`)
```php
GET /api/v1/health                      # System health check
GET /api/v1/live/afl                    # Current AFL data
GET /api/v1/live/afl/scoreboard/{round?} # AFL scoreboard
GET /api/v1/live/afl/match/h2h          # Head-to-head records
GET /api/v1/live/afl/match/summary      # Match summaries
POST /api/test-broadcast                # WebSocket testing
```

## Development Patterns

### Service Pattern
Services in `app/Services/` follow a consistent pattern:
- Interface definitions in `Facade/` directory
- Driver pattern for external API integrations
- Trait-based analytics for modular functionality
- Comprehensive error handling and retry logic

### Event-Driven Architecture
- Events in `app/Events/` trigger WebSocket broadcasts
- Jobs in `app/Jobs/` handle background processing
- Real-time data uses fresh data injection to bypass caching

### Command Pattern
Console commands in `app/Console/Commands/` for:
- Data fetching with single and recurring modes
- Broadcasting and debugging
- Performance monitoring with execution timing

## Environment Configuration

### Required Variables
```env
# API Configuration
RAPIDAPI_KEY=your_api_key
RAPIDAPI_HOST=v3.football.api-sports.io
RAPIDAPI_BASE_URL=https://v3.football.api-sports.io

# Redis Configuration (separate databases for different purposes)
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1
REDIS_QUEUE_DB=2
REDIS_REVERB_DB=3

# WebSocket Configuration
REVERB_APP_ID=sports_app
REVERB_HOST=0.0.0.0
REVERB_PORT=8080
```

## Performance Considerations

### Smart Caching Strategy
- Multiple Redis databases for different data types
- TTL optimization based on data volatility
- Cache invalidation on data changes
- Fresh data pipeline for real-time requirements

### Queue Optimization
- Recursive job scheduling for continuous operation
- Match day vs off-day polling frequency
- Background processing to avoid blocking
- Horizon for monitoring and failure recovery

### Database Optimization
- UUID-based distributed design
- JSON columns for flexible schema
- Strategic indexing for query performance
- Model events for automated data enrichment

## Testing Framework

Uses **Pest PHP** testing framework:
- Feature tests in `tests/Feature/`
- Unit tests in `tests/Unit/`
- Laravel-specific testing helpers
- Database transactions for test isolation

## Unique Features

### AFL-Specific Intelligence
- Round-based season structure understanding
- Match day detection for dynamic polling
- Venue and team-specific analytics
- Form analysis and performance trends

### Sports Data Sophistication
- Multi-provider API abstraction
- Change detection algorithms
- Real-time vs cached data strategies
- Comprehensive statistics and analytics

### Production-Ready Features
- Service management scripts (`startup.sh`, `shutdown.sh`)
- Comprehensive logging and monitoring
- Error recovery and retry mechanisms
- Horizontal scaling support for WebSockets