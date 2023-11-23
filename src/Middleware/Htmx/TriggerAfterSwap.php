<?php

namespace Nebula\Middleware\Htmx;

use Nebula\Interfaces\Http\{Response, Request};
use Nebula\Interfaces\Middleware\Middleware;
use Closure;

/**
 * HTMX Response Middleware
 * allows you to trigger client-side events after the swap step
 * HX-Trigger-After-Swap https://htmx.org/docs
 *
 * @package Nebula\Middleware\Http
 */
class TriggerAfterSwap implements Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $route_middleware = $request->route?->getMiddleware();
        if ($route_middleware && preg_grep("/trigger-after-swap/", $route_middleware)) {
            $index = middlewareIndex($route_middleware, "trigger-after-swap");
            $uri = str_replace("trigger-after-swap=", "", $route_middleware[$index]);
            if ($uri !== "trigger-after-swap") {
				$response->setHeader("HX-Target-After-Swap", $uri);
            }
        }

        return $response;
    }
}







