<?php
require_once __DIR__ . '/includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) { flash('error','CSRF inválido'); redirect(url('index.php')); }

    $u = $_POST['usuario'] ?? '';
    $p = $_POST['clave'] ?? '';

    if (Auth::attempt($u, $p)) {
        flash('ok', 'Bienvenido, '.e(Auth::user()['nombre']));
        if (Auth::isAdmin()) redirect(url('admin/dashboard.php'));
        redirect(url('cliente/dashboard.php'));
    } else {
        flash('error','Credenciales inválidas');
        redirect(url('index.php'));
    }
}

$user = Auth::user();
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title><?= e(APP_NAME) ?></title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="<?= url('assets/app.css') ?>">
</head>
<body>
<nav class="topbar">
  <div class="brand"><?= e(APP_NAME) ?></div>
  <div class="menu">
    <?php if ($user): ?>
      <a href="<?= url(Auth::isAdmin() ? 'admin/dashboard.php' : 'cliente/dashboard.php') ?>">Dashboard</a>
      <a href="<?= url('logout.php') ?>">Cerrar sesión</a>
    <?php endif; ?>
  </div>
</nav>

<main class="container">
<?php if ($m = flash('error')): ?><div class="alert error"><?= e($m) ?></div><?php endif; ?>
<?php if ($m = flash('ok')): ?><div class="alert ok"><?= e($m) ?></div><?php endif; ?>

<?php if (!$user): ?>
  <h1>Intranet</h1>
  <form method="post" action="">
    <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
    <label>Usuario</label>
    <input name="usuario" required>
    <label>Contraseña</label>
    <input name="clave" type="password" required>
    <button type="submit">Entrar</button>
    <p><a href="<?= url('forgot.php') ?>">¿Olvidaste tu contraseña?</a></p>
  </form>
<?php else: ?>
  <h1>Hola, <?= e($user['nombre']) ?></h1>
  <p>Ir al <a href="<?= url(Auth::isAdmin() ? 'admin/dashboard.php' : 'cliente/dashboard.php') ?>">dashboard</a>.</p>
<?php endif; ?>
</main>

<footer class="footer">
  <small>@ Stiven Vanegas Jimenez <?= date('Y') ?> Correagro S.A.</small>
</footer>
</body>
</html>