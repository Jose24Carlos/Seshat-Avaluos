<?php /** @var ?string $error */ /** @var ?string $codigo */ ?>
<div class="card" style="max-width:520px;">
    <h1>Seguimiento público</h1>
    <p class="muted">Introduce el código que recibiste al aceptar la cotización (ej. AV-XXXXXXXX).</p>
    <?php if (!empty($error)): ?><div class="err"><?= e($error) ?></div><?php endif; ?>
    <form method="post" action="<?= url('/seguimiento') ?>">
        <input type="hidden" name="_csrf" value="<?= e(\App\Csrf::token()) ?>">
        <label>Código</label>
        <input name="codigo" required value="<?= e($codigo ?? '') ?>" placeholder="AV-…">
        <p><button type="submit">Consultar</button></p>
    </form>
    <p><a href="<?= url('/') ?>">Inicio</a></p>
</div>
