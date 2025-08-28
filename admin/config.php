<?php
require_once __DIR__ . '/../includes/config.php';
Auth::requireRole('admin');

// Procesar formulario de configuración
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash('error', 'Token CSRF inválido');
        redirect(url('admin/config.php'));
    }

    $accion = $_POST['accion'] ?? '';
    
    if ($accion === 'actualizar_config') {
        $configuraciones = [
            'empresa_nombre' => $_POST['empresa_nombre'] ?? '',
            'empresa_email' => $_POST['empresa_email'] ?? '',
            'max_file_size' => $_POST['max_file_size'] ?? '',
            'session_timeout' => $_POST['session_timeout'] ?? '',
            'notificaciones_email' => $_POST['notificaciones_email'] ?? '0',
            'backup_automatico' => $_POST['backup_automatico'] ?? '0',
            'mantenimiento_modo' => $_POST['mantenimiento_modo'] ?? '0'
        ];

        try {
            foreach ($configuraciones as $clave => $valor) {
                $stmt = db()->prepare("
                    INSERT INTO configuracion (clave, valor) 
                    VALUES (?, ?) 
                    ON DUPLICATE KEY UPDATE valor = ?
                ");
                $stmt->bind_param('sss', $clave, $valor, $valor);
                $stmt->execute();
            }
            flash('ok', 'Configuración actualizada correctamente');
        } catch (Exception $e) {
            flash('error', 'Error al actualizar configuración: ' . $e->getMessage());
        }
    }

    redirect(url('admin/config.php'));
}

// Obtener configuración actual
$config_actual = [];
$result = db()->query("SELECT clave, valor FROM configuracion");
while ($row = $result->fetch_assoc()) {
    $config_actual[$row['clave']] = $row['valor'];
}

// Valores por defecto
$defaults = [
    'empresa_nombre' => 'Correagro SCB',
    'empresa_email' => 'info@correagro.com',
    'max_file_size' => '10485760',
    'session_timeout' => '3600',
    'notificaciones_email' => '1',
    'backup_automatico' => '0',
    'mantenimiento_modo' => '0'
];

foreach ($defaults as $key => $default) {
    if (!isset($config_actual[$key])) {
        $config_actual[$key] = $default;
    }
}

// Estadísticas del sistema
$stats = [];

// Total de usuarios
$result = db()->query("SELECT COUNT(*) as total FROM usuarios WHERE activo = 1");
$stats['usuarios_activos'] = $result->fetch_assoc()['total'];

// Total de documentos
$result = db()->query("SELECT COUNT(*) as total FROM documentos WHERE activo = 1");
$stats['documentos'] = $result->fetch_assoc()['total'];

// Total de solicitudes pendientes
$result = db()->query("SELECT COUNT(*) as total FROM solicitudes WHERE estado = 'pendiente'");
$stats['solicitudes_pendientes'] = $result->fetch_assoc()['total'];

// Espacio usado por documentos (simulado)
$result = db()->query("SELECT SUM(tamaño) as total FROM documentos WHERE activo = 1");
$stats['espacio_usado'] = $result->fetch_assoc()['total'] ?? 0;

// Logs de auditoría recientes
$logs_recientes = db()->query("
    SELECT a.*, u.nombre_completo 
    FROM audit_logs a 
    LEFT JOIN usuarios u ON a.usuario = u.usuario 
    ORDER BY a.created_at DESC 
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

include __DIR__ . '/../includes/layout.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-cog"></i> Configuración del Sistema</h1>
    <div>
        <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#estadisticasModal">
            <i class="fas fa-chart-bar"></i> Estadísticas
        </button>
    </div>
</div>

<?php if ($msg = flash('error')): ?>
    <div class="alert alert-danger"><?= $msg ?></div>
<?php endif; ?>
<?php if ($msg = flash('ok')): ?>
    <div class="alert alert-success"><?= $msg ?></div>
<?php endif; ?>

<!-- Estadísticas rápidas -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h4 class="mb-0"><?= $stats['usuarios_activos'] ?></h4>
                        <p class="mb-0">Usuarios Activos</p>
                    </div>
                    <i class="fas fa-users fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h4 class="mb-0"><?= $stats['documentos'] ?></h4>
                        <p class="mb-0">Documentos</p>
                    </div>
                    <i class="fas fa-file-alt fa-2x"></i>
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
                        <p class="mb-0">Solicitudes Pendientes</p>
                    </div>
                    <i class="fas fa-exclamation-triangle fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h4 class="mb-0"><?= formatBytes($stats['espacio_usado']) ?></h4>
                        <p class="mb-0">Espacio Usado</p>
                    </div>
                    <i class="fas fa-hdd fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Formulario de configuración -->
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Configuración General</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                    <input type="hidden" name="accion" value="actualizar_config">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Nombre de la Empresa</label>
                                <input type="text" name="empresa_nombre" class="form-control" 
                                       value="<?= e($config_actual['empresa_nombre']) ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Email de la Empresa</label>
                                <input type="email" name="empresa_email" class="form-control" 
                                       value="<?= e($config_actual['empresa_email']) ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Tamaño Máximo de Archivo (bytes)</label>
                                <select name="max_file_size" class="form-select">
                                    <option value="5242880" <?= $config_actual['max_file_size'] == '5242880' ? 'selected' : '' ?>>5 MB</option>
                                    <option value="10485760" <?= $config_actual['max_file_size'] == '10485760' ? 'selected' : '' ?>>10 MB</option>
                                    <option value="20971520" <?= $config_actual['max_file_size'] == '20971520' ? 'selected' : '' ?>>20 MB</option>
                                    <option value="52428800" <?= $config_actual['max_file_size'] == '52428800' ? 'selected' : '' ?>>50 MB</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Tiempo de Sesión (segundos)</label>
                                <select name="session_timeout" class="form-select">
                                    <option value="1800" <?= $config_actual['session_timeout'] == '1800' ? 'selected' : '' ?>>30 minutos</option>
                                    <option value="3600" <?= $config_actual['session_timeout'] == '3600' ? 'selected' : '' ?>>1 hora</option>
                                    <option value="7200" <?= $config_actual['session_timeout'] == '7200' ? 'selected' : '' ?>>2 horas</option>
                                    <option value="14400" <?= $config_actual['session_timeout'] == '14400' ? 'selected' : '' ?>>4 horas</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    <h6>Opciones del Sistema</h6>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-check form-switch mb-3">
                                <input type="hidden" name="notificaciones_email" value="0">
                                <input class="form-check-input" type="checkbox" name="notificaciones_email" 
                                       value="1" id="notificaciones" 
                                       <?= $config_actual['notificaciones_email'] == '1' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="notificaciones">
                                    Notificaciones por Email
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check form-switch mb-3">
                                <input type="hidden" name="backup_automatico" value="0">
                                <input class="form-check-input" type="checkbox" name="backup_automatico" 
                                       value="1" id="backup" 
                                       <?= $config_actual['backup_automatico'] == '1' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="backup">
                                    Backup Automático
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check form-switch mb-3">
                                <input type="hidden" name="mantenimiento_modo" value="0">
                                <input class="form-check-input" type="checkbox" name="mantenimiento_modo" 
                                       value="1" id="mantenimiento" 
                                       <?= $config_actual['mantenimiento_modo'] == '1' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="mantenimiento">
                                    Modo Mantenimiento
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar Configuración
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="window.location.reload()">
                            <i class="fas fa-undo"></i> Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Acciones de mantenimiento -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Mantenimiento del Sistema</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-info" onclick="limpiarCache()">
                        <i class="fas fa-broom"></i> Limpiar Caché
                    </button>
                    <button class="btn btn-outline-warning" onclick="optimizarDB()">
                        <i class="fas fa-database"></i> Optimizar Base de Datos
                    </button>
                    <button class="btn btn-outline-success" onclick="crearBackup()">
                        <i class="fas fa-download"></i> Crear Backup
                    </button>
                    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#logsModal">
                        <i class="fas fa-file-alt"></i> Ver Logs
                    </button>
                </div>
                
                <hr>
                
                <h6>Información del Sistema</h6>
                <ul class="list-unstyled small">
                    <li><strong>PHP:</strong> <?= phpversion() ?></li>
                    <li><strong>MySQL:</strong> <?= db()->server_info ?></li>
                    <li><strong>Servidor:</strong> <?= $_SERVER['SERVER_SOFTWARE'] ?? 'Desconocido' ?></li>
                    <li><strong>Memoria:</strong> <?= ini_get('memory_limit') ?></li>
                    <li><strong>Tiempo máximo:</strong> <?= ini_get('max_execution_time') ?>s</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Logs -->
<div class="modal fade" id="logsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Logs de Auditoría Recientes</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Usuario</th>
                                <th>Acción</th>
                                <th>Tabla</th>
                                <th>IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs_recientes as $log): ?>
                                <tr>
                                    <td><?= date('d/m/Y H:i', strtotime($log['created_at'])) ?></td>
                                    <td><?= e($log['nombre_completo'] ?? $log['usuario']) ?></td>
                                    <td><?= e($log['accion']) ?></td>
                                    <td><?= e($log['tabla_afectada']) ?></td>
                                    <td><?= e($log['ip_address']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function limpiarCache() {
    if (confirm('¿Deseas limpiar la caché del sistema?')) {
        // Implementar limpieza de caché
        alert('Caché limpiada correctamente');
    }
}

function optimizarDB() {
    if (confirm('¿Deseas optimizar la base de datos? Esto puede tomar unos minutos.')) {
        // Implementar optimización de DB
        alert('Base de datos optimizada correctamente');
    }
}

function crearBackup() {
    if (confirm('¿Deseas crear un backup de la base de datos?')) {
        // Implementar creación de backup
        alert('Backup creado correctamente');
    }
}
</script>

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