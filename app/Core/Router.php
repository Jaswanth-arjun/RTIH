<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var list<array{methods: list<string>, pattern: string, regex: string, handler: callable|array{0: class-string, 1: string}}> */
    private array $routes = [];

    /**
     * @param list<string> $methods
     * @param callable|array{0: class-string, 1: string} $handler
     */
    public function add(array $methods, string $pattern, callable|array $handler): void
    {
        $normalized = '/' . trim($pattern, '/');
        if ($normalized === '//') {
            $normalized = '/';
        }

        $regex = preg_replace(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
            '(?P<$1>[0-9]+)',
            $normalized
        );

        $this->routes[] = [
            'methods' => array_map('strtoupper', $methods),
            'pattern' => $normalized,
            'regex' => '#^' . $regex . '$#',
            'handler' => $handler,
        ];
    }

    /**
     * @param callable|array{0: class-string, 1: string} $handler
     */
    public function get(string $pattern, callable|array $handler): void
    {
        $this->add(['GET'], $pattern, $handler);
    }

    /**
     * @param callable|array{0: class-string, 1: string} $handler
     */
    public function post(string $pattern, callable|array $handler): void
    {
        $this->add(['POST'], $pattern, $handler);
    }

    public function dispatch(string $method, string $path): void
    {
        $method = strtoupper($method);
        if ($method === 'HEAD') {
            $method = 'GET';
        }

        $allowed = [];

        foreach ($this->routes as $route) {
            if (preg_match($route['regex'], $path, $matches) !== 1) {
                continue;
            }

            if (!in_array($method, $route['methods'], true)) {
                $allowed = array_merge($allowed, $route['methods']);
                continue;
            }

            $params = array_filter(
                $matches,
                static fn (int|string $key): bool => is_string($key),
                ARRAY_FILTER_USE_KEY
            );

            $handler = $route['handler'];

            if (is_array($handler)) {
                [$class, $action] = $handler;
                $controller = new $class();
                $controller->{$action}(...array_values($params));
                return;
            }

            $handler(...array_values($params));
            return;
        }

        if ($allowed !== []) {
            header('Allow: ' . implode(', ', array_unique($allowed)));
            abort(405, 'Method not allowed.', 'That request method is not supported for this page.');
        }

        abort(404, 'Route not found: ' . $method . ' ' . $path, 'The page you are looking for could not be found.');
    }
}
