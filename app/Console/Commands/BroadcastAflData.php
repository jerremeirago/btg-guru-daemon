<?php

namespace App\Console\Commands;

use App\Events\AflDataUpdate;
use App\Models\AflApiResponse;
use Illuminate\Console\Command;

class BroadcastAflData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'broadcast:afl-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Broadcast the latest AFL data to WebSocket channel';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Fetching latest AFL data...');
        $latestData = AflApiResponse::query()->orderBy('updated_at', 'desc')->first();

        if (!$latestData) {
            $this->error('No AFL data found to broadcast');
            return 1;
        }

        $this->info('Found data: ID=' . $latestData->id . ', Last Updated=' . $latestData->updated_at);
        
        try {
            $this->info('Broadcasting event to channel: sports.live.afl');
            event(new AflDataUpdate($latestData));
            $this->info('Event broadcast successfully');
        } catch (\Exception $e) {
            $this->error('Error broadcasting event: ' . $e->getMessage());
            return 1;
        }

        $this->info('AFL data has been broadcast to WebSocket channel: sports.live.afl');
        return 0;
    }
}
