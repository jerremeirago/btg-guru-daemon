<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Handle user login request
     * 
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function login(Request $request): JsonResponse
    {
        // Implement rate limiting to prevent brute force attacks
        $key = 'login.' . $request->ip();
        
        // Allow 5 login attempts per minute
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            
            return response()->json([
                'message' => 'Too many login attempts. Please try again in ' . $seconds . ' seconds.',
                'seconds_remaining' => $seconds
            ], 429);
        }
        
        // Validate request
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);
        
        // Attempt to authenticate
        if (!Auth::attempt($request->only('email', 'password'))) {
            // Increment the rate limiter counter
            RateLimiter::hit($key);
            
            return response()->json([
                'message' => 'Invalid username or password',
                'errors' => [
                    'email' => ['The provided credentials are incorrect.']
                ]
            ], 401);
        }
        
        // Reset rate limiter
        RateLimiter::clear($key);
        
        // Get authenticated user
        $user = User::where('email', $request->email)->first();
        
        // Check if email is verified
        if (!$user->email_verified_at) {
            return response()->json([
                'message' => 'Email not verified. Please verify your email before logging in.'
            ], 403);
        }
        
        // Revoke existing tokens (optional)
        // $user->tokens()->delete();
        
        // Create new token
        $token = $user->createToken('auth_token')->plainTextToken;
        
        return response()->json([
            'token' => $token,
            'email' => $user->email
        ]);
    }
    
    /**
     * Handle user logout request
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        // Revoke the token that was used to authenticate the current request
        if ($request->user()) {
            $request->user()->tokens()->where('id', $request->user()->currentAccessToken()->id)->delete();
        }
        
        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }
}
