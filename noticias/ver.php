<?php
require_once __DIR__ . '/../includes/config.php';
Auth::check() or redirect(url('index.php'));
include __DIR__ . '/../includes/header.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare("SELECT n.*, u.nombre_completo 
                       FROM noticias n 
                       JOIN usuarios u ON u.id = n.publicado_por
                       WHERE n.id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$noticia = $stmt->get_result()->fetch_assoc();
if (!$noticia) exit('Noticia no encontrada');
?>
<div class="row">
  <div class="col-lg-8 mx-auto">
    <div class="card shadow-sm">
      <div class="card-body">
        <h1 class="card-title"><?= e($noticia['titulo']) ?></h1>
        <p class="text-muted mb-3">
          por <?= e($noticia['nombre_completo']) ?> el <?= e($noticia['creado_en']) ?>
        </p>
        <p class="card-text"><?= nl2br(e($noticia['contenido'])) ?></p>
        <?php if (Auth::isAdmin()): ?>
          <a href="<?= url('noticias/editar.php?id='.$noticia['id']) ?>" class="btn btn-warning">Editar</a>
          <a href="<?= url('noticias/eliminar.php?id='.$noticia['id']) ?>" class="btn btn-danger" onclick="return confirm('¿Eliminar noticia?')">Eliminar</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
