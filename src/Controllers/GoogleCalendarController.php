<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Env;
use App\Services\CalendarService;
use App\View;

final class GoogleCalendarController extends BaseController
{
    /** @param array<string, string> $_ */
    public function conectar(array $_): void
    {
        $u = $this->requireRol('valuador');
        $client = CalendarService::clienteGoogle();
        if ($client === null) {
            echo View::render('valuador/calendar_setup', [
                'title' => 'Google Calendar',
                'user' => $u,
                'error' => 'Faltan GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET o GOOGLE_REDIRECT_URI en .env, o la librería Google no está instalada (composer install).',
            ]);
            return;
        }
        $_SESSION['google_oauth_uid'] = (int) $u['id'];
        $url = $client->createAuthUrl();
        header('Location: ' . $url, true, 302);
    }

    /** @param array<string, string> $_ */
    public function callback(array $_): void
    {
        $u = $this->usuarioActual();
        if ($u === null || ($u['rol'] ?? '') !== 'valuador') {
            $this->redirect('/login');
            return;
        }
        $client = CalendarService::clienteGoogle();
        if ($client === null || !isset($_GET['code'])) {
            $this->redirect('/valuador/calendario');
            return;
        }
        $code = (string) $_GET['code'];
        $token = $client->fetchAccessTokenWithAuthCode($code);
        if (isset($token['error'])) {
            $this->redirect('/valuador/calendario');
            return;
        }
        CalendarService::guardarTokensValuador((int) $u['id'], $token);
        unset($_SESSION['google_oauth_uid']);
        $this->redirect('/valuador/calendario');
    }

    /** @param array<string, string> $_ */
    public function instrucciones(array $_): void
    {
        $u = $this->requireRol('valuador');
        $redirect = Env::get('GOOGLE_REDIRECT_URI', '');
        echo View::render('valuador/calendar_setup', [
            'title' => 'Google Calendar',
            'user' => $u,
            'redirect_uri' => $redirect,
            'connected' => !empty($u['google_refresh_token']),
            'lib' => CalendarService::libreriaDisponible(),
        ]);
    }
}
