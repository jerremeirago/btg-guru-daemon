<?php

namespace App\Console\Commands;

use App\Services\Afl\AflService;
use Illuminate\Console\Command;
use App\Models\AflApiResponse;
use Illuminate\Support\Str;
use App\Models\Types\AflRequestType;
use App\Jobs\AflStandingSyncJob;

class FetchAflStandingsCommand extends Command
{
    /**
     * The name and signature of the command.
     *
     * @var string
     */
    protected $signature = 'api:afl:standings {--recurring : Run the command in recurring mode using queue}';

    /**
     * The command description.
     *
     * @var string
     */
    protected $description = 'Fetch AFL standings from GoalServe API';

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
        $this->info('Fetching AFL standings from GoalServe API...');

        // get the starting time in seconds
        $startTime = microtime(true);
        $data = $this->service->getApiStandings();
        $endTime = microtime(true);
        $responseTime = $endTime - $startTime;

        $uri = $data['uri'];

        if (empty($data['response'])) {
            $this->error('Failed to fetch AFL standings');
            return Command::FAILURE;
        }

        $this->info('Successfully fetched AFL standings');
        // Update database with the new content
        $response = $data['response'];

        // Create or update based on $uri
        AflApiResponse::updateOrCreate([
            'uri' => $uri,
        ], [
            'response' => $response,
            'response_code' => $data['response_code'],
            'response_time' => round($responseTime),
            'request_id' => Str::uuid(),
            'request_type' => AflRequestType::Standings->name,
        ]);

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
        $this->info('Starting AFL data sync in recurring mode (every 12 hours)...');

        // Dispatch the job to run immediately
        // @TODO: Replace this with its own job
        AflStandingSyncJob::dispatch();

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
