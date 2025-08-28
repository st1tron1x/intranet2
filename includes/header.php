<?php
require_once __DIR__ . '/config.php';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title><?= APP_NAME ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="<?= url('index.php') ?>"><?= APP_NAME ?></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <?php if (Auth::check()): ?>
          <li class="nav-item"><a class="nav-link" href="<?= url('dashboard.php') ?>">Dashboard</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= url('noticias/index.php') ?>">Noticias</a></li>
          <?php if (Auth::isAdmin()): ?>
            <li class="nav-item"><a class="nav-link" href="<?= url('admin/dashboard.php') ?>">Admin</a></li>
          <?php endif; ?>
          <li class="nav-item"><a class="nav-link" href="<?= url('logout.php') ?>">Salir</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="<?= url('index.php') ?>">Login</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
<div class="container mt-4">
