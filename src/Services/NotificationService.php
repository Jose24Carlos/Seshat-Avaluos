<?php

declare(strict_types=1);

namespace App\Services;

use App\Database;
use PDO;

final class NotificationService
{
    public static function crear(
        int $usuarioId,
        string $tipo,
        string $titulo,
        string $cuerpo,
        ?array $datos = null
    ): void {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            'INSERT INTO notificaciones (usuario_id, tipo, titulo, cuerpo, datos_json) VALUES (?,?,?,?,?)'
        );
        $json = $datos !== null ? json_encode($datos, JSON_UNESCAPED_UNICODE) : null;
        $stmt->execute([$usuarioId, $tipo, $titulo, $cuerpo, $json]);
    }

    /** @return list<array<string, mixed>> */
    public static function listarNoLeidas(int $usuarioId, int $limite = 30): array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            'SELECT * FROM notificaciones WHERE usuario_id = ? AND leida_en IS NULL ORDER BY creado_en DESC LIMIT ' . (int) $limite
        );
        $stmt->execute([$usuarioId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return list<array<string, mixed>> */
    public static function listarTodas(int $usuarioId, int $limite = 50): array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            'SELECT * FROM notificaciones WHERE usuario_id = ? ORDER BY creado_en DESC LIMIT ' . (int) $limite
        );
        $stmt->execute([$usuarioId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function marcarLeida(int $notifId, int $usuarioId): void
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            'UPDATE notificaciones SET leida_en = NOW() WHERE id = ? AND usuario_id = ? AND leida_en IS NULL'
        );
        $stmt->execute([$notifId, $usuarioId]);
    }
}
