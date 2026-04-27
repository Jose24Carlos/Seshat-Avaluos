<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Database;
use App\Env;
use App\Services\AvaluoService;
use App\Services\CalendarService;
use App\Services\NotificationService;
use App\View;
use DateTimeImmutable;
use DateTimeZone;
use PDO;

final class ClienteAvaluoController extends BaseController
{
    /** @param array<string, string> $_ */
    public function index(array $_): void
    {
        $u = $this->requireRol('cliente');
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            'SELECT a.*, v.nombre AS valuador_nombre FROM avaluos a
             LEFT JOIN usuarios v ON v.id = a.valuador_id
             WHERE a.cliente_id = ? ORDER BY a.actualizado_en DESC'
        );
        $stmt->execute([(int) $u['id']]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo View::render('cliente/avaluos_index', ['title' => 'Mis avalúos', 'user' => $u, 'avaluos' => $rows]);
    }

    /** @param array<string, string> $_ */
    public function create(array $_): void
    {
        $u = $this->requireRol('cliente');
        $pdo = Database::pdo();
        $vals = $pdo->query("SELECT id, nombre, email FROM usuarios WHERE rol = 'valuador' ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
        echo View::render('cliente/avaluos_nuevo', ['title' => 'Solicitar avalúo', 'user' => $u, 'valuadores' => $vals]);
    }

    /** @param array<string, string> $_ */
    public function store(array $_): void
    {
        if (!$this->validateCsrf()) {
            $this->abortCsrf();
        }
        $u = $this->requireRol('cliente');
        $valuadorId = (int) ($_POST['valuador_id'] ?? 0);
        $titulo = trim((string) ($_POST['titulo'] ?? ''));
        $descripcion = trim((string) ($_POST['descripcion'] ?? ''));
        $direccion = trim((string) ($_POST['direccion_inmueble'] ?? ''));
        $tipo = trim((string) ($_POST['tipo_inmueble'] ?? ''));
        if ($valuadorId < 1 || $titulo === '' || $direccion === '') {
            $pdo = Database::pdo();
            $vals = $pdo->query("SELECT id, nombre, email FROM usuarios WHERE rol = 'valuador' ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
            echo View::render('cliente/avaluos_nuevo', [
                'title' => 'Solicitar avalúo',
                'user' => $u,
                'valuadores' => $vals,
                'error' => 'Selecciona valuador, título y dirección del inmueble.',
            ]);
            return;
        }
        $pdo = Database::pdo();
        $chk = $pdo->prepare("SELECT id FROM usuarios WHERE id = ? AND rol = 'valuador'");
        $chk->execute([$valuadorId]);
        if (!$chk->fetch()) {
            $this->redirect('/mis-avaluos/nuevo');
            return;
        }
        $stmt = $pdo->prepare(
            'INSERT INTO avaluos (cliente_id, valuador_id, titulo, descripcion, direccion_inmueble, tipo_inmueble, estado)
             VALUES (?,?,?,?,?,?,\'solicitado\')'
        );
        $stmt->execute([
            (int) $u['id'],
            $valuadorId,
            $titulo,
            $descripcion !== '' ? $descripcion : null,
            $direccion,
            $tipo !== '' ? $tipo : null,
        ]);
        $aid = (int) $pdo->lastInsertId();
        AvaluoService::registrarHistorial($aid, (int) $u['id'], '', 'solicitado', 'Solicitud creada');
        $vstmt = $pdo->prepare('SELECT nombre FROM usuarios WHERE id = ?');
        $vstmt->execute([$valuadorId]);
        $vn = (string) ($vstmt->fetchColumn() ?: 'Valuador');
        NotificationService::crear(
            $valuadorId,
            'nueva_solicitud',
            'Nueva solicitud de avalúo',
            'El cliente ' . ($u['nombre'] ?? '') . ' ha solicitado un avalúo: ' . $titulo,
            ['avaluo_id' => $aid]
        );
        $this->redirect('/mis-avaluos/' . $aid);
    }

    /** @param array<string, string> $p */
    public function show(array $p): void
    {
        $u = $this->requireRol('cliente');
        $id = (int) ($p['id'] ?? 0);
        $row = $this->fetchAvaluoCliente($id, (int) $u['id']);
        if (!$row) {
            http_response_code(404);
            echo View::render('errors/404', ['title' => 'No encontrado']);
            return;
        }
        $pdo = Database::pdo();
        $cots = $pdo->prepare('SELECT * FROM cotizaciones WHERE avaluo_id = ? ORDER BY creado_en DESC');
        $cots->execute([$id]);
        $cotizaciones = $cots->fetchAll(PDO::FETCH_ASSOC);
        $vis = $pdo->prepare('SELECT * FROM visita_solicitudes WHERE avaluo_id = ? ORDER BY creado_en DESC');
        $vis->execute([$id]);
        $visitas = $vis->fetchAll(PDO::FETCH_ASSOC);
        $hist = $pdo->prepare('SELECT h.*, u.nombre FROM avaluo_estado_historial h JOIN usuarios u ON u.id = h.usuario_id WHERE h.avaluo_id = ? ORDER BY h.creado_en DESC');
        $hist->execute([$id]);
        $historial = $hist->fetchAll(PDO::FETCH_ASSOC);
        $vstmt = $pdo->prepare('SELECT * FROM usuarios WHERE id = ?');
        $vstmt->execute([(int) $row['valuador_id']]);
        $valuador = $vstmt->fetch(PDO::FETCH_ASSOC) ?: [];
        echo View::render('cliente/avaluo_show', [
            'title' => $row['titulo'],
            'user' => $u,
            'avaluo' => $row,
            'cotizaciones' => $cotizaciones,
            'visitas' => $visitas,
            'historial' => $historial,
            'valuador' => $valuador,
            'calendar_ok' => CalendarService::libreriaDisponible() && CalendarService::clienteGoogle() !== null
                && !empty($valuador['google_refresh_token']),
        ]);
    }

    /** @param array<string, string> $p */
    public function aceptarCotizacion(array $p): void
    {
        if (!$this->validateCsrf()) {
            $this->abortCsrf();
        }
        $u = $this->requireRol('cliente');
        $aid = (int) ($p['id'] ?? 0);
        $cid = (int) ($p['cid'] ?? 0);
        $row = $this->fetchAvaluoCliente($aid, (int) $u['id']);
        if (!$row) {
            http_response_code(404);
            return;
        }
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT * FROM cotizaciones WHERE id = ? AND avaluo_id = ? AND estado = ?');
            $stmt->execute([$cid, $aid, 'pendiente']);
            $cot = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$cot) {
                $pdo->rollBack();
                $this->redirect('/mis-avaluos/' . $aid);
                return;
            }
            $codigo = AvaluoService::generarCodigoSeguimiento();
            $pdo->prepare('UPDATE cotizaciones SET estado = ? WHERE id = ?')->execute(['aceptada', $cid]);
            $pdo->prepare('UPDATE cotizaciones SET estado = ? WHERE avaluo_id = ? AND id != ?')->execute(['rechazada', $aid, $cid]);
            $pdo->prepare('UPDATE avaluos SET codigo_seguimiento = ?, estado = ? WHERE id = ?')->execute([$codigo, 'espera_visita', $aid]);
            AvaluoService::registrarHistorial($aid, (int) $u['id'], (string) $row['estado'], 'espera_visita', 'Cotización aceptada');
            $pdo->commit();
        } catch (\Throwable) {
            $pdo->rollBack();
            $this->redirect('/mis-avaluos/' . $aid);
            return;
        }
        NotificationService::crear(
            (int) $row['valuador_id'],
            'cotizacion_aceptada',
            'Cotización aceptada',
            'El cliente aceptó la cotización del avalúo «' . $row['titulo'] . '». Código: ' . $codigo,
            ['avaluo_id' => $aid]
        );
        NotificationService::crear(
            (int) $u['id'],
            'codigo_seguimiento',
            'Código de seguimiento',
            'Tu código para seguir el avalúo es: ' . $codigo,
            ['avaluo_id' => $aid, 'codigo' => $codigo]
        );
        $this->redirect('/mis-avaluos/' . $aid);
    }

    /** @param array<string, string> $p */
    public function solicitarVisita(array $p): void
    {
        if (!$this->validateCsrf()) {
            $this->abortCsrf();
        }
        $u = $this->requireRol('cliente');
        $aid = (int) ($p['id'] ?? 0);
        $inicio = (string) ($_POST['inicio'] ?? '');
        $fin = (string) ($_POST['fin'] ?? '');
        $mensaje = trim((string) ($_POST['mensaje'] ?? ''));
        $row = $this->fetchAvaluoCliente($aid, (int) $u['id']);
        if (!$row || ($row['estado'] ?? '') !== 'espera_visita') {
            $this->redirect('/mis-avaluos');
            return;
        }
        if ($inicio === '' || $fin === '') {
            $this->redirect('/mis-avaluos/' . $aid);
            return;
        }
        $tz = new DateTimeZone(Env::get('APP_TIMEZONE', 'America/Chicago') ?? 'America/Chicago');
        $iniDb = self::parseDatetimeLocal($inicio, $tz);
        $finDb = self::parseDatetimeLocal($fin, $tz);
        if ($iniDb === null || $finDb === null) {
            $this->redirect('/mis-avaluos/' . $aid);
            return;
        }
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            'INSERT INTO visita_solicitudes (avaluo_id, cliente_id, inicio_propuesto, fin_propuesto, mensaje) VALUES (?,?,?,?,?)'
        );
        $stmt->execute([$aid, (int) $u['id'], $iniDb, $finDb, $mensaje !== '' ? $mensaje : null]);
        NotificationService::crear(
            (int) $row['valuador_id'],
            'visita_solicitada',
            'Solicitud de visita',
            'El cliente propuso una fecha para la visita del avalúo «' . $row['titulo'] . '».',
            ['avaluo_id' => $aid]
        );
        $this->redirect('/mis-avaluos/' . $aid);
    }

    /** @param array<string, string> $p */
    public function disponibilidad(array $p): void
    {
        $u = $this->requireRol('cliente');
        $aid = (int) ($p['id'] ?? 0);
        $row = $this->fetchAvaluoCliente($aid, (int) $u['id']);
        if (!$row) {
            $this->json(['error' => 'No autorizado'], 403);
            return;
        }
        $vid = (int) $row['valuador_id'];
        $val = CalendarService::usuarioPorId($vid);
        if (!$val) {
            $this->json(['slots' => [], 'mensaje' => 'Valuador no encontrado']);
            return;
        }
        $desde = $_GET['desde'] ?? date('c');
        $hasta = $_GET['hasta'] ?? date('c', strtotime('+14 days'));
        $slots = CalendarService::slotsLibres($val, (string) $desde, (string) $hasta, 60, 30);
        $this->json(['slots' => $slots]);
    }

    private static function parseDatetimeLocal(string $valor, DateTimeZone $tz): ?string
    {
        $valor = trim($valor);
        foreach (['Y-m-d\TH:i', 'Y-m-d\TH:i:s'] as $f) {
            $dt = DateTimeImmutable::createFromFormat($f, $valor, $tz);
            if ($dt instanceof DateTimeImmutable) {
                return $dt->format('Y-m-d H:i:s');
            }
        }
        return null;
    }

    /** @return array<string, mixed>|null */
    private function fetchAvaluoCliente(int $id, int $clienteId): ?array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT * FROM avaluos WHERE id = ? AND cliente_id = ?');
        $stmt->execute([$id, $clienteId]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }
}
