<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Minimal regex router with per-route auth/role/CSRF options.
 */
final class Router
{
    /** @var array<int, array{method: string, pattern: string, handler: mixed, options: array<string, mixed>}> */
    private array $routes = [];

    /** @param mixed $handler
     *  @param array<string, mixed> $options
     */
    public function add(string $method, string $path, mixed $handler, array $options = []): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => '/' . trim($path, '/'),
            'handler' => $handler,
            'options' => $options,
        ];
    }

    /** @param mixed $handler
     *  @param array<string, mixed> $options
     */
    public function get(string $path, mixed $handler, array $options = []): void
    {
        $this->add('GET', $path, $handler, $options);
    }

    /** @param mixed $handler
     *  @param array<string, mixed> $options
     */
    public function post(string $path, mixed $handler, array $options = []): void
    {
        $this->add('POST', $path, $handler, $options);
    }

    /** @param mixed $handler
     *  @param array<string, mixed> $options
     */
    public function put(string $path, mixed $handler, array $options = []): void
    {
        $this->add('PUT', $path, $handler, $options);
    }

    /** @param mixed $handler
     *  @param array<string, mixed> $options
     */
    public function patch(string $path, mixed $handler, array $options = []): void
    {
        $this->add('PATCH', $path, $handler, $options);
    }

    /** @param mixed $handler
     *  @param array<string, mixed> $options
     */
    public function delete(string $path, mixed $handler, array $options = []): void
    {
        $this->add('DELETE', $path, $handler, $options);
    }

    public function dispatch(Request $request, Application $app): void
    {
        if ($request->method() === 'OPTIONS') {
            http_response_code(204);
            exit;
        }

        $path = $request->path();
        $methodMismatch = false;
        $allowed = [];

        foreach ($this->routes as $route) {
            $params = $this->match($route['pattern'], $path);
            if ($params === null) {
                continue;
            }
            if ($route['method'] !== $request->method()) {
                $methodMismatch = true;
                $allowed[] = $route['method'];
                continue;
            }

            $options = $route['options'];
            $requiresAuth = (bool) ($options['auth'] ?? false) || !empty($options['roles']);
            $user = null;

            if ($requiresAuth) {
                $user = $request->user() ?? \App\Middleware\AuthMiddleware::authenticate($request, $app);
            }

            if (!empty($options['roles'])) {
                \App\Middleware\RoleMiddleware::authorize($request, (array) $options['roles'], $user);
            }

            $csrfEnabled = (bool) ($options['csrf'] ?? true);
            if ($csrfEnabled && !$request->isReadOnly()) {
                Csrf::validate($request);
            }

            $handler = $route['handler'];
            if (is_array($handler) && count($handler) === 2 && is_string($handler[0])) {
                $class = $handler[0];
                $method = $handler[1];
                $controller = new $class($app);
                $result = $controller->{$method}($request, $params);
            } elseif (is_callable($handler)) {
                $result = $handler($request, $params, $app);
            } else {
                throw new ApiException('INTERNAL_ERROR', 'Route handler is invalid.', 500);
            }

            if (is_array($result)) {
                $status = isset($result['status']) && is_int($result['status']) ? $result['status'] : 200;
                $meta = isset($result['meta']) && is_array($result['meta']) ? $result['meta'] : [];
                $data = array_key_exists('data', $result) ? $result['data'] : $result;
                Response::json($data, $status, $meta);
            }

            return;
        }

        if ($methodMismatch) {
            header('Allow: ' . implode(', ', array_values(array_unique($allowed))));
            throw ApiException::methodNotAllowed();
        }

        throw ApiException::notFound('The API endpoint was not found.');
    }

    /** @return array<string, string>|null */
    private function match(string $pattern, string $path): ?array
    {
        $regex = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', static function (array $m): string {
            return '(?P<' . $m[1] . '>[^/]+)';
        }, $pattern);

        $regex = '#^' . $regex . '$#';
        if (!preg_match($regex, $path, $matches)) {
            return null;
        }

        $params = [];
        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $params[$key] = rawurldecode($value);
            }
        }

        return $params;
    }
}
