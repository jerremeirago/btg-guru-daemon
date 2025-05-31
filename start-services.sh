#!/bin/bash

# Laravel Background Services Script
# This script starts AFL data fetching, Reverb WebSocket server, and queue listener
# All services run in the background with proper logging

# Set script directory and log directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
LOG_DIR="/var/www/html/storage/logs"
PID_DIR="/tmp"

# Create log directory if it doesn't exist
mkdir -p "$LOG_DIR"

# Function to log messages
log_message() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_DIR/services.log"
}

# Function to start a service in background
start_service() {
    local service_name=$1
    local command=$2
    local log_file="$LOG_DIR/${service_name}.log"
    local pid_file="$PID_DIR/${service_name}.pid"
    
    log_message "Starting $service_name..."
    
    # Start the service in background and capture PID
    nohup $command >> "$log_file" 2>&1 &
    local pid=$!
    
    # Save PID to file
    echo $pid > "$pid_file"
    
    # Wait a moment to check if process started successfully
    sleep 2
    
    if kill -0 $pid 2>/dev/null; then
        log_message "$service_name started successfully (PID: $pid)"
        log_message "Logs: $log_file"
    else
        log_message "ERROR: Failed to start $service_name"
        return 1
    fi
}

# Function to stop all services
stop_services() {
    log_message "Stopping all services..."
    
    # Stop AFL data fetching
    if [ -f "$PID_DIR/afl-data.pid" ]; then
        local pid=$(cat "$PID_DIR/afl-data.pid")
        if kill -0 $pid 2>/dev/null; then
            kill -TERM $pid
            log_message "Stopped AFL data service (PID: $pid)"
        fi
        rm -f "$PID_DIR/afl-data.pid"
    fi
    
    # Stop Reverb server
    if [ -f "$PID_DIR/reverb.pid" ]; then
        local pid=$(cat "$PID_DIR/reverb.pid")
        if kill -0 $pid 2>/dev/null; then
            kill -TERM $pid
            log_message "Stopped Reverb server (PID: $pid)"
        fi
        rm -f "$PID_DIR/reverb.pid"
    fi
    
    # Stop queue listener
    if [ -f "$PID_DIR/queue.pid" ]; then
        local pid=$(cat "$PID_DIR/queue.pid")
        if kill -0 $pid 2>/dev/null; then
            kill -TERM $pid
            log_message "Stopped queue listener (PID: $pid)"
        fi
        rm -f "$PID_DIR/queue.pid"
    fi
    
    log_message "All services stopped"
}

# Function to check service status
check_status() {
    log_message "Checking service status..."
    
    # Check AFL data service
    if [ -f "$PID_DIR/afl-data.pid" ]; then
        local pid=$(cat "$PID_DIR/afl-data.pid")
        if kill -0 $pid 2>/dev/null; then
            log_message "AFL data service is running (PID: $pid)"
        else
            log_message "AFL data service is not running"
        fi
    else
        log_message "AFL data service is not running"
    fi
    
    # Check Reverb server
    if [ -f "$PID_DIR/reverb.pid" ]; then
        local pid=$(cat "$PID_DIR/reverb.pid")
        if kill -0 $pid 2>/dev/null; then
            log_message "Reverb server is running (PID: $pid)"
        else
            log_message "Reverb server is not running"
        fi
    else
        log_message "Reverb server is not running"
    fi
    
    # Check queue listener
    if [ -f "$PID_DIR/queue.pid" ]; then
        local pid=$(cat "$PID_DIR/queue.pid")
        if kill -0 $pid 2>/dev/null; then
            log_message "Queue listener is running (PID: $pid)"
        else
            log_message "Queue listener is not running"
        fi
    else
        log_message "Queue listener is not running"
    fi
}

# Function to restart all services
restart_services() {
    log_message "Restarting all services..."
    stop_services
    sleep 3
    start_all_services
}

# Function to start all services
start_all_services() {
    log_message "Starting all Laravel background services..."
    
    # Clear any previous caches
    log_message "Clearing caches..."
    php artisan config:clear >> "$LOG_DIR/services.log" 2>&1
    php artisan cache:clear >> "$LOG_DIR/services.log" 2>&1
    
    # Start Reverb server first (other services may depend on it)
    start_service "reverb" "php artisan reverb:start"
    
    # Wait for Reverb to be fully ready
    sleep 3
    
    # Start queue listener
    start_service "queue" "php artisan queue:listen"
    
    # Wait for queue to be ready
    sleep 2
    
    # Start AFL data fetching (this should be last as it may depend on the others)
    start_service "afl-data" "php artisan api:afl --recurring"
    
    log_message "All services startup sequence completed"
    log_message "You can monitor logs in: $LOG_DIR/"
    log_message "Use '$0 status' to check service status"
    log_message "Use '$0 stop' to stop all services"
}

# Trap to handle script interruption
trap 'echo "Script interrupted. Stopping services..."; stop_services; exit 1' INT TERM

# Main script logic
case "${1:-start}" in
    start)
        start_all_services
        ;;
    stop)
        stop_services
        ;;
    restart)
        restart_services
        ;;
    status)
        check_status
        ;;
    *)
        echo "Usage: $0 {start|stop|restart|status}"
        echo ""
        echo "Commands:"
        echo "  start   - Start all services (default)"
        echo "  stop    - Stop all services"
        echo "  restart - Restart all services"
        echo "  status  - Check service status"
        exit 1
        ;;
esac