<?php /** @var array $user */ /** @var list<array> $items */ ?>
<div class="card">
    <h1>Notificaciones</h1>
    <p class="muted">Los cambios en tus avalúos generan avisos aquí.</p>
</div>
<div class="card">
    <?php if ($items === []): ?>
        <p class="muted">No hay notificaciones.</p>
    <?php else: ?>
        <ul class="ntf">
            <?php foreach ($items as $n): ?>
                <li>
                    <strong><?= e((string) $n['titulo']) ?></strong>
                    <?php if (empty($n['leida_en'])): ?>
                        <form action="<?= url('/notificaciones/' . (int) $n['id'] . '/leida') ?>" method="post" style="display:inline;margin-left:0.35rem;">
                            <input type="hidden" name="_csrf" value="<?= e(\App\Csrf::token()) ?>">
                            <button type="submit" class="secondary" style="font-size:0.8rem;padding:0.15rem 0.4rem;">Marcar leída</button>
                        </form>
                    <?php endif; ?>
                    <br><span class="muted"><?= e((string) ($n['cuerpo'] ?? '')) ?></span><br>
                    <span class="muted" style="font-size:0.8rem;"><?= e((string) $n['creado_en']) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
