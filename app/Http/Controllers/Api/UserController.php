<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Get the authenticated user's profile
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();
        
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'subscription_tier' => $user->subscription_tier,
            'has_active_subscription' => $user->hasActiveSubscription(),
            'initials' => $user->initials(),
        ]);
    }
    
    /**
     * Get the user's subscription information
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function subscription(Request $request): JsonResponse
    {
        $user = $request->user();
        
        return response()->json([
            'tier' => $user->subscription_tier,
            'active' => $user->hasActiveSubscription(),
            'expires_at' => $user->subscription_expires_at ? $user->subscription_expires_at->toIso8601String() : null,
        ]);
    }
}
