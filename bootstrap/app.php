<?php

use App\Exceptions\DeviceAssignmentException;
use App\Http\Middleware\AuthenticateDevice;
use App\Http\Middleware\PrepareUserContext;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Illuminate\Database\QueryException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . "/../routes/web.php",
        api: __DIR__ . "/../routes/api.php",
        commands: __DIR__ . "/../routes/console.php",
        health: "/up",
        then: function () {
            Route::middleware('api')
                ->prefix('gateway')
                ->group(base_path('routes/gateway.php'));
         },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            "prepare.user" => PrepareUserContext::class,
            "auth.device" => AuthenticateDevice::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle API exceptions with consistent format
        $exceptions->render(function (Throwable $e, $request) {
            // Only handle API requests
            if (!$request->is("api/*")) {
                return null; // Let default handler process non-API requests
            }

            // Log the full error for debugging (server-side only)
            if (app()->bound("log")) {
                \Log::error("API Exception: " . $e->getMessage(), [
                    "exception" => get_class($e),
                    "file" => $e->getFile(),
                    "line" => $e->getLine(),
                    "trace" => $e->getTraceAsString(),
                    "url" => $request->fullUrl(),
                    "method" => $request->method(),
                    "ip" => $request->ip(),
                    "user_id" => $request->user()?->id,
                ]);
            }

            // Handle specific exception types
            // 1. Validation Exceptions (422)
            if ($e instanceof ValidationException) {
                return response()->json(
                    [
                        "message" => $e->getMessage() ?: "Validation failed",
                        "errors" => $e->errors(),
                    ],
                    422,
                );
            }

            // 2. Authentication Exceptions (401)
            if ($e instanceof AuthenticationException) {
                return response()->json(
                    [
                        "message" => "Unauthenticated",
                    ],
                    401,
                );
            }

            // 3. Authorization Exceptions (403)
            if ($e instanceof AuthorizationException) {
                return response()->json(
                    [
                        "message" =>
                            $e->getMessage() ?: "This action is unauthorized",
                    ],
                    403,
                );
            }

            // 3.5. Device Assignment Exceptions (422)
            if ($e instanceof DeviceAssignmentException) {
                return response()->json(
                    [
                        "message" => $e->getMessage(),
                    ],
                    422,
                );
            }

            // 4. Model Not Found (404)
            if ($e instanceof ModelNotFoundException) {
                $model = strtolower(class_basename($e->getModel()));
                return response()->json(
                    [
                        "message" => ucfirst($model) . " not found",
                    ],
                    404,
                );
            }

            // 5. Not Found HTTP Exception (404)
            if ($e instanceof NotFoundHttpException) {
                return response()->json(
                    [
                        "message" => "The requested resource was not found",
                    ],
                    404,
                );
            }

            // 6. Method Not Allowed (405)
            if ($e instanceof MethodNotAllowedHttpException) {
                return response()->json(
                    [
                        "message" =>
                            "The HTTP method is not allowed for this route",
                    ],
                    405,
                );
            }

            // 7. Database Query Exceptions (500 - but don't leak SQL)
            if ($e instanceof QueryException) {
                $message = "Database error occurred";
                $code = 500;

                // Provide more specific messages for common issues without leaking SQL
                if (str_contains($e->getMessage(), "Duplicate entry")) {
                    $message = "A record with this information already exists";
                    $code = 409; // Conflict
                } elseif (
                    str_contains($e->getMessage(), "foreign key constraint")
                ) {
                    $message =
                        "Cannot perform this action due to related records";
                    $code = 409;
                } elseif (
                    str_contains($e->getMessage(), "Connection refused")
                ) {
                    $message = "Database connection failed";
                }

                return response()->json(
                    [
                        "message" => $message,
                    ],
                    $code,
                );
            }

            // 8. Generic HTTP Exceptions
            if ($e instanceof HttpException) {
                return response()->json(
                    [
                        "message" => $e->getMessage() ?: "An error occurred",
                    ],
                    $e->getStatusCode(),
                );
            }

            // 9. All other exceptions (500 - Internal Server Error)
            // Never leak internal error details in production
            $message = "An unexpected error occurred";
            $code = 500;

            // In development, provide more details
            if (config("app.debug")) {
                return response()->json(
                    [
                        "message" => $e->getMessage(),
                        "exception" => get_class($e),
                        "file" => $e->getFile(),
                        "line" => $e->getLine(),
                        "trace" => collect($e->getTrace())->take(5)->toArray(),
                    ],
                    $code,
                );
            }

            return response()->json(
                [
                    "message" => $message,
                ],
                $code,
            );
        });
    })
    ->create();
