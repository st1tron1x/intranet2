<?php
require_once __DIR__ . '/includes/config.php';
Auth::check() or redirect(url('index.php'));

$user = Auth::user();

// Estadísticas para el dashboard
$stats = [];

// Contar noticias recientes (últimas 30 días)
$result = db()->query("SELECT COUNT(*) as total FROM noticias WHERE fecha_publicacion >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND estado = 'activo'");
$stats['noticias_recientes'] = $result->fetch_assoc()['total'];

// Contar solicitudes pendientes del usuario
$stmt = db()->prepare("SELECT COUNT(*) as total FROM solicitudes WHERE usuario = ? AND estado = 'pendiente'");
$stmt->bind_param('s', $user['usuario']);
$stmt->execute();
$stats['solicitudes_pendientes'] = $stmt->get_result()->fetch_assoc()['total'];

// Contar documentos disponibles
$result = db()->query("SELECT COUNT(*) as total FROM documentos WHERE activo = 1");
$stats['documentos'] = $result->fetch_assoc()['total'];

// Eventos próximos (próximos 7 días)
$result = db()->query("SELECT COUNT(*) as total FROM eventos WHERE fecha_inicio BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)");
$stats['eventos_proximos'] = $result->fetch_assoc()['total'];

// Si es admin, obtener estadísticas adicionales
$admin_stats = [];
if (Auth::isAdmin()) {
    // Total de usuarios activos
    $result = db()->query("SELECT COUNT(*) as total FROM usuarios WHERE activo = 1");
    $admin_stats['usuarios_activos'] = $result->fetch_assoc()['total'];
    
    // Solicitudes pendientes de todos los usuarios
    $result = db()->query("SELECT COUNT(*) as total FROM solicitudes WHERE estado = 'pendiente'");
    $admin_stats['todas_solicitudes_pendientes'] = $result->fetch_assoc()['total'];
    
    // Capacitaciones no completadas
    $result = db()->query("
        SELECT COUNT(DISTINCT c.id) as total 
        FROM capacitaciones c 
        LEFT JOIN capacitaciones_progreso cp ON c.id = cp.capacitacion_id 
        WHERE c.activo = 1 AND (cp.completada IS NULL OR cp.completada = 0)
    ");
    $admin_stats['capacitaciones_pendientes'] = $result->fetch_assoc()['total'];
}

// Obtener noticias recientes (últimas 5)
$noticias_recientes = db()->query("
    SELECT n.id, n.titulo, n.fecha_publicacion, u.nombre_completo
    FROM noticias n 
    JOIN usuarios u ON u.usuario = n.autor
    WHERE n.estado = 'activo'
    ORDER BY n.fecha_publicacion DESC 
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// Obtener eventos próximos
$eventos_proximos = db()->query("
    SELECT id, titulo, fecha_inicio, tipo, color
    FROM eventos 
    WHERE fecha_inicio BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 14 DAY)
    ORDER BY fecha_inicio ASC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// Obtener solicitudes recientes del usuario
$stmt = db()->prepare("
    SELECT id, tipo, titulo, fecha_solicitud, estado, prioridad
    FROM solicitudes 
    WHERE usuario = ?
    ORDER BY fecha_solicitud DESC 
    LIMIT 5
");
$stmt->bind_param('s', $user['usuario']);
$stmt->execute();
$mis_solicitudes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

include __DIR__ . '/includes/layout.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1>¡Bienvenido, <?= e($user['nombre']) ?>!</h1>
        <p class="text-muted">Panel de Control - Intranet Correagro</p>
    </div>
    <div class="text-end">
        <small class="text-muted">Último acceso: <?= date('d/m/Y H:i') ?></small>
    </div>
</div>

<!-- Tarjetas de estadísticas -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h4 class="mb-0"><?= $stats['noticias_recientes'] ?></h4>
                        <p class="mb-0">Noticias Recientes</p>
                    </div>
                    <div class="ms-3">
                        <i class="fas fa-newspaper fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-warning text-dark">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h4 class="mb-0"><?= $stats['solicitudes_pendientes'] ?></h4>
                        <p class="mb-0">Mis Solicitudes Pendientes</p>
                    </div>
                    <div class="ms-3">
                        <i class="fas fa-file-alt fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h4 class="mb-0"><?= $stats['documentos'] ?></h4>
                        <p class="mb-0">Documentos Disponibles</p>
                    </div>
                    <div class="ms-3">
                        <i class="fas fa-folder-open fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h4 class="mb-0"><?= $stats['eventos_proximos'] ?></h4>
                        <p class="mb-0">Eventos Próximos</p>
                    </div>
                    <div class="ms-3">
                        <i class="fas fa-calendar fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (Auth::isAdmin()): ?>
<!-- Estadísticas de administrador -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <h3>Panel de Administrador</h3>
    </div>
    <div class="col-md-4">
        <div class="card bg-dark text-white">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h4 class="mb-0"><?= $admin_stats['usuarios_activos'] ?></h4>
                        <p class="mb-0">Usuarios Activos</p>
                    </div>
                    <div class="ms-3">
                        <i class="fas fa-users fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card bg-danger text-white">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h4 class="mb-0"><?= $admin_stats['todas_solicitudes_pendientes'] ?></h4>
                        <p class="mb-0">Solicitudes Pendientes</p>
                    </div>
                    <div class="ms-3">
                        <i class="fas fa-exclamation-circle fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card bg-secondary text-white">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h4 class="mb-0"><?= $admin_stats['capacitaciones_pendientes'] ?></h4>
                        <p class="mb-0">Capacitaciones Pendientes</p>
                    </div>
                    <div class="ms-3">
                        <i class="fas fa-graduation-cap fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row g-4">
    <!-- Accesos rápidos -->
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">Accesos Rápidos</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6">
                        <a href="<?= url('noticias/index.php') ?>" class="btn btn-outline-primary w-100">
                            <i class="fas fa-newspaper mb-2"></i><br>
                            Noticias
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="<?= url('documentos/index.php') ?>" class="btn btn-outline-info w-100">
                            <i class="fas fa-folder mb-2"></i><br>
                            Documentos
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="<?= url('solicitudes/index.php') ?>" class="btn btn-outline-warning w-100">
                            <i class="fas fa-file-alt mb-2"></i><br>
                            Solicitudes
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="<?= url('capacitaciones/index.php') ?>" class="btn btn-outline-success w-100">
                            <i class="fas fa-graduation-cap mb-2"></i><br>
                            Capacitación
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="<?= url('eventos/index.php') ?>" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-calendar mb-2"></i><br>
                            Calendario
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="<?= url('perfil.php') ?>" class="btn btn-outline-dark w-100">
                            <i class="fas fa-user mb-2"></i><br>
                            Mi Perfil
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Noticias recientes -->
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Noticias Recientes</h5>
                <a href="<?= url('noticias/index.php') ?>" class="btn btn-sm btn-outline-primary">Ver todas</a>
            </div>
            <div class="card-body">
                <?php if (empty($noticias_recientes)): ?>
                    <p class="text-muted text-center">No hay noticias recientes</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($noticias_recientes as $noticia): ?>
                            <a href="<?= url('noticias/ver.php?id='.$noticia['id']) ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?= e($noticia['titulo']) ?></h6>
                                    <small><?= date('d/m/Y', strtotime($noticia['fecha_publicacion'])) ?></small>
                                </div>
                                <small class="text-muted">Por <?= e($noticia['nombre_completo']) ?></small>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mt-2">
    <!-- Mis solicitudes -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Mis Solicitudes Recientes</h5>
                <a href="<?= url('solicitudes/index.php') ?>" class="btn btn-sm btn-outline-primary">Ver todas</a>
            </div>
            <div class="card-body">
                <?php if (empty($mis_solicitudes)): ?>
                    <p class="text-muted text-center">No tienes solicitudes recientes</p>
                    <div class="text-center">
                        <a href="<?= url('solicitudes/crear.php') ?>" class="btn btn-primary">Nueva Solicitud</a>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($mis_solicitudes as $solicitud): ?>
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?= e($solicitud['titulo'] ?: ucfirst($solicitud['tipo'])) ?></h6>
                                    <small><?= date('d/m/Y', strtotime($solicitud['fecha_solicitud'])) ?></small>
                                </div>
                                <p class="mb-1">
                                    <span class="badge bg-secondary"><?= ucfirst(str_replace('_', ' ', $solicitud['tipo'])) ?></span>
                                    <span class="badge <?= match($solicitud['estado']) {
                                        'pendiente' => 'bg-warning',
                                        'en_proceso' => 'bg-info',
                                        'aprobada' => 'bg-success',
                                        'rechazada' => 'bg-danger',
                                        'completada' => 'bg-success',
                                        default => 'bg-secondary'
                                    } ?>"><?= ucfirst($solicitud['estado']) ?></span>
                                </p>
                                <small class="text-muted">Prioridad: <?= ucfirst($solicitud['prioridad']) ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Eventos próximos -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Eventos Próximos</h5>
                <a href="<?= url('eventos/index.php') ?>" class="btn btn-sm btn-outline-primary">Ver calendario</a>
            </div>
            <div class="card-body">
                <?php if (empty($eventos_proximos)): ?>
                    <p class="text-muted text-center">No hay eventos próximos</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($eventos_proximos as $evento): ?>
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?= e($evento['titulo']) ?></h6>
                                    <small><?= date('d/m/Y', strtotime($evento['fecha_inicio'])) ?></small>
                                </div>
                                <p class="mb-1">
                                    <span class="badge" style="background-color: <?= e($evento['color']) ?>;">
                                        <?= ucfirst(str_replace('_', ' ', $evento['tipo'])) ?>
                                    </span>
                                </p>
                                <small class="text-muted"><?= date('H:i', strtotime($evento['fecha_inicio'])) ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Enlaces útiles -->
<div class="row g-4 mt-2">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Enlaces Útiles</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="d-grid">
                            <a href="<?= url('recursos/directorio.php') ?>" class="btn btn-outline-primary">
                                <i class="fas fa-address-book mb-2"></i><br>
                                Directorio de Empleados
                            </a>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="d-grid">
                            <a href="<?= url('recursos/manual.php') ?>" class="btn btn-outline-info">
                                <i class="fas fa-book mb-2"></i><br>
                                Manual del Empleado
                            </a>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="d-grid">
                            <a href="<?= url('recursos/politicas.php') ?>" class="btn btn-outline-warning">
                                <i class="fas fa-gavel mb-2"></i><br>
                                Políticas Corporativas
                            </a>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="d-grid">
                            <a href="<?= url('soporte/index.php') ?>" class="btn btn-outline-danger">
                                <i class="fas fa-life-ring mb-2"></i><br>
                                Soporte Técnico
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<?php include __DIR__ . '/includes/layout_footer.php'; ?>