<?php

namespace App\Core;

class App
{
    private Router $router;
    private Request $request;

    public function __construct()
    {
        $this->request = new Request();
        $this->router = new Router();
    }

    public function boot(): void
    {
        // Set timezone
        date_default_timezone_set(env('APP_TIMEZONE', 'Europe/Rome'));

        // Start session
        Session::start();

        // Security headers (CSP with nonce, set via PHP for dynamic nonce)
        $nonce = csp_nonce();
        header("X-Content-Type-Options: nosniff");
        header("Referrer-Policy: strict-origin-when-cross-origin");
        header("Permissions-Policy: geolocation=(), microphone=(), camera=()");

        // Allow iframe embedding for public booking pages, deny for dashboard/admin
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        $isProtected = str_starts_with($uri, '/dashboard') || str_starts_with($uri, '/admin') || str_starts_with($uri, '/auth');
        if ($isProtected) {
            header("X-Frame-Options: DENY");
            header("Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-{$nonce}' https://cdn.jsdelivr.net; style-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; font-src 'self' https://cdn.jsdelivr.net data:; img-src 'self' data: https:; connect-src 'self'; frame-ancestors 'none'");
        } else {
            header("Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-{$nonce}' https://cdn.jsdelivr.net; style-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; font-src 'self' https://cdn.jsdelivr.net data:; img-src 'self' data: https:; connect-src 'self'; frame-ancestors *");
        }

        // Load routes
        $router = $this->router;
        require BASE_PATH . '/config/routes.php';
    }

    public function run(): void
    {
        $method = $this->request->method();
        $uri = $this->request->uri();

        // Resolve route
        $match = $this->router->resolve($method, $uri);

        if (!$match) {
            // Try tenant resolution for booking pages
            $tenant = TenantResolver::resolve($this->request->host(), $uri);
            if ($tenant) {
                // Re-resolve with tenant-aware URI
                $match = $this->router->resolve($method, $uri);
            }

            if (!$match) {
                Response::notFound();
            }
        }

        // Set route params on request
        $this->request->setParams($match['params']);

        // Run middleware
        $this->runMiddleware($match['middleware']);

        // Execute controller
        [$controllerClass, $action] = $match['handler'];

        if (!class_exists($controllerClass)) {
            Response::serverError("Controller not found: {$controllerClass}");
        }

        $controller = new $controllerClass();
        if (!method_exists($controller, $action)) {
            Response::serverError("Action not found: {$controllerClass}::{$action}");
        }

        $controller->$action($this->request);
    }

    private function runMiddleware(array $middleware): void
    {
        $middlewareMap = [
            'auth'      => \App\Middleware\AuthMiddleware::class,
            'admin'     => \App\Middleware\AdminMiddleware::class,
            'tenant'    => \App\Middleware\TenantMiddleware::class,
            'csrf'      => \App\Middleware\CSRFMiddleware::class,
            'ratelimit' => \App\Middleware\RateLimitMiddleware::class,
            'dashboard-ratelimit' => \App\Middleware\DashboardRateLimitMiddleware::class,
        ];

        foreach ($middleware as $name) {
            $class = $middlewareMap[$name] ?? null;
            if ($class && class_exists($class)) {
                $instance = new $class();
                $instance->handle($this->request);
            }
        }
    }

    public function getRouter(): Router
    {
        return $this->router;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }
}
