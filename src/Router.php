<?php

declare(strict_types=1);

namespace App;

final class Router
{
    /** @var array<string, array<string, array{0: string, 1: string}>> */
    private array $routes = [];

    public function get(string $path, string $handler): void
    {
        $this->routes['GET'][$this->norm($path)] = $this->parse($handler);
    }

    public function post(string $path, string $handler): void
    {
        $this->routes['POST'][$this->norm($path)] = $this->parse($handler);
    }

    /** @return array{0: string, 1: string} */
    private function parse(string $handler): array
    {
        [$class, $method] = explode('@', $handler, 2);
        return [$class, $method];
    }

    private function norm(string $path): string
    {
        $path = '/' . trim($path, '/');
        return $path === '//' ? '/' : $path;
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = '/' . trim((string) $path, '/');
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }
        $base = Url::basePath();
        if ($base !== '' && str_starts_with($path, $base)) {
            $rest = substr($path, strlen($base));
            $path = ($rest === '' || $rest === false) ? '/' : ('/' . ltrim($rest, '/'));
        }
        $method = strtoupper($method);
        $map = $this->routes[$method] ?? [];

        // Coincidencia exacta primero
        if (isset($map[$path])) {
            $this->invoke($map[$path], []);
            return;
        }

        // Rutas con parámetros :id
        foreach ($map as $route => $handler) {
            $regex = preg_replace('#\{([a-z_]+)\}#', '(?P<$1>[^/]+)', $route);
            if ($regex === null) {
                continue;
            }
            $regex = '#^' . $regex . '$#i';
            if (preg_match($regex, $path, $m)) {
                $params = [];
                foreach ($m as $k => $v) {
                    if (is_string($k)) {
                        $params[$k] = $v;
                    }
                }
                $this->invoke($handler, $params);
                return;
            }
        }

        http_response_code(404);
        echo View::render('errors/404', ['title' => 'No encontrado']);
    }

    /** @param array<string, string> $params */
    private function invoke(array $handler, array $params): void
    {
        [$class, $method] = $handler;
        $fqcn = 'App\\Controllers\\' . $class;
        if (!class_exists($fqcn)) {
            http_response_code(500);
            echo 'Controlador no disponible.';
            return;
        }
        $ctrl = new $fqcn();
        if (!method_exists($ctrl, $method)) {
            http_response_code(500);
            echo 'Acción no disponible.';
            return;
        }
        $ctrl->{$method}($params);
    }
}
