<?php

declare(strict_types=1);

session_start();

// Raíz del proyecto: carpeta que contiene /src (típico: repo con /public, o todo junto en /seshat)
$publicDir = __DIR__;
$root = is_dir($publicDir . '/src') ? $publicDir : dirname($publicDir);
if (is_file($root . '/vendor/autoload.php')) {
    require $root . '/vendor/autoload.php';
} else {
    spl_autoload_register(static function (string $class) use ($root): void {
        $prefix = 'App\\';
        if (!str_starts_with($class, $prefix)) {
            return;
        }
        $rel = str_replace('\\', '/', substr($class, strlen($prefix)));
        $path = $root . '/src/' . $rel . '.php';
        if (is_file($path)) {
            require $path;
        }
    });
}

require $root . '/src/helpers.php';

use App\Env;
use App\Router;

Env::load($root . '/.env');

$router = new Router();
$router->get('/', 'HomeController@index');

$router->get('/login', 'AuthController@showLogin');
$router->post('/login', 'AuthController@login');
$router->get('/registro', 'AuthController@showRegister');
$router->post('/registro', 'AuthController@register');
$router->post('/logout', 'AuthController@logout');

$router->get('/panel', 'DashboardController@index');

$router->get('/seguimiento', 'SeguimientoController@form');
$router->post('/seguimiento', 'SeguimientoController@buscar');
$router->get('/seguimiento/{codigo}', 'SeguimientoController@ver');

$router->get('/mis-avaluos', 'ClienteAvaluoController@index');
$router->get('/mis-avaluos/nuevo', 'ClienteAvaluoController@create');
$router->post('/mis-avaluos', 'ClienteAvaluoController@store');
$router->get('/mis-avaluos/{id}', 'ClienteAvaluoController@show');
$router->post('/mis-avaluos/{id}/cotizacion/{cid}/aceptar', 'ClienteAvaluoController@aceptarCotizacion');
$router->get('/mis-avaluos/{id}/disponibilidad', 'ClienteAvaluoController@disponibilidad');
$router->post('/mis-avaluos/{id}/visita', 'ClienteAvaluoController@solicitarVisita');

$router->get('/valuador/avaluos', 'ValuadorAvaluoController@index');
$router->get('/valuador/avaluos/{id}', 'ValuadorAvaluoController@show');
$router->post('/valuador/avaluos/{id}/cotizar', 'ValuadorAvaluoController@cotizar');
$router->post('/valuador/avaluos/{id}/estado', 'ValuadorAvaluoController@actualizarEstado');
$router->post('/valuador/avaluos/{id}/editar', 'ValuadorAvaluoController@editarDetalle');
$router->post('/valuador/avaluos/{id}/visita-realizada', 'ValuadorAvaluoController@visitaRealizada');
$router->post('/valuador/visita/{vid}/confirmar', 'ValuadorAvaluoController@visitaConfirmar');
$router->post('/valuador/visita/{vid}/rechazar', 'ValuadorAvaluoController@visitaRechazar');

$router->get('/notificaciones', 'NotificationController@index');
$router->post('/notificaciones/{id}/leida', 'NotificationController@marcarLeida');

$router->get('/valuador/calendario', 'GoogleCalendarController@instrucciones');
$router->get('/valuador/google/conectar', 'GoogleCalendarController@conectar');
$router->get('/google/callback', 'GoogleCalendarController@callback');

$uri = $_SERVER['REQUEST_URI'] ?? '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$router->dispatch($method, $uri);
