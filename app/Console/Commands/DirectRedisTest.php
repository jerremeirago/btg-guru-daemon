<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class DirectRedisTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:redis';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Directly publish to Redis for WebSocket testing';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Publishing directly to Redis...');
        
        $channel = 'sports.live.afl';
        $event = 'afl.update';
        
        // Create a simple message
        $message = [
            'event' => $event,
            'data' => json_encode([
                'message' => 'Direct Redis test at ' . now()->toIso8601String(),
                'id' => uniqid()
            ]),
            'channel' => $channel
        ];
        
        try {
            // Get Redis connection
            $redis = Redis::connection();
            
            // Publish to the Reverb channel format
            $reverbChannel = "reverb:{$channel}";
            $this->info("Publishing to Redis channel: {$reverbChannel}");
            
            // Publish the message
            $result = $redis->publish($reverbChannel, json_encode($message));
            
            if ($result > 0) {
                $this->info("Message published successfully to {$result} subscribers");
            } else {
                $this->warn("Message published but no subscribers were found");
            }
            
            $this->info("Message content: " . json_encode($message, JSON_PRETTY_PRINT));
            
        } catch (\Exception $e) {
            $this->error("Error publishing to Redis: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
