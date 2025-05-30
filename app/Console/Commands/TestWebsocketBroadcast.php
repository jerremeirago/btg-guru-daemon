<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Broadcast;

class TestWebsocketBroadcast extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:websocket';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a simple test message to WebSocket channels';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Broadcasting simple test message...');
        
        // Simple direct broadcast without using an event class
        Broadcast::channel('sports.live.afl', function() {
            return true;
        });
        
        $data = [
            'message' => 'This is a test message',
            'timestamp' => now()->toIso8601String(),
            'id' => uniqid()
        ];
        
        try {
            // Direct broadcast to the channel
            Broadcast::channel('sports.live.afl')->broadcast('test.message', $data);
            $this->info('Simple test message broadcast successfully');
        } catch (\Exception $e) {
            $this->error('Error broadcasting test message: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
