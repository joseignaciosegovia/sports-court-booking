<?php

use App\Http\Middleware\RoleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Http\Middleware\TrustProxies;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => RoleMiddleware::class,
        ]);

        /*
         * Render actúa como proxy inverso:
         *
         * Navegador
         *     HTTPS
         *       ↓
         * Render
         *       ↓ HTTP
         * Nginx/PHP
         *
         * Confiamos en las cabeceras X-Forwarded-* para que
         * Laravel sepa que la petición original fue HTTPS.
         */
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
        );

        $middleware->redirectGuestsTo(function (Request $request) {
            if (RoleMiddleware::isIntranetRequest($request)) {
                return route('intranet.login');
            }
            return route('login');
        });

        $middleware->redirectUsersTo(function (Request $request) {

            if ($request->user()->isClient()) {
                return route('client.dashboard');
            }

            if ($request->user()->isManagerOrAdmin()) {
                return route('manager.dashboard');
            }

            return route('home');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();