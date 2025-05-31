<?php

namespace App\Console\Commands;

use App\Services\Afl\AflService;
use Illuminate\Console\Command;
use App\Models\AflApiResponse;
use App\Events\AflDataUpdate;
use Illuminate\Support\Str;
use App\Jobs\AflLiveDataSyncJob;

class FetchAflLiveDataCommand extends Command
{
    /**
     * The name and signature of the command.
     *
     * @var string
     */
    protected $signature = 'api:afl {--recurring : Run the command in recurring mode using queue}';

    /**
     * The command description.
     *
     * @var string
     */
    protected $description = 'Fetch AFL data from GoalServe API';

    protected AflService $service;

    public function __construct(AflService $aflService)
    {
        $this->service = $aflService;
        parent::__construct();
    }

    public function handle()
    {
        if ($this->option('recurring')) {
            return $this->handleRecurring();
        }

        return $this->once();
    }

    public function once(): int
    {
        $this->info('Fetching AFL data from GoalServe API...');

        // get the starting time in seconds
        $startTime = microtime(true);
        $data = $this->service->getData();
        $endTime = microtime(true);
        $responseTime = $endTime - $startTime;

        $uri = $data['uri'];

        if (empty($data['response'])) {
            $this->error('Failed to fetch AFL data');
            return Command::FAILURE;
        }

        $this->info('Successfully fetched AFL data');
        // Update database with the new content
        $response = $data['response'];

        // Create or update based on $uri
        $latestData = AflApiResponse::updateOrCreate([
            'uri' => $uri,
        ], [
            'response' => $response,
            'response_code' => $data['response_code'],
            'response_time' => round($responseTime),
            'request_id' => Str::uuid(),
        ]);

        // Broadcast the new update
        event(new AflDataUpdate($latestData));
        $this->info('Event broadcast successfully');
        // show the details like the uri, response code, and response duration
        $this->info('Event Summary');
        $this->info('URI: ' . $uri);
        $this->info('Response Code: HTTP/2 ' . $data['response_code']);
        $this->info('API call took: ' . round($responseTime) . ' seconds');


        return Command::SUCCESS;
    }

    /**
     * Execute the command in recurring mode using queue.
     *
     * @return void
     */
    private function handleRecurring()
    {
        $this->info('Starting AFL data sync in recurring mode (every 15 seconds)...');

        // Dispatch the job to run immediately
        AflLiveDataSyncJob::dispatch();

        $this->info('Job dispatched successfully. Check logs for results.');
        $this->info('You can monitor the job in Laravel Horizon dashboard.');
        $this->info('Press Ctrl+C to stop this command, but the job will continue running in the background.');

        // Keep the command running to show that it's active
        while (true) {
            sleep(1);
        }

        return self::SUCCESS;
    }
}
