<?php
require_once __DIR__ . '/../includes/config.php';
Auth::check() or redirect(url('index.php'));

$user = Auth::user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash('error', 'Token CSRF inválido');
        redirect(url('solicitudes/crear.php'));
    }

    $tipo = $_POST['tipo'] ?? '';
    $titulo = trim($_POST['titulo'] ?? '');
    $motivo = trim($_POST['motivo'] ?? '');
    $fecha_inicio = $_POST['fecha_inicio'] ?? null;
    $fecha_fin = $_POST['fecha_fin'] ?? null;
    $correo_notificacion = trim($_POST['correo_notificacion'] ?? '');
    $prioridad = $_POST['prioridad'] ?? 'media';

    // Validaciones básicas
    $errores = [];
    if (empty($tipo)) $errores[] = 'Debe seleccionar un tipo de solicitud';
    if (empty($motivo)) $errores[] = 'Debe especificar el motivo de la solicitud';

    // Validaciones específicas por tipo
    if ($tipo === 'vacaciones' && (empty($fecha_inicio) || empty($fecha_fin))) {
        $errores[] = 'Para vacaciones debe especificar fechas de inicio y fin';
    }
    
    if ($tipo === 'permiso' && empty($fecha_inicio)) {
        $errores[] = 'Para permisos debe especificar la fecha';
    }

    if (empty($errores)) {
        // Generar título automático si no se proporcionó
        if (empty($titulo)) {
            $titulo = match($tipo) {
                'vacaciones' => 'Solicitud de Vacaciones',
                'certificado_laboral' => 'Solicitud de Certificado Laboral',
                'soporte_tecnico' => 'Solicitud de Soporte Técnico',
                'reserva_sala' => 'Reserva de Sala de Reuniones',
                'mantenimiento' => 'Solicitud de Mantenimiento',
                'papeleria' => 'Solicitud de Papelería',
                'permiso' => 'Solicitud de Permiso',
                default => 'Solicitud'
            };
        }

        try {
            $stmt = db()->prepare("
                INSERT INTO solicitudes (usuario, tipo, titulo, motivo, fecha_inicio, fecha_fin, correo_notificacion, prioridad) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param('ssssssss', 
                $user['usuario'], 
                $tipo, 
                $titulo, 
                $motivo, 
                $fecha_inicio ?: null, 
                $fecha_fin ?: null, 
                $correo_notificacion, 
                $prioridad
            );
            $stmt->execute();
            
            flash('ok', 'Solicitud creada exitosamente. Se notificará a los administradores.');
            redirect(url('solicitudes/index.php'));
            
        } catch (Exception $e) {
            $errores[] = 'Error al crear la solicitud: ' . $e->getMessage();
        }
    }

    if ($errores) {
        flash('error', implode('<br>', $errores));
    }
}

include __DIR__ . '/../includes/layout.php';
?>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">Nueva Solicitud</h3>
            </div>
            <div class="card-body">
                <?php if ($msg = flash('error')): ?>
                    <div class="alert alert-danger"><?= $msg ?></div>
                <?php endif; ?>

                <form method="POST" id="formSolicitud">
                    <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Tipo de Solicitud *</label>
                                <select name="tipo" class="form-select" required id="tipoSolicitud">
                                    <option value="">Seleccionar tipo...</option>
                                    <option value="vacaciones">Vacaciones</option>
                                    <option value="certificado_laboral">Certificado Laboral</option>
                                    <option value="soporte_tecnico">Soporte Técnico</option>
                                    <option value="reserva_sala">Reserva de Sala</option>
                                    <option value="mantenimiento">Mantenimiento</option>
                                    <option value="papeleria">Papelería e Insumos</option>
                                    <option value="permiso">Permiso</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Prioridad</label>
                                <select name="prioridad" class="form-select">
                                    <option value="baja">Baja</option>
                                    <option value="media" selected>Media</option>
                                    <option value="alta">Alta</option>
                                    <option value="critica">Crítica</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Título (opcional)</label>
                        <input type="text" name="titulo" class="form-control" 
                               placeholder="Se generará automáticamente si se deja vacío">
                    </div>

                    <!-- Campos específicos por tipo de solicitud -->
                    <div id="camposFechas" style="display: none;">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Fecha de Inicio</label>
                                    <input type="date" name="fecha_inicio" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Fecha de Fin</label>
                                    <input type="date" name="fecha_fin" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="campoFecha" style="display: none;">
                        <div class="mb-3">
                            <label class="form-label">Fecha</label>
                            <input type="date" name="fecha_inicio" class="form-control">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Descripción / Motivo *</label>
                        <textarea name="motivo" class="form-control" rows="4" required 
                                  placeholder="Describe detalladamente tu solicitud..."></textarea>
                        <div class="form-text" id="motivoHelp"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Correo para Notificaciones</label>
                        <input type="email" name="correo_notificacion" class="form-control" 
                               value="<?= e($user['email'] ?? '') ?>" 
                               placeholder="Se usará para enviarte actualizaciones">
                    </div>

                    <div class="card bg-light mb-3">
                        <div class="card-body">
                            <h6 class="card-title">Información importante:</h6>
                            <ul class="mb-0 small">
                                <li>Las solicitudes serán revisadas por los administradores</li>
                                <li>Recibirás notificaciones sobre el estado de tu solicitud</li>
                                <li>Para solicitudes urgentes, marca prioridad "Alta" o "Crítica"</li>
                                <li>Proporciona toda la información necesaria para agilizar el proceso</li>
                            </ul>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Enviar Solicitud
                        </button>
                        <a href="<?= url('solicitudes/index.php') ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Guía de tipos de solicitud -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-info-circle"></i> Guía de Solicitudes
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6><strong>Vacaciones:</strong></h6>
                        <p class="small text-muted">Solicita días de descanso. Especifica fechas de inicio y fin.</p>
                        
                        <h6><strong>Certificado Laboral:</strong></h6>
                        <p class="small text-muted">Para certificados de ingresos, constancias laborales, etc.</p>
                        
                        <h6><strong>Soporte Técnico:</strong></h6>
                        <p class="small text-muted">Problemas con equipos, software, accesos, etc.</p>
                        
                        <h6><strong>Reserva de Sala:</strong></h6>
                        <p class="small text-muted">Solicita el uso de salas de reuniones o espacios corporativos.</p>
                    </div>
                    <div class="col-md-6">
                        <h6><strong>Mantenimiento:</strong></h6>
                        <p class="small text-muted">Reparaciones, mantenimiento de mobiliario, instalaciones.</p>
                        
                        <h6><strong>Papelería e Insumos:</strong></h6>
                        <p class="small text-muted">Solicita materiales de oficina, insumos de trabajo.</p>
                        
                        <h6><strong>Permiso:</strong></h6>
                        <p class="small text-muted">Ausencias por citas médicas, trámites, emergencias.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('tipoSolicitud').addEventListener('change', function() {
    const tipo = this.value;
    const camposFechas = document.getElementById('camposFechas');
    const campoFecha = document.getElementById('campoFecha');
    const motivoHelp = document.getElementById('motivoHelp');
    
    // Ocultar todos los campos específicos
    camposFechas.style.display = 'none';
    campoFecha.style.display = 'none';
    
    // Mostrar campos según el tipo
    if (tipo === 'vacaciones') {
        camposFechas.style.display = 'block';
        motivoHelp.textContent = 'Especifica el motivo de las vacaciones y cualquier información relevante.';
    } else if (tipo === 'permiso') {
        campoFecha.style.display = 'block';
        motivoHelp.textContent = 'Indica el motivo del permiso (cita médica, trámite personal, etc.) y horario si es parcial.';
    } else if (tipo === 'certificado_laboral') {
        motivoHelp.textContent = 'Especifica qué tipo de certificado necesitas y para qué lo vas a usar.';
    } else if (tipo === 'soporte_tecnico') {
        motivoHelp.textContent = 'Describe detalladamente el problema técnico, qué equipos están afectados y cuándo ocurrió.';
    } else if (tipo === 'reserva_sala') {
        motivoHelp.textContent = 'Indica la fecha, hora y duración de la reunión, número de participantes y equipos necesarios.';
    } else if (tipo === 'mantenimiento') {
        motivoHelp.textContent = 'Describe qué necesita reparación o mantenimiento, ubicación exacta y urgencia.';
    } else if (tipo === 'papeleria') {
        motivoHelp.textContent = 'Lista los materiales o insumos que necesitas, cantidades aproximadas.';
    } else {
        motivoHelp.textContent = 'Proporciona todos los detalles necesarios para procesar tu solicitud.';
    }
});

// Validación de fechas
document.querySelector('input[name="fecha_fin"]').addEventListener('change', function() {
    const fechaInicio = document.querySelector('input[name="fecha_inicio"]').value;
    const fechaFin = this.value;
    
    if (fechaInicio && fechaFin && new Date(fechaFin) < new Date(fechaInicio)) {
        alert('La fecha de fin no puede ser anterior a la fecha de inicio');
        this.value = '';
    }
});
</script>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<?php include __DIR__ . '/../includes/layout_footer.php'; ?>