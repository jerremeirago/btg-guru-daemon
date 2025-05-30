#!/bin/bash

# BTS Guru Daemon Startup Script
# This script starts all required services for the BTS Guru daemon

# Set the working directory to the project root
cd "$(dirname "$0")"

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${BLUE}Starting BTS Guru Daemon services...${NC}"

# Create logs directory if it doesn't exist
mkdir -p ./storage/logs/services

# Function to start a service in the background
start_service() {
    local service_name=$1
    local command=$2
    local log_file="./storage/logs/services/${service_name}.log"
    
    echo -e "${YELLOW}Starting ${service_name}...${NC}"
    nohup $command > "$log_file" 2>&1 &
    local pid=$!
    echo $pid > "./storage/logs/services/${service_name}.pid"
    echo -e "${GREEN}${service_name} started with PID ${pid}${NC}"
}

# Start Reverb WebSocket server
start_service "reverb" "php artisan reverb:start"

# Wait a moment for Reverb to initialize
sleep 2

# Start Queue Worker
start_service "queue" "php artisan queue:work --queue=default,broadcasts --tries=3"

# Wait a moment for Queue to initialize
sleep 2

# Run the AFL data fetcher once
echo -e "${YELLOW}Fetching initial AFL data...${NC}"
php artisan api:afl
echo -e "${GREEN}Initial AFL data fetch completed${NC}"

# Set up a cron-like job to fetch AFL data every minute
start_service "afl_fetcher" "watch -n 60 php artisan api:afl"

echo -e "${BLUE}All services started successfully!${NC}"
echo -e "${YELLOW}To view logs:${NC}"
echo -e "  Reverb: tail -f ./storage/logs/services/reverb.log"
echo -e "  Queue: tail -f ./storage/logs/services/queue.log"
echo -e "  AFL Fetcher: tail -f ./storage/logs/services/afl_fetcher.log"

echo -e "${YELLOW}To stop all services:${NC}"
echo -e "  ./shutdown.sh"
