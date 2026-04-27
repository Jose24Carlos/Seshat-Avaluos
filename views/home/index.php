<?php /** @var ?array $user */ ?>
<div class="card">
    <h1>Gestión y seguimiento de avalúos</h1>
    <p class="muted">Clientes solicitan avalúos; los valuadores cotizan, gestionan estados y calendario. Los clientes siguen el progreso y reciben notificaciones.</p>
    <?php if (!$user): ?>
        <p><a class="btn" href="<?= url('/registro') ?>">Crear cuenta</a> <a class="btn secondary" href="<?= url('/login') ?>">Iniciar sesión</a></p>
    <?php else: ?>
        <p><a class="btn" href="<?= url('/panel') ?>">Ir al panel</a></p>
    <?php endif; ?>
</div>
