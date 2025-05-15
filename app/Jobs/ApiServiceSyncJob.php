<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApiServiceSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    private $retryAfterSeconds;

    /**
     * Create a new job instance.
     *
     * @param int $retryAfterSeconds
     * @return void
     */
    public function __construct(int $retryAfterSeconds = 3)
    {
        $this->retryAfterSeconds = $retryAfterSeconds;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            Log::info('Making request to dummy JSON test endpoint...');

            $response = Http::get('https://dummyjson.com/test');

            if ($response->successful()) {
                Log::info('Request successful: ' . json_encode($response->json()));
            } else {
                Log::error('Request failed with status code: ' . $response->status());
                Log::error('Response: ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('An error occurred: ' . $e->getMessage());
        }

        // Re-dispatch the job to run again after the specified interval
        self::dispatch()->delay(now()->addSeconds($this->retryAfterSeconds));
    }
}
