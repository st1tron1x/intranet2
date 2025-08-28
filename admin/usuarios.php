<?php
require_once __DIR__ . '/../includes/config.php';
Auth::requireRole('admin');

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash('error', 'Token CSRF inválido');
        redirect(url('admin/usuarios.php'));
    }

    $accion = $_POST['accion'] ?? '';
    
    if ($accion === 'crear') {
        $usuario = trim($_POST['usuario'] ?? '');
        $nombre = trim($_POST['nombre_completo'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $cargo = trim($_POST['cargo'] ?? '');
        $departamento = trim($_POST['departamento'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $clave = password_hash($_POST['clave'] ?? 'temporal123', PASSWORD_DEFAULT);
        $admin = isset($_POST['admin']) ? 1 : 0;

        try {
            $stmt = db()->prepare(
                "INSERT INTO usuarios (usuario, clave, admin, nombre_completo, email, telefono, cargo, departamento, fecha_ingreso) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURDATE())"
            );
            $stmt->bind_param('ssisssss', $usuario, $clave, $admin, $nombre, $email, $telefono, $cargo, $departamento);
            $stmt->execute();
            flash('ok', 'Usuario creado exitosamente');
        } catch (Exception $e) {
            flash('error', 'Error al crear usuario: ' . $e->getMessage());
        }
    }
    
    if ($accion === 'editar') {
        $id = (int)$_POST['id'];
        $nombre = trim($_POST['nombre_completo'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $cargo = trim($_POST['cargo'] ?? '');
        $departamento = trim($_POST['departamento'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $admin = isset($_POST['admin']) ? 1 : 0;
        $activo = isset($_POST['activo']) ? 1 : 0;

        $stmt = db()->prepare(
            "UPDATE usuarios SET nombre_completo=?, email=?, telefono=?, cargo=?, departamento=?, admin=?, activo=? WHERE id=?"
        );
        $stmt->bind_param('ssssssii', $nombre, $email, $telefono, $cargo, $departamento, $admin, $activo, $id);
        $stmt->execute();
        flash('ok', 'Usuario actualizado');
    }

    if ($accion === 'cambiar_clave') {
        $id = (int)$_POST['id'];
        $nueva_clave = password_hash($_POST['nueva_clave'], PASSWORD_DEFAULT);
        
        $stmt = db()->prepare("UPDATE usuarios SET clave=? WHERE id=?");
        $stmt->bind_param('si', $nueva_clave, $id);
        $stmt->execute();
        flash('ok', 'Contraseña actualizada');
    }

    redirect(url('admin/usuarios.php'));
}

// Obtener usuarios
$usuarios = db()->query(
    "SELECT id, usuario, nombre_completo, email, cargo, departamento, admin, activo, fecha_ingreso, ultimo_login 
     FROM usuarios ORDER BY nombre_completo"
)->fetch_all(MYSQLI_ASSOC);

include __DIR__ . '/../includes/layout.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Gestión de Usuarios</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#crearUsuarioModal">
        <i class="fas fa-plus"></i> Nuevo Usuario
    </button>
</div>

<?php if ($msg = flash('error')): ?>
    <div class="alert alert-danger"><?= e($msg) ?></div>
<?php endif; ?>
<?php if ($msg = flash('ok')): ?>
    <div class="alert alert-success"><?= e($msg) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Nombre Completo</th>
                        <th>Email</th>
                        <th>Cargo</th>
                        <th>Departamento</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Último Login</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $user): ?>
                    <tr>
                        <td><?= e($user['usuario']) ?></td>
                        <td><?= e($user['nombre_completo']) ?></td>
                        <td><?= e($user['email']) ?></td>
                        <td><?= e($user['cargo']) ?></td>
                        <td><?= e($user['departamento']) ?></td>
                        <td>
                            <span class="badge <?= $user['admin'] ? 'bg-danger' : 'bg-info' ?>">
                                <?= $user['admin'] ? 'Admin' : 'Usuario' ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge <?= $user['activo'] ? 'bg-success' : 'bg-secondary' ?>">
                                <?= $user['activo'] ? 'Activo' : 'Inactivo' ?>
                            </span>
                        </td>
                        <td><?= $user['ultimo_login'] ? date('d/m/Y H:i', strtotime($user['ultimo_login'])) : 'Nunca' ?></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="editarUsuario(<?= htmlspecialchars(json_encode($user)) ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-warning" onclick="cambiarClave(<?= $user['id'] ?>, '<?= e($user['nombre_completo']) ?>')">
                                <i class="fas fa-key"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Crear Usuario -->
<div class="modal fade" id="crearUsuarioModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nuevo Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="accion" value="crear">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Usuario *</label>
                                <input type="text" name="usuario" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Contraseña inicial *</label>
                                <input type="password" name="clave" class="form-control" placeholder="temporal123">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nombre Completo *</label>
                        <input type="text" name="nombre_completo" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Teléfono</label>
                                <input type="text" name="telefono" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Cargo</label>
                                <input type="text" name="cargo" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Departamento</label>
                                <select name="departamento" class="form-select">
                                    <option value="">Seleccionar...</option>
                                    <option value="Gerencia">Gerencia</option>
                                    <option value="Administración">Administración</option>
                                    <option value="Contabilidad">Contabilidad</option>
                                    <option value="Comercial">Comercial</option>
                                    <option value="Operaciones">Operaciones</option>
                                    <option value="IT">IT</option>
                                    <option value="RRHH">RRHH</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="admin" class="form-check-input" id="admin">
                        <label class="form-check-label" for="admin">Permisos de Administrador</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear Usuario</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Usuario -->
<div class="modal fade" id="editarUsuarioModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="formEditarUsuario">
                <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="accion" value="editar">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Usuario</label>
                        <input type="text" id="edit_usuario" class="form-control" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nombre Completo *</label>
                        <input type="text" name="nombre_completo" id="edit_nombre" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" id="edit_email" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Teléfono</label>
                                <input type="text" name="telefono" id="edit_telefono" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Cargo</label>
                                <input type="text" name="cargo" id="edit_cargo" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Departamento</label>
                                <select name="departamento" id="edit_departamento" class="form-select">
                                    <option value="">Seleccionar...</option>
                                    <option value="Gerencia">Gerencia</option>
                                    <option value="Administración">Administración</option>
                                    <option value="Contabilidad">Contabilidad</option>
                                    <option value="Comercial">Comercial</option>
                                    <option value="Operaciones">Operaciones</option>
                                    <option value="IT">IT</option>
                                    <option value="RRHH">RRHH</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" name="admin" class="form-check-input" id="edit_admin">
                        <label class="form-check-label" for="edit_admin">Permisos de Administrador</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="activo" class="form-check-input" id="edit_activo">
                        <label class="form-check-label" for="edit_activo">Usuario Activo</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Actualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Cambiar Contraseña -->
<div class="modal fade" id="cambiarClaveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cambiar Contraseña</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="accion" value="cambiar_clave">
                <input type="hidden" name="id" id="clave_id">
                <div class="modal-body">
                    <p>Cambiar contraseña para: <strong id="clave_usuario"></strong></p>
                    <div class="mb-3">
                        <label class="form-label">Nueva Contraseña</label>
                        <input type="password" name="nueva_clave" class="form-control" required minlength="6">
                        <div class="form-text">Mínimo 6 caracteres</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">Cambiar Contraseña</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editarUsuario(user) {
    document.getElementById('edit_id').value = user.id;
    document.getElementById('edit_usuario').value = user.usuario;
    document.getElementById('edit_nombre').value = user.nombre_completo || '';
    document.getElementById('edit_email').value = user.email || '';
    document.getElementById('edit_telefono').value = user.telefono || '';
    document.getElementById('edit_cargo').value = user.cargo || '';
    document.getElementById('edit_departamento').value = user.departamento || '';
    document.getElementById('edit_admin').checked = user.admin == 1;
    document.getElementById('edit_activo').checked = user.activo == 1;
    
    new bootstrap.Modal(document.getElementById('editarUsuarioModal')).show();
}

function cambiarClave(id, nombre) {
    document.getElementById('clave_id').value = id;
    document.getElementById('clave_usuario').textContent = nombre;
    new bootstrap.Modal(document.getElementById('cambiarClaveModal')).show();
}
</script>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<?php include __DIR__ . '/../includes/layout_footer.php'; ?>