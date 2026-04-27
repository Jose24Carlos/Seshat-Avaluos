<?php /** @var ?string $error */ /** @var ?string $email */ ?>
<div class="card" style="max-width:480px;">
    <h1>Iniciar sesión</h1>
    <?php if (!empty($error)): ?><div class="err"><?= e($error) ?></div><?php endif; ?>
    <form method="post" action="<?= url('/login') ?>">
        <input type="hidden" name="_csrf" value="<?= e(\App\Csrf::token()) ?>">
        <label>Email</label>
        <input type="email" name="email" required value="<?= e($email ?? '') ?>">
        <label>Contraseña</label>
        <input type="password" name="password" required>
        <p style="margin-top:1rem;"><button type="submit">Entrar</button></p>
    </form>
    <p class="muted"><a href="<?= url('/registro') ?>">Crear cuenta</a></p>
</div>
