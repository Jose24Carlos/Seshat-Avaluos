<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Csrf;
use App\Database;
use App\Url;
use App\View;
use PDO;

abstract class BaseController
{
    /** @return array<string, mixed>|null */
    protected function usuarioActual(): ?array
    {
        if (empty($_SESSION['user_id'])) {
            return null;
        }
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE id = ?');
        $stmt->execute([(int) $_SESSION['user_id']]);
        $u = $stmt->fetch(PDO::FETCH_ASSOC);
        return $u ?: null;
    }

    /** @return array<string, mixed> */
    protected function requireAuth(): array
    {
        $u = $this->usuarioActual();
        if ($u === null) {
            $this->redirect('/login');
            exit;
        }
        return $u;
    }

    /** @return array<string, mixed> */
    protected function requireRol(string $rol): array
    {
        $u = $this->requireAuth();
        if (($u['rol'] ?? '') !== $rol) {
            http_response_code(403);
            echo View::render('errors/403', ['title' => 'Acceso denegado']);
            exit;
        }
        return $u;
    }

    protected function redirect(string $url): void
    {
        if (preg_match('#^https?://#i', $url)) {
            header('Location: ' . $url, true, 302);
            return;
        }
        header('Location: ' . Url::to($url), true, 302);
    }

    protected function validateCsrf(): bool
    {
        $t = $_POST['_csrf'] ?? '';
        return Csrf::validate(is_string($t) ? $t : null);
    }

    protected function abortCsrf(): void
    {
        http_response_code(419);
        echo 'Token de seguridad inválido o caducado. Vuelve atrás e intenta de nuevo.';
        exit;
    }

    protected function json(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}
