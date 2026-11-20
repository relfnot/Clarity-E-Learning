<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var array<int, array{method: string, pattern: string, regex: string, handler: callable|array{0: class-string, 1: string}, middlewares: array<int, object>}> */
    private array $routes = [];

    /**
     * @param callable|array{0: class-string, 1: string} $handler
     * @param array<int, object> $middlewares
     */
    public function get(string $path, callable|array $handler, array $middlewares = []): void
    {
        $this->addRoute('GET', $path, $handler, $middlewares);
    }

    /**
     * @param callable|array{0: class-string, 1: string} $handler
     * @param array<int, object> $middlewares
     */
    public function post(string $path, callable|array $handler, array $middlewares = []): void
    {
        $this->addRoute('POST', $path, $handler, $middlewares);
    }

    /**
     * @param callable|array{0: class-string, 1: string} $handler
     * @param array<int, object> $middlewares
     */
    public function put(string $path, callable|array $handler, array $middlewares = []): void
    {
        $this->addRoute('PUT', $path, $handler, $middlewares);
    }

    /**
     * @param callable|array{0: class-string, 1: string} $handler
     * @param array<int, object> $middlewares
     */
    public function patch(string $path, callable|array $handler, array $middlewares = []): void
    {
        $this->addRoute('PATCH', $path, $handler, $middlewares);
    }

    /**
     * @param callable|array{0: class-string, 1: string} $handler
     * @param array<int, object> $middlewares
     */
    public function delete(string $path, callable|array $handler, array $middlewares = []): void
    {
        $this->addRoute('DELETE', $path, $handler, $middlewares);
    }

    /**
     * @param callable|array{0: class-string, 1: string} $handler
     * @param array<int, object> $middlewares
     */
    public function addRoute(
        string $method,
        string $path,
        callable|array $handler,
        array $middlewares = []
    ): void {
        $normalized = '/' . trim($path, '/');
        if ($normalized !== '/') {
            $normalized = rtrim($normalized, '/');
        }

        $regex = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '(?P<$1>[^/]+)', $normalized);
        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $normalized,
            'regex' => '#^' . $regex . '$#',
            'handler' => $handler,
            'middlewares' => $middlewares,
        ];
    }

    public function dispatch(Request $request): mixed
    {
        if ($request->method() === 'OPTIONS') {
            Response::noContent();
        }

        $path = $request->path();
        $method = $request->method();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['regex'], $path, $matches) === 1) {
                $params = [];
                foreach ($matches as $key => $value) {
                    if (is_string($key)) {
                        $params[$key] = $value;
                    }
                }
                $request->setRouteParams($params);

                foreach ($route['middlewares'] as $middleware) {
                    $middleware->handle($request);
                }

                $handler = $route['handler'];
                if (is_array($handler)) {
                    [$class, $action] = $handler;
                    $controller = new $class();
                    return $controller->$action($request);
                }

                return $handler($request);
            }
        }

        throw new HttpException('NOT_FOUND', 'Endpoint tidak ditemukan.', 404);
    }
}
