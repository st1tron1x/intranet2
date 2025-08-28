<?php
require_once __DIR__ . '/includes/config.php';
Auth::check() or redirect(url('index.php'));
include __DIR__ . '/includes/layout.php';

$user = Auth::user();
?>

<h1 class="mb-4">Bienvenido, <?= e($user['nombre_completo']) ?></h1>

<div class="row g-4">
  <!-- Card Noticias -->
  <div class="col-md-6 col-lg-4">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <h5 class="card-title">Noticias</h5>
        <p class="card-text">Consulta las últimas novedades internas.</p>
        <a href="<?= url('noticias/index.php') ?>" class="btn btn-primary">Ver noticias</a>
      </div>
    </div>
  </div>

  <!-- Card Perfil -->
  <div class="col-md-6 col-lg-4">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <h5 class="card-title">Mi perfil</h5>
        <p class="card-text">Revisa y actualiza tu información personal.</p>
        <a href="<?= url('perfil.php') ?>" class="btn btn-secondary">Ver perfil</a>
      </div>
    </div>
  </div>

  <!-- Solo visible para ADMIN -->
  <?php if (Auth::isAdmin()): ?>
  <div class="col-md-6 col-lg-4">
    <div class="card shadow-sm h-100 border-warning">
      <div class="card-body">
        <h5 class="card-title">Administración</h5>
        <p class="card-text">Gestiona usuarios, roles y configuraciones.</p>
        <a href="<?= url('admin/dashboard.php') ?>" class="btn btn-warning">Ir a Admin</a>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/layout_footer.php'; ?>