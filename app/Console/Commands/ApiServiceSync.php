<?php

namespace App\Console\Commands;

use App\Jobs\ApiServiceSyncJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ApiServiceSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:sync {--recurring : Run the command in recurring mode using queue}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make a request to the dummy JSON test endpoint, with option to run every 20 seconds';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Check if recurring option is set
        if ($this->option('recurring')) {
            return $this->handleRecurring();
        }

        return $this->handleOnce();
    }

    /**
     * Execute the command once.
     *
     * @return int
     */
    private function handleOnce()
    {
        $this->info('Making request to dummy JSON test endpoint...');

        try {
            $response = Http::get('https://dummyjson.com/test');

            if ($response->successful()) {
                $this->info('Request successful!');
                $this->info('Response:');
                $this->line(json_encode($response->json(), JSON_PRETTY_PRINT));
                return self::SUCCESS;
            } else {
                $this->error('Request failed with status code: ' . $response->status());
                $this->line('Response: ' . $response->body());
                return self::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error('An error occurred: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Execute the command in recurring mode using queue.
     *
     * @return int
     */
    private function handleRecurring()
    {
        $this->info('Starting API sync in recurring mode (every 3 seconds)...');

        // Dispatch the job to run immediately
        ApiServiceSyncJob::dispatch();

        $this->info('Job dispatched successfully. Check logs for results.');
        $this->info('You can monitor the job in Laravel Horizon dashboard.');
        $this->info('Press Ctrl+C to stop this command, but the job will continue running in the background.');

        // Keep the command running to show that it's active
        while (true) {
            sleep(1);
        }

        return self::SUCCESS; // This will never be reached
    }
}
