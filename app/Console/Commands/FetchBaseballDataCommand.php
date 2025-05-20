<?php

namespace App\Console\Commands;

use App\Services\RapidApi\BaseballApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class FetchBaseballDataCommand extends Command
{
    /**
     * The name and signature of the command.
     *
     * @var string
     */
    protected $signature = 'api:baseball 
                            {--day= : Day of the month}
                            {--month= : Month number}
                            {--year= : Year}';

    /**
     * The command description.
     *
     * @var string
     */
    protected $description = 'Fetch baseball data from RapidAPI for a specific date';

    /**
     * The baseball API service.
     *
     * @var \App\Services\RapidApi\BaseballApiService
     */
    protected BaseballApiService $baseballApiService;

    /**
     * Create a new command instance.
     *
     * @param \App\Services\RapidApi\BaseballApiService $baseballApiService
     * @return void
     */
    public function __construct(BaseballApiService $baseballApiService)
    {
        parent::__construct();
        $this->baseballApiService = $baseballApiService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        // Get date parameters from options or use current date
        $day = $this->option('day') ?? Carbon::now()->day;
        $month = $this->option('month') ?? Carbon::now()->month;
        $year = $this->option('year') ?? Carbon::now()->year;

        $this->info("Fetching baseball data for date: {$day}/{$month}/{$year}");

        // Construct the URL for informational purposes
        $baseUrl = config('services.rapidapi.base_url');
        $endpoint = "api/baseball/matches/{$day}/{$month}/{$year}";
        $url = $baseUrl . '/' . $endpoint;
        $this->info("Fetching from {$url}");

        try {
            // Fetch baseball matches for the specified date using the service
            // Bypass cache to get fresh data from the API
            $matches = $this->baseballApiService->getMatchesByDate(
                (int) $day,
                (int) $month,
                (int) $year,
                true // bypass cache
            );

            // Display summary of fetched data
            if (isset($matches['data']) && is_array($matches['data'])) {
                $matchCount = count($matches['data']);
                $this->info("Successfully fetched {$matchCount} baseball matches.");

                // Display match details in a table
                if ($matchCount > 0) {
                    $tableData = [];
                    foreach ($matches['data'] as $match) {
                        $tableData[] = [
                            'id' => $match['id'] ?? 'N/A',
                            'status' => $match['status'] ?? 'N/A',
                            'home_team' => $match['home_team']['name'] ?? 'N/A',
                            'away_team' => $match['away_team']['name'] ?? 'N/A',
                            'score' => ($match['home_score'] ?? '?') . ' - ' . ($match['away_score'] ?? '?'),
                        ];
                    }

                    $this->table(
                        ['ID', 'Status', 'Home Team', 'Away Team', 'Score'],
                        $tableData
                    );
                }
            } elseif (isset($matches['error'])) {
                $this->error("Error fetching data: {$matches['error']}");
                return Command::FAILURE;
            } else {
                $this->info("No matches found for the specified date.");
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to fetch baseball data: {$e->getMessage()}");
            $this->error($e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}
