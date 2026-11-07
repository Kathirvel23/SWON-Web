<?php
declare(strict_types=1);

namespace App;

/**
 * Path router with `{name}` placeholders. No rewriting rules beyond the
 * front controller; works on Apache/XAMPP and with `php -S`.
 */
final class Router
{
    /** @var array<string, list<array{pattern: string, handler: array{0: class-string, 1: string}}>> */
    private array $routes = [];

    /** @param array{0: class-string, 1: string} $handler */
    public function get(string $path, array $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    /** @param array{0: class-string, 1: string} $handler */
    public function post(string $path, array $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    /** @param array{0: class-string, 1: string} $handler */
    private function add(string $method, string $path, array $handler): void
    {
        $pattern = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $path);
        $this->routes[$method][] = ['pattern' => '#^' . $pattern . '$#', 'handler' => $handler];
    }

    public function dispatch(string $method, string $path): void
    {
        $path = '/' . trim($path, '/');
        if ($path === '/') {
            $path = '/';
        }

        foreach ($this->routes[$method] ?? [] as $route) {
            if (preg_match($route['pattern'], $path, $matches) === 1) {
                $params = [];
                foreach ($matches as $key => $value) {
                    if (is_string($key)) {
                        $params[$key] = rawurldecode($value);
                    }
                }

                [$class, $action] = $route['handler'];
                $controller = new $class();
                $controller->{$action}($params);

                return;
            }
        }

        // Known path, wrong verb -> 405
        foreach (['GET', 'POST'] as $other) {
            if ($other === $method) {
                continue;
            }
            foreach ($this->routes[$other] ?? [] as $route) {
                if (preg_match($route['pattern'], $path) === 1) {
                    http_response_code(405);
                    View::render('errors/405', ['title' => 'Method not allowed']);

                    return;
                }
            }
        }

        http_response_code(404);
        View::render('errors/404', ['title' => 'Page not found']);
    }
}
