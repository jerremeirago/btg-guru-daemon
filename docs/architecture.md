# BTS Guru Daemon Service - Architecture

## Overview

The BTS Guru Daemon Service is designed as a scalable, real-time sports data proxy that sits between RapidAPI sports data providers and end users. It leverages Laravel 12, Redis, and WebSockets to provide a high-performance, real-time data streaming service.

## System Architecture

```
┌─────────────┐     ┌─────────────────────────────────────┐     ┌─────────────┐
│             │     │                                     │     │             │
│  RapidAPI   │────▶│  BTS Guru Daemon Service (Laravel)  │────▶│  End Users  │
│             │     │                                     │     │             │
└─────────────┘     └─────────────────────────────────────┘     └─────────────┘
                                     │
                                     │
                          ┌──────────┴──────────┐
                          │                     │
                    ┌─────┴─────┐         ┌─────┴─────┐
                    │           │         │           │
                    │  Redis    │         │ PostgreSQL │
                    │           │         │           │
                    └───────────┘         └───────────┘
```

## Core Components

### 1. Data Fetching Layer

- **Background Workers**: Laravel jobs that poll RapidAPI at configurable intervals
- **Adaptive Polling**: Adjusts polling frequency based on sport type and game state
- **Error Handling**: Implements retry mechanisms and exponential backoff
- **Change Detection**: Identifies meaningful updates to broadcast

### 2. Caching Layer

- **Redis Cache**: Stores API responses with appropriate TTL
- **Intelligent Invalidation**: Cache invalidation based on game status
- **Data Normalization**: Consistent format across different sports
- **Memory Optimization**: Efficient storage using Redis data structures

### 3. WebSocket Layer

- **Laravel Reverb**: Handles WebSocket connections and broadcasting
- **Channel Management**: Organized by sport, league, and match ID
- **Authentication**: Secure private channels for authenticated users
- **Presence Channels**: Track user subscriptions and activity

### 4. API Layer

- **RESTful Endpoints**: Standard HTTP API for data access
- **Authentication**: API key-based authentication with Laravel Sanctum
- **Rate Limiting**: Configurable limits based on subscription tier
- **Documentation**: OpenAPI/Swagger documentation

### 5. Monitoring Layer

- **Laravel Horizon**: Queue monitoring and management
- **Logging**: Comprehensive logging of system events
- **Alerting**: Notifications for system issues or rate limit warnings
- **Analytics**: Track usage patterns and performance metrics

## Data Flow

1. **Data Acquisition**: Background workers poll RapidAPI endpoints at optimal intervals
2. **Data Processing**: Normalize and transform data into a consistent format
3. **Change Detection**: Identify meaningful changes in sports data
4. **Caching**: Store processed data in Redis with appropriate TTL
5. **Broadcasting**: Publish updates to appropriate WebSocket channels
6. **API Access**: Provide RESTful API endpoints for data access
7. **Monitoring**: Track system performance and usage metrics

## Scaling Strategy

- **Horizontal Scaling**: Multiple WebSocket servers behind load balancer
- **Worker Pool**: Auto-scaling based on number of active games
- **Connection Pooling**: Reduce database load
- **Redis Cluster**: High availability for caching and pub/sub
- **Load Balancing**: Distribute WebSocket connections across servers
