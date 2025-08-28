<?php
require_once __DIR__ . '/../includes/config.php';
Auth::requireRole('admin');
include __DIR__ . '/../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $resumen = trim($_POST['resumen'] ?? '');
    $contenido = trim($_POST['contenido'] ?? '');
    $uid = Auth::user()['id'];

    $stmt = db()->prepare("INSERT INTO noticias (titulo,resumen,contenido,publicado_por) VALUES (?,?,?,?)");
    $stmt->bind_param('sssi', $titulo, $resumen, $contenido, $uid);
    $stmt->execute();

    flash('ok','Noticia creada');
    redirect(url('noticias/index.php'));
}
?>
<div class="row">
  <div class="col-lg-6 mx-auto">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title mb-3">Crear noticia</h2>
        <form method="post">
          <div class="mb-3">
            <label class="form-label">Título</label>
            <input name="titulo" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Resumen</label>
            <textarea name="resumen" class="form-control"></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">Contenido</label>
            <textarea name="contenido" class="form-control" rows="6" required></textarea>
          </div>
          <button type="submit" class="btn btn-success">Guardar</button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
