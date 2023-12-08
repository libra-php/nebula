<?php

namespace Nebula\Middleware\Admin;

use App\Models\User;
use Nebula\Interfaces\Middleware\Middleware;
use Nebula\Interfaces\Http\{Response, Request};
use Nebula\Traits\Http\Response as HttpResponse;
use Closure;

/**
 * This middleware provides authentication
 *
 * @package Nebula\Middleware\Admin
 */
class Authentication implements Middleware
{
    use HttpResponse;

    public function handle(Request $request, Closure $next): Response
    {
        $middleware = $request->route?->getMiddleware();
        if (
            is_array($middleware) &&
            in_array("auth", $middleware) &&
            !$this->isAuthenticated()
        ) {
            // Redirect or return an error response if the user is not authenticated
            return $this->response(401, "Unauthorized");
        }

        $response = $next($request);

        return $response;
    }

    private function isAuthenticated(): bool
    {
        $uuid = session()->get("user");
        if (!$uuid) {
            return false;
        }
        $user = User::search(["uuid", $uuid]);
        return !is_null($user);
    }
}
