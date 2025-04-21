<?php

declare(strict_types=1);

namespace App\Http;

use App\Core\Container;
use ReflectionException;

class Router
{
    private array $routes;

    public function __construct(
        string $routesPath,
        private readonly Container $container
    )
    {
        $flatRoutes = require $routesPath;

        $this->routes = [];

        foreach ($flatRoutes as $name => $route) {
            $path = $route['path'];
            $method = strtoupper($route['method']);

            if (!isset($this->routes[$path])) {
                $this->routes[$path] = [];
            }

            $this->routes[$path][$method] = [
                'controller' => $route['controller'],
                'name' => $name,
            ];
        }
    }

    /**
     * @throws ReflectionException
     */
    public function dispatch(string $method, string $uri, array $input, array $query = []): void
    {
        $path = strtok($uri, '?');
        $method = strtoupper($method);

        if (!isset($this->routes[$path])) {
            http_response_code(404);
            echo json_encode(['error' => "Route '$path' not found"]);
            return;
        }

        if (!isset($this->routes[$path][$method])) {
            http_response_code(405);
            echo json_encode([
                'error' => "Method '$method' not allowed for '$path'",
                'allowed_methods' => array_keys($this->routes[$path]),
            ]);
            return;
        }

        $route = $this->routes[$path][$method];
        $controllerClass = $route['controller'];

        $controller = $this->container->make($controllerClass);

        if (!is_callable($controller)) {
            http_response_code(500);
            echo json_encode(['error' => "Controller '$controllerClass' is not invokable"]);
            return;
        }

        $response = $controller($input, $query);

        if ($response instanceof JsonResponse) {
            $response->send();
        }
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }
}
