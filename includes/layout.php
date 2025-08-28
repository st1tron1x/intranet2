<?php
require_once __DIR__ . '/config.php';
Auth::check() or redirect(url('index.php'));
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title><?= APP_NAME ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="d-flex">
  <!-- Sidebar -->
  <nav class="d-flex flex-column flex-shrink-0 p-3 bg-light" style="width: 220px; height: 100vh;">
    <a href="<?= url('dashboard.php') ?>" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-decoration-none">
      <span class="fs-5 fw-bold"><?= APP_NAME ?></span>
    </a>
    <hr>
    <ul class="nav nav-pills flex-column mb-auto">
      <li class="nav-item">
        <a href="<?= url('dashboard.php') ?>" class="nav-link">Dashboard</a>
      </li>
      <li>
        <a href="<?= url('noticias/index.php') ?>" class="nav-link">Noticias</a>
      </li>
      <?php if (Auth::isAdmin()): ?>
      <li>
        <a href="<?= url('admin/usuarios.php') ?>" class="nav-link">Usuarios</a>
      </li>
      <li>
        <a href="<?= url('admin/config.php') ?>" class="nav-link">Configuración</a>
      </li>
      <?php endif; ?>
    </ul>
    <hr>
    <a href="<?= url('logout.php') ?>" class="btn btn-outline-danger btn-sm">Cerrar sesión</a>
  </nav>

  <!-- Contenido -->
  <main class="flex-grow-1 p-4">
