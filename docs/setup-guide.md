# BTS Guru Daemon Service - Setup Guide

## Project Setup

This guide will walk you through setting up the BTS Guru Daemon Service, a Laravel 12 application that acts as a proxy for RapidAPI sports data with real-time WebSocket updates.

## Prerequisites

- PHP 8.2 or higher
- Composer
- Docker and Docker Compose
- Node.js and NPM
- RapidAPI account with access to sports data APIs

## Installation Steps

### 1. Clone the Repository

The project is already set up with the basic Laravel 12 structure. Make sure you have the latest code.

### 2. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install
```

### 3. Environment Configuration

Copy the `.env.example` file to `.env` if not already done:

```bash
cp .env.example .env
```

Update the following environment variables in the `.env` file:

```
# API Configuration
RAPIDAPI_KEY=your_api_key
RAPIDAPI_HOST=v3.football.api-sports.io
RAPIDAPI_BASE_URL=https://v3.football.api-sports.io

# Redis Configuration
REDIS_HOST=redis
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

### 4. Start Docker Containers

The project uses Laravel Sail for Docker containerization:

```bash
./vendor/bin/sail up -d
```

This will start the following containers:
- Laravel application
- PostgreSQL database
- Redis server

### 5. Run Database Migrations

Create the necessary database tables:

```bash
./vendor/bin/sail artisan migrate
```

### 6. Install Required Packages

Install the additional packages needed for the project:

```bash
./vendor/bin/sail composer require laravel/sanctum laravel/horizon laravel/reverb predis/predis guzzlehttp/guzzle
```

### 7. Publish Package Assets

Publish the configuration files for Sanctum, Horizon, and Reverb:

```bash
./vendor/bin/sail artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
./vendor/bin/sail artisan vendor:publish --provider="Laravel\Horizon\HorizonServiceProvider"
./vendor/bin/sail artisan vendor:publish --provider="Laravel\Reverb\ReverbServiceProvider"
```

### 8. Configure Broadcasting

Update the `config/broadcasting.php` file to use Reverb as the default broadcast driver:

```php
'default' => env('BROADCAST_DRIVER', 'reverb'),
```

### 9. Start Horizon

Start the Laravel Horizon process to manage background jobs:

```bash
./vendor/bin/sail artisan horizon
```

### 10. Start Reverb

Start the Laravel Reverb WebSocket server:

```bash
./vendor/bin/sail artisan reverb:start
```

### 11. Build Frontend Assets

Compile the frontend assets:

```bash
./vendor/bin/sail npm run build
```

## Project Structure

The project follows a standard Laravel 12 structure with the following additions:

### Key Directories

- `app/Services` - Service classes for RapidAPI integration and data processing
- `app/Jobs` - Background jobs for data polling and processing
- `app/Events` - Events for WebSocket broadcasting
- `app/Http/Controllers/Api` - API controllers for RESTful endpoints
- `app/Models` - Database models for sports data
- `config` - Configuration files for the application
- `database/migrations` - Database migrations for creating tables
- `routes` - API and WebSocket channel routes

## Development Workflow

1. **Create Models and Migrations**: Define database schema for sports data
2. **Implement Service Layer**: Create services for RapidAPI integration
3. **Build Background Jobs**: Implement data polling and processing jobs
4. **Configure WebSockets**: Set up Reverb for real-time updates
5. **Create API Endpoints**: Build RESTful API for data access
6. **Implement Authentication**: Set up Sanctum for API authentication
7. **Configure Monitoring**: Set up Horizon for queue monitoring

## Testing

Run the test suite to ensure everything is working correctly:

```bash
./vendor/bin/sail artisan test
```

## Deployment

For production deployment, follow these additional steps:

1. Configure a production-ready `.env` file
2. Set up Supervisor to manage worker processes
3. Configure a web server (Nginx/Apache) with proper SSL
4. Set up a load balancer for WebSocket servers
5. Configure Redis for high availability
6. Set up monitoring and alerting

## Troubleshooting

### Common Issues

1. **Redis Connection Errors**: Ensure Redis is running and properly configured
2. **WebSocket Connection Failures**: Check Reverb configuration and firewall settings
3. **API Rate Limiting**: Monitor RapidAPI usage to avoid hitting limits
4. **Queue Backlog**: Increase worker processes if jobs are backing up
5. **Database Connection Issues**: Verify PostgreSQL connection settings

### Debugging Tools

- Laravel Horizon dashboard: `/horizon`
- Laravel Telescope (if installed): `/telescope`
- Laravel logs: `storage/logs/laravel.log`
- Docker logs: `docker logs container_name`
