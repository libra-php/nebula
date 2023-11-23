<?php

namespace Nebula\Middleware\Htmx;

use Nebula\Interfaces\Http\{Response, Request};
use Nebula\Interfaces\Middleware\Middleware;
use Closure;

/**
 * HTMX Response Middleware
 * replaces the current URL in the location bar
 * HX-Replace-Url https://htmx.org/docs
 *
 * @package Nebula\Middleware\Http
 */
class ReplaceUrl implements Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $route_middleware = $request->route?->getMiddleware();
        if ($route_middleware && preg_grep("/replace-url/", $route_middleware)) {
            $index = middlewareIndex($route_middleware, "replace-url");
            $uri = str_replace("replace-url=", "", $route_middleware[$index]);
            if ($uri !== "replace-url") {
				$response->setHeader("HX-Replace-Url", $uri);
            }
        }

        return $response;
    }
}

