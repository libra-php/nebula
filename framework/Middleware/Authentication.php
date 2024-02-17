<?php

namespace Nebula\Framework\Middleware;
use Closure;
use Nebula\Framework\Auth\Auth;
use Nebula\Framework\Middleware\Interface\Middleware;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Authentication implements Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
		$middleware = $request->get("route")?->getMiddleware();

		if ($middleware && in_array("auth", $middleware)) {
			Auth::redirectSignIn();
		}

		$response = $next($request);

		return $response;
    }
}