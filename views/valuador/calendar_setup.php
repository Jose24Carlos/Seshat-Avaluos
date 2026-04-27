<?php
/** @var array $user */
/** @var ?string $error */
/** @var ?string $redirect_uri */
/** @var ?bool $connected */
/** @var ?bool $lib */
?>
<div class="card">
    <h1>Google Calendar</h1>
    <?php if (!empty($error)): ?><div class="err"><?= e($error) ?></div><?php endif; ?>
    <p class="muted">Conecta tu cuenta de Google para que los clientes consulten huecos libres (según tu calendario) y para crear eventos al confirmar visitas.</p>
    <ol style="padding-left:1.2rem;">
        <li>Crea un proyecto en <a href="https://console.cloud.google.com/" target="_blank" rel="noopener">Google Cloud Console</a>.</li>
        <li>Habilita la API de Google Calendar.</li>
        <li>Credenciales → OAuth cliente web. URI de redirección autorizada: <code><?= e($redirect_uri ?? '(configura GOOGLE_REDIRECT_URI en .env)') ?></code></li>
        <li>Copia Client ID y Secret en el archivo <code>.env</code> del servidor.</li>
        <li>Ejecuta <code>composer install</code> en el proyecto para instalar la librería de Google.</li>
    </ol>
    <?php if (isset($lib) && !$lib): ?>
        <div class="err">La librería Google no está cargada. Ejecuta composer install.</div>
    <?php elseif (isset($connected) && $connected): ?>
        <div class="ok">Calendario conectado. Puedes desconectar revocando el acceso desde tu cuenta de Google (seguridad) y borrando tokens en base de datos si hace falta.</div>
        <p><a class="btn secondary" href="<?= url('/valuador/google/conectar') ?>">Volver a autorizar / renovar permisos</a></p>
    <?php else: ?>
        <p><a class="btn" href="<?= url('/valuador/google/conectar') ?>">Conectar con Google</a></p>
    <?php endif; ?>
</div>
