<?php
require_once __DIR__ . '/../includes/config.php';
Auth::requireRole('admin');
include __DIR__ . '/../includes/header.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare("SELECT * FROM noticias WHERE id=?");
$stmt->bind_param('i',$id);
$stmt->execute();
$noticia = $stmt->get_result()->fetch_assoc();
if (!$noticia) exit('Noticia no encontrada');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $resumen = trim($_POST['resumen'] ?? '');
    $contenido = trim($_POST['contenido'] ?? '');

    $stmt = db()->prepare("UPDATE noticias SET titulo=?, resumen=?, contenido=? WHERE id=?");
    $stmt->bind_param('sssi', $titulo, $resumen, $contenido, $id);
    $stmt->execute();

    flash('ok','Noticia actualizada');
    redirect(url('noticias/ver.php?id='.$id));
}
?>
<div class="row">
  <div class="col-lg-6 mx-auto">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title mb-3">Editar noticia</h2>
        <form method="post">
          <div class="mb-3">
            <label class="form-label">Título</label>
            <input name="titulo" class="form-control" value="<?= e($noticia['titulo']) ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Resumen</label>
            <textarea name="resumen" class="form-control"><?= e($noticia['resumen']) ?></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">Contenido</label>
            <textarea name="contenido" class="form-control" rows="6" required><?= e($noticia['contenido']) ?></textarea>
          </div>
          <button type="submit" class="btn btn-success">Actualizar</button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>