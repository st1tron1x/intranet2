<?php
require_once __DIR__ . '/../includes/config.php';
Auth::check() or redirect(url('index.php'));

$user = Auth::user();
$isAdmin = Auth::isAdmin();

// Filtros
$filtro_categoria = (int)($_GET['categoria'] ?? 0);
$filtro_busqueda = trim($_GET['busqueda'] ?? '');

// Obtener categorías de documentos
$categorias = db()->query("
    SELECT id, nombre, descripcion, icono, color, 
           (SELECT COUNT(*) FROM documentos WHERE categoria_id = categorias_documentos.id AND activo = 1) as total_documentos
    FROM categorias_documentos 
    WHERE activo = 1 
    ORDER BY orden, nombre
")->fetch_all(MYSQLI_ASSOC);

// Construcción de consulta para documentos
$where_conditions = ["d.activo = 1"];
$params = [];
$param_types = '';

if ($filtro_categoria) {
    $where_conditions[] = "d.categoria_id = ?";
    $params[] = $filtro_categoria;
    $param_types .= 'i';
}

if ($filtro_busqueda) {
    $where_conditions[] = "(d.titulo LIKE ? OR d.descripcion LIKE ? OR d.tags LIKE ?)";
    $busqueda_param = "%$filtro_busqueda%";
    $params[] = $busqueda_param;
    $params[] = $busqueda_param;
    $params[] = $busqueda_param;
    $param_types .= 'sss';
}

$where_sql = implode(' AND ', $where_conditions);

// Obtener documentos
$sql = "SELECT d.*, c.nombre as categoria_nombre, c.icono as categoria_icono, c.color as categoria_color,
               u.nombre_completo as subido_por_nombre
        FROM documentos d 
        LEFT JOIN categorias_documentos c ON d.categoria_id = c.id
        LEFT JOIN usuarios u ON d.usuario_subida = u.usuario
        WHERE $where_sql
        ORDER BY d.created_at DESC";

$stmt = db()->prepare($sql);
if ($params) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$documentos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

include __DIR__ . '/../includes/layout.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Biblioteca de Documentos</h1>
    <?php if ($isAdmin): ?>
        <div>
            <a href="<?= url('documentos/subir.php') ?>" class="btn btn-primary">
                <i class="fas fa-upload"></i> Subir Documento
            </a>
            <a href="<?= url('documentos/categorias.php') ?>" class="btn btn-outline-secondary">
                <i class="fas fa-tags"></i> Categorías
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- Filtros de búsqueda -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Categoría</label>
                <select name="categoria" class="form-select">
                    <option value="">Todas las categorías</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $filtro_categoria === $cat['id'] ? 'selected' : '' ?>>
                            <?= e($cat['nombre']) ?> (<?= $cat['total_documentos'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Buscar</label>
                <input type="text" name="busqueda" class="form-control" 
                       placeholder="Buscar por título, descripción o etiquetas..."
                       value="<?= e($filtro_busqueda) ?>">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-outline-primary w-100">
                    <i class="fas fa-search"></i> Buscar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Categorías destacadas -->
<?php if (!$filtro_categoria && !$filtro_busqueda): ?>
<div class="row g-4 mb-4">
    <?php foreach ($categorias as $categoria): ?>
        <div class="col-md-6 col-lg-3">
            <a href="?categoria=<?= $categoria['id'] ?>" class="text-decoration-none">
                <div class="card h-100 border-0 shadow-sm categoria-card" 
                     style="border-left: 4px solid <?= e($categoria['color']) ?> !important;">
                    <div class="card-body text-center">
                        <i class="fas fa-<?= e($categoria['icono']) ?> fa-2x mb-3" 
                           style="color: <?= e($categoria['color']) ?>;"></i>
                        <h5 class="card-title"><?= e($categoria['nombre']) ?></h5>
                        <p class="card-text text-muted small"><?= e($categoria['descripcion']) ?></p>
                        <span class="badge" style="background-color: <?= e($categoria['color']) ?>;">
                            <?= $categoria['total_documentos'] ?> documento(s)
                        </span>
                    </div>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Lista de documentos -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <?php if ($filtro_categoria || $filtro_busqueda): ?>
                Resultados
                <?php if ($filtro_busqueda): ?>
                    para "<?= e($filtro_busqueda) ?>"
                <?php endif ?>
                (<?= count($documentos) ?> documento(s))
            <?php else: ?>
                Documentos Recientes
            <?php endif; ?>
        </h5>
        <?php if ($filtro_categoria || $filtro_busqueda): ?>
            <a href="<?= url('documentos/index.php') ?>" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-times"></i> Limpiar filtros
            </a>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if (empty($documentos)): ?>
            <div class="text-center py-5">
                <i class="fas fa-folder-open fa-4x text-muted mb-3"></i>
                <h5 class="text-muted">No se encontraron documentos</h5>
                <p class="text-muted">
                    <?php if ($filtro_busqueda || $filtro_categoria): ?>
                        Prueba ajustando los filtros de búsqueda
                    <?php else: ?>
                        Aún no hay documentos disponibles
                    <?php endif; ?>
                </p>
                <?php if ($isAdmin): ?>
                    <a href="<?= url('documentos/subir.php') ?>" class="btn btn-primary">
                        <i class="fas fa-upload"></i> Subir Primer Documento
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($documentos as $doc): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 documento-card">
                            <div class="card-body">
                                <div class="d-flex align-items-start mb-2">
                                    <div class="me-3">
                                        <i class="fas fa-file-<?= match(strtolower($doc['tipo_archivo'])) {
                                            'pdf' => 'pdf text-danger',
                                            'doc', 'docx' => 'word text-primary',
                                            'xls', 'xlsx' => 'excel text-success',
                                            'ppt', 'pptx' => 'powerpoint text-warning',
                                            'jpg', 'jpeg', 'png', 'gif' => 'image text-info',
                                            default => 'alt text-secondary'
                                        } ?> fa-2x"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="card-title mb-1"><?= e($doc['titulo']) ?></h6>
                                        <?php if ($doc['categoria_nombre']): ?>
                                            <span class="badge mb-2" 
                                                  style="background-color: <?= e($doc['categoria_color']) ?>;">
                                                <?= e($doc['categoria_nombre']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <?php if ($doc['descripcion']): ?>
                                    <p class="card-text small text-muted">
                                        <?= e(mb_substr($doc['descripcion'], 0, 100)) ?>
                                        <?= mb_strlen($doc['descripcion']) > 100 ? '...' : '' ?>
                                    </p>
                                <?php endif; ?>
                                
                                <div class="mt-auto">
                                    <div class="d-flex justify-content-between align-items-center text-muted small mb-2">
                                        <span>
                                            <i class="fas fa-download"></i> <?= $doc['descargas'] ?> descargas
                                        </span>
                                        <span><?= formatBytes($doc['tamaño']) ?></span>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center small text-muted mb-3">
                                        <span>Por <?= e($doc['subido_por_nombre']) ?></span>
                                        <span><?= date('d/m/Y', strtotime($doc['created_at'])) ?></span>
                                    </div>
                                    
                                    <?php if ($doc['tags']): ?>
                                        <div class="mb-2">
                                            <?php foreach (explode(',', $doc['tags']) as $tag): ?>
                                                <span class="badge bg-light text-dark me-1">#<?= e(trim($tag)) ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="d-grid gap-2">
                                        <a href="<?= url('documentos/descargar.php?id='.$doc['id']) ?>" 
                                           class="btn btn-primary btn-sm">
                                            <i class="fas fa-download"></i> Descargar
                                        </a>
                                        <?php if ($isAdmin): ?>
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?= url('documentos/editar.php?id='.$doc['id']) ?>" 
                                                   class="btn btn-outline-secondary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="<?= url('documentos/eliminar.php?id='.$doc['id']) ?>" 
                                                   class="btn btn-outline-danger"
                                                   onclick="return confirm('¿Eliminar este documento?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.categoria-card:hover {
    transform: translateY(-2px);
    transition: transform 0.2s;
}

.documento-card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    transition: box-shadow 0.2s;
}
</style>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<?php 
// Función helper para formatear bytes
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

include __DIR__ . '/../includes/layout_footer.php'; 
?>