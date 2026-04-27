<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Database;
use App\Env;
use App\View;
use PDO;

final class AuthController extends BaseController
{
    /** @param array<string, string> $_ */
    public function showLogin(array $_): void
    {
        if ($this->usuarioActual()) {
            $this->redirect('/panel');
            return;
        }
        echo View::render('auth/login', ['title' => 'Iniciar sesión']);
    }

    /** @param array<string, string> $_ */
    public function login(array $_): void
    {
        if (!$this->validateCsrf()) {
            $this->abortCsrf();
        }
        $email = trim((string) ($_POST['email'] ?? ''));
        $pass = (string) ($_POST['password'] ?? '');
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE email = ?');
        $stmt->execute([$email]);
        $u = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$u || !password_verify($pass, (string) $u['password_hash'])) {
            echo View::render('auth/login', [
                'title' => 'Iniciar sesión',
                'error' => 'Credenciales incorrectas.',
                'email' => $email,
            ]);
            return;
        }
        $_SESSION['user_id'] = (int) $u['id'];
        $this->redirect('/panel');
    }

    /** @param array<string, string> $_ */
    public function showRegister(array $_): void
    {
        if ($this->usuarioActual()) {
            $this->redirect('/panel');
            return;
        }
        echo View::render('auth/register', ['title' => 'Registro']);
    }

    /** @param array<string, string> $_ */
    public function register(array $_): void
    {
        if (!$this->validateCsrf()) {
            $this->abortCsrf();
        }
        $nombre = trim((string) ($_POST['nombre'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $telefono = trim((string) ($_POST['telefono'] ?? ''));
        $pass = (string) ($_POST['password'] ?? '');
        $rol = ($_POST['rol'] ?? 'cliente') === 'valuador' ? 'valuador' : 'cliente';
        if ($rol === 'valuador') {
            $secret = Env::get('VALUADOR_REGISTER_SECRET', '');
            $given = (string) ($_POST['valuador_secret'] ?? '');
            if ($secret === null || $secret === '' || !hash_equals($secret, $given)) {
                echo View::render('auth/register', [
                    'title' => 'Registro',
                    'error' => 'Registro como valuador no autorizado. Contacta al administrador o deja el campo secreto vacío y regístrate como cliente.',
                    'nombre' => $nombre,
                    'email' => $email,
                ]);
                return;
            }
        }
        if ($nombre === '' || $email === '' || strlen($pass) < 8) {
            echo View::render('auth/register', [
                'title' => 'Registro',
                'error' => 'Completa nombre, email y contraseña (mínimo 8 caracteres).',
                'nombre' => $nombre,
                'email' => $email,
            ]);
            return;
        }
        $pdo = Database::pdo();
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO usuarios (email, password_hash, nombre, telefono, rol) VALUES (?,?,?,?,?)'
            );
            $stmt->execute([$email, $hash, $nombre, $telefono !== '' ? $telefono : null, $rol]);
        } catch (\Throwable) {
            echo View::render('auth/register', [
                'title' => 'Registro',
                'error' => 'No se pudo registrar (¿email ya existente?).',
                'nombre' => $nombre,
                'email' => $email,
            ]);
            return;
        }
        $_SESSION['user_id'] = (int) $pdo->lastInsertId();
        $this->redirect('/panel');
    }

    /** @param array<string, string> $_ */
    public function logout(array $_): void
    {
        if (!$this->validateCsrf()) {
            $this->abortCsrf();
        }
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        $this->redirect('/');
    }
}
