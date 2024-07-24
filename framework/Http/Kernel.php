<?php

namespace Nebula\Framework\Http;

use Error;
use Exception;
use Composer\ClassMapGenerator\ClassMapGenerator;
use Nebula\Framework\Middleware\Middleware;
use Nebula\Framework\System\Interface\Kernel as NebulaInterface;
use Nebula\Framework\System\Kernel as SystemKernel;
use Nebula\Framework\Traits\Singleton;
use StellarRouter\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use StellarRouter\Router;

class Kernel extends SystemKernel implements NebulaInterface
{
    use Singleton;

    private Router $router;
    private Request $request;
    protected array $middleware = [];

    protected function bootstrap(): void
    {
        parent::bootstrap();
        $this->request = Request::createFromGlobals();
        $this->initRouter();
        $this->registerMiddleware();
    }

    public function response(): void
    {
        $route = $this->routing($this->request);
        $this->request->attributes->add(["route" => $route]);
        $response = $this->middleware()
            ->layer($this->middleware)
            ->handle($this->request, function () use ($route) {
                return $this->resolve($this->request, $route);
            });
        $response->prepare($this->request);
        $response->send();
    }

    /**
     * Handle URL routing, mapping incoming requests to the appropriate
     * controllers or actions within the application.
     * @param Request $request
     */
    protected function routing(Request $request): ?Route
    {
        $router = $this->router();
        return $router->handleRequest(
            $request->getMethod(),
            $request->getPathInfo()
        );
    }

    /**
     * Resolve a controller endpoint
     * @param Request $request
     * @param Route $route
     */
    protected function resolve(Request $request, ?Route $route): mixed
    {
        if ($route) {
            try {
                $content = null;
                $headers = [];
                $handlerClass = $route->getHandlerClass();
                $handlerMethod = $route->getHandlerMethod();
                $routeParameters = $route->getParameters();
                $routeMiddleware = $route->getMiddleware();
                $routePayload = $route->getPayload();
                if ($handlerClass) {
                    $class = new $handlerClass($request);
                    $content = $class->$handlerMethod(...$routeParameters);
                } elseif ($routePayload) {
                    $content = $routePayload(...$routeParameters);
                }
                if (in_array("api", $routeMiddleware)) {
                    return $content;
                }
                return new Response($content, 200, $headers);
            } catch (Exception $ex) {
                error_log(
                    print_r(
                        [
                            "type" => "Exception",
                            "message" => $ex->getMessage(),
                            "file" => $ex->getFile() . ":" . $ex->getLine(),
                        ],
                        true
                    )
                );
                header("Location: /server-error", response_code: 500);
                exit();
            } catch (Error $err) {
                error_log(
                    print_r(
                        [
                            "type" => "Error",
                            "message" => $err->getMessage(),
                            "file" => $err->getFile() . ":" . $err->getLine(),
                        ],
                        true
                    )
                );
                header("Location: /server-error", response_code: 500);
                exit();
            }
        } else {
            header("Location: /page-not-found", response_code: 302);
            exit();
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
            $this->middleware[$i] = new $class();
        }
    }

    /**
     * Get framework router class
     */
    public function router(): Router
    {
        return $this->router;
    }

    /**
     * Register controller routes
     * @param Router $router
     * @param array $map controller class map
     */
    private function registerControllers(Router $router, array $map): void
    {
        foreach ($map as $controller => $path) {
            $router->registerClass($controller);
        }
    }

    /**
     * Get a controller class map
     * @param string $controllers_path application controller path
     * @return array<class-string,non-empty-string>
     */
    private function controllerMap(string $controllers_path): array
    {
        if (!file_exists($controllers_path)) {
            throw new Exception("controller path doesn't exist");
        }
        return ClassMapGenerator::createMap($controllers_path);
    }

    private function initRouter(): void
    {
        $this->router = new Router();
        $this->registerControllers(
            $this->router,
            $this->controllerMap($this->paths["controllers"])
        );
    }
}
