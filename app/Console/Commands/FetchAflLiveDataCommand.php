<?php

namespace App\Console\Commands;

use App\Services\Afl\AflService;
use Illuminate\Console\Command;
use App\Models\AflApiResponse;
use App\Events\AflDataUpdate;

class FetchAflLiveDataCommand extends Command
{
    /**
     * The name and signature of the command.
     *
     * @var string
     */
    protected $signature = 'api:afl';

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

    public function handle(): int
    {
        $this->info('Fetching AFL data from GoalServe API...');

        $data = $this->service->getData();
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
        ]);

        // Broadcast the new update
        event(new AflDataUpdate($latestData));
        $this->info('Event broadcast successfully');

        return Command::SUCCESS;
    }
}
