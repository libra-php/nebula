<?php

namespace Nebula\Middleware\Htmx;

use Nebula\Interfaces\Http\{Response, Request};
use Nebula\Interfaces\Middleware\Middleware;
use Closure;

/**
 * HTMX Response Middleware
 * can be used to do a client-side redirect to a new location
 * HX-Redirect https://htmx.org/docs
 *
 * @package Nebula\Middleware\Http
 */
class Redirect implements Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $route_middleware = $request->route?->getMiddleware();
        if ($route_middleware && preg_grep("/redirect/", $route_middleware)) {
            $index = middlewareIndex($route_middleware, "redirect");
            $uri = str_replace("redirect=", "", $route_middleware[$index]);
            if ($uri !== "redirect") {
                $response->setHeader("HX-Redirect", $uri);
            }
        }

        return $response;
    }
}
