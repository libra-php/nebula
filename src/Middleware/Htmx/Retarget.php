<?php

namespace Nebula\Middleware\Htmx;

use Nebula\Interfaces\Http\{Response, Request};
use Nebula\Interfaces\Middleware\Middleware;
use Closure;

/**
 * HTMX Response Middleware
 * a CSS selector that updates the target of the content update to a different element on the page
 * HX-Retarget https://htmx.org/docs
 *
 * @package Nebula\Middleware\Http
 */
class Retarget implements Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $route_middleware = $request->route?->getMiddleware();
        if ($route_middleware && preg_grep("/retarget/", $route_middleware)) {
            $index = middlewareIndex($route_middleware, "retarget");
            $uri = str_replace("retarget=", "", $route_middleware[$index]);
            if ($uri !== "retarget") {
                $response->setHeader("HX-Retarget", $uri);
            }
        }

        return $response;
    }
}
