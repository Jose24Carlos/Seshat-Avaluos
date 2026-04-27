<?php

declare(strict_types=1);

namespace App;

/**
 * URLs con prefijo de subcarpeta (ej. /seshat) cuando APP_BASE_PATH está definido
 * o se deduce de SCRIPT_NAME.
 */
final class Url
{
    private static ?string $cachedBase = null;

    /** Prefijo sin barra final; vacío si la app está en la raíz del sitio. */
    public static function basePath(): string
    {
        if (self::$cachedBase !== null) {
            return self::$cachedBase;
        }
        $fromEnv = Env::get('APP_BASE_PATH', '');
        if (is_string($fromEnv)) {
            $fromEnv = trim($fromEnv);
        }
        if (is_string($fromEnv) && $fromEnv !== '' && $fromEnv !== '/') {
            self::$cachedBase = '/' . trim($fromEnv, '/');
            return self::$cachedBase;
        }
        $script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
        $script = str_replace('\\', '/', (string) $script);
        $dir = rtrim(str_replace('\\', '/', dirname($script)), '/');
        if ($dir === '' || $dir === '/') {
            self::$cachedBase = '';
            return self::$cachedBase;
        }
        self::$cachedBase = $dir;
        return self::$cachedBase;
    }

    /** Ruta absoluta en el sitio, ej. to('/login') → /seshat/login */
    public static function to(string $path = '/'): string
    {
        $path = trim($path);
        if ($path === '' || $path === '/') {
            $b = self::basePath();
            return $b === '' ? '/' : $b . '/';
        }
        $path = '/' . ltrim($path, '/');
        $b = self::basePath();
        return $b === '' ? $path : $b . $path;
    }
}
