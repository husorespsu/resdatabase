<?php

declare(strict_types=1);

/**
 * Router – lightweight front-controller router (no namespace).
 * Supports GET/POST, named route params {id}, middleware arrays.
 */
class Router
{
    private array $routes = [];
    private string $basePath = '/research';

    // ── Registration ──────────────────────────────────────────

    public function get(string $path, string $handler, array $middleware = []): void
    {
        $this->addRoute('GET', $path, $handler, $middleware);
    }

    public function post(string $path, string $handler, array $middleware = []): void
    {
        $this->addRoute('POST', $path, $handler, $middleware);
    }

    private function addRoute(string $method, string $path, string $handler, array $middleware): void
    {
        $this->routes[$method][] = [
            'pattern'    => $this->buildPattern($path),
            'handler'    => $handler,
            'middleware' => $middleware,
        ];
    }

    // ── Dispatch ──────────────────────────────────────────────

    public function dispatch(string $method, string $uri): bool
    {
        // Support _method override for PUT/PATCH/DELETE from forms
        if ($method === 'POST' && !empty($_POST['_method'])) {
            $override = strtoupper($_POST['_method']);
            if (in_array($override, ['PUT', 'PATCH', 'DELETE'], true)) {
                $method = $override;
            }
        }

        $routes = $this->routes[$method] ?? [];

        foreach ($routes as $route) {
            if (preg_match($route['pattern'], $uri, $matches) === 1) {
                // Named params only
                $params = array_filter($matches, fn($k) => is_string($k), ARRAY_FILTER_USE_KEY);

                // Run middleware
                $this->runMiddleware($route['middleware']);

                // Dispatch handler
                $this->callHandler($route['handler'], $params);
                return true;
            }
        }

        return false;
    }

    // ── Middleware ────────────────────────────────────────────

    private function runMiddleware(array $middleware): void
    {
        foreach ($middleware as $mw) {
            if ($mw === 'auth') {
                Middleware::requireAuth();
            } elseif (str_starts_with($mw, 'role:')) {
                $roles = explode(',', substr($mw, 5));
                Middleware::requireRole($roles);
            }
        }
    }

    // ── Handler ───────────────────────────────────────────────

    private function callHandler(string $handler, array $params): void
    {
        // Format: "ControllerClass@method"
        [$class, $method] = explode('@', $handler, 2);

        $controllerFile = BASE_PATH . '/app/controllers/' . $class . '.php';
        if (!file_exists($controllerFile)) {
            throw new \RuntimeException("Controller file not found: {$controllerFile}");
        }
        require_once $controllerFile;

        if (!class_exists($class)) {
            throw new \RuntimeException("Controller class [{$class}] not found.");
        }

        $controller = new $class();

        if (!method_exists($controller, $method)) {
            throw new \RuntimeException("Method [{$method}] not found on [{$class}].");
        }

        // Pass route params as argument if method accepts them
        $reflection = new \ReflectionMethod($controller, $method);
        if ($reflection->getNumberOfParameters() > 0) {
            $controller->{$method}($params);
        } else {
            $controller->{$method}();
        }
    }

    // ── Pattern Builder ───────────────────────────────────────

    private function buildPattern(string $path): string
    {
        // Replace {param} before quoting; route literals only contain safe chars
        $pattern = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
            fn(array $m) => '(?P<' . $m[1] . '>[^/]+)',
            $path
        );
        return '#^' . $pattern . '$#u';
    }
}
