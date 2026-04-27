<?php
/**
 * Diagnóstico de servidor de hosting — avalúo / aplicación web
 *
 * IMPORTANTE: Elimina o protege este archivo en producción (solo uso temporal).
 * Opcional: define DIAGNOSTICO_CLAVE antes de incluir, o descomenta la verificación abajo.
 */

declare(strict_types=1);

// Descomenta y define una clave para acceso por URL: ?clave=tu_secreto
// define('DIAGNOSTICO_CLAVE', 'cambia-esto-y-borralo');

if (defined('DIAGNOSTICO_CLAVE') && DIAGNOSTICO_CLAVE !== '') {
    $clave = isset($_GET['clave']) ? (string) $_GET['clave'] : '';
    if (!hash_equals(DIAGNOSTICO_CLAVE, $clave)) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Acceso denegado.';
        exit;
    }
}

header('Content-Type: text/html; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow');

$extRecomendadas = [
    'pdo' => 'PDO (bases de datos)',
    'pdo_mysql' => 'PDO MySQL',
    'mysqli' => 'MySQLi',
    'curl' => 'HTTP cliente/servicios',
    'openssl' => 'HTTPS y cifrado',
    'mbstring' => 'Texto multibyte (UTF-8)',
    'json' => 'JSON API',
    'fileinfo' => 'Tipos MIME / subidas',
    'zip' => 'Paquetes y despliegue',
    'gd' => 'Imágenes (miniaturas, PDF con libs)',
    'intl' => 'Internacionalización',
    'xml' => 'XML / SOAP',
    'dom' => 'DOM / plantillas',
    'session' => 'Sesiones',
    'filter' => 'Validación de entrada',
    'hash' => 'Hashes',
    'zlib' => 'Compresión',
];

function siNo(bool $ok): string
{
    return $ok ? '<span class="ok">Sí</span>' : '<span class="no">No</span>';
}

function bytesLegibles(int|float $bytes): string
{
    $u = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($u) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $u[$i];
}

function iniBytes(string $key): int
{
    $v = ini_get($key);
    if ($v === false || $v === '') {
        return 0;
    }
    $v = trim((string) $v);
    $last = strtolower($v[strlen($v) - 1]);
    $num = (int) $v;
    return match ($last) {
        'g' => $num * 1024 * 1024 * 1024,
        'm' => $num * 1024 * 1024,
        'k' => $num * 1024,
        default => (int) $v,
    };
}

$phpVersion = PHP_VERSION;
$phpSapi = PHP_SAPI;
$serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? '(no informado)';
$documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? '(no informado)';
$serverName = $_SERVER['SERVER_NAME'] ?? '(no informado)';
$tempDir = sys_get_temp_dir();
$tempWritable = is_writable($tempDir);
$docWritable = is_dir($documentRoot) && is_writable($documentRoot);

$ini = [
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'max_input_time' => ini_get('max_input_time'),
    'post_max_size' => ini_get('post_max_size'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'max_file_uploads' => ini_get('max_file_uploads'),
    'default_charset' => ini_get('default_charset') ?: '(vacío)',
    'date.timezone' => ini_get('date.timezone') ?: '(vacío)',
    'display_errors' => ini_get('display_errors'),
    'log_errors' => ini_get('log_errors'),
    'session.save_handler' => ini_get('session.save_handler'),
    'allow_url_fopen' => ini_get('allow_url_fopen'),
];

$disabled = ini_get('disable_functions');
$disabledList = $disabled ? array_filter(array_map('trim', explode(',', $disabled))) : [];

$opcacheLoaded = extension_loaded('Zend OPcache') || extension_loaded('opcache');
$opcacheEnabled = function_exists('opcache_get_configuration') ? opcache_get_configuration() : null;

?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Diagnóstico de hosting</title>
    <style>
        :root { --ok: #0d6efd; --good: #198754; --bad: #dc3545; --muted: #6c757d; }
        body { font-family: system-ui, sans-serif; margin: 1.5rem; max-width: 960px; color: #212529; line-height: 1.5; }
        h1 { font-size: 1.35rem; margin-bottom: 0.25rem; }
        .aviso { background: #fff3cd; border: 1px solid #ffc107; padding: 0.75rem 1rem; border-radius: 6px; margin: 1rem 0; }
        table { width: 100%; border-collapse: collapse; margin: 1rem 0; font-size: 0.95rem; }
        th, td { border: 1px solid #dee2e6; padding: 0.5rem 0.65rem; text-align: left; }
        th { background: #f8f9fa; }
        .ok { color: var(--good); font-weight: 600; }
        .no { color: var(--bad); font-weight: 600; }
        .muted { color: var(--muted); font-size: 0.9rem; }
        code { background: #f1f3f5; padding: 0.1rem 0.35rem; border-radius: 4px; font-size: 0.88em; }
        footer { margin-top: 2rem; font-size: 0.85rem; color: var(--muted); }
    </style>
</head>
<body>
    <h1>Diagnóstico del servidor de hosting</h1>
    <p class="muted">Uso interno para comprobar compatibilidad con una aplicación web de seguimiento de avalúo. Generado <?php echo htmlspecialchars(date('c'), ENT_QUOTES, 'UTF-8'); ?>.</p>

    <div class="aviso">
        <strong>Seguridad:</strong> borra este archivo (<code>diagnostico-hosting.php</code>) o protégelo con clave cuando termines el diagnóstico. Expone detalles del entorno.
    </div>

    <h2>Entorno PHP</h2>
    <table>
        <tr><th>Concepto</th><th>Valor</th></tr>
        <tr><td>Versión de PHP</td><td><code><?php echo htmlspecialchars($phpVersion, ENT_QUOTES, 'UTF-8'); ?></code></td></tr>
        <tr><td>SAPI</td><td><code><?php echo htmlspecialchars($phpSapi, ENT_QUOTES, 'UTF-8'); ?></code></td></tr>
        <tr><td>Zend Engine</td><td><code><?php echo htmlspecialchars((string) zend_version(), ENT_QUOTES, 'UTF-8'); ?></code></td></tr>
        <tr><td>OPcache cargado</td><td><?php echo siNo($opcacheLoaded); ?></td></tr>
        <?php if (is_array($opcacheEnabled) && isset($opcacheEnabled['directives']['opcache.enable'])): ?>
        <tr><td>OPcache enable (directiva)</td><td><code><?php echo htmlspecialchars((string) $opcacheEnabled['directives']['opcache.enable'], ENT_QUOTES, 'UTF-8'); ?></code></td></tr>
        <?php endif; ?>
    </table>

    <h2>Servidor HTTP</h2>
    <table>
        <tr><th>Concepto</th><th>Valor</th></tr>
        <tr><td>Software</td><td><?php echo htmlspecialchars($serverSoftware, ENT_QUOTES, 'UTF-8'); ?></td></tr>
        <tr><td>SERVER_NAME</td><td><?php echo htmlspecialchars($serverName, ENT_QUOTES, 'UTF-8'); ?></td></tr>
        <tr><td>DOCUMENT_ROOT</td><td><code><?php echo htmlspecialchars($documentRoot, ENT_QUOTES, 'UTF-8'); ?></code></td></tr>
        <tr><td>HTTPS</td><td><?php echo siNo(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'); ?></td></tr>
    </table>

    <h2>Rutas y permisos</h2>
    <table>
        <tr><th>Concepto</th><th>Valor</th></tr>
        <tr><td>Directorio temporal del sistema</td><td><code><?php echo htmlspecialchars($tempDir, ENT_QUOTES, 'UTF-8'); ?></code></td></tr>
        <tr><td>Temporal escribible</td><td><?php echo siNo($tempWritable); ?></td></tr>
        <tr><td>DOCUMENT_ROOT escribible</td><td><?php echo siNo($docWritable); ?> <span class="muted">(no siempre necesario; caché/logs suelen ir fuera del webroot)</span></td></tr>
    </table>

    <h2>Extensiones recomendadas (app web + base de datos)</h2>
    <table>
        <tr><th>Extensión</th><th>Cargada</th><th>Uso típico</th></tr>
        <?php foreach ($extRecomendadas as $ext => $desc): ?>
        <tr>
            <td><code><?php echo htmlspecialchars($ext, ENT_QUOTES, 'UTF-8'); ?></code></td>
            <td><?php echo siNo(extension_loaded($ext)); ?></td>
            <td><?php echo htmlspecialchars($desc, ENT_QUOTES, 'UTF-8'); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <h2>Directivas php.ini relevantes</h2>
    <table>
        <tr><th>Directiva</th><th>Valor</th></tr>
        <?php foreach ($ini as $k => $v): ?>
        <tr><td><code><?php echo htmlspecialchars($k, ENT_QUOTES, 'UTF-8'); ?></code></td><td><?php echo htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); ?></td></tr>
        <?php endforeach; ?>
    </table>
    <p class="muted">Límite efectivo de subida: el menor entre <code>upload_max_filesize</code> y <code>post_max_size</code>. Actualmente aprox. <strong><?php echo htmlspecialchars(bytesLegibles(min(iniBytes('upload_max_filesize'), iniBytes('post_max_size'))), ENT_QUOTES, 'UTF-8'); ?></strong> por petición (orden de magnitud).</p>

    <h2>Funciones deshabilitadas</h2>
    <?php if ($disabledList === []): ?>
        <p>Ninguna listada en <code>disable_functions</code> (o la directiva está vacía).</p>
    <?php else: ?>
        <p class="muted"><?php echo count($disabledList); ?> función(es). Algunas herramientas de despliegue o consola pueden fallar si están aquí.</p>
        <p><code><?php echo htmlspecialchars(implode(', ', array_slice($disabledList, 0, 80)), ENT_QUOTES, 'UTF-8'); ?><?php echo count($disabledList) > 80 ? '…' : ''; ?></code></p>
    <?php endif; ?>

    <h2>Comprobación opcional de MySQL</h2>
    <p class="muted">No se conecta automáticamente. Si el hosting ya te dio host, usuario y base, puedes probar con parámetros GET (solo en entorno de pruebas):</p>
    <p><code>?db_host=localhost&amp;db_user=usuario&amp;db_pass=***&amp;db_name=base</code></p>
    <?php
    $dbHost = isset($_GET['db_host']) ? (string) $_GET['db_host'] : '';
    $dbUser = isset($_GET['db_user']) ? (string) $_GET['db_user'] : '';
    $dbPass = isset($_GET['db_pass']) ? (string) $_GET['db_pass'] : '';
    $dbName = isset($_GET['db_name']) ? (string) $_GET['db_name'] : '';

    if ($dbHost !== '' && $dbUser !== '' && $dbName !== '') {
        echo '<p><strong>Resultado:</strong> ';
        if (!extension_loaded('mysqli')) {
            echo '<span class="no">mysqli no está disponible.</span>';
        } else {
            mysqli_report(MYSQLI_REPORT_OFF);
            $mysqli = @new mysqli($dbHost, $dbUser, $dbPass, $dbName);
            if ($mysqli->connect_error) {
                echo '<span class="no">Error: ' . htmlspecialchars($mysqli->connect_error, ENT_QUOTES, 'UTF-8') . '</span>';
            } else {
                echo '<span class="ok">Conexión correcta.</span> Servidor MySQL: <code>' . htmlspecialchars($mysqli->server_info ?? '', ENT_QUOTES, 'UTF-8') . '</code>';
                $mysqli->close();
            }
        }
        echo '</p>';
    } else {
        echo '<p class="muted">Sin parámetros de base de datos en esta petición.</p>';
    }
    ?>

    <footer>
        Archivo: <code>diagnostico-hosting.php</code> — revisa versión de PHP ≥ 8.1 u 8.2 según tu framework, límites de subida para documentos del avalúo, y extensiones PDO/MySQL.
    </footer>
</body>
</html>
