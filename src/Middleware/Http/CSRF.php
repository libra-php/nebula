<?php

namespace Nebula\Middleware\Http;

use Nebula\Interfaces\Http\{Response, Request};
use Nebula\Interfaces\Middleware\Middleware;
use Nebula\Traits\Http\Response as NebulaResponse;
use Closure;

/**
 * This middleware provides CSRF protection
 *
 * @package Nebula\Middleware\Http
 */
class CSRF implements Middleware
{
    use NebulaResponse;

    public function handle(Request $request, Closure $next): Response
    {
        $this->token();
        $route_middleware = $request->route?->getMiddleware();

        // API endpoints are not CSRF protected
        if (
            !$this->validate($request) &&
            $route_middleware &&
            !in_array("api", $route_middleware)
        ) {
            return $this->response(403, "Invalid CSRF token");
        }

        $response = $next($request);

        return $response;
    }

    public function token(): void
    {
        $token = session()->get("csrf_token");
        if (is_null($token)) {
            $token = token();
            session()->set("csrf_token", $token);
        }
        $this->track();
    }

    public function track(): void
    {
        $token_ts = session()->get("csrf_token_ts");
        if (is_null($token_ts) || $token_ts + 3600 < time()) {
            $token = token();
            $token_ts = time();
            session()->set("csrf_token", $token);
            session()->set("csrf_token_ts", $token_ts);
        }
    }

    public function validate(Request $request): bool
    {
        $request_method = $request->getMethod();
        if (in_array($request_method, ["GET", "HEAD", "OPTIONS"])) {
            return true;
        }

        $token = $request->csrf_token;
        if (
            !is_null($token) &&
            hash_equals(session()->get("csrf_token"), $token)
        ) {
            return true;
        }

        return false;
    }
}
