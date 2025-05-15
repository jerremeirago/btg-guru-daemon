BTS Guru Daemon Service

## Project Overview
Create a Laravel 12 application that acts as a proxy for RapidAPI sports data, providing real-time score updates via WebSockets. This application will:
1. Cache API responses in Redis to reduce direct calls to RapidAPI
2. Stream real-time sports data updates via Laravel Reverb WebSockets
3. Run background jobs to continuously fetch the latest scores
4. Implement API key authentication for end-users
5. Bypass RapidAPI's throttling and user limitations

## Architecture Overview

### 1. Data Flow Architecture
- RapidAPI Sports Data → Laravel Background Workers → Redis Cache → WebSocket Broadcasting → End Users
- Scheduled polling jobs fetch fresh data at optimal intervals based on sport type
- Change detection identifies score updates and broadcasts only meaningful changes
- Redis powers both caching and pub/sub mechanisms for real-time updates

### 2. Key Technical Components
- **Laravel Reverb**: For WebSocket server implementation
- **Redis**: For caching, queues, and pub/sub messaging
- **Laravel Horizon**: For queue monitoring and management
- **Laravel Sanctum**: For API authentication
- **Background Jobs**: For continuous data polling with auto-scaling capacity

## Implementation Roadmap

### Phase 1: Foundation Setup (Week 1)
1. Initialize Laravel 12 project with required packages
2. Set up database migrations for user, API keys, and sports data models
3. Configure Redis for caching and queue processing
4. Implement basic API authentication with rate limiting

### Phase 2: RapidAPI Integration (Week 1-2)
1. Create service layer for communicating with RapidAPI sports endpoints
2. Implement intelligent caching strategies for different data types
3. Build change detection algorithms for identifying score updates
4. Create data normalization to handle different sports formats consistently

### Phase 3: Real-time Infrastructure (Week 2-3)
1. Configure Laravel Reverb for WebSocket communication
2. Implement event broadcasting system for score updates
3. Create channel subscription management for different sports/leagues
4. Build background polling workers with automatic scaling

### Phase 4: API Layer (Week 3-4)
1. Create RESTful API endpoints for sports data access
2. Implement WebSocket subscription management API
3. Build user dashboard for monitoring usage and managing API keys
4. Add comprehensive API documentation with OpenAPI/Swagger

### Phase 5: Monitoring & Production Readiness (Week 4)
1. Set up Horizon dashboard for queue monitoring
2. Implement logging and alerting for service disruptions
3. Create deployment pipeline for production environment
4. Add performance optimization for high-traffic scenarios

## Core Technology Stack
- Laravel 12 (PHP 8.2+)
- Redis (for caching, queues, and pub/sub)
- MySQL/PostgreSQL (for persistent storage)
- Laravel Reverb (for WebSockets)
- Laravel Horizon (for queue monitoring)
- Laravel Sanctum (for API authentication)
- Supervisor (for process management)

## Key Considerations

### Polling Frequency
- Live events: 10-15 seconds for most sports
- Higher frequency (5-10 seconds) for fast-paced sports like basketball
- Lower frequency (30-60 seconds) for slower sports like baseball
- Auto-adjustment based on game period (more frequent during crucial moments)

### Caching Strategy
- Short TTL (30-60 seconds) for live game data
- Longer TTL (5-15 minutes) for static data like team information
- Redis hash structures for efficient storage of game states
- Intelligent invalidation based on game status changes

### Scalability Considerations
- Horizontal scaling of WebSocket servers using Redis pub/sub
- Worker pool auto-scaling based on number of live games
- Rate limiting based on user subscription tiers
- Connection pooling to reduce database load

## Implementation Details

### Background Data Fetching
- Create Laravel command `sports:poll` that runs continuously
- Implement worker processes that scale with number of active games
- Use queues to distribute polling tasks across multiple workers
- Implement exponential backoff for failed API requests

### WebSocket Implementation
- Use Laravel Reverb for WebSocket server
- Create channel naming convention: `sports.{sport}.{league}.{match_id}`
- Implement presence channels for user tracking
- Add authorization middleware for private channels

### Deployment Architecture
- Redis cluster for high availability
- Multiple WebSocket servers behind load balancer
- Worker processes managed by Supervisor
- Monitoring via Laravel Horizon dashboard

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