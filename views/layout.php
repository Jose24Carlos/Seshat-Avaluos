<?php
/** @var string $title */
/** @var ?array $user */
$user = $user ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Seshat') ?> — Avalúos</title>
    <style>
        :root { --bg: #f4f6f8; --card: #fff; --text: #1a1d21; --muted: #5c6570; --accent: #0b5fff; --border: #e2e6ea; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; background: var(--bg); color: var(--text); line-height: 1.5; }
        a { color: var(--accent); text-decoration: none; }
        a:hover { text-decoration: underline; }
        header { background: var(--card); border-bottom: 1px solid var(--border); padding: 0.75rem 1.25rem; display: flex; flex-wrap: wrap; align-items: center; gap: 0.75rem; justify-content: space-between; }
        header nav { display: flex; flex-wrap: wrap; gap: 0.65rem 1rem; align-items: center; }
        main { max-width: 960px; margin: 0 auto; padding: 1.25rem; }
        .card { background: var(--card); border: 1px solid var(--border); border-radius: 10px; padding: 1rem 1.15rem; margin-bottom: 1rem; }
        h1 { font-size: 1.35rem; margin: 0 0 0.75rem; }
        h2 { font-size: 1.1rem; margin: 0 0 0.5rem; }
        table { width: 100%; border-collapse: collapse; font-size: 0.92rem; }
        th, td { border: 1px solid var(--border); padding: 0.45rem 0.55rem; text-align: left; }
        th { background: #f8fafc; }
        .muted { color: var(--muted); font-size: 0.9rem; }
        .badge { display: inline-block; padding: 0.15rem 0.45rem; border-radius: 6px; background: #e8eef7; font-size: 0.8rem; }
        .err { background: #fde8e8; color: #9b1c1c; padding: 0.65rem 0.85rem; border-radius: 8px; margin-bottom: 1rem; }
        .ok { background: #e6f4ea; color: #137333; padding: 0.65rem 0.85rem; border-radius: 8px; margin-bottom: 1rem; }
        label { display: block; margin: 0.35rem 0 0.15rem; font-weight: 600; font-size: 0.88rem; }
        input, textarea, select { width: 100%; max-width: 520px; padding: 0.45rem 0.55rem; border: 1px solid var(--border); border-radius: 6px; font: inherit; }
        textarea { min-height: 90px; }
        button, .btn { display: inline-block; padding: 0.45rem 0.85rem; border-radius: 6px; border: 1px solid var(--accent); background: var(--accent); color: #fff; font: inherit; cursor: pointer; text-decoration: none; }
        button.secondary, .btn.secondary { background: #fff; color: var(--accent); }
        .row { display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center; }
        ul.ntf { list-style: none; padding: 0; margin: 0; }
        ul.ntf li { padding: 0.5rem 0; border-bottom: 1px solid var(--border); }
    </style>
</head>
<body>
<header>
    <strong><a href="<?= url('/') ?>" style="color:inherit;text-decoration:none;">Seshat Avalúos</a></strong>
    <nav>
        <?php if ($user): ?>
            <span class="muted"><?= e((string) ($user['nombre'] ?? '')) ?> (<?= e((string) ($user['rol'] ?? '')) ?>)</span>
            <a href="<?= url('/panel') ?>">Panel</a>
            <?php if (($user['rol'] ?? '') === 'cliente'): ?>
                <a href="<?= url('/mis-avaluos') ?>">Mis avalúos</a>
            <?php else: ?>
                <a href="<?= url('/valuador/avaluos') ?>">Avalúos</a>
                <a href="<?= url('/valuador/calendario') ?>">Calendario</a>
            <?php endif; ?>
            <a href="<?= url('/notificaciones') ?>">Notificaciones</a>
            <form action="<?= url('/logout') ?>" method="post" style="display:inline;margin:0;">
                <input type="hidden" name="_csrf" value="<?= e(\App\Csrf::token()) ?>">
                <button type="submit" class="secondary" style="padding:0.25rem 0.5rem;font-size:0.85rem;">Salir</button>
            </form>
        <?php else: ?>
            <a href="<?= url('/seguimiento') ?>">Seguimiento</a>
            <a href="<?= url('/login') ?>">Entrar</a>
            <a href="<?= url('/registro') ?>">Registro</a>
        <?php endif; ?>
    </nav>
</header>
<main>
    <?= $content ?? '' ?>
</main>
</body>
</html>
