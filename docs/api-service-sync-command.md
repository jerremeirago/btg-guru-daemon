# API Service Sync Command Documentation

## Overview

The `api:sync` command is part of the BTS Guru Daemon Service that provides a way to make HTTP requests to external API endpoints. It supports both one-time execution and recurring execution modes, with the latter running every 3 seconds using Laravel's queue system and Redis.

## Command Details

- **Name**: `api:sync`
- **Location**: `app/Console/Commands/ApiServiceSync.php`
- **Description**: Make a request to the dummy JSON test endpoint, with option to run every 3 seconds

## Usage

### One-time Execution

To run the command once and see the immediate results:

```bash
php artisan api:sync
```

This will:
1. Make a single HTTP request to the test endpoint
2. Display the response in the console
3. Exit after completion

### Recurring Execution

To run the command in recurring mode (every 3 seconds):

```bash
php artisan api:sync --recurring
```

This will:
1. Dispatch a background job (`ApiServiceSyncJob`) to the queue
2. The job will make the HTTP request and log the results
3. After completion, the job will re-dispatch itself with a 3-second delay
4. The command will keep running in the console (can be stopped with Ctrl+C)
5. The background job will continue running even after the command is stopped

## Requirements for Recurring Mode

For the recurring mode to work properly, you need:

1. **Redis Server** running (for queue processing)
2. **Laravel Queue Worker** running:
   ```bash
   php artisan queue:work --queue=default
   ```

   To run the queue worker in the background, you can use one of these methods:

   **Using Supervisor (Recommended for Production):**
   Supervisor is a process control system that can monitor and automatically restart the queue worker if it fails.
   
   Example supervisor configuration (`/etc/supervisor/conf.d/bts-daemon-worker.conf`):
   ```ini
   [program:bts-daemon-worker]
   process_name=%(program_name)s_%(process_num)02d
   command=php /path/to/bts-daemon/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
   autostart=true
   autorestart=true
   stopasgroup=true
   killasgroup=true
   user=www-data
   numprocs=2
   redirect_stderr=true
   stdout_logfile=/path/to/bts-daemon/storage/logs/worker.log
   stopwaitsecs=3600
   ```

   **Using nohup (Simple Background Process):**
   ```bash
   nohup php artisan queue:work > storage/logs/queue-worker.log 2>&1 &
   ```

   **Using Screen (For Development):**
   ```bash
   # Install screen if not already installed
   apt-get install screen
   
   # Start a new screen session
   screen -S queue-worker
   
   # Run the queue worker
   php artisan queue:work
   
   # Detach from screen with Ctrl+A followed by D
   # To reattach later: screen -r queue-worker
   ```

3. **Laravel Horizon** (optional, for monitoring):
   ```bash
   php artisan horizon
   ```
   
   To run Horizon in the background:
   ```bash
   nohup php artisan horizon > storage/logs/horizon.log 2>&1 &
   ```

## Implementation Details

### Command Structure

The command is implemented with a clean separation of concerns:

- `handle()`: Entry point that determines which mode to run
- `handleOnce()`: Handles one-time execution
- `handleRecurring()`: Handles recurring execution

### Background Job

The recurring functionality is implemented using a dedicated job class:

- **Class**: `ApiServiceSyncJob`
- **Location**: `app/Jobs/ApiServiceSyncJob.php`
- **Functionality**:
  - Makes HTTP requests to the test endpoint
  - Logs responses using Laravel's logging system
  - Self-dispatches with a 3-second delay after completion

## Monitoring

When running in recurring mode, you can monitor the job execution:

1. **Logs**: Check Laravel logs for request results
2. **Horizon Dashboard**: Access the Laravel Horizon dashboard to monitor queue processing:
   ```bash
   # Start Horizon
   php artisan horizon
   
   # Access dashboard at
   http://your-app-url/horizon
   ```

## Error Handling

The command and job implement proper error handling:

- All exceptions are caught and logged
- HTTP response status codes are checked
- Failed requests are properly reported

## Integration with BTS Guru Daemon

This command is part of the BTS Guru Daemon Service's API integration layer, which:

- Provides real-time data updates
- Uses Redis for caching and queue processing
- Implements background workers for continuous data polling
- Follows Laravel and PSR-12 coding standards
