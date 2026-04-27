<?php

declare(strict_types=1);

namespace App\Controllers;

use App\View;

final class HomeController extends BaseController
{
    /** @param array<string, string> $_ */
    public function index(array $_): void
    {
        $u = $this->usuarioActual();
        echo View::render('home/index', [
            'title' => 'Inicio',
            'user' => $u,
        ]);
    }
}
