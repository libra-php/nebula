<?php

namespace Nebula\Middleware\Htmx;

use Nebula\Interfaces\Http\{Response, Request};
use Nebula\Interfaces\Middleware\Middleware;
use Closure;

/**
 * HTMX Response Middleware
 * if set to “true” the client-side will do a full refresh of the page
 * HX-Refresh https://htmx.org/docs
 *
 * @package Nebula\Middleware\Http
 */
class Refresh implements Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $route_middleware = $request->route?->getMiddleware();
        if ($route_middleware && preg_grep("/refresh/", $route_middleware)) {
			$response->setHeader("HX-Refresh", "true");
        }

        return $response;
    }
}

