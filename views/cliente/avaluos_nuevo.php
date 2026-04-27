<?php /** @var array $user */ /** @var list<array> $valuadores */ /** @var ?string $error */ ?>
<div class="card" style="max-width:640px;">
    <h1>Solicitar avalúo</h1>
    <?php if (!empty($error)): ?><div class="err"><?= e($error) ?></div><?php endif; ?>
    <?php if ($valuadores === []): ?>
        <div class="err">No hay valuadores registrados. Un administrador debe crear una cuenta de valuador primero.</div>
    <?php else: ?>
    <form method="post" action="<?= url('/mis-avaluos') ?>">
        <input type="hidden" name="_csrf" value="<?= e(\App\Csrf::token()) ?>">
        <label>Valuador</label>
        <select name="valuador_id" required>
            <?php foreach ($valuadores as $v): ?>
                <option value="<?= (int) $v['id'] ?>"><?= e((string) $v['nombre']) ?> — <?= e((string) $v['email']) ?></option>
            <?php endforeach; ?>
        </select>
        <label>Título breve</label>
        <input name="titulo" required placeholder="Ej. Avalúo casa habitación">
        <label>Dirección del inmueble</label>
        <input name="direccion_inmueble" required>
        <label>Tipo de inmueble</label>
        <input name="tipo_inmueble" placeholder="Casa, departamento, terreno…">
        <label>Descripción / requisitos</label>
        <textarea name="descripcion"></textarea>
        <p style="margin-top:1rem;"><button type="submit">Enviar solicitud</button></p>
    </form>
    <?php endif; ?>
</div>
