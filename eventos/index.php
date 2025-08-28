<?php
require_once __DIR__ . '/../includes/config.php';
Auth::check() or redirect(url('index.php'));

$user = Auth::user();
$isAdmin = Auth::isAdmin();

// Obtener vista (mes/semana/día)
$vista = $_GET['vista'] ?? 'mes';
$fecha = $_GET['fecha'] ?? date('Y-m-d');

// Validar fecha
try {
    $fechaObj = new DateTime($fecha);
} catch (Exception $e) {
    $fechaObj = new DateTime();
    $fecha = $fechaObj->format('Y-m-d');
}

$año = $fechaObj->format('Y');
$mes = $fechaObj->format('m');
$dia = $fechaObj->format('d');

// Obtener eventos del mes actual
$primerDiaMes = $fechaObj->format('Y-m-01');
$ultimoDiaMes = $fechaObj->format('Y-m-t');

$eventos = db()->query("
    SELECT id, titulo, descripcion, tipo, fecha_inicio, fecha_fin, todo_el_dia, color,
           DATE(fecha_inicio) as fecha_evento,
           TIME(fecha_inicio) as hora_inicio,
           TIME(fecha_fin) as hora_fin
    FROM eventos 
    WHERE DATE(fecha_inicio) BETWEEN '$primerDiaMes' AND '$ultimoDiaMes'
    ORDER BY fecha_inicio ASC
")->fetch_all(MYSQLI_ASSOC);

// Organizar eventos por fecha para el calendario
$eventosPorFecha = [];
foreach ($eventos as $evento) {
    $fechaEvento = $evento['fecha_evento'];
    if (!isset($eventosPorFecha[$fechaEvento])) {
        $eventosPorFecha[$fechaEvento] = [];
    }
    $eventosPorFecha[$fechaEvento][] = $evento;
}

// Generar calendario
function generarCalendario($año, $mes, $eventosPorFecha) {
    $primerDia = mktime(0, 0, 0, $mes, 1, $año);
    $nombreMes = date('F Y', $primerDia);
    $diasEnMes = date('t', $primerDia);
    $diaSemana = date('w', $primerDia); // 0 = domingo
    
    $html = '<div class="calendar">';
    $html .= '<table class="table table-bordered">';
    $html .= '<thead class="table-light">';
    $html .= '<tr>';
    $html .= '<th class="text-center">Dom</th>';
    $html .= '<th class="text-center">Lun</th>';
    $html .= '<th class="text-center">Mar</th>';
    $html .= '<th class="text-center">Mié</th>';
    $html .= '<th class="text-center">Jue</th>';
    $html .= '<th class="text-center">Vie</th>';
    $html .= '<th class="text-center">Sáb</th>';
    $html .= '</tr>';
    $html .= '</thead>';
    $html .= '<tbody>';
    
    $contador = 0;
    $fecha = 1;
    
    for ($i = 0; $i < 6; $i++) {
        $html .= '<tr>';
        for ($j = 0; $j < 7; $j++) {
            if ($contador < $diaSemana || $fecha > $diasEnMes) {
                $html .= '<td class="calendar-day empty"></td>';
            } else {
                $fechaCompleta = sprintf('%04d-%02d-%02d', $año, $mes, $fecha);
                $esHoy = $fechaCompleta === date('Y-m-d');
                $claseHoy = $esHoy ? 'today' : '';
                
                $html .= "<td class='calendar-day $claseHoy'>";
                $html .= "<div class='day-number'>$fecha</div>";
                
                if (isset($eventosPorFecha[$fechaCompleta])) {
                    foreach ($eventosPorFecha[$fechaCompleta] as $evento) {
                        $html .= "<div class='event-item' style='background-color: {$evento['color']};' title='{$evento['titulo']}'>";
                        $html .= "<small>" . mb_substr($evento['titulo'], 0, 15) . "</small>";
                        $html .= "</div>";
                    }
                }
                
                $html .= '</td>';
                $fecha++;
            }
            $contador++;
        }
        $html .= '</tr>';
        if ($fecha > $diasEnMes) break;
    }
    
    $html .= '</tbody>';
    $html .= '</table>';
    $html .= '</div>';
    
    return $html;
}

include __DIR__ . '/../includes/layout.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>
        <i class="fas fa-calendar"></i> Calendario Corporativo
    </h1>
    <?php if ($isAdmin): ?>
        <div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#nuevoEventoModal">
                <i class="fas fa-plus"></i> Nuevo Evento
            </button>
        </div>
    <?php endif; ?>
</div>

<!-- Navegación del calendario -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-6">
                <div class="btn-group" role="group">
                    <a href="?fecha=<?= $fechaObj->modify('-1 month')->format('Y-m-d') ?>&vista=<?= $vista ?>" 
                       class="btn btn-outline-primary">
                        <i class="fas fa-chevron-left"></i> Anterior
                    </a>
                    <a href="?fecha=<?= date('Y-m-d') ?>&vista=<?= $vista ?>" 
                       class="btn btn-outline-primary">Hoy</a>
                    <a href="?fecha=<?= $fechaObj->modify('+2 months')->format('Y-m-d') ?>&vista=<?= $vista ?>" 
                       class="btn btn-outline-primary">
                        Siguiente <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
            </div>
            <div class="col-md-6 text-end">
                <h4 class="mb-0">
                    <?= strftime('%B %Y', $fechaObj->modify('-1 month')->getTimestamp()) ?>
                </h4>
            </div>
        </div>
    </div>
</div>

<!-- Leyenda de tipos de eventos -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-12">
                <h6>Tipos de Eventos:</h6>
                <div class="d-flex flex-wrap gap-3">
                    <span class="badge bg-success">
                        <i class="fas fa-birthday-cake"></i> Cumpleaños
                    </span>
                    <span class="badge bg-primary">
                        <i class="fas fa-graduation-cap"></i> Capacitación
                    </span>
                    <span class="badge bg-info">
                        <i class="fas fa-users"></i> Reunión
                    </span>
                    <span class="badge bg-warning text-dark">
                        <i class="fas fa-star"></i> Festivo
                    </span>
                    <span class="badge bg-purple" style="background-color: #6f42c1;">
                        <i class="fas fa-building"></i> Corporativo
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Calendario -->
<div class="card">
    <div class="card-body">
        <?= generarCalendario($año, $mes, $eventosPorFecha) ?>
    </div>
</div>

<!-- Lista de eventos del día -->
<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Eventos de Hoy</h5>
            </div>
            <div class="card-body">
                <?php
                $eventosHoy = $eventosPorFecha[date('Y-m-d')] ?? [];
                if (empty($eventosHoy)):
                ?>
                    <p class="text-muted text-center">No hay eventos programados para hoy</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($eventosHoy as $evento): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1"><?= e($evento['titulo']) ?></h6>
                                        <p class="mb-1 small text-muted"><?= e($evento['descripcion']) ?></p>
                                        <small>
                                            <span class="badge" style="background-color: <?= e($evento['color']) ?>;">
                                                <?= ucfirst($evento['tipo']) ?>
                                            </span>
                                            <?php if (!$evento['todo_el_dia']): ?>
                                                <span class="text-muted ms-2">
                                                    <?= date('H:i', strtotime($evento['hora_inicio'])) ?>
                                                    <?php if ($evento['hora_fin']): ?>
                                                        - <?= date('H:i', strtotime($evento['hora_fin'])) ?>
                                                    <?php endif; ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted ms-2">Todo el día</span>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    <?php if ($isAdmin): ?>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" onclick="editarEvento(<?= $evento['id'] ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-outline-danger" onclick="eliminarEvento(<?= $evento['id'] ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Próximos Eventos</h5>
            </div>
            <div class="card-body">
                <?php
                $eventosProximos = db()->query("
                    SELECT * FROM eventos 
                    WHERE DATE(fecha_inicio) > CURDATE() 
                    ORDER BY fecha_inicio ASC 
                    LIMIT 5
                ")->fetch_all(MYSQLI_ASSOC);
                ?>
                
                <?php if (empty($eventosProximos)): ?>
                    <p class="text-muted text-center">No hay eventos próximos</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($eventosProximos as $evento): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1"><?= e($evento['titulo']) ?></h6>
                                        <small class="text-muted">
                                            <?= date('d/m/Y', strtotime($evento['fecha_inicio'])) ?>
                                            <?php if (!$evento['todo_el_dia']): ?>
                                                - <?= date('H:i', strtotime($evento['fecha_inicio'])) ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    <span class="badge" style="background-color: <?= e($evento['color']) ?>;">
                                        <?= ucfirst($evento['tipo']) ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if ($isAdmin): ?>
<!-- Modal Nuevo Evento -->
<div class="modal fade" id="nuevoEventoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nuevo Evento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= url('eventos/crear.php') ?>">
                <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Título *</label>
                        <input type="text" name="titulo" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea name="descripcion" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Tipo *</label>
                                <select name="tipo" class="form-select" required>
                                    <option value="corporativo">Corporativo</option>
                                    <option value="reunion">Reunión</option>
                                    <option value="capacitacion">Capacitación</option>
                                    <option value="cumpleanos">Cumpleaños</option>
                                    <option value="festivo">Festivo</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Color</label>
                                <select name="color" class="form-select">
                                    <option value="#007bff">Azul</option>
                                    <option value="#28a745">Verde</option>
                                    <option value="#ffc107">Amarillo</option>
                                    <option value="#dc3545">Rojo</option>
                                    <option value="#6f42c1">Púrpura</option>
                                    <option value="#fd7e14">Naranja</option>
                                    <option value="#20c997">Turquesa</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="todo_el_dia" class="form-check-input" id="todoElDia">
                            <label class="form-check-label" for="todoElDia">
                                Todo el día
                            </label>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Fecha de Inicio *</label>
                                <input type="date" name="fecha_inicio" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Fecha de Fin</label>
                                <input type="date" name="fecha_fin" class="form-control">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row" id="camposHora">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Hora de Inicio</label>
                                <input type="time" name="hora_inicio" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Hora de Fin</label>
                                <input type="time" name="hora_fin" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear Evento</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
.calendar {
    width: 100%;
}

.calendar-day {
    height: 120px;
    vertical-align: top;
    position: relative;
    padding: 5px;
}

.calendar-day.empty {
    background-color: #f8f9fa;
}

.calendar-day.today {
    background-color: #e3f2fd;
    border: 2px solid #2196f3;
}

.day-number {
    font-weight: bold;
    margin-bottom: 5px;
}

.event-item {
    font-size: 10px;
    padding: 2px 4px;
    margin: 1px 0;
    border-radius: 3px;
    color: white;
    cursor: pointer;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}

.event-item:hover {
    opacity: 0.8;
}

.calendar td {
    border: 1px solid #dee2e6;
}

.calendar th {
    text-align: center;
    padding: 10px;
    background-color: #f8f9fa;
}

@media (max-width: 768px) {
    .calendar-day {
        height: 80px;
        padding: 2px;
    }
    
    .event-item {
        font-size: 8px;
        padding: 1px 2px;
    }
}
</style>

<script>
// Manejar checkbox "Todo el día"
document.getElementById('todoElDia').addEventListener('change', function() {
    const camposHora = document.getElementById('camposHora');
    if (this.checked) {
        camposHora.style.display = 'none';
    } else {
        camposHora.style.display = 'block';
    }
});

// Funciones para administradores
<?php if ($isAdmin): ?>
function editarEvento(id) {
    // Implementar edición de evento
    alert('Función de edición en desarrollo');
}

function eliminarEvento(id) {
    if (confirm('¿Estás seguro de que quieres eliminar este evento?')) {
        window.location.href = `<?= url('eventos/eliminar.php') ?>?id=${id}`;
    }
}
<?php endif; ?>

// Hacer eventos clickeables en el calendario
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('event-item')) {
        const titulo = e.target.getAttribute('title');
        alert('Evento: ' + titulo);
    }
});
</script>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<?php include __DIR__ . '/../includes/layout_footer.php'; ?>