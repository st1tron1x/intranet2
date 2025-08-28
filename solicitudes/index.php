<?php
require_once __DIR__ . '/../includes/config.php';
Auth::check() or redirect(url('index.php'));

$user = Auth::user();
$isAdmin = Auth::isAdmin();

// Filtros
$filtro_tipo = $_GET['tipo'] ?? '';
$filtro_estado = $_GET['estado'] ?? '';

// Construcción de consulta
$where_conditions = [];
$params = [];
$param_types = '';

if (!$isAdmin) {
    $where_conditions[] = "usuario = ?";
    $params[] = $user['usuario'];
    $param_types .= 's';
}

if ($filtro_tipo) {
    $where_conditions[] = "tipo = ?";
    $params[] = $filtro_tipo;
    $param_types .= 's';
}

if ($filtro_estado) {
    $where_conditions[] = "estado = ?";
    $params[] = $filtro_estado;
    $param_types .= 's';
}

$where_sql = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Obtener solicitudes
$sql = "SELECT s.*, u.nombre_completo, admin.nombre_completo as respondido_por_nombre
        FROM solicitudes s 
        JOIN usuarios u ON s.usuario = u.usuario
        LEFT JOIN usuarios admin ON s.respondido_por = admin.usuario
        $where_sql
        ORDER BY s.fecha_solicitud DESC";

$stmt = db()->prepare($sql);
if ($params) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$solicitudes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

include __DIR__ . '/../includes/layout.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><?= $isAdmin ? 'Gestión de Solicitudes' : 'Mis Solicitudes' ?></h1>
    <a href="<?= url('solicitudes/crear.php') ?>" class="btn btn-primary">
        <i class="fas fa-plus"></i> Nueva Solicitud
    </a>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Tipo de Solicitud</label>
                <select name="tipo" class="form-select">
                    <option value="">Todos los tipos</option>
                    <option value="vacaciones" <?= $filtro_tipo === 'vacaciones' ? 'selected' : '' ?>>Vacaciones</option>
                    <option value="certificado_laboral" <?= $filtro_tipo === 'certificado_laboral' ? 'selected' : '' ?>>Certificado Laboral</option>
                    <option value="soporte_tecnico" <?= $filtro_tipo === 'soporte_tecnico' ? 'selected' : '' ?>>Soporte Técnico</option>
                    <option value="reserva_sala" <?= $filtro_tipo === 'reserva_sala' ? 'selected' : '' ?>>Reserva de Sala</option>
                    <option value="mantenimiento" <?= $filtro_tipo === 'mantenimiento' ? 'selected' : '' ?>>Mantenimiento</option>
                    <option value="papeleria" <?= $filtro_tipo === 'papeleria' ? 'selected' : '' ?>>Papelería</option>
                    <option value="permiso" <?= $filtro_tipo === 'permiso' ? 'selected' : '' ?>>Permiso</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Estado</label>
                <select name="estado" class="form-select">
                    <option value="">Todos los estados</option>
                    <option value="pendiente" <?= $filtro_estado === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                    <option value="en_proceso" <?= $filtro_estado === 'en_proceso' ? 'selected' : '' ?>>En Proceso</option>
                    <option value="aprobada" <?= $filtro_estado === 'aprobada' ? 'selected' : '' ?>>Aprobada</option>
                    <option value="rechazada" <?= $filtro_estado === 'rechazada' ? 'selected' : '' ?>>Rechazada</option>
                    <option value="completada" <?= $filtro_estado === 'completada' ? 'selected' : '' ?>>Completada</option>
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-outline-primary me-2">Filtrar</button>
                <a href="<?= url('solicitudes/index.php') ?>" class="btn btn-outline-secondary">Limpiar</a>
            </div>
        </form>
    </div>
</div>

<!-- Lista de solicitudes -->
<div class="card">
    <div class="card-body">
        <?php if (empty($solicitudes)): ?>
            <div class="text-center py-4">
                <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No hay solicitudes</h5>
                <p class="text-muted">¡Crea tu primera solicitud!</p>
                <a href="<?= url('solicitudes/crear.php') ?>" class="btn btn-primary">Nueva Solicitud</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <?php if ($isAdmin): ?>
                                <th>Usuario</th>
                            <?php endif; ?>
                            <th>Tipo</th>
                            <th>Título</th>
                            <th>Estado</th>
                            <th>Prioridad</th>
                            <th>Fecha Solicitud</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($solicitudes as $solicitud): ?>
                            <tr>
                                <td>#<?= $solicitud['id'] ?></td>
                                <?php if ($isAdmin): ?>
                                    <td><?= e($solicitud['nombre_completo']) ?></td>
                                <?php endif; ?>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?= ucfirst(str_replace('_', ' ', $solicitud['tipo'])) ?>
                                    </span>
                                </td>
                                <td><?= e($solicitud['titulo'] ?: 'Sin título') ?></td>
                                <td>
                                    <span class="badge <?= match($solicitud['estado']) {
                                        'pendiente' => 'bg-warning text-dark',
                                        'en_proceso' => 'bg-info',
                                        'aprobada' => 'bg-success',
                                        'rechazada' => 'bg-danger',
                                        'completada' => 'bg-success',
                                        default => 'bg-secondary'
                                    } ?>">
                                        <?= ucfirst(str_replace('_', ' ', $solicitud['estado'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?= match($solicitud['prioridad']) {
                                        'baja' => 'bg-light text-dark',
                                        'media' => 'bg-info',
                                        'alta' => 'bg-warning text-dark',
                                        'critica' => 'bg-danger',
                                        default => 'bg-secondary'
                                    } ?>">
                                        <?= ucfirst($solicitud['prioridad']) ?>
                                    </span>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($solicitud['fecha_solicitud'])) ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= url('solicitudes/ver.php?id='.$solicitud['id']) ?>" 
                                           class="btn btn-outline-primary" title="Ver detalles">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($isAdmin && in_array($solicitud['estado'], ['pendiente', 'en_proceso'])): ?>
                                            <a href="<?= url('solicitudes/gestionar.php?id='.$solicitud['id']) ?>" 
                                               class="btn btn-outline-success" title="Gestionar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Estadísticas rápidas -->
<?php if ($isAdmin): ?>
<div class="row g-4 mt-4">
    <?php
    $estadisticas = [
        'pendientes' => 0,
        'en_proceso' => 0,
        'aprobadas' => 0,
        'rechazadas' => 0
    ];
    
    $result = db()->query("
        SELECT estado, COUNT(*) as total 
        FROM solicitudes 
        WHERE estado IN ('pendiente', 'en_proceso', 'aprobada', 'rechazada')
        GROUP BY estado
    ");
    
    while ($row = $result->fetch_assoc()) {
        $estadisticas[$row['estado']] = $row['total'];
    }
    ?>
    
    <div class="col-md-3">
        <div class="card bg-warning text-dark">
            <div class="card-body text-center">
                <h3><?= $estadisticas['pendientes'] ?></h3>
                <p class="mb-0">Pendientes</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h3><?= $estadisticas['en_proceso'] ?></h3>
                <p class="mb-0">En Proceso</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h3><?= $estadisticas['aprobadas'] ?></h3>
                <p class="mb-0">Aprobadas</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-danger text-white">
            <div class="card-body text-center">
                <h3><?= $estadisticas['rechazadas'] ?></h3>
                <p class="mb-0">Rechazadas</p>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<?php include __DIR__ . '/../includes/layout_footer.php'; ?>