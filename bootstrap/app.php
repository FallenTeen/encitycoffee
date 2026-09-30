<?php

use App\Http\Middleware\BlockMainDomainAuthPages;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Support\ApiErrorResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            BlockMainDomainAuthPages::class,
        ]);
        $middleware->alias([
            'role' => App\Http\Middleware\EnsureRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // API clients must always get JSON, even when they forget the
        // "Accept: application/json" header, otherwise a 500 turns into an
        // unparsable HTML page on the mobile POS.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request): bool => ApiErrorResponse::handles($request)
        );

        // Single catch-all so the status codes stay consistent and ordered.
        // Returning null defers to the framework (web/Inertia) behaviour.
        $exceptions->render(function (Throwable $e, Request $request) {
            return ApiErrorResponse::forThrowable($e, $request);
        });
    })->create();
