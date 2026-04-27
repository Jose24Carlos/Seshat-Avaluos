<?php
/** @var array $avaluo */
/** @var list<array> $historial */
/** @var string $estado_etiqueta */
use App\Services\AvaluoService;
?>
<div class="card">
    <h1>Seguimiento: <?= e((string) ($avaluo['codigo_seguimiento'] ?? '')) ?></h1>
    <p><strong><?= e((string) $avaluo['titulo']) ?></strong></p>
    <p>Estado actual: <span class="badge"><?= e($estado_etiqueta) ?></span></p>
    <p class="muted">Valuador: <?= e((string) ($avaluo['valuador_nombre'] ?? '—')) ?></p>
    <p>Inmueble: <?= e((string) $avaluo['direccion_inmueble']) ?></p>
</div>
<div class="card">
    <h2>Historial de estados</h2>
    <table>
        <tr><th>Fecha</th><th>Cambio</th><th>Nota</th></tr>
        <?php foreach ($historial as $h): ?>
            <tr>
                <td><?= e((string) $h['creado_en']) ?></td>
                <td><?= e((string) $h['estado_anterior']) ?> → <?= e((string) $h['estado_nuevo']) ?></td>
                <td><?= e((string) ($h['nota'] ?? '')) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>
<p><a href="<?= url('/seguimiento') ?>">Otra consulta</a></p>
