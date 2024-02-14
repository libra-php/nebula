<?php

namespace Nebula\Framework\Http;

use Composer\ClassMapGenerator\ClassMapGenerator;
use Dotenv\Dotenv;
use Exception;
use Nebula\Framework\Middleware\Middleware;
use StellarRouter\Route;
use StellarRouter\Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Kernel
{
	protected array $middleware = [];

	/**
	 * Main kernel method
	 * Sets up framework and returns an HTTP response
	 */
	public function main(): Response
	{
		$this->bootstrap();
		$request = $this->request();
		$route = $this->routing($request);
		$request->attributes->add(["route" => $route]);
		$response = $this->middleware()
			->layer($this->middleware)
			->handle($request, function () use ($request, $route) {
				return $this->resolve($request, $route);
			});
		$response->prepare($request);
		return $response;
	}

	/**
	 * Initializes the framework, sets up essential configurations,
	 * and prepares the environment for the application to run.
	 */
	protected function bootstrap(): void
	{
		$env_path = config("path.root");
		$this->environment($env_path);
		$this->registerMiddleware();
	}

	/**
	 * Load environment variables
	 * @param string $path path to .env
	 */
	protected function environment(string $path): void
	{
		if (!file_exists($path)) error_log("warning: your .env path: '$path' doesn't exist");
		$dotenv = Dotenv::createImmutable($path);
		$dotenv->safeLoad();
	}

	/**
	 * Handle URL routing, mapping incoming requests to the appropriate
	 * controllers or actions within the application.
	 * @param Request $request
	 */
	protected function routing(Request $request): ?Route
	{
		$router = $this->router();
		return $router->handleRequest($request->getMethod(), $request->getPathInfo());
	}

	/**
	 * Resolve a controller endpoint
	 * @param Request $request
	 * @param Route $route
	 */
	protected function resolve(Request $request, Route $route): Response
	{
		if ($route) {
			try {
				$content = null;
				$headers = [];
				$handlerClass = $route->getHandlerClass();
				$handlerMethod = $route->getHandlerMethod();
				$routeParameters = $route->getParameters();
				$routePayload = $route->getPayload();
				if ($handlerClass) {
					$class = new $handlerClass($request);
					$content = $class->$handlerMethod(...$routeParameters);
				} elseif ($routePayload) {
					$content = $routePayload(...$routeParameters);
				}
				return new Response(content: $content, headers: $headers);
			} catch (Exception $ex) {
				error_log("Kernel panic! " . $ex->getMessage());
				die("Fatal error!");
			}
		}
	}

	/**
	 * Get framework middleware class
	 */
	protected function middleware(): Middleware
	{
		return new Middleware();
	}

	/**
	 * Register middleware to filter HTTP requests entering the application.
	 */
	protected function registerMiddleware(): void
	{
		foreach ($this->middleware as $i => $class) {
			$this->middleware[$i] = new $class;
		}
	}

	/**
	 * Get framework router class and register controllers
	 */
	protected function router(): Router
	{
		$router = new Router();
		$controller_path = config("path.controllers");
		$this->registerControllers($router, $this->controllerMap($controller_path));
		return $router;
	}

	/**
	 * Get a controller class map
	 * @param string $controller_path application controller path
	 */
	protected function controllerMap(string $controller_path): array
	{
		if (!file_exists($controller_path)) throw new Exception("controller path doesn't exist");
		return ClassMapGenerator::createMap($controller_path);
	}

	/**
	 * Register controller routes
	 * @param Router $router
	 * @param array $map controller class map
	 */
	protected function registerControllers(Router $router, array $map): void
	{
		foreach ($map as $controller => $path) {
			$router->registerClass($controller);
		}
	}

	/**
	 * Handle incoming HTTP requests, parse them, and prepare them
	 * for further processing.
	 */
	protected function request(): Request
	{
		return Request::createFromGlobals();
	}
}