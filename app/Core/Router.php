<?php

namespace App\Core;

class Router
{
    private array $routes = [];
    private string $prefix = '';
    private array $groupMiddleware = [];

    public function get(string $path, array $handler, array $middleware = []): void
    {
        $this->addRoute('GET', $path, $handler, $middleware);
    }

    public function post(string $path, array $handler, array $middleware = []): void
    {
        $this->addRoute('POST', $path, $handler, $middleware);
    }

    public function put(string $path, array $handler, array $middleware = []): void
    {
        $this->addRoute('PUT', $path, $handler, $middleware);
    }

    public function delete(string $path, array $handler, array $middleware = []): void
    {
        $this->addRoute('DELETE', $path, $handler, $middleware);
    }

    public function group(string $prefix, array $middleware, callable $callback): void
    {
        $previousPrefix = $this->prefix;
        $previousMiddleware = $this->groupMiddleware;

        $this->prefix .= $prefix;
        $this->groupMiddleware = array_merge($this->groupMiddleware, $middleware);

        $callback($this);

        $this->prefix = $previousPrefix;
        $this->groupMiddleware = $previousMiddleware;
    }

    private function addRoute(string $method, string $path, array $handler, array $middleware): void
    {
        $fullPath = $this->prefix . $path;
        $allMiddleware = array_merge($this->groupMiddleware, $middleware);

        // Convert {param} to named regex groups
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $fullPath);
        $pattern = '#^' . $pattern . '$#';

        $this->routes[] = [
            'method'     => $method,
            'path'       => $fullPath,
            'pattern'    => $pattern,
            'handler'    => $handler,
            'middleware'  => $allMiddleware,
        ];
    }

    public function resolve(string $method, string $uri): ?array
    {
        // Support PUT/DELETE via POST with _method field
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $uri, $matches)) {
                // Extract named parameters only
                $params = array_filter($matches, fn($key) => !is_int($key), ARRAY_FILTER_USE_KEY);

                return [
                    'handler'    => $route['handler'],
                    'params'     => $params,
                    'middleware'  => $route['middleware'],
                ];
            }
        }

        return null;
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }
}
