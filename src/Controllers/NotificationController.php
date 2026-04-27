<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\NotificationService;
use App\View;

final class NotificationController extends BaseController
{
    /** @param array<string, string> $_ */
    public function index(array $_): void
    {
        $u = $this->requireAuth();
        $items = NotificationService::listarTodas((int) $u['id'], 80);
        echo View::render('notificaciones/index', [
            'title' => 'Notificaciones',
            'user' => $u,
            'items' => $items,
        ]);
    }

    /** @param array<string, string> $p */
    public function marcarLeida(array $p): void
    {
        if (!$this->validateCsrf()) {
            $this->abortCsrf();
        }
        $u = $this->requireAuth();
        $id = (int) ($p['id'] ?? 0);
        NotificationService::marcarLeida($id, (int) $u['id']);
        $this->redirect('/notificaciones');
    }
}
