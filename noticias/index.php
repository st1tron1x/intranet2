<?php
require_once __DIR__ . '/../includes/config.php';
Auth::check() or redirect(url('index.php'));
include __DIR__ . '/../includes/layout.php';

$res = db()->query("SELECT n.id, n.titulo, n.resumen, n.creado_en, u.nombre_completo 
                    FROM noticias n 
                    JOIN usuarios u ON u.id = n.publicado_por
                    WHERE n.activo = 1
                    ORDER BY n.creado_en DESC");
?>
<h1 class="mb-4">Noticias internas</h1>

<?php if (Auth::isAdmin()): ?>
  <a class="btn btn-primary mb-3" href="<?= url('noticias/crear.php') ?>">+ Nueva noticia</a>
<?php endif; ?>

<div class="list-group">
<?php while ($n = $res->fetch_assoc()): ?>
  <a href="<?= url('noticias/ver.php?id='.$n['id']) ?>" 
     class="list-group-item list-group-item-action">
    <h5 class="mb-1"><?= e($n['titulo']) ?></h5>
    <?php if ($n['resumen']): ?>
      <p class="mb-1"><?= e($n['resumen']) ?></p>
    <?php endif; ?>
    <small class="text-muted">por <?= e($n['nombre_completo']) ?>, <?= e($n['creado_en']) ?></small>
  </a>
<?php endwhile; ?>
</div>
<?php include __DIR__ . '/../includes/layout_footer.php'; ?>
