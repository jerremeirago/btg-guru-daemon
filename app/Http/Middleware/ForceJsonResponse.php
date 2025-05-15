<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceJsonResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        // Get the response
        $response = $next($request);

        // If the response is a redirect and we're in an API route,
        // convert it to a JSON response with a 404 status code
        if (
            $response instanceof Response &&
            $request->is('api/*') &&
            $response->isRedirection()
        ) {

            return response()->json([
                'message' => 'Resource not found',
                'status' => 'error'
            ], 404);
        }

        return $response;
    }
}
