<?php
/** @var array $user */
/** @var list<array> $avaluos */
use App\Services\AvaluoService;
?>
<div class="card">
    <h1>Mis avalúos</h1>
    <p><a class="btn" href="<?= url('/mis-avaluos/nuevo') ?>">Nueva solicitud</a></p>
</div>
<div class="card">
    <?php if ($avaluos === []): ?>
        <p class="muted">Aún no has solicitado avalúos.</p>
    <?php else: ?>
        <table>
            <tr><th>Título</th><th>Estado</th><th>Código</th><th></th></tr>
            <?php foreach ($avaluos as $a): ?>
                <tr>
                    <td><?= e((string) $a['titulo']) ?></td>
                    <td><?= e(AvaluoService::etiquetaEstado((string) $a['estado'])) ?></td>
                    <td><code><?= e((string) ($a['codigo_seguimiento'] ?? '—')) ?></code></td>
                    <td><a href="<?= url('/mis-avaluos/' . (int) $a['id']) ?>">Detalle</a></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>
