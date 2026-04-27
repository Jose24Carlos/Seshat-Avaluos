<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Database;
use App\Services\NotificationService;
use App\View;
use PDO;

final class DashboardController extends BaseController
{
    /** @param array<string, string> $_ */
    public function index(array $_): void
    {
        $u = $this->requireAuth();
        $pdo = Database::pdo();
        if ($u['rol'] === 'cliente') {
            $stmt = $pdo->prepare(
                'SELECT a.*, u.nombre AS valuador_nombre FROM avaluos a
                 LEFT JOIN usuarios u ON u.id = a.valuador_id
                 WHERE a.cliente_id = ? ORDER BY a.actualizado_en DESC LIMIT 20'
            );
            $stmt->execute([(int) $u['id']]);
            $avaluos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $stmt = $pdo->prepare(
                'SELECT a.*, c.nombre AS cliente_nombre FROM avaluos a
                 JOIN usuarios c ON c.id = a.cliente_id
                 WHERE a.valuador_id = ? ORDER BY a.actualizado_en DESC LIMIT 50'
            );
            $stmt->execute([(int) $u['id']]);
            $avaluos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        $notifs = NotificationService::listarNoLeidas((int) $u['id'], 15);
        echo View::render('panel/index', [
            'title' => 'Panel',
            'user' => $u,
            'avaluos' => $avaluos,
            'notificaciones' => $notifs,
        ]);
    }
}
