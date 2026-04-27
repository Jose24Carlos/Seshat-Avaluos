<?php
/** @var array $user */
/** @var list<array> $avaluos */
use App\Services\AvaluoService;
?>
<div class="card">
    <h1>Avalúos asignados</h1>
    <p><a href="<?= url('/valuador/calendario') ?>">Conectar Google Calendar</a> para que los clientes vean disponibilidad.</p>
</div>
<div class="card">
    <?php if ($avaluos === []): ?>
        <p class="muted">No tienes avalúos asignados.</p>
    <?php else: ?>
        <table>
            <tr><th>Título</th><th>Cliente</th><th>Estado</th><th>Código</th><th></th></tr>
            <?php foreach ($avaluos as $a): ?>
                <tr>
                    <td><?= e((string) $a['titulo']) ?></td>
                    <td><?= e((string) ($a['cliente_nombre'] ?? '')) ?></td>
                    <td><?= e(AvaluoService::etiquetaEstado((string) $a['estado'])) ?></td>
                    <td><code><?= e((string) ($a['codigo_seguimiento'] ?? '—')) ?></code></td>
                    <td><a href="<?= url('/valuador/avaluos/' . (int) $a['id']) ?>">Gestionar</a></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>
