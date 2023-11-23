<?php

namespace Nebula\Middleware\Htmx;

use Nebula\Interfaces\Http\{Response, Request};
use Nebula\Interfaces\Middleware\Middleware;
use Closure;

/**
 * HTMX Response Middleware
 * allows you to specify how the response will be swapped. See hx-swap for possible values
 * HX-Reswap https://htmx.org/docs
 *
 * @package Nebula\Middleware\Http
 */
class Reswap implements Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $route_middleware = $request->route?->getMiddleware();
        if ($route_middleware && preg_grep("/reswap/", $route_middleware)) {
            $index = middlewareIndex($route_middleware, "reswap");
            $uri = str_replace("reswap=", "", $route_middleware[$index]);
            if ($uri !== "reswap") {
				$response->setHeader("HX-Reswap", $uri);
            }
        }

        return $response;
    }
}


