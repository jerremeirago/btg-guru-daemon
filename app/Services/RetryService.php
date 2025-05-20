<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class RetryService
{
    /**
     * Maximum number of retry attempts.
     *
     * @var int
     */
    protected int $maxAttempts;
    
    /**
     * Base delay between retries in milliseconds.
     *
     * @var int
     */
    protected int $baseDelayMs;
    
    /**
     * Maximum delay between retries in milliseconds.
     *
     * @var int
     */
    protected int $maxDelayMs;
    
    /**
     * Create a new retry service instance.
     *
     * @param int $maxAttempts
     * @param int $baseDelayMs
     * @param int $maxDelayMs
     * @return void
     */
    public function __construct(
        int $maxAttempts = 3,
        int $baseDelayMs = 1000,
        int $maxDelayMs = 10000
    ) {
        $this->maxAttempts = $maxAttempts;
        $this->baseDelayMs = $baseDelayMs;
        $this->maxDelayMs = $maxDelayMs;
    }
    
    /**
     * Execute a callback with retry logic.
     *
     * @param callable $callback
     * @param callable|null $shouldRetry
     * @param callable|null $onRetry
     * @return mixed
     * @throws \Exception
     */
    public function execute(
        callable $callback,
        callable $shouldRetry = null,
        callable $onRetry = null
    ) {
        $attempt = 1;
        $lastException = null;
        
        // Default shouldRetry function (retry on any exception)
        if ($shouldRetry === null) {
            $shouldRetry = function (\Throwable $e) {
                return true;
            };
        }
        
        // Default onRetry function (log the retry)
        if ($onRetry === null) {
            $onRetry = function (\Throwable $e, int $attempt, int $delayMs) {
                Log::warning("Retry attempt {$attempt} after {$delayMs}ms delay", [
                    'exception' => $e->getMessage(),
                    'attempt' => $attempt,
                    'delay_ms' => $delayMs,
                ]);
            };
        }
        
        while ($attempt <= $this->maxAttempts) {
            try {
                return $callback($attempt);
            } catch (\Throwable $e) {
                $lastException = $e;
                
                // Check if we should retry
                if ($attempt >= $this->maxAttempts || !$shouldRetry($e)) {
                    break;
                }
                
                // Calculate delay with exponential backoff and jitter
                $delayMs = $this->calculateDelay($attempt);
                
                // Notify about retry
                $onRetry($e, $attempt, $delayMs);
                
                // Sleep before retry
                usleep($delayMs * 1000);
                
                $attempt++;
            }
        }
        
        // If we've exhausted all retries, throw the last exception
        throw $lastException;
    }
    
    /**
     * Calculate delay with exponential backoff and jitter.
     *
     * @param int $attempt
     * @return int
     */
    protected function calculateDelay(int $attempt): int
    {
        // Exponential backoff: baseDelay * 2^(attempt-1)
        $delay = $this->baseDelayMs * pow(2, $attempt - 1);
        
        // Add jitter (random value between 0 and 1) to prevent thundering herd
        $jitter = mt_rand(0, 100) / 100;
        $delay = $delay * (1 + $jitter);
        
        // Cap at maximum delay
        return min((int) $delay, $this->maxDelayMs);
    }
    
    /**
     * Set maximum number of retry attempts.
     *
     * @param int $maxAttempts
     * @return $this
     */
    public function setMaxAttempts(int $maxAttempts): self
    {
        $this->maxAttempts = $maxAttempts;
        return $this;
    }
    
    /**
     * Set base delay between retries in milliseconds.
     *
     * @param int $baseDelayMs
     * @return $this
     */
    public function setBaseDelay(int $baseDelayMs): self
    {
        $this->baseDelayMs = $baseDelayMs;
        return $this;
    }
    
    /**
     * Set maximum delay between retries in milliseconds.
     *
     * @param int $maxDelayMs
     * @return $this
     */
    public function setMaxDelay(int $maxDelayMs): self
    {
        $this->maxDelayMs = $maxDelayMs;
        return $this;
    }
}
