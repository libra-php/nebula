<?php

namespace Nebula\Http;

use Nebula\Interfaces\Http\Response;
use Nebula\Interfaces\Routing\Router;
use Nebula\Interfaces\Framework\Kernel as NebulaKernel;
use Nebula\Middleware\Middleware;
use Nebula\Traits\Http\Response as HttpResponse;
use Nebula\Traits\Instance\Singleton;
use StellarRouter\Route;
use Throwable;

/**
 * Class Kernel
 * @package Nebula\Http
 */
class Kernel implements NebulaKernel
{
    use HttpResponse;
    use Singleton;

    public Router $router;
    protected array $middleware = [];

    /**
     * Setup the application
     */
    public function setup(): void
    {
        $this->router = app()->get(Router::class);
        $this->registerMiddleware();
        $this->registerControllers();
    }

    /**
     * Register the controller classes
     */
    private function registerControllers(): void
    {
        // Register the controller classes
        $controllers_path = config("paths.controllers");
        foreach (classMap($controllers_path) as $class_name => $filename) {
            $this->router->registerClass($class_name);
        }
    }

    /**
     * Register the middleware classes
     */
    private function registerMiddleware(): void
    {
        foreach ($this->middleware as $i => $class) {
            $this->middleware[$i] = app()->get($class);
        }
    }

    /**
     * Resolve the route and execute controller method
     * @param Route|null $route
     * @return Response
     */
    public function resolveRoute(?Route $route): Response
    {
        $response = app()->get(Response::class);
        if ($route) {
            try {
                $response->setHeader("X-Powered-By", "Nebula");
                $handlerClass = $route->getHandlerClass();
                $handlerMethod = $route->getHandlerMethod();
                $routeParameters = $route->getParameters();
                $routePayload = $route->getPayload();
                $response->setStatusCode(200);
                $content = "";
                if ($handlerClass) {
                    $class = app()->get($handlerClass);
                    $content = $class->$handlerMethod(...$routeParameters);
                } elseif ($routePayload) {
                    $content = $routePayload(...$routeParameters);
                }
                $response->setContent($content);
            } catch (\Exception $ex) {
                return $this->handleException($ex);
            } catch (\Error $err) {
                return $this->handleError($err);
            }
        } else {
            return $this->response(404, "Page not found");
        }
        return $response;
    }

    /**
     * Handle the request and return a response
     * @return Response
     */
    public function handle(): Response
    {
        $request = request();
        // Figure out the route
        $route = $this->router->handleRequest(
            $request->getMethod(),
            $request->getUri()
        );
        // Save the route to the request (e.g. use in middleware)
        $request->route = $route;
        $runner = app()->get(Middleware::class);
        $response = $runner
            ->layer($this->middleware)
            ->handle($request, fn() => $this->resolveRoute($route));
        return $response;
    }

    /**
     * Handle any application exceptions
     */
    public function handleException(Throwable $exception): Response
    {
        $config = config("app");
        error_log("\n");
        error_log("Nebula Exception" . PHP_EOL);
        error_log(
            "File: " .
                $exception->getFile() .
                ":" .
                $exception->getLine() .
                PHP_EOL
        );
        error_log("Message: " . $exception->getMessage() . PHP_EOL);
        error_log("Trace: " . $exception->getTraceAsString() . PHP_EOL);
        return $config["debug"]
            ? $this->whoops($exception)
            : $this->response(500, "Server error");
    }

    /**
     * Handle any application errors
     */
    public function handleError(Throwable $error): Response
    {
        $config = config("app");
        error_log("\n");
        error_log("Nebula Error" . PHP_EOL);
        error_log(
            "File: " .
                $error->getFile() .
                ":" .
                $error->getLine() .
                PHP_EOL
        );
        error_log("Message: " . $error->getMessage() . PHP_EOL);
        error_log("Trace: " . $error->getTraceAsString() . PHP_EOL);
        return $config["debug"]
            ? $this->whoops($error)
            : $this->response(500, "Server error");
    }

    /**
     * Return a nice error page
     */
    private function whoops(Throwable $exception): Response
    {
        $whoops = new \Whoops\Run();
        $whoops->allowQuit(false);
        $whoops->writeToOutput(false);
        $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler());
        return $this->response(500, $whoops->handleException($exception));
    }

    /**
     * Terminate the application
     */
    public function terminate(): never
    {
        logger("timeEnd", "Nebula");
        exit();
    }
}
