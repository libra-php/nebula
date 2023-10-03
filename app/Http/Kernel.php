<?php

namespace App\Http;

use Nebula\Http\Kernel as HttpKernel;

final class Kernel extends HttpKernel
{
    // Register your application middleware classes
    // Middleware classes are executed in the order
    // they are defined (top to bottom for request,
    // bottom to top for response)
    protected array $middleware = [
        \Nebula\Middleware\Http\QuickExit::class,
        \Nebula\Middleware\Http\CSRF::class,
        \Nebula\Middleware\Http\RateLimit::class,
        \Nebula\Middleware\Admin\Authentication::class,
        \Nebula\Middleware\Http\CachedResponse::class,
        \Nebula\Middleware\Http\PushUrl::class,
        \Nebula\Middleware\Http\JsonResponse::class,
        \Nebula\Middleware\Http\Log::class,
    ];
}
