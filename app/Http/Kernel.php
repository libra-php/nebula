<?php

namespace App\Http;

use Nebula\Framework\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
	/**
	 * Application middleware stack (order FILO)
	 * @var array $middlware application middleware
	 */
	protected array $middleware = [
		\Nebula\Framework\Middleware\RequestUuid::class
	];
}