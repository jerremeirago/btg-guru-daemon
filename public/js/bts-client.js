/**
 * BTS Guru WebSocket Client
 * A reusable client for connecting to BTS Guru WebSocket services
 * and handling real-time sports data updates.
 */

class BTSGuruClient {
    /**
     * Initialize the BTS Guru client
     * 
     * @param {Object} config - Configuration options
     * @param {string} config.host - WebSocket host (default: window.location.hostname)
     * @param {number} config.port - WebSocket port (default: 8080)
     * @param {string} config.key - WebSocket app key (default: 'bts_guru_app_key')
     * @param {boolean} config.debug - Enable debug logging (default: false)
     * @param {function} config.onConnect - Callback when connection is established
     * @param {function} config.onDisconnect - Callback when connection is closed
     * @param {function} config.onError - Callback when connection error occurs
     */
    constructor(config = {}) {
        this.config = {
            host: config.host || window.location.hostname,
            port: config.port || 8080,
            key: config.key || 'bts_guru_app_key',
            debug: config.debug || false,
            onConnect: config.onConnect || (() => {}),
            onDisconnect: config.onDisconnect || (() => {}),
            onError: config.onError || (() => {})
        };

        this.echo = null;
        this.channels = {};
        this.dataCache = {};
        this.isConnected = false;
        this.pendingFetches = {};
        
        // Bind methods
        this.connect = this.connect.bind(this);
        this.disconnect = this.disconnect.bind(this);
        this.subscribe = this.subscribe.bind(this);
        this.unsubscribe = this.unsubscribe.bind(this);
        this.fetchData = this.fetchData.bind(this);
        this.log = this.log.bind(this);
    }

    /**
     * Connect to the WebSocket server
     * 
     * @returns {Promise} Resolves when connected
     */
    connect() {
        return new Promise((resolve, reject) => {
            if (this.isConnected) {
                this.log('Already connected');
                resolve();
                return;
            }

            this.log('Connecting to WebSocket server...');

            try {
                // Initialize Laravel Echo
                window.Echo = new Echo({
                    broadcaster: 'pusher',  // Use pusher for Reverb compatibility
                    key: this.config.key,
                    wsHost: this.config.host,
                    wsPort: this.config.port,
                    forceTLS: false,
                    disableStats: true,
                    encrypted: false,
                    cluster: 'mt1',  // Default cluster
                    enabledTransports: ['ws', 'wss']
                });
                
                this.echo = window.Echo;
                
                // Connection events using Pusher's connection API
                this.echo.connector.pusher.connection.bind('connected', () => {
                    this.isConnected = true;
                    this.log('Connected to WebSocket server');
                    this.config.onConnect();
                    resolve();
                });
                
                this.echo.connector.pusher.connection.bind('disconnected', () => {
                    this.isConnected = false;
                    this.log('Disconnected from WebSocket server');
                    this.config.onDisconnect();
                });
                
                this.echo.connector.pusher.connection.bind('error', (error) => {
                    this.log('WebSocket error', error);
                    this.config.onError(error);
                    reject(error);
                });
                
            } catch (error) {
                this.log('Error initializing WebSocket', error);
                reject(error);
            }
        });
    }

    /**
     * Disconnect from the WebSocket server
     */
    disconnect() {
        if (!this.isConnected || !this.echo) {
            return;
        }

        this.log('Disconnecting from WebSocket server');
        
        // First leave all channels
        Object.keys(this.channels).forEach(channelName => {
            this.echo.leave(channelName);
        });
        
        // Then disconnect Echo
        this.echo.disconnect();
        
        // Clean up
        this.echo = null;
        window.Echo = null;
        this.isConnected = false;
        this.channels = {};
    }

    /**
     * Subscribe to a channel
     * 
     * @param {string} channelName - Channel name to subscribe to
     * @param {Object} handlers - Event handlers
     * @param {function} handlers.onUpdate - Called when data is updated
     * @param {function} handlers.onDataReceived - Called when full data is received
     * @returns {Object} Channel subscription
     */
    subscribe(channelName, handlers = {}) {
        if (!this.isConnected || !this.echo) {
            throw new Error('Not connected to WebSocket server');
        }

        if (this.channels[channelName]) {
            this.log(`Already subscribed to ${channelName}`);
            return this.channels[channelName];
        }

        this.log(`Subscribing to ${channelName}`);
        
        const channel = this.echo.channel(channelName);
        
        // Set up event listeners
        channel.listen('.afl.update', (data) => this.handleUpdate(channelName, data, handlers));
        channel.listen('afl.update', (data) => this.handleUpdate(channelName, data, handlers));
        
        // Store channel reference
        this.channels[channelName] = {
            name: channelName,
            channel: channel,
            handlers: handlers
        };
        
        return this.channels[channelName];
    }

    /**
     * Unsubscribe from a channel
     * 
     * @param {string} channelName - Channel to unsubscribe from
     */
    unsubscribe(channelName) {
        if (!this.channels[channelName]) {
            return;
        }

        this.log(`Unsubscribing from ${channelName}`);
        this.echo.leave(channelName);
        delete this.channels[channelName];
    }

    /**
     * Handle update event from WebSocket
     * 
     * @param {string} channelName - Channel name
     * @param {Object} data - Event data
     * @param {Object} handlers - Event handlers
     */
    handleUpdate(channelName, eventData, handlers) {
        this.log(`Received update on ${channelName}`, eventData);
        
        // Extract the actual data from the event structure
        // Laravel Reverb/Echo events might be nested differently depending on the event format
        let data = eventData;
        
        // If the data is wrapped in a data property (common with Laravel events)
        if (eventData && eventData.data) {
            data = eventData.data;
        }
        
        // Call onUpdate handler if provided
        if (handlers.onUpdate) {
            handlers.onUpdate(data);
        }
        
        // Check if we should fetch full data
        // Look for a URI or fetch URL in the data
        const fetchUrl = data.fetch;
        
        if (fetchUrl) {
            // Avoid duplicate fetches for the same data
            const fetchId = `${channelName}-${data.request_id}`;
            
            if (!this.pendingFetches[fetchId]) {
                this.pendingFetches[fetchId] = true;
                this.log(`Fetching full data from ${fetchUrl} | Fetch ID: ${fetchId}`);
                
                this.fetchData(fetchUrl)
                    .then(fullData => {
                        // Store in cache
                        this.dataCache[data.requets_id] = fullData;
                        
                        // Call onDataReceived handler if provided
                        if (handlers.onDataReceived) {
                            handlers.onDataReceived(fullData, data);
                        }
                        
                        delete this.pendingFetches[fetchId];
                    })
                    .catch(error => {
                        this.log(`Error fetching data from ${fetchUrl}`, error);
                        delete this.pendingFetches[fetchId];
                    });
            }
        }
    }

    /**
     * Fetch data from API
     * 
     * @param {string} url - URL to fetch data from
     * @returns {Promise} Resolves with fetched data
     */
    fetchData(url) {
        this.log(`Fetching data from ${url}`);
        
        return fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error ${response.status}`);
            }
            return response.json();
        });
    }

    /**
     * Log message if debug is enabled
     * 
     * @param {string} message - Message to log
     * @param {*} data - Optional data to log
     */
    log(message, data) {
        if (!this.config.debug) {
            return;
        }
        
        if (data) {
            console.log(`[BTSGuruClient] ${message}`, data);
        } else {
            console.log(`[BTSGuruClient] ${message}`);
        }
    }

    /**
     * Get cached data by ID
     * 
     * @param {string} id - Data ID
     * @returns {Object|null} Cached data or null if not found
     */
    getCachedData(id) {
        return this.dataCache[id] || null;
    }

    /**
     * Clear data cache
     */
    clearCache() {
        this.dataCache = {};
    }
}
