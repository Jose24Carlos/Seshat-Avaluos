<?php
/** @var array $user */
/** @var array $avaluo */
/** @var list<array> $cotizaciones */
/** @var list<array> $visitas */
/** @var list<array> $historial */
/** @var array $cliente */
/** @var array $estados */
/** @var bool $calendar_connected */
use App\Services\AvaluoService;
$id = (int) $avaluo['id'];
?>
<div class="card">
    <h1><?= e((string) $avaluo['titulo']) ?></h1>
    <p class="muted">Estado: <strong><?= e(AvaluoService::etiquetaEstado((string) $avaluo['estado'])) ?></strong>
        <?php if (!empty($avaluo['codigo_seguimiento'])): ?> · Código <code><?= e((string) $avaluo['codigo_seguimiento']) ?></code><?php endif; ?>
    </p>
    <p><strong>Cliente:</strong> <?= e((string) ($cliente['nombre'] ?? '')) ?> — <?= e((string) ($cliente['email'] ?? '')) ?>
        <?php if (!empty($cliente['telefono'])): ?> · <?= e((string) $cliente['telefono']) ?><?php endif; ?></p>
</div>
<div class="card">
    <h2>Editar información del avalúo</h2>
    <form method="post" action="<?= url('/valuador/avaluos/' . $id . '/editar') ?>">
        <input type="hidden" name="_csrf" value="<?= e(\App\Csrf::token()) ?>">
        <label>Título</label>
        <input name="titulo" required value="<?= e((string) $avaluo['titulo']) ?>">
        <label>Dirección</label>
        <input name="direccion_inmueble" required value="<?= e((string) $avaluo['direccion_inmueble']) ?>">
        <label>Tipo</label>
        <input name="tipo_inmueble" value="<?= e((string) ($avaluo['tipo_inmueble'] ?? '')) ?>">
        <label>Descripción</label>
        <textarea name="descripcion"><?= e((string) ($avaluo['descripcion'] ?? '')) ?></textarea>
        <p><button type="submit">Guardar</button></p>
    </form>
</div>
<div class="card">
    <h2>Cambiar estado</h2>
    <form method="post" action="<?= url('/valuador/avaluos/' . $id . '/estado') ?>" class="row">
        <input type="hidden" name="_csrf" value="<?= e(\App\Csrf::token()) ?>">
        <select name="estado">
            <?php foreach ($estados as $k => $lab): ?>
                <option value="<?= e($k) ?>" <?= ($avaluo['estado'] === $k) ? 'selected' : '' ?>><?= e($lab) ?></option>
            <?php endforeach; ?>
        </select>
        <input name="nota" placeholder="Nota interna / mensaje al cliente" style="flex:1;min-width:200px;max-width:none;">
        <button type="submit">Actualizar</button>
    </form>
    <?php if ($avaluo['estado'] === 'espera_visita'): ?>
        <form method="post" action="<?= url('/valuador/avaluos/' . $id . '/visita-realizada') ?>" style="margin-top:0.75rem;">
            <input type="hidden" name="_csrf" value="<?= e(\App\Csrf::token()) ?>">
            <button type="submit" class="secondary">Marcar visita como realizada → en proceso</button>
        </form>
    <?php endif; ?>
</div>
<div class="card">
    <h2>Nueva cotización</h2>
    <?php if (!in_array($avaluo['estado'], ['solicitado', 'cotizado'], true)): ?>
        <p class="muted">Solo se envían cotizaciones en estado solicitado o cotizado.</p>
    <?php else: ?>
    <form method="post" action="<?= url('/valuador/avaluos/' . $id . '/cotizar') ?>">
        <input type="hidden" name="_csrf" value="<?= e(\App\Csrf::token()) ?>">
        <label>Monto</label>
        <input name="monto" type="number" step="0.01" min="0.01" required>
        <label>Moneda</label>
        <input name="moneda" value="USD" maxlength="8">
        <label>Detalle</label>
        <textarea name="detalle"></textarea>
        <p><button type="submit">Enviar cotización</button></p>
    </form>
    <?php endif; ?>
    <h3>Cotizaciones enviadas</h3>
    <table>
        <tr><th>Monto</th><th>Estado</th><th>Fecha</th></tr>
        <?php foreach ($cotizaciones as $c): ?>
            <tr>
                <td><?= e((string) $c['moneda']) ?> <?= e(number_format((float) $c['monto'], 2)) ?></td>
                <td><?= e((string) $c['estado']) ?></td>
                <td><?= e((string) $c['creado_en']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>
<div class="card">
    <h2>Solicitudes de visita</h2>
    <?php if ($visitas === []): ?>
        <p class="muted">Ninguna pendiente o histórica.</p>
    <?php else: ?>
        <?php foreach ($visitas as $v): ?>
            <div style="border-bottom:1px solid var(--border);padding:0.5rem 0;">
                <p><strong><?= e((string) $v['cliente_nombre']) ?></strong> · <?= e((string) $v['inicio_propuesto']) ?> — <?= e((string) $v['fin_propuesto']) ?>
                    · <span class="badge"><?= e((string) $v['estado']) ?></span></p>
                <?php if ($v['mensaje']): ?><p class="muted"><?= e((string) $v['mensaje']) ?></p><?php endif; ?>
                <?php if ($v['estado'] === 'pendiente'): ?>
                    <form method="post" action="<?= url('/valuador/visita/' . (int) $v['id'] . '/confirmar') ?>" style="display:inline;">
                        <input type="hidden" name="_csrf" value="<?= e(\App\Csrf::token()) ?>">
                        <button type="submit">Confirmar<?= $calendar_connected ? ' (+ Google Calendar)' : '' ?></button>
                    </form>
                    <form method="post" action="<?= url('/valuador/visita/' . (int) $v['id'] . '/rechazar') ?>" style="display:inline;margin-left:0.5rem;">
                        <input type="hidden" name="_csrf" value="<?= e(\App\Csrf::token()) ?>">
                        <input name="motivo" placeholder="Motivo rechazo" style="width:auto;max-width:220px;">
                        <button type="submit" class="secondary">Rechazar</button>
                    </form>
                <?php elseif (!empty($v['respuesta_valuador'])): ?>
                    <p class="muted"><?= e((string) $v['respuesta_valuador']) ?></p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<div class="card">
    <h2>Historial de estados</h2>
    <table>
        <tr><th>Fecha</th><th>Usuario</th><th>Cambio</th><th>Nota</th></tr>
        <?php foreach ($historial as $h): ?>
            <tr>
                <td><?= e((string) $h['creado_en']) ?></td>
                <td><?= e((string) $h['nombre']) ?></td>
                <td><?= e((string) $h['estado_anterior']) ?> → <?= e((string) $h['estado_nuevo']) ?></td>
                <td><?= e((string) ($h['nota'] ?? '')) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>
