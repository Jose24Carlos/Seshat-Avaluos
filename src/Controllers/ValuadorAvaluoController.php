<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Database;
use App\Services\AvaluoService;
use App\Services\CalendarService;
use App\Services\NotificationService;
use App\View;
use PDO;

final class ValuadorAvaluoController extends BaseController
{
    /** @param array<string, string> $_ */
    public function index(array $_): void
    {
        $u = $this->requireRol('valuador');
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            'SELECT a.*, c.nombre AS cliente_nombre, c.email AS cliente_email FROM avaluos a
             JOIN usuarios c ON c.id = a.cliente_id
             WHERE a.valuador_id = ? ORDER BY a.actualizado_en DESC'
        );
        $stmt->execute([(int) $u['id']]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo View::render('valuador/avaluos_index', ['title' => 'Avalúos asignados', 'user' => $u, 'avaluos' => $rows]);
    }

    /** @param array<string, string> $p */
    public function show(array $p): void
    {
        $u = $this->requireRol('valuador');
        $id = (int) ($p['id'] ?? 0);
        $row = $this->fetchAvaluoValuador($id, (int) $u['id']);
        if (!$row) {
            http_response_code(404);
            echo View::render('errors/404', ['title' => 'No encontrado']);
            return;
        }
        $pdo = Database::pdo();
        $cots = $pdo->prepare('SELECT * FROM cotizaciones WHERE avaluo_id = ? ORDER BY creado_en DESC');
        $cots->execute([$id]);
        $cotizaciones = $cots->fetchAll(PDO::FETCH_ASSOC);
        $vis = $pdo->prepare(
            'SELECT v.*, c.nombre AS cliente_nombre FROM visita_solicitudes v
             JOIN usuarios c ON c.id = v.cliente_id WHERE v.avaluo_id = ? ORDER BY v.creado_en DESC'
        );
        $vis->execute([$id]);
        $visitas = $vis->fetchAll(PDO::FETCH_ASSOC);
        $hist = $pdo->prepare('SELECT h.*, u.nombre FROM avaluo_estado_historial h JOIN usuarios u ON u.id = h.usuario_id WHERE h.avaluo_id = ? ORDER BY h.creado_en DESC');
        $hist->execute([$id]);
        $historial = $hist->fetchAll(PDO::FETCH_ASSOC);
        $cstmt = $pdo->prepare('SELECT nombre, email, telefono FROM usuarios WHERE id = ?');
        $cstmt->execute([(int) $row['cliente_id']]);
        $cliente = $cstmt->fetch(PDO::FETCH_ASSOC) ?: [];
        echo View::render('valuador/avaluo_show', [
            'title' => $row['titulo'],
            'user' => $u,
            'avaluo' => $row,
            'cotizaciones' => $cotizaciones,
            'visitas' => $visitas,
            'historial' => $historial,
            'cliente' => $cliente,
            'estados' => AvaluoService::ESTADOS,
            'calendar_connected' => !empty($u['google_refresh_token']),
        ]);
    }

    /** @param array<string, string> $p */
    public function cotizar(array $p): void
    {
        if (!$this->validateCsrf()) {
            $this->abortCsrf();
        }
        $u = $this->requireRol('valuador');
        $id = (int) ($p['id'] ?? 0);
        $row = $this->fetchAvaluoValuador($id, (int) $u['id']);
        if (!$row || !in_array($row['estado'], ['solicitado', 'cotizado'], true)) {
            $this->redirect('/valuador/avaluos');
            return;
        }
        $monto = (float) str_replace(',', '.', (string) ($_POST['monto'] ?? '0'));
        $moneda = trim((string) ($_POST['moneda'] ?? 'USD')) ?: 'USD';
        $detalle = trim((string) ($_POST['detalle'] ?? ''));
        if ($monto <= 0) {
            $this->redirect('/valuador/avaluos/' . $id);
            return;
        }
        $pdo = Database::pdo();
        $pdo->prepare(
            'INSERT INTO cotizaciones (avaluo_id, valuador_id, monto, moneda, detalle) VALUES (?,?,?,?,?)'
        )->execute([$id, (int) $u['id'], $monto, $moneda, $detalle !== '' ? $detalle : null]);
        AvaluoService::cambiarEstado($id, (int) $u['id'], 'cotizado', 'Cotización enviada');
        NotificationService::crear(
            (int) $row['cliente_id'],
            'nueva_cotizacion',
            'Nueva cotización',
            'Recibiste una cotización para el avalúo «' . $row['titulo'] . '».',
            ['avaluo_id' => $id]
        );
        $this->redirect('/valuador/avaluos/' . $id);
    }

    /** @param array<string, string> $p */
    public function actualizarEstado(array $p): void
    {
        if (!$this->validateCsrf()) {
            $this->abortCsrf();
        }
        $u = $this->requireRol('valuador');
        $id = (int) ($p['id'] ?? 0);
        $row = $this->fetchAvaluoValuador($id, (int) $u['id']);
        if (!$row) {
            http_response_code(404);
            return;
        }
        $nuevo = (string) ($_POST['estado'] ?? '');
        if (!isset(AvaluoService::ESTADOS[$nuevo])) {
            $this->redirect('/valuador/avaluos/' . $id);
            return;
        }
        $nota = trim((string) ($_POST['nota'] ?? ''));
        $ant = (string) $row['estado'];
        AvaluoService::cambiarEstado($id, (int) $u['id'], $nuevo, $nota !== '' ? $nota : null);
        if ($ant !== $nuevo) {
            NotificationService::crear(
                (int) $row['cliente_id'],
                'cambio_estado',
                'Estado del avalúo actualizado',
                'El avalúo «' . $row['titulo'] . '» pasó a: ' . AvaluoService::etiquetaEstado($nuevo),
                ['avaluo_id' => $id, 'estado' => $nuevo]
            );
        }
        $this->redirect('/valuador/avaluos/' . $id);
    }

    /** @param array<string, string> $p */
    public function editarDetalle(array $p): void
    {
        if (!$this->validateCsrf()) {
            $this->abortCsrf();
        }
        $u = $this->requireRol('valuador');
        $id = (int) ($p['id'] ?? 0);
        $row = $this->fetchAvaluoValuador($id, (int) $u['id']);
        if (!$row) {
            http_response_code(404);
            return;
        }
        $titulo = trim((string) ($_POST['titulo'] ?? ''));
        $descripcion = trim((string) ($_POST['descripcion'] ?? ''));
        $direccion = trim((string) ($_POST['direccion_inmueble'] ?? ''));
        $tipo = trim((string) ($_POST['tipo_inmueble'] ?? ''));
        if ($titulo === '' || $direccion === '') {
            $this->redirect('/valuador/avaluos/' . $id);
            return;
        }
        $pdo = Database::pdo();
        $pdo->prepare(
            'UPDATE avaluos SET titulo = ?, descripcion = ?, direccion_inmueble = ?, tipo_inmueble = ? WHERE id = ?'
        )->execute([$titulo, $descripcion !== '' ? $descripcion : null, $direccion, $tipo !== '' ? $tipo : null, $id]);
        NotificationService::crear(
            (int) $row['cliente_id'],
            'avaluo_editado',
            'Datos del avalúo actualizados',
            'El valuador actualizó la información del avalúo «' . $titulo . '».',
            ['avaluo_id' => $id]
        );
        $this->redirect('/valuador/avaluos/' . $id);
    }

    /** @param array<string, string> $p */
    public function visitaConfirmar(array $p): void
    {
        if (!$this->validateCsrf()) {
            $this->abortCsrf();
        }
        $u = $this->requireRol('valuador');
        $vid = (int) ($p['vid'] ?? 0);
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            'SELECT v.id AS vid, v.avaluo_id, v.cliente_id, v.inicio_propuesto, v.fin_propuesto, v.estado AS vestado,
                    a.titulo, a.valuador_id
             FROM visita_solicitudes v JOIN avaluos a ON a.id = v.avaluo_id
             WHERE v.id = ? AND a.valuador_id = ?'
        );
        $stmt->execute([$vid, (int) $u['id']]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$r || ($r['vestado'] ?? '') !== 'pendiente') {
            $this->redirect('/valuador/avaluos');
            return;
        }
        $aid = (int) $r['avaluo_id'];
        $eventId = null;
        if (!empty($u['google_refresh_token'])) {
            $eventId = CalendarService::crearEventoVisita(
                $u,
                'Visita avalúo: ' . $r['titulo'],
                (string) $r['inicio_propuesto'],
                (string) $r['fin_propuesto'],
                'Cliente ID ' . $r['cliente_id']
            );
        }
        $pdo->prepare(
            'UPDATE visita_solicitudes SET estado = ?, evento_google_id = ?, respuesta_valuador = ? WHERE id = ?'
        )->execute(['confirmada', $eventId, 'Confirmada por el valuador', $vid]);
        NotificationService::crear(
            (int) $r['cliente_id'],
            'visita_confirmada',
            'Visita confirmada',
            'Tu visita para el avalúo «' . $r['titulo'] . '» fue confirmada.',
            ['avaluo_id' => $aid]
        );
        $this->redirect('/valuador/avaluos/' . $aid);
    }

    /** @param array<string, string> $p */
    public function visitaRechazar(array $p): void
    {
        if (!$this->validateCsrf()) {
            $this->abortCsrf();
        }
        $u = $this->requireRol('valuador');
        $vid = (int) ($p['vid'] ?? 0);
        $motivo = trim((string) ($_POST['motivo'] ?? ''));
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            'SELECT v.*, a.titulo, a.cliente_id, a.id AS avaluo_id FROM visita_solicitudes v
             JOIN avaluos a ON a.id = v.avaluo_id WHERE v.id = ? AND a.valuador_id = ?'
        );
        $stmt->execute([$vid, (int) $u['id']]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$r || $r['estado'] !== 'pendiente') {
            $this->redirect('/valuador/avaluos');
            return;
        }
        $pdo->prepare(
            'UPDATE visita_solicitudes SET estado = ?, respuesta_valuador = ? WHERE id = ?'
        )->execute(['rechazada', $motivo !== '' ? $motivo : 'Rechazada', $vid]);
        NotificationService::crear(
            (int) $r['cliente_id'],
            'visita_rechazada',
            'Propuesta de visita no aceptada',
            'El valuador no aceptó la fecha propuesta para «' . $r['titulo'] . '». ' . ($motivo !== '' ? 'Motivo: ' . $motivo : ''),
            ['avaluo_id' => (int) $r['avaluo_id']]
        );
        $this->redirect('/valuador/avaluos/' . (int) $r['avaluo_id']);
    }

    /** @param array<string, string> $p */
    public function visitaRealizada(array $p): void
    {
        if (!$this->validateCsrf()) {
            $this->abortCsrf();
        }
        $u = $this->requireRol('valuador');
        $id = (int) ($p['id'] ?? 0);
        $row = $this->fetchAvaluoValuador($id, (int) $u['id']);
        if (!$row || $row['estado'] !== 'espera_visita') {
            $this->redirect('/valuador/avaluos/' . $id);
            return;
        }
        AvaluoService::cambiarEstado($id, (int) $u['id'], 'en_proceso', 'Visita realizada; trabajo en curso');
        NotificationService::crear(
            (int) $row['cliente_id'],
            'cambio_estado',
            'Visita completada',
            'El avalúo «' . $row['titulo'] . '» está en proceso tras la visita.',
            ['avaluo_id' => $id]
        );
        $this->redirect('/valuador/avaluos/' . $id);
    }

    /** @return array<string, mixed>|null */
    private function fetchAvaluoValuador(int $id, int $valuadorId): ?array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT * FROM avaluos WHERE id = ? AND valuador_id = ?');
        $stmt->execute([$id, $valuadorId]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }
}
