<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class DebugWebsocket extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'debug:websocket {channel=sports.live.afl}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Debug WebSocket broadcasting by sending a test message';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $channel = $this->argument('channel');
        $this->info("Debugging WebSocket channel: {$channel}");
        
        // Create a simple test message
        $message = [
            'event' => 'test.message',
            'data' => json_encode([
                'timestamp' => now()->toIso8601String(),
                'message' => 'This is a test message from the debug command',
                'random_id' => uniqid()
            ]),
            'channel' => $channel
        ];
        
        $this->info('Preparing to send test message...');
        
        try {
            // Get the Redis connection from Laravel's Redis facade
            $redis = Redis::connection(config('broadcasting.connections.reverb.connection', 'default'));
            
            // Get the Redis key for the channel
            $key = "reverb:{$channel}";
            
            $this->info("Publishing to Redis key: {$key}");
            
            // Publish the message directly to Redis
            $result = $redis->publish($key, json_encode($message));
            
            if ($result) {
                $this->info("Message published successfully! Receivers: {$result}");
                $this->info("Message content: " . json_encode($message, JSON_PRETTY_PRINT));
            } else {
                $this->warn('Message published but no receivers were found.');
            }
        } catch (\Exception $e) {
            $this->error('Error publishing message: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
