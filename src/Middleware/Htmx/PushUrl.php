<?php

namespace Nebula\Middleware\Htmx;

use Nebula\Interfaces\Http\{Response, Request};
use Nebula\Interfaces\Middleware\Middleware;
use Closure;

/**
 * HTMX Response Middleware
 * pushes a new url into the history stack
 * HX-Push-Url https://htmx.org/docs
 *
 * @package Nebula\Middleware\Http
 */
class PushUrl implements Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $route_middleware = $request->route?->getMiddleware();
        if ($route_middleware && preg_grep("/push-url/", $route_middleware)) {
            $index = middlewareIndex($route_middleware, "push-url");
            $uri = str_replace("push-url=", "", $route_middleware[$index]);
            if ($uri === "push-url") {
                $route_name = $request->route->getName();
                $params = $request->route->getParameters();
                $uri = buildRoute($route_name, ...$params);
                $uri = str_replace("/part", "", $uri);
            }
            $response->setHeader("HX-Push-Url", $uri);
        }

        return $response;
    }
}
