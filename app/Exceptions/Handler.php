<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;


class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        // Register a global exception handler for API routes
        $this->renderable(function (Throwable $e, Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                if ($e instanceof NotFoundHttpException) {
                    return response()->json([
                        'message' => 'Resource not found',
                        'status' => 'error'
                    ], 404);
                }

                if ($e instanceof AuthenticationException) {
                    return response()->json([
                        'message' => 'Unauthenticated',
                        'status' => 'error'
                    ], 401);
                }

                // Handle any other exceptions for API routes
                return response()->json([
                    'message' => $e->getMessage() ?: 'Server Error',
                    'status' => 'error'
                ], 500);
            }
        });

        // The specific handlers below are kept for reference but are redundant now
        $this->renderable(function (Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return response()->json([
                    'message' => 'Resource not found',
                    'status' => 'error'
                ], 404);
            }
        });

        $this->renderable(function (Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return response()->json([
                    'message' => 'Unauthenticated',
                    'status' => 'error'
                ], 401);
            }
        });
    }
}
