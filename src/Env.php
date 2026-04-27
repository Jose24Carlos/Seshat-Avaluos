<?php

declare(strict_types=1);

namespace App;

final class Env
{
    /** @var array<string, string> */
    private static array $vars = [];

    public static function load(string $path): void
    {
        if (!is_readable($path)) {
            return;
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (!str_contains($line, '=')) {
                continue;
            }
            [$k, $v] = explode('=', $line, 2);
            $k = trim($k);
            $v = trim($v);
            if ($v !== '' && ($v[0] === '"' || $v[0] === "'")) {
                $q = $v[0];
                if (str_ends_with($v, $q)) {
                    $v = substr($v, 1, -1);
                }
            }
            self::$vars[$k] = $v;
            $_ENV[$k] = $v;
            putenv($k . '=' . $v);
        }
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        if (array_key_exists($key, self::$vars)) {
            return self::$vars[$key];
        }
        $v = $_ENV[$key] ?? getenv($key);
        if ($v === false || $v === '') {
            return $default;
        }
        return (string) $v;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $v = self::get($key);
        if ($v === null) {
            return $default;
        }
        return in_array(strtolower($v), ['1', 'true', 'yes', 'on'], true);
    }
}
