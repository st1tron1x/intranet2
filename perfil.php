<?php
require_once __DIR__ . '/includes/config.php';
Auth::check() or redirect(url('index.php'));

$user = Auth::user();

// Obtener datos completos del usuario
$stmt = db()->prepare("
    SELECT * FROM usuarios 
    WHERE usuario = ? 
    LIMIT 1
");
$stmt->bind_param('s', $user['usuario']);
$stmt->execute();
$userData = $stmt->get_result()->fetch_assoc();

if (!$userData) {
    flash('error', 'Usuario no encontrado');
    redirect(url('dashboard.php'));
}

// Procesar actualización de perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash('error', 'Token CSRF inválido');
        redirect(url('perfil.php'));
    }

    $accion = $_POST['accion'] ?? '';
    
    if ($accion === 'actualizar_perfil') {
        $nombre_completo = trim($_POST['nombre_completo'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        
        $errores = [];
        if (empty($nombre_completo)) $errores[] = 'El nombre completo es requerido';
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'El email no es válido';
        }
        
        if (empty($errores)) {
            try {
                $stmt = db()->prepare("
                    UPDATE usuarios 
                    SET nombre_completo = ?, email = ?, telefono = ?
                    WHERE usuario = ?
                ");
                $stmt->bind_param('ssss', $nombre_completo, $email, $telefono, $user['usuario']);
                $stmt->execute();
                
                flash('ok', 'Perfil actualizado correctamente');
                
                // Actualizar sesión
                $_SESSION['user']['nombre'] = $nombre_completo;
                
            } catch (Exception $e) {
                flash('error', 'Error al actualizar perfil: ' . $e->getMessage());
            }
        } else {
            flash('error', implode('<br>', $errores));
        }
    }
    
    if ($accion === 'cambiar_clave') {
        $clave_actual = $_POST['clave_actual'] ?? '';
        $clave_nueva = $_POST['clave_nueva'] ?? '';
        $clave_confirmar = $_POST['clave_confirmar'] ?? '';
        
        $errores = [];
        
        // Verificar contraseña actual
        if (!password_verify($clave_actual, $userData['clave'])) {
            $errores[] = 'La contraseña actual es incorrecta';
        }
        
        if (strlen($clave_nueva) < 6) {
            $errores[] = 'La nueva contraseña debe tener al menos 6 caracteres';
        }
        
        if ($clave_nueva !== $clave_confirmar) {
            $errores[] = 'Las contraseñas no coinciden';
        }
        
        if (empty($errores)) {
            try {
                $clave_hash = password_hash($clave_nueva, PASSWORD_DEFAULT);
                $stmt = db()->prepare("UPDATE usuarios SET clave = ? WHERE usuario = ?");
                $stmt->bind_param('ss', $clave_hash, $user['usuario']);
                $stmt->execute();
                
                flash('ok', 'Contraseña cambiada correctamente');
            } catch (Exception $e) {
                flash('error', 'Error al cambiar contraseña: ' . $e->getMessage());
            }
        } else {
            flash('error', implode('<br>', $errores));
        }
    }
    
    redirect(url('perfil.php'));
}

// Obtener estadísticas del usuario
$stats = [];

// Solicitudes del usuario
$stmt = db()->prepare("SELECT estado, COUNT(*) as total FROM solicitudes WHERE usuario = ? GROUP BY estado");
$stmt->bind_param('s', $user['usuario']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $stats['solicitudes'][$row['estado']] = $row['total'];
}

// Total de documentos descargados (simulado)
$stats['documentos_descargados'] = 0;

// Capacitaciones completadas
$stmt = db()->prepare("
    SELECT COUNT(*) as total 
    FROM capacitaciones_progreso 
    WHERE usuario = ? AND completada = 1
");
$stmt->bind_param('s', $user['usuario']);
$stmt->execute();
$stats['capacitaciones_completadas'] = $stmt->get_result()->fetch_assoc()['total'];

// Eventos creados (si es admin)
if (Auth::isAdmin()) {
    $stmt = db()->prepare("SELECT COUNT(*) as total FROM eventos WHERE creado_por = ?");
    $stmt->bind_param('s', $user['usuario']);
    $stmt->execute();
    $stats['eventos_creados'] = $stmt->get_result()->fetch_assoc()['total'];
}

include __DIR__ . '/includes/layout.php';
?>

<div class="row">
    <div class="col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-user"></i> Mi Perfil</h1>
        </div>

        <?php if ($msg = flash('error')): ?>
            <div class="alert alert-danger"><?= $msg ?></div>
        <?php endif; ?>
        <?php if ($msg = flash('ok')): ?>
            <div class="alert alert-success"><?= $msg ?></div>
        <?php endif; ?>

        <!-- Información personal -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Información Personal</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                    <input type="hidden" name="accion" value="actualizar_perfil">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Usuario</label>
                                <input type="text" class="form-control" value="<?= e($userData['usuario']) ?>" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Nombre Completo *</label>
                                <input type="text" name="nombre_completo" class="form-control" 
                                       value="<?= e($userData['nombre_completo']) ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" 
                                       value="<?= e($userData['email']) ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Teléfono</label>
                                <input type="text" name="telefono" class="form-control" 
                                       value="<?= e($userData['telefono']) ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Cargo</label>
                                <input type="text" class="form-control" value="<?= e($userData['cargo']) ?>" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Departamento</label>
                                <input type="text" class="form-control" value="<?= e($userData['departamento']) ?>" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Fecha de Ingreso</label>
                                <input type="text" class="form-control" 
                                       value="<?= $userData['fecha_ingreso'] ? date('d/m/Y', strtotime($userData['fecha_ingreso'])) : 'No especificada' ?>" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Rol</label>
                                <input type="text" class="form-control" 
                                       value="<?= $userData['admin'] ? 'Administrador' : 'Usuario' ?>" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Actualizar Información
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Cambiar contraseña -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Cambiar Contraseña</h5>
            </div>
            <div class="card-body">
                <form method="POST" id="formCambiarClave">
                    <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                    <input type="hidden" name="accion" value="cambiar_clave">
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Contraseña Actual *</label>
                                <input type="password" name="clave_actual" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Nueva Contraseña *</label>
                                <input type="password" name="clave_nueva" class="form-control" required minlength="6">
                                <div class="form-text">Mínimo 6 caracteres</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Confirmar Nueva Contraseña *</label>
                                <input type="password" name="clave_confirmar" class="form-control" required minlength="6">
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-key"></i> Cambiar Contraseña
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Sidebar con estadísticas -->
    <div class="col-lg-4">
        <!-- Foto de perfil -->
        <div class="card mb-4">
            <div class="card-body text-center">
                <div class="mb-3">
                    <i class="fas fa-user-circle fa-5x text-muted"></i>
                </div>
                <h5 class="card-title"><?= e($userData['nombre_completo']) ?></h5>
                <p class="card-text text-muted">
                    <?= e($userData['cargo']) ?><br>
                    <?= e($userData['departamento']) ?>
                </p>
                <span class="badge <?= $userData['admin'] ? 'bg-danger' : 'bg-primary' ?>">
                    <?= $userData['admin'] ? 'Administrador' : 'Usuario' ?>
                </span>
            </div>
        </div>

        <!-- Estadísticas del usuario -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">Mis Estadísticas</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="text-center">
                            <h4 class="text-primary mb-0">
                                <?= array_sum($stats['solicitudes'] ?? []) ?>
                            </h4>
                            <small class="text-muted">Total Solicitudes</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center">
                            <h4 class="text-success mb-0">
                                <?= $stats['solicitudes']['aprobada'] ?? 0 ?>
                            </h4>
                            <small class="text-muted">Aprobadas</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center">
                            <h4 class="text-info mb-0">
                                <?= $stats['capacitaciones_completadas'] ?>
                            </h4>
                            <small class="text-muted">Capacitaciones</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center">
                            <h4 class="text-warning mb-0">
                                <?= $stats['solicitudes']['pendiente'] ?? 0 ?>
                            </h4>
                            <small class="text-muted">Pendientes</small>
                        </div>
                    </div>
                </div>
                
                <?php if (Auth::isAdmin()): ?>
                    <hr>
                    <div class="text-center">
                        <h4 class="text-purple mb-0" style="color: #6f42c1;">
                            <?= $stats['eventos_creados'] ?>
                        </h4>
                        <small class="text-muted">Eventos Creados</small>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Actividad reciente -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">Actividad Reciente</h6>
            </div>
            <div class="card-body">
                <?php
                // Obtener actividad reciente del usuario
                $stmt = db()->prepare("
                    SELECT 'solicitud' as tipo, titulo, fecha_solicitud as fecha
                    FROM solicitudes 
                    WHERE usuario = ? 
                    ORDER BY fecha_solicitud DESC 
                    LIMIT 5
                ");
                $stmt->bind_param('s', $user['usuario']);
                $stmt->execute();
                $actividad = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                ?>
                
                <?php if (empty($actividad)): ?>
                    <p class="text-muted text-center">Sin actividad reciente</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($actividad as $item): ?>
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 small">
                                            <i class="fas fa-file-alt text-primary me-1"></i>
                                            <?= e($item['titulo'] ?: 'Solicitud') ?>
                                        </h6>
                                        <small class="text-muted">
                                            <?= date('d/m/Y H:i', strtotime($item['fecha'])) ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Validar que las contraseñas coincidan
document.getElementById('formCambiarClave').addEventListener('submit', function(e) {
    const nueva = document.querySelector('input[name="clave_nueva"]').value;
    const confirmar = document.querySelector('input[name="clave_confirmar"]').value;
    
    if (nueva !== confirmar) {
        e.preventDefault();
        alert('Las contraseñas no coinciden');
        return false;
    }
    
    if (nueva.length < 6) {
        e.preventDefault();
        alert('La nueva contraseña debe tener al menos 6 caracteres');
        return false;
    }
    
    return true;
});

// Mostrar/ocultar contraseñas
function togglePassword(inputName) {
    const input = document.querySelector(`input[name="${inputName}"]`);
    const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
    input.setAttribute('type', type);
}
</script>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<?php include __DIR__ . '/includes/layout_footer.php'; ?>