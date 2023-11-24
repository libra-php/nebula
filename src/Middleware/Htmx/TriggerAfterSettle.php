<?php

namespace Nebula\Middleware\Htmx;

use Nebula\Interfaces\Http\{Response, Request};
use Nebula\Interfaces\Middleware\Middleware;
use Closure;

/**
 * HTMX Response Middleware
 * allows you to trigger client-side events after the settle step
 * HX-Trigger-After-Settle https://htmx.org/docs
 *
 * @package Nebula\Middleware\Http
 */
class TriggerAfterSettle implements Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $route_middleware = $request->route?->getMiddleware();
        if (
            $route_middleware &&
            preg_grep("/trigger-after-settle/", $route_middleware)
        ) {
            $index = middlewareIndex($route_middleware, "trigger-after-settle");
            $uri = str_replace(
                "trigger-after-settle=",
                "",
                $route_middleware[$index]
            );
            if ($uri !== "trigger-after-settle") {
                $response->setHeader("HX-Target-After-Settle", $uri);
            }
        }

        return $response;
    }
}
