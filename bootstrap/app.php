<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Jetstream\Http\Middleware\AuthenticateSession;
use Laravel\Passport\Http\Middleware\EnsureClientIsResourceOwner;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectUsersTo('/dashboard');

        $middleware->web(append: [
            AuthenticateSession::class,
        ]);

        $middleware->alias([
            'client' => EnsureClientIsResourceOwner::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // The order/invoice validation rules encode the SFA response payload as JSON in
        // their message; the integration expects it back verbatim with HTTP 200.
        $exceptions->render(function (ValidationException $e, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            foreach (['transaction_id', 'customer_code'] as $field) {
                if (array_key_exists($field, $e->errors())) {
                    return response()->json(json_decode($e->errors()[$field][0], true), 200);
                }
            }

            return null;
        });
    })->create();
