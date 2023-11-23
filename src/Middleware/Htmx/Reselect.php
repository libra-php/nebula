<?php

namespace Nebula\Middleware\Htmx;

use Nebula\Interfaces\Http\{Response, Request};
use Nebula\Interfaces\Middleware\Middleware;
use Closure;

/**
 * HTMX Response Middleware
 * a CSS selector that allows you to choose which part of the response is used to be swapped in. Overrides an existing hx-select on the triggering element
 * HX-Reselect https://htmx.org/docs
 *
 * @package Nebula\Middleware\Http
 */
class Reselect implements Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $route_middleware = $request->route?->getMiddleware();
        if ($route_middleware && preg_grep("/reselect/", $route_middleware)) {
            $index = middlewareIndex($route_middleware, "reselect");
            $uri = str_replace("reselect=", "", $route_middleware[$index]);
            if ($uri !== "reselect") {
				$response->setHeader("HX-Reselect", $uri);
            }
        }

        return $response;
    }
}




