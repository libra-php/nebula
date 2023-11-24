<?php

namespace Nebula\Middleware\Htmx;

use Nebula\Interfaces\Http\{Response, Request};
use Nebula\Interfaces\Middleware\Middleware;
use Closure;

/**
 * HTMX Response Middleware
 * allows you to do a client-side redirect that does not do a full page reload
 * HX-Location https://htmx.org/docs
 *
 * @package Nebula\Middleware\Http
 */
class Location implements Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $route_middleware = $request->route?->getMiddleware();
        if ($route_middleware && preg_grep("/location/", $route_middleware)) {
            $index = middlewareIndex($route_middleware, "location");
            $uri = str_replace("location=", "", $route_middleware[$index]);
            if ($uri !== "location") {
                $response->setHeader("HX-Location", $uri);
            }
        }

        return $response;
    }
}
