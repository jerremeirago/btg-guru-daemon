<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\AflApiResponse;
use App\Services\Afl\AflService;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// Public channels for sports data - available to all users
// Broadcast::channel('sports.{sport}', function ($user, $sport) {
//     return true; // Public channel, available to all
// });

Broadcast::channel('sports.live.afl', function () {
    return true;

    // return [
    //     'id' => $latestData->id,
    //     'uri' => $latestData->uri,
    //     'data' => $latestData->response,
    //     'updated_at' => $latestData->updated_at->toIso8601String(),
    // ];
});

// Broadcast::channel('sports.{sport}.leagues.{league}', function ($user, $sport, $league) {
//     return true; // Public channel, available to all
// });

// Broadcast::channel('sports.{sport}.leagues.{league}.matches.{match}', function ($user, $sport, $league, $match) {
//     return true; // Public channel, available to all
// });

// // Private channels for sports data - require authentication
// Broadcast::channel('private-sports.{sport}', function ($user, $sport) {
//     return $user->hasActiveSubscription();
// });

// Broadcast::channel('private-sports.{sport}.leagues.{league}', function ($user, $sport, $league) {
//     return $user->hasActiveSubscription();
// });

// Broadcast::channel('private-sports.{sport}.leagues.{league}.matches.{match}', function ($user, $sport, $league, $match) {
//     return $user->hasActiveSubscription();
// });

// // Presence channels for tracking users watching specific matches
// Broadcast::channel('presence-sports.{sport}.leagues.{league}.matches.{match}', function ($user, $sport, $league, $match) {
//     if ($user->hasActiveSubscription()) {
//         return [
//             'id' => $user->id,
//             'name' => $user->name,
//             'subscription_tier' => $user->subscription_tier ?? 'basic',
//         ];
//     }

//     return false;
// });
