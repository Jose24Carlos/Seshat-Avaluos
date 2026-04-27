<?php

declare(strict_types=1);

namespace App;

final class View
{
    public static function render(string $template, array $data = []): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        $path = dirname(__DIR__) . '/views/' . $template . '.php';
        if (!is_file($path)) {
            return '';
        }
        include $path;
        $content = (string) ob_get_clean();
        ob_start();
        $layout = dirname(__DIR__) . '/views/layout.php';
        include $layout;
        return (string) ob_get_clean();
    }
}
