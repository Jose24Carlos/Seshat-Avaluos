<?php

declare(strict_types=1);

namespace App\Services;

use App\Database;
use PDO;

final class AvaluoService
{
    public const ESTADOS = [
        'solicitado' => 'Solicitado',
        'cotizado' => 'Cotizado',
        'espera_visita' => 'En espera de visita',
        'en_proceso' => 'En proceso',
        'finalizado' => 'Finalizado',
        'cancelado' => 'Cancelado',
    ];

    public static function etiquetaEstado(string $estado): string
    {
        return self::ESTADOS[$estado] ?? $estado;
    }

    public static function generarCodigoSeguimiento(): string
    {
        return 'AV-' . strtoupper(bin2hex(random_bytes(4)));
    }

    public static function registrarHistorial(
        int $avaluoId,
        int $usuarioId,
        string $anterior,
        string $nuevo,
        ?string $nota = null
    ): void {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            'INSERT INTO avaluo_estado_historial (avaluo_id, usuario_id, estado_anterior, estado_nuevo, nota) VALUES (?,?,?,?,?)'
        );
        $stmt->execute([$avaluoId, $usuarioId, $anterior, $nuevo, $nota]);
    }

    public static function cambiarEstado(
        int $avaluoId,
        int $actorId,
        string $nuevoEstado,
        ?string $nota = null
    ): void {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT estado FROM avaluos WHERE id = ?');
        $stmt->execute([$avaluoId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return;
        }
        $ant = (string) $row['estado'];
        if ($ant === $nuevoEstado) {
            return;
        }
        $u = $pdo->prepare('UPDATE avaluos SET estado = ?, estado_notas = COALESCE(?, estado_notas) WHERE id = ?');
        $u->execute([$nuevoEstado, $nota, $avaluoId]);
        self::registrarHistorial($avaluoId, $actorId, $ant, $nuevoEstado, $nota);
    }
}
