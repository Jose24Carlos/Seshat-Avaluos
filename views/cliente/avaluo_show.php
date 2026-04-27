<?php
/** @var array $user */
/** @var array $avaluo */
/** @var list<array> $cotizaciones */
/** @var list<array> $visitas */
/** @var list<array> $historial */
/** @var array $valuador */
/** @var bool $calendar_ok */
use App\Services\AvaluoService;
$aid = (int) $avaluo['id'];
?>
<div class="card">
    <h1><?= e((string) $avaluo['titulo']) ?></h1>
    <p class="muted">Estado: <strong><?= e(AvaluoService::etiquetaEstado((string) $avaluo['estado'])) ?></strong>
        <?php if (!empty($avaluo['codigo_seguimiento'])): ?>
            · Código: <code><?= e((string) $avaluo['codigo_seguimiento']) ?></code>
        <?php endif; ?>
    </p>
    <p><strong>Inmueble:</strong> <?= e((string) $avaluo['direccion_inmueble']) ?></p>
    <?php if (!empty($avaluo['tipo_inmueble'])): ?><p><strong>Tipo:</strong> <?= e((string) $avaluo['tipo_inmueble']) ?></p><?php endif; ?>
    <?php if (!empty($avaluo['descripcion'])): ?><p><?= nl2br(e((string) $avaluo['descripcion'])) ?></p><?php endif; ?>
    <p class="muted">Valuador: <?= e((string) ($valuador['nombre'] ?? '')) ?></p>
</div>
<div class="card">
    <h2>Cotizaciones</h2>
    <?php if ($cotizaciones === []): ?>
        <p class="muted">Aún no hay cotización.</p>
    <?php else: ?>
        <table>
            <tr><th>Monto</th><th>Estado</th><th>Detalle</th><th></th></tr>
            <?php foreach ($cotizaciones as $c): ?>
                <tr>
                    <td><?= e((string) $c['moneda']) ?> <?= e(number_format((float) $c['monto'], 2)) ?></td>
                    <td><?= e((string) $c['estado']) ?></td>
                    <td><?= e((string) ($c['detalle'] ?? '')) ?></td>
                    <td>
                        <?php if ($c['estado'] === 'pendiente' && $avaluo['estado'] === 'cotizado'): ?>
                            <form method="post" action="<?= url('/mis-avaluos/' . $aid . '/cotizacion/' . (int) $c['id'] . '/aceptar') ?>" style="margin:0;">
                                <input type="hidden" name="_csrf" value="<?= e(\App\Csrf::token()) ?>">
                                <button type="submit">Aceptar</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>
<div class="card">
    <h2>Visitas</h2>
    <?php if ($avaluo['estado'] !== 'espera_visita'): ?>
        <p class="muted">Podrás solicitar una visita cuando el avalúo esté <strong>en espera de visita</strong> (tras aceptar la cotización).</p>
    <?php else: ?>
    <?php if (!$calendar_ok): ?>
        <p class="muted">El valuador aún no ha conectado Google Calendar o faltan credenciales. Puedes proponer fecha manualmente (el valuador la revisará en su panel).</p>
    <?php else: ?>
        <p class="muted">Horarios sugeridos según disponibilidad del valuador (lunes a viernes, 8:00–18:00).</p>
        <p><button type="button" id="btnSlots">Cargar disponibilidad (próximos 14 días)</button></p>
        <div id="slots" class="muted"></div>
    <?php endif; ?>
    <form method="post" action="<?= url('/mis-avaluos/' . $aid . '/visita') ?>" id="formVisita" style="margin-top:1rem;">
        <input type="hidden" name="_csrf" value="<?= e(\App\Csrf::token()) ?>">
        <label>Inicio propuesto (horario local)</label>
        <input type="datetime-local" name="inicio" id="inicio" required>
        <label>Fin propuesto</label>
        <input type="datetime-local" name="fin" id="fin" required>
        <label>Mensaje (opcional)</label>
        <input name="mensaje" maxlength="500">
        <p><button type="submit">Solicitar visita</button></p>
    </form>
    <?php endif; ?>
    <h3>Solicitudes enviadas</h3>
    <?php if ($visitas === []): ?>
        <p class="muted">Ninguna.</p>
    <?php else: ?>
        <table>
            <tr><th>Inicio</th><th>Fin</th><th>Estado</th><th>Respuesta</th></tr>
            <?php foreach ($visitas as $v): ?>
                <tr>
                    <td><?= e((string) $v['inicio_propuesto']) ?></td>
                    <td><?= e((string) $v['fin_propuesto']) ?></td>
                    <td><?= e((string) $v['estado']) ?></td>
                    <td><?= e((string) ($v['respuesta_valuador'] ?? '')) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
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
<?php if ($calendar_ok && $avaluo['estado'] === 'espera_visita'): ?>
<script>
(function(){
  const aid = <?= json_encode($aid) ?>;
  const base = <?= json_encode(\App\Url::basePath()) ?>;
  const btn = document.getElementById('btnSlots');
  const box = document.getElementById('slots');
  const inicio = document.getElementById('inicio');
  const fin = document.getElementById('fin');
  btn.addEventListener('click', async function(){
    btn.disabled = true;
    box.textContent = 'Cargando…';
    const desde = new Date().toISOString();
    const hasta = new Date(Date.now()+14*864e5).toISOString();
    const r = await fetch((base||'') + '/mis-avaluos/'+aid+'/disponibilidad?desde='+encodeURIComponent(desde)+'&hasta='+encodeURIComponent(hasta), {credentials:'same-origin'});
    const j = await r.json();
    if (!j.slots || !j.slots.length){ box.textContent = 'No se encontraron huecos o el valuador no tiene calendario conectado.'; btn.disabled=false; return; }
    box.innerHTML = '';
    j.slots.slice(0,24).forEach(function(s){
      const b = document.createElement('button');
      b.type = 'button';
      b.className = 'secondary';
      b.style.margin = '0.25rem';
      b.textContent = s.inicio.replace('T',' ').slice(0,16);
      b.addEventListener('click', function(){
        function toLocal(iso){
          const d = new Date(iso);
          const p = n => String(n).padStart(2,'0');
          return d.getFullYear()+'-'+p(d.getMonth()+1)+'-'+p(d.getDate())+'T'+p(d.getHours())+':'+p(d.getMinutes());
        }
        inicio.value = toLocal(s.inicio);
        fin.value = toLocal(s.fin);
      });
      box.appendChild(b);
    });
    btn.disabled = false;
  });
})();
</script>
<?php endif; ?>
