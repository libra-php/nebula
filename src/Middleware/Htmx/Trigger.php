<?php

namespace Nebula\Middleware\Htmx;

use Nebula\Interfaces\Http\{Response, Request};
use Nebula\Interfaces\Middleware\Middleware;
use Closure;

/**
 * HTMX Response Middleware
 * allows you to trigger client-side events
 * HX-Trigger https://htmx.org/docs
 *
 * @package Nebula\Middleware\Http
 */
class Trigger implements Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $route_middleware = $request->route?->getMiddleware();
        if ($route_middleware && preg_grep("/trigger/", $route_middleware)) {
            $index = middlewareIndex($route_middleware, "trigger");
            $uri = str_replace("trigger=", "", $route_middleware[$index]);
            if ($uri !== "trigger") {
				$response->setHeader("HX-Target", $uri);
            }
        }

        return $response;
    }
}





