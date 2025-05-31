#!/bin/bash

# To run: nohup ./background-startup.sh > /dev/null 2>&1 &

# BTS Guru Daemon Background Startup Script
# This script starts all required services for the BTS Guru daemon in complete background mode

# Set the working directory to the project root
cd "$(dirname "$0")"

# Create logs directory if it doesn't exist
mkdir -p ./storage/logs/services

# Main log file
MAIN_LOG="./storage/logs/services/daemon-startup.log"

# Redirect all output to the log file
exec > "$MAIN_LOG" 2>&1

# Log start time
echo "[$(date)] Starting BTS Guru Daemon services in background mode..."

# Function to start a service in the background
start_service() {
    local service_name=$1
    local command=$2
    local log_file="./storage/logs/services/${service_name}.log"
    
    echo "[$(date)] Starting ${service_name}..."
    nohup $command > "$log_file" 2>&1 &
    local pid=$!
    echo $pid > "./storage/logs/services/${service_name}.pid"
    echo "[$(date)] ${service_name} started with PID ${pid}"
}

# Start Reverb WebSocket server
start_service "reverb" "php artisan reverb:start"

# Wait a moment for Reverb to initialize
sleep 2

# Start Queue Worker
start_service "queue" "php artisan queue:work --queue=default,broadcasts --tries=3"
start_service "queue" "php artisan queue:listen"

# Wait a moment for Queue to initialize
sleep 2

# Run the AFL data fetcher once
echo "[$(date)] Fetching initial AFL data..."
php artisan api:afl --recurring >> "$MAIN_LOG" 2>&1
echo "[$(date)] Initial AFL data fetch completed"

# Set up a cron-like job to fetch AFL data every minute
start_service "afl_fetcher" "watch -n 60 php artisan api:afl --recurring"

echo "[$(date)] All services started successfully!"
echo "[$(date)] To view logs:"
echo "  Main log: tail -f $MAIN_LOG"
echo "  Reverb: tail -f ./storage/logs/services/reverb.log"
echo "  Queue: tail -f ./storage/logs/services/queue.log"
echo "  AFL Fetcher: tail -f ./storage/logs/services/afl_fetcher.log"

echo "[$(date)] To stop all services:"
echo "  ./shutdown.sh"
