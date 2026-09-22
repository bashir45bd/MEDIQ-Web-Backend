<?php
declare(strict_types=1);

namespace App\Helpers;

class Router {
    private static array $routes = [];

    public static function get(string $path, callable|array $handler, array $middlewares = []): void {
        self::addRoute('GET', $path, $handler, $middlewares);
    }

    public static function post(string $path, callable|array $handler, array $middlewares = []): void {
        self::addRoute('POST', $path, $handler, $middlewares);
    }

    public static function put(string $path, callable|array $handler, array $middlewares = []): void {
        self::addRoute('PUT', $path, $handler, $middlewares);
    }

    public static function delete(string $path, callable|array $handler, array $middlewares = []): void {
        self::addRoute('DELETE', $path, $handler, $middlewares);
    }

    private static function addRoute(string $method, string $path, callable|array $handler, array $middlewares = []): void {
        self::$routes[] = [
            'method'      => strtoupper($method),
            'path'        => $path,
            'handler'     => $handler,
            'middlewares' => $middlewares
        ];
    }

    public static function dispatch(string $requestMethod, string $requestUri): void {
        // Strip query string
        $uri = parse_url($requestUri, PHP_URL_PATH) ?? '/';
        $method = strtoupper($requestMethod);

        // Normalize slashes
        $uri = rtrim($uri, '/');
        if ($uri === '') {
            $uri = '/';
        }

        // Support subfolders (e.g. /mediq/backend/public/api/v1/...) in XAMPP htdocs
        if (($pos = strpos($uri, '/api/v1')) !== false) {
            $uri = substr($uri, $pos);
        }

        foreach (self::$routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $pattern = self::convertPathToRegex($route['path']);
            if (preg_match($pattern, $uri, $matches)) {
                // Extract named params
                $params = [];
                foreach ($matches as $key => $val) {
                    if (is_string($key)) {
                        $params[$key] = $val;
                    }
                }

                // Execute route middlewares
                foreach ($route['middlewares'] as $mw) {
                    if (is_callable($mw)) {
                        $mw();
                    } elseif (is_string($mw) && method_exists($mw, 'handle')) {
                        $mw::handle();
                    }
                }

                // Execute handler
                $handler = $route['handler'];
                if (is_array($handler)) {
                    [$class, $action] = $handler;
                    $controller = new $class();
                    $controller->$action($params);
                    return;
                }

                if (is_callable($handler)) {
                    $handler($params);
                    return;
                }
            }
        }

        Response::error('ROUTE_NOT_FOUND', "Endpoint {$method} {$uri} not found.", 404);
    }

    private static function convertPathToRegex(string $path): string {
        $path = rtrim($path, '/');
        if ($path === '') {
            $path = '/';
        }

        // Replace {id} with (?P<id>[^/]+)
        $regex = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $regex . '$#';
    }
}
