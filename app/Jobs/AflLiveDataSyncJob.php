<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/**
 * Job to periodically fetch AFL live data.
 * 
 * This job executes the FetchAflLiveDataCommand and then
 * re-dispatches itself to run again after a specified interval.
 */
class AflLiveDataSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    private int $retryAfterSeconds;

    /**
     * Create a new job instance.
     *
     * @param int $retryAfterSeconds
     * @return void
     */
    public function __construct(int $retryAfterSeconds = 15)
    {
        $this->retryAfterSeconds = $retryAfterSeconds;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        try {
            Log::info('Running FetchAflLiveDataCommand...');

            // Execute the AFL data fetch command
            $exitCode = Artisan::call('api:afl');

            if ($exitCode === 0) {
                Log::info('FetchAflLiveDataCommand completed successfully');
                Log::info('Command output: ' . Artisan::output());
            } else {
                Log::error('FetchAflLiveDataCommand failed with exit code: ' . $exitCode);
                Log::error('Command output: ' . Artisan::output());
            }
        } catch (\Exception $e) {
            Log::error('An error occurred while running FetchAflLiveDataCommand: ' . $e->getMessage());
            Log::error('Exception trace: ' . $e->getTraceAsString());
        }

        // Re-dispatch the job to run again after the specified interval
        self::dispatch()->delay(now()->addSeconds($this->retryAfterSeconds));
    }
}
