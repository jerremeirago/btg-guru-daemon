<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;

class TieredRateLimiting
{
    /**
     * The rate limiter instance.
     *
     * @var \Illuminate\Cache\RateLimiter
     */
    protected $rateLimiter;
    
    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Cache\RateLimiter  $rateLimiter
     * @return void
     */
    public function __construct(RateLimiter $rateLimiter)
    {
        $this->rateLimiter = $rateLimiter;
    }
    
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'error' => 'Unauthenticated',
                'message' => 'API authentication required'
            ], 401);
        }
        
        // Get the user's subscription tier (default to 'basic' if not set)
        $tier = $user->subscription_tier ?? 'basic';
        
        // Get rate limit configuration for this tier
        $tierLimits = [
            'basic' => [
                'limit' => 60,    // 60 requests per minute
                'period' => 60,
            ],
            'premium' => [
                'limit' => 300,   // 300 requests per minute
                'period' => 60,
            ],
            'enterprise' => [
                'limit' => 1000,  // 1000 requests per minute
                'period' => 60,
            ],
        ];
        
        $config = $tierLimits[$tier] ?? $tierLimits['basic'];
        
        // Create a unique key for this user
        $key = 'api:' . $user->id;
        $maxAttempts = $config['limit'];
        $decaySeconds = $config['period'];
        
        // Check if the user has exceeded their rate limit
        if ($this->rateLimiter->tooManyAttempts($key, $maxAttempts)) {
            return response()->json([
                'error' => 'Too Many Requests',
                'message' => 'API rate limit exceeded',
                'retry_after' => $this->rateLimiter->availableIn($key),
                'tier' => $tier,
                'limit' => $maxAttempts,
                'period' => $decaySeconds . ' seconds',
            ], 429);
        }
        
        // Increment the rate limiter counter
        $this->rateLimiter->hit($key, $decaySeconds);
        
        $response = $next($request);
        
        // Add rate limit headers to the response
        $response->headers->set('X-RateLimit-Limit', $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', $maxAttempts - $this->rateLimiter->attempts($key));
        $response->headers->set('X-RateLimit-Reset', $this->rateLimiter->availableIn($key));
        
        return $response;
    }
}
