# BTS Guru Daemon Service - API Authentication & Rate Limiting

## Overview

This document outlines the API authentication and rate limiting strategy for the BTS Guru Daemon Service, which provides secure access to sports data while preventing abuse.

## Authentication Architecture

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│             │     │             │     │             │
│  Client     │────▶│  Sanctum    │────▶│  Protected  │
│  Request    │     │  Middleware │     │  Resources  │
│             │     │             │     │             │
└─────────────┘     └─────────────┘     └─────────────┘
                           │
                           ▼
                    ┌─────────────┐
                    │             │
                    │  Rate Limit │
                    │  Middleware │
                    │             │
                    └─────────────┘
```

## Authentication Implementation

The BTS Guru Daemon Service uses Laravel Sanctum for API token authentication, which provides a lightweight solution for API token authentication.

### API Key Generation

API keys are generated for users through the admin dashboard or API:

```php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiKeyController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);
        
        $token = $request->user()->createToken(
            $request->name,
            ['*'], // Abilities/permissions
            $request->expires_at
        );
        
        return response()->json([
            'token' => $token->plainTextToken,
            'name' => $request->name,
            'expires_at' => $token->accessToken->expires_at,
        ]);
    }
    
    public function index(Request $request)
    {
        return response()->json([
            'tokens' => $request->user()->tokens()->get(),
        ]);
    }
    
    public function destroy(Request $request, $id)
    {
        $request->user()->tokens()->where('id', $id)->delete();
        
        return response()->json([
            'message' => 'Token deleted successfully',
        ]);
    }
}
```

### Token Storage

API tokens are stored in the `personal_access_tokens` table, which is created by Laravel Sanctum's migration:

```php
Schema::create('personal_access_tokens', function (Blueprint $table) {
    $table->id();
    $table->morphs('tokenable');
    $table->string('name');
    $table->string('token', 64)->unique();
    $table->text('abilities')->nullable();
    $table->timestamp('last_used_at')->nullable();
    $table->timestamp('expires_at')->nullable();
    $table->timestamps();
});
```

### Authentication Middleware

API routes are protected using Sanctum's authentication middleware:

```php
// routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/sports', [SportController::class, 'index']);
    Route::get('/sports/{sport}/leagues', [LeagueController::class, 'index']);
    Route::get('/sports/{sport}/leagues/{league}/matches', [MatchController::class, 'index']);
    Route::get('/sports/{sport}/leagues/{league}/matches/{match}', [MatchController::class, 'show']);
    
    // WebSocket subscription management
    Route::post('/subscribe', [SubscriptionController::class, 'store']);
    Route::delete('/unsubscribe', [SubscriptionController::class, 'destroy']);
    
    // User dashboard
    Route::get('/user/usage', [UsageController::class, 'index']);
    Route::get('/user/tokens', [ApiKeyController::class, 'index']);
    Route::post('/user/tokens', [ApiKeyController::class, 'store']);
    Route::delete('/user/tokens/{id}', [ApiKeyController::class, 'destroy']);
});
```

### Token Validation

Incoming requests are validated using Sanctum's authentication middleware:

```php
// app/Http/Kernel.php
protected $middlewareGroups = [
    'api' => [
        \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        'throttle:api',
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ],
];
```

## Rate Limiting

The BTS Guru Daemon Service implements a tiered rate limiting strategy based on user subscription levels.

### Rate Limit Configuration

Rate limits are defined in the `config/sanctum.php` file:

```php
'rate_limits' => [
    'basic' => [
        'limit' => 60,
        'period' => 60, // 60 requests per minute
    ],
    'premium' => [
        'limit' => 300,
        'period' => 60, // 300 requests per minute
    ],
    'enterprise' => [
        'limit' => 1000,
        'period' => 60, // 1000 requests per minute
    ],
],
```

### Rate Limit Middleware

A custom middleware handles rate limiting based on user subscription tier:

```php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;

class TieredRateLimiting
{
    protected $rateLimiter;
    
    public function __construct(RateLimiter $rateLimiter)
    {
        $this->rateLimiter = $rateLimiter;
    }
    
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'error' => 'Unauthenticated',
            ], 401);
        }
        
        $tier = $user->subscription_tier ?? 'basic';
        $config = Config::get("sanctum.rate_limits.{$tier}");
        
        $key = 'api:' . $user->id;
        $maxAttempts = $config['limit'] ?? 60;
        $decaySeconds = $config['period'] ?? 60;
        
        if ($this->rateLimiter->tooManyAttempts($key, $maxAttempts)) {
            return response()->json([
                'error' => 'Too Many Requests',
                'message' => 'API rate limit exceeded',
                'retry_after' => $this->rateLimiter->availableIn($key),
            ], 429);
        }
        
        $this->rateLimiter->hit($key, $decaySeconds);
        
        $response = $next($request);
        
        $response->headers->set(
            'X-RateLimit-Limit',
            $maxAttempts
        );
        
        $response->headers->set(
            'X-RateLimit-Remaining',
            $maxAttempts - $this->rateLimiter->attempts($key)
        );
        
        return $response;
    }
}
```

### Applying Rate Limiting

The rate limiting middleware is applied to API routes:

```php
// routes/api.php
Route::middleware(['auth:sanctum', 'tiered.rate.limit'])->group(function () {
    // API routes
});
```

### Rate Limit Headers

API responses include rate limit headers to help clients manage their usage:

- `X-RateLimit-Limit`: Maximum number of requests allowed per period
- `X-RateLimit-Remaining`: Number of requests remaining in the current period
- `X-RateLimit-Reset`: Time when the rate limit will reset (Unix timestamp)

## WebSocket Authentication

WebSocket connections are authenticated using Laravel Reverb's authentication mechanism, which leverages Sanctum tokens.

### Channel Authorization

Channel authorization is defined in `routes/channels.php`:

```php
Broadcast::channel('sports.{sport}.leagues.{league}.matches.{match}', function ($user, $sport, $league, $match) {
    return true; // Public channel, available to all
});

Broadcast::channel('private-sports.{sport}.leagues.{league}.matches.{match}', function ($user, $sport, $league, $match) {
    // Check if user has access to this private channel
    return $user->hasActiveSubscription();
});

Broadcast::channel('presence-sports.{sport}.leagues.{league}.matches.{match}', function ($user, $sport, $league, $match) {
    if ($user->hasActiveSubscription()) {
        // Return user data to be included in presence channel
        return [
            'id' => $user->id,
            'name' => $user->name,
        ];
    }
    
    return false;
});
```

### WebSocket Connection Authentication

Clients authenticate WebSocket connections using their Sanctum token:

```javascript
// Client-side authentication
const echo = new Echo({
    broadcaster: 'reverb',
    key: 'your_reverb_app_key',
    wsHost: window.location.hostname,
    wsPort: 8080,
    forceTLS: false,
    disableStats: true,
    enabledTransports: ['ws', 'wss'],
    auth: {
        headers: {
            Authorization: `Bearer ${apiToken}`,
            Accept: 'application/json',
        },
    },
});
```

## Subscription Tiers

The BTS Guru Daemon Service offers multiple subscription tiers with different capabilities:

### Basic Tier
- 60 API requests per minute
- Public channels only
- Standard polling frequency
- 24-hour data retention

### Premium Tier
- 300 API requests per minute
- Public and private channels
- Enhanced polling frequency
- 7-day data retention
- Additional sports and leagues

### Enterprise Tier
- 1000+ API requests per minute
- Public, private, and presence channels
- Highest polling frequency
- 30-day data retention
- All sports and leagues
- Custom webhook integration
- Dedicated support

## Usage Tracking

The service tracks API usage for billing and monitoring purposes:

```php
namespace App\Http\Middleware;

use App\Models\ApiUsage;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackApiUsage
{
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        
        $response = $next($request);
        
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        
        if ($request->user()) {
            ApiUsage::create([
                'user_id' => $request->user()->id,
                'endpoint' => $request->path(),
                'method' => $request->method(),
                'status_code' => $response->getStatusCode(),
                'response_time' => $responseTime,
            ]);
        }
        
        return $response;
    }
}
```

## Security Considerations

The API authentication system implements several security measures:

1. **Token Hashing**: API tokens are hashed in the database
2. **Token Expiration**: Tokens can be configured to expire automatically
3. **Token Abilities**: Tokens can be restricted to specific abilities/permissions
4. **HTTPS Only**: API endpoints are only accessible via HTTPS
5. **CORS Protection**: Cross-Origin Resource Sharing is properly configured
6. **IP Blocking**: Automatic blocking of IPs with suspicious activity

## API Documentation

The API is documented using OpenAPI/Swagger:

```php
/**
 * @OA\Info(
 *     title="BTS Guru Daemon API",
 *     version="1.0.0",
 *     description="API for accessing real-time sports data",
 *     @OA\Contact(
 *         email="support@btsguru.com"
 *     )
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */

/**
 * @OA\Get(
 *     path="/api/sports",
 *     summary="Get all sports",
 *     tags={"Sports"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(ref="#/components/schemas/Sport")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthenticated"
 *     ),
 *     @OA\Response(
 *         response=429,
 *         description="Too Many Requests"
 *     )
 * )
 */
```

## User Dashboard

The service includes a user dashboard for managing API keys and monitoring usage:

1. **API Key Management**: Create, view, and revoke API keys
2. **Usage Statistics**: View API usage and rate limit status
3. **Subscription Management**: Upgrade or downgrade subscription tier
4. **WebSocket Subscriptions**: Manage WebSocket channel subscriptions
5. **Billing Information**: View and update billing details
