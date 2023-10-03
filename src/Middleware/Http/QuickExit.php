<?php

namespace Nebula\Middleware\Http;

use App\Http\Kernel;
use Nebula\Interfaces\Http\{Response, Request};
use Nebula\Interfaces\Middleware\Middleware;
use Closure;

/**
 * This middleware returns the response immediately
 * and halts the middleware chain
 *
 * @package Nebula\Middleware\Http
 */
class QuickExit implements Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $route_middleware = $request->route?->getMiddleware();
        if (
            $route_middleware &&
            preg_grep("/quick-exit/", $route_middleware)
        ) {
            $response = Kernel::getInstance()->resolveRoute($request->route);
            $response->send();
            die;
        }

        $response = $next($request);


        return $response;
    }
}

