<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Csrf;
use App\Database;
use App\Services\AvaluoService;
use App\View;
use PDO;

final class SeguimientoController extends BaseController
{
    /** @param array<string, string> $_ */
    public function form(array $_): void
    {
        echo View::render('public/seguimiento', ['title' => 'Seguimiento de avalúo']);
    }

    /** @param array<string, string> $_ */
    public function buscar(array $_): void
    {
        $tok = $_POST['_csrf'] ?? null;
        if (!Csrf::validate(is_string($tok) ? $tok : null)) {
            $this->abortCsrf();
        }
        $codigo = strtoupper(trim((string) ($_POST['codigo'] ?? '')));
        if ($codigo === '') {
            echo View::render('public/seguimiento', ['title' => 'Seguimiento', 'error' => 'Ingresa el código.']);
            return;
        }
        $this->redirect('/seguimiento/' . rawurlencode($codigo));
    }

    /** @param array<string, string> $p */
    public function ver(array $p): void
    {
        $codigo = strtoupper(trim((string) ($p['codigo'] ?? '')));
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT a.*, v.nombre AS valuador_nombre FROM avaluos a LEFT JOIN usuarios v ON v.id = a.valuador_id WHERE a.codigo_seguimiento = ?');
        $stmt->execute([$codigo]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            echo View::render('public/seguimiento', ['title' => 'Seguimiento', 'error' => 'Código no encontrado.', 'codigo' => $codigo]);
            return;
        }
        $hist = $pdo->prepare('SELECT h.*, u.nombre FROM avaluo_estado_historial h JOIN usuarios u ON u.id = h.usuario_id WHERE h.avaluo_id = ? ORDER BY h.creado_en DESC');
        $hist->execute([(int) $row['id']]);
        $historial = $hist->fetchAll(PDO::FETCH_ASSOC);
        echo View::render('public/seguimiento_ver', [
            'title' => 'Seguimiento ' . e($codigo),
            'avaluo' => $row,
            'historial' => $historial,
            'estado_etiqueta' => AvaluoService::etiquetaEstado((string) $row['estado']),
        ]);
    }
}
