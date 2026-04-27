<?php
/** @var array $user */
/** @var list<array> $avaluos */
/** @var list<array> $notificaciones */
use App\Services\AvaluoService;
?>
<div class="card">
    <h1>Panel</h1>
    <p class="muted">Resumen de tus avalúos y avisos recientes.</p>
</div>
<?php if ($notificaciones !== []): ?>
<div class="card">
    <h2>Sin leer</h2>
    <ul class="ntf">
        <?php foreach ($notificaciones as $n): ?>
            <li>
                <strong><?= e((string) $n['titulo']) ?></strong><br>
                <span class="muted"><?= e((string) $n['cuerpo']) ?></span>
                <form action="<?= url('/notificaciones/' . (int) $n['id'] . '/leida') ?>" method="post" style="display:inline;margin-left:0.5rem;">
                    <input type="hidden" name="_csrf" value="<?= e(\App\Csrf::token()) ?>">
                    <button type="submit" class="secondary" style="font-size:0.8rem;padding:0.2rem 0.45rem;">Marcar leída</button>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>
    <p><a href="<?= url('/notificaciones') ?>">Ver todas</a></p>
</div>
<?php endif; ?>
<div class="card">
    <h2>Avalúos</h2>
    <?php if ($user['rol'] === 'cliente'): ?>
        <p><a class="btn" href="<?= url('/mis-avaluos/nuevo') ?>">Solicitar avalúo</a></p>
    <?php endif; ?>
    <?php if ($avaluos === []): ?>
        <p class="muted">No hay avalúos aún.</p>
    <?php else: ?>
        <table>
            <tr><th>Título</th><th>Estado</th><?php if ($user['rol'] === 'cliente'): ?><th>Valuador</th><?php else: ?><th>Cliente</th><?php endif; ?><th></th></tr>
            <?php foreach ($avaluos as $a): ?>
                <tr>
                    <td><?= e((string) $a['titulo']) ?></td>
                    <td><span class="badge"><?= e(AvaluoService::etiquetaEstado((string) $a['estado'])) ?></span></td>
                    <?php if ($user['rol'] === 'cliente'): ?>
                        <td><?= e((string) ($a['valuador_nombre'] ?? '—')) ?></td>
                        <td><a href="<?= url('/mis-avaluos/' . (int) $a['id']) ?>">Ver</a></td>
                    <?php else: ?>
                        <td><?= e((string) ($a['cliente_nombre'] ?? '')) ?></td>
                        <td><a href="<?= url('/valuador/avaluos/' . (int) $a['id']) ?>">Gestionar</a></td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>
