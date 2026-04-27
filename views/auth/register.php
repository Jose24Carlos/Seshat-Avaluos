<?php /** @var ?string $error */ /** @var ?string $nombre */ /** @var ?string $email */ ?>
<div class="card" style="max-width:520px;">
    <h1>Registro</h1>
    <?php if (!empty($error)): ?><div class="err"><?= e($error) ?></div><?php endif; ?>
    <form method="post" action="<?= url('/registro') ?>">
        <input type="hidden" name="_csrf" value="<?= e(\App\Csrf::token()) ?>">
        <label>Nombre</label>
        <input name="nombre" required value="<?= e($nombre ?? '') ?>">
        <label>Email</label>
        <input type="email" name="email" required value="<?= e($email ?? '') ?>">
        <label>Teléfono (opcional)</label>
        <input name="telefono" value="<?= e(isset($telefono) ? (string) $telefono : '') ?>">
        <label>Contraseña (mín. 8 caracteres)</label>
        <input type="password" name="password" required minlength="8">
        <label>Rol</label>
        <select name="rol">
            <option value="cliente">Cliente</option>
            <option value="valuador">Valuador</option>
        </select>
        <label>Clave secreta valuador (solo si eliges Valuador; definida en .env como VALUADOR_REGISTER_SECRET)</label>
        <input name="valuador_secret" type="password" placeholder="Vacío si eres cliente">
        <p style="margin-top:1rem;"><button type="submit">Registrarme</button></p>
    </form>
</div>
