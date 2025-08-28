<?php
require_once __DIR__ . '/../includes/config.php';
Auth::requireRole('admin');
include __DIR__ . '/../includes/layout.php';
?>

<h1 class="mb-4">Panel de Administración</h1>

<div class="row g-4">
  <div class="col-md-6">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title">Gestión de usuarios</h5>
        <p class="card-text">Alta, baja y modificación de usuarios internos.</p>
        <a href="<?= url('admin/usuarios.php') ?>" class="btn btn-primary">Administrar</a>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title">Configuración</h5>
        <p class="card-text">Parámetros del sistema y permisos.</p>
        <a href="<?= url('admin/config.php') ?>" class="btn btn-secondary">Configurar</a>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/layout_footer.php'; ?>