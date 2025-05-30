#!/bin/bash

# BTS Guru Daemon Shutdown Script
# This script stops all services started by startup.sh

# Set the working directory to the project root
cd "$(dirname "$0")"

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${BLUE}Stopping BTS Guru Daemon services...${NC}"

# Function to stop a service
stop_service() {
    local service_name=$1
    local pid_file="./storage/logs/services/${service_name}.pid"
    
    if [ -f "$pid_file" ]; then
        local pid=$(cat "$pid_file")
        echo -e "${YELLOW}Stopping ${service_name} (PID: ${pid})...${NC}"
        
        if kill -0 $pid 2>/dev/null; then
            kill $pid
            sleep 1
            
            # Check if process is still running and force kill if necessary
            if kill -0 $pid 2>/dev/null; then
                echo -e "${YELLOW}Process still running, force killing...${NC}"
                kill -9 $pid
            fi
            
            echo -e "${GREEN}${service_name} stopped${NC}"
        else
            echo -e "${RED}${service_name} (PID: ${pid}) is not running${NC}"
        fi
        
        rm "$pid_file"
    else
        echo -e "${RED}${service_name} PID file not found${NC}"
    fi
}

# Stop all services
stop_service "reverb"
stop_service "queue"
stop_service "afl_fetcher"

# Additional cleanup for any watch processes
echo -e "${YELLOW}Cleaning up any remaining watch processes...${NC}"
pkill -f "watch -n 60 php artisan api:afl" 2>/dev/null

echo -e "${BLUE}All services stopped successfully!${NC}"
