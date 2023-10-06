<?php

namespace Nebula\Middleware\Http;

use Nebula\Interfaces\Http\{Response, Request};
use Nebula\Interfaces\Middleware\Middleware;
use Closure;

/**
 * This middleware pushes url history via HX-Push-Url
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
            // Get the cache duration from the route middleware
            $index = middlewareIndex($route_middleware, "push-url");
            $uri = str_replace("push-url=", "", $route_middleware[$index]);
            if (
                $uri === "push-url" &&
                preg_grep("/module/", $route_middleware)
            ) {
                $params = $request->route->getParameters();
                $uri = $this->moduleRoute(...$params);
                if ($request->route->getName() === "module.create.part") {
                    $uri .= "/create";
                }
            } elseif ($uri === "push-url") {
                $uri = $request->route->getPath();
            }
            $response->setHeader("HX-Push-Url", $uri);
        }

        return $response;
    }

    private function moduleRoute(string $module, ?string $id = null): string
    {
        if (!is_null($id)) {
            $route_path = route("module.edit");
            $route_path = str_replace("{id}", $id, $route_path);
        } else {
            $route_path = route("module.index");
        }
        return str_replace("{module}", $module, $route_path);
    }
}
