<?php

namespace App\Console\Commands;

use App\Services\RapidApi\BaseballApiService;
use App\Services\RapidApi\BasketballApiService;
use App\Services\RapidApi\FootballApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class FetchSportsDataCommand extends Command
{
    /**
     * The name and signature of the command.
     *
     * @var string
     */
    protected $signature = 'api:sports 
                            {--sport=all : Sport type (football, baseball, basketball, or all)}
                            {--day= : Day of the month}
                            {--month= : Month number}
                            {--year= : Year}';

    /**
     * The command description.
     *
     * @var string
     */
    protected $description = 'Fetch sports data from RapidAPI for a specific date';

    /**
     * The baseball API service.
     *
     * @var \App\Services\RapidApi\BaseballApiService
     */
    protected BaseballApiService $baseballApiService;

    /**
     * The football API service.
     *
     * @var \App\Services\RapidApi\FootballApiService
     */
    protected FootballApiService $footballApiService;

    /**
     * The basketball API service.
     *
     * @var \App\Services\RapidApi\BasketballApiService
     */
    protected BasketballApiService $basketballApiService;

    /**
     * Create a new command instance.
     *
     * @param \App\Services\RapidApi\BaseballApiService $baseballApiService
     * @param \App\Services\RapidApi\FootballApiService $footballApiService
     * @param \App\Services\RapidApi\BasketballApiService $basketballApiService
     * @return void
     */
    public function __construct(
        BaseballApiService $baseballApiService,
        FootballApiService $footballApiService,
        BasketballApiService $basketballApiService
    ) {
        parent::__construct();
        $this->baseballApiService = $baseballApiService;
        $this->footballApiService = $footballApiService;
        $this->basketballApiService = $basketballApiService;
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
        $sport = strtolower($this->option('sport'));

        $this->info("Fetching sports data for date: {$day}/{$month}/{$year}");

        $exitCode = Command::SUCCESS;

        // Determine which sports to fetch
        $sports = [];
        if ($sport === 'all') {
            $sports = ['football', 'baseball', 'basketball'];
        } elseif (in_array($sport, ['football', 'baseball', 'basketball'])) {
            $sports = [$sport];
        } else {
            $this->error("Invalid sport type: {$sport}. Valid options are: football, baseball, basketball, all");
            return Command::FAILURE;
        }

        // Fetch data for each selected sport
        foreach ($sports as $sportType) {
            $this->info("\nProcessing {$sportType} data...");
            
            try {
                $matches = [];
                
                // Construct the URL for informational purposes
                $baseUrl = config('services.rapidapi.base_url');
                $endpoint = "api/{$sportType}/matches/{$day}/{$month}/{$year}";
                $url = $baseUrl . '/' . $endpoint;
                $this->info("Fetching from {$url}");
                
                // Call the appropriate service based on sport type
                switch ($sportType) {
                    case 'football':
                        $matches = $this->footballApiService->getMatchesByDate(
                            (int) $day,
                            (int) $month,
                            (int) $year,
                            true // bypass cache
                        );
                        break;
                    case 'baseball':
                        $matches = $this->baseballApiService->getMatchesByDate(
                            (int) $day,
                            (int) $month,
                            (int) $year,
                            true // bypass cache
                        );
                        break;
                    case 'basketball':
                        $matches = $this->basketballApiService->getMatchesByDate(
                            (int) $day,
                            (int) $month,
                            (int) $year,
                            true // bypass cache
                        );
                        break;
                }

                // Display summary of fetched data
                if (isset($matches['data']) && is_array($matches['data'])) {
                    $matchCount = count($matches['data']);
                    $this->info("Successfully fetched {$matchCount} {$sportType} matches.");
                    
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
                    $this->error("Error fetching {$sportType} data: {$matches['error']}");
                    $exitCode = Command::FAILURE;
                } else {
                    $this->info("No {$sportType} matches found for the specified date.");
                }
            } catch (\Exception $e) {
                $this->error("Failed to fetch {$sportType} data: {$e->getMessage()}");
                $exitCode = Command::FAILURE;
            }
        }

        return $exitCode;
    }
}
