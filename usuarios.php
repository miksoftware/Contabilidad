<?php
session_start();

// Solo administradores pueden acceder
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: dashboard.php');
    exit();
}

require_once 'config/database.php';

// Procesar acciones CRUD
$mensaje = '';
$tipoMensaje = 'success';

if ($_POST) {
    $accion = $_POST['accion'] ?? '';
    
    try {
        switch ($accion) {
            case 'crear':
                $nombre = trim($_POST['nombre']);
                $email = trim($_POST['email']);
                $password = $_POST['password'];
                $rol = $_POST['rol'];
                
                if (empty($nombre) || empty($email) || empty($password)) {
                    throw new Exception('Todos los campos son obligatorios');
                }
                
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception('Email inválido');
                }
                
                if (strlen($password) < 6) {
                    throw new Exception('La contraseña debe tener al menos 6 caracteres');
                }
                
                // Verificar que el email no exista
                $existeEmail = $db->fetch("SELECT id FROM usuarios WHERE email = ?", [$email]);
                if ($existeEmail) {
                    throw new Exception('Ya existe un usuario con ese email');
                }
                
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                
                $db->query(
                    "INSERT INTO usuarios (nombre, email, password, rol) VALUES (?, ?, ?, ?)",
                    [$nombre, $email, $passwordHash, $rol]
                );
                
                $mensaje = 'Usuario creado exitosamente';
                break;
                
            case 'editar':
                $id = intval($_POST['id']);
                $nombre = trim($_POST['nombre']);
                $email = trim($_POST['email']);
                $rol = $_POST['rol'];
                $password = $_POST['password'] ?? '';
                
                if (empty($nombre) || empty($email)) {
                    throw new Exception('Nombre y email son obligatorios');
                }
                
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception('Email inválido');
                }
                
                // Verificar que el email no exista en otro usuario
                $existeEmail = $db->fetch("SELECT id FROM usuarios WHERE email = ? AND id != ?", [$email, $id]);
                if ($existeEmail) {
                    throw new Exception('Ya existe otro usuario con ese email');
                }
                
                if (!empty($password)) {
                    if (strlen($password) < 6) {
                        throw new Exception('La contraseña debe tener al menos 6 caracteres');
                    }
                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                    $db->query(
                        "UPDATE usuarios SET nombre = ?, email = ?, password = ?, rol = ? WHERE id = ?",
                        [$nombre, $email, $passwordHash, $rol, $id]
                    );
                } else {
                    $db->query(
                        "UPDATE usuarios SET nombre = ?, email = ?, rol = ? WHERE id = ?",
                        [$nombre, $email, $rol, $id]
                    );
                }
                
                $mensaje = 'Usuario actualizado exitosamente';
                break;
                
            case 'toggle_activo':
                $id = intval($_POST['id']);
                
                // No permitir desactivar el propio usuario
                if ($id == $_SESSION['user_id']) {
                    throw new Exception('No puedes desactivar tu propio usuario');
                }
                
                $db->query("UPDATE usuarios SET activo = NOT activo WHERE id = ?", [$id]);
                $mensaje = 'Estado de usuario actualizado';
                break;
                
            case 'eliminar':
                $id = intval($_POST['id']);
                
                // No permitir eliminar el propio usuario
                if ($id == $_SESSION['user_id']) {
                    throw new Exception('No puedes eliminar tu propio usuario');
                }
                
                // Verificar si tiene transacciones
                $transacciones = $db->fetch(
                    "SELECT COUNT(*) as total FROM transacciones WHERE usuario_id = ?",
                    [$id]
                );
                
                if ($transacciones['total'] > 0) {
                    throw new Exception('No se puede eliminar un usuario con transacciones. Desactívalo en su lugar.');
                }
                
                $db->query("DELETE FROM usuarios WHERE id = ?", [$id]);
                $mensaje = 'Usuario eliminado exitosamente';
                break;
        }
    } catch (Exception $e) {
        $mensaje = $e->getMessage();
        $tipoMensaje = 'danger';
    }
}

// Obtener todos los usuarios con estadísticas
$usuarios = $db->fetchAll(
    "SELECT u.*, 
     (SELECT COUNT(*) FROM transacciones WHERE usuario_id = u.id) as total_transacciones,
     (SELECT SUM(cantidad) FROM transacciones WHERE usuario_id = u.id AND tipo = 'ingreso') as total_ingresos,
     (SELECT SUM(cantidad) FROM transacciones WHERE usuario_id = u.id AND tipo = 'gasto') as total_gastos
     FROM usuarios u 
     ORDER BY u.activo DESC, u.created_at DESC"
);

$titulo = 'Gestión de Usuarios - Contabilidad Familiar';
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="transacciones.php">
                            <i class="fas fa-exchange-alt me-2"></i>Transacciones
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="categorias.php">
                            <i class="fas fa-tags me-2"></i>Categorías
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="cuentas.php">
                            <i class="fas fa-university me-2"></i>Cuentas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="metas.php">
                            <i class="fas fa-bullseye me-2"></i>Metas de Ahorro
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reportes.php">
                            <i class="fas fa-chart-bar me-2"></i>Reportes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="presupuestos.php">
                            <i class="fas fa-calculator me-2"></i>Presupuestos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="usuarios.php">
                            <i class="fas fa-users me-2"></i>Usuarios
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="transferencias.php">
                            <i class="fas fa-exchange-alt me-2"></i>Transferencias
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="fas fa-users me-2"></i>Gestión de Usuarios
                    <span class="badge bg-secondary fs-6"><?php echo count($usuarios); ?> usuarios</span>
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#nuevoUsuarioModal">
                        <i class="fas fa-user-plus me-1"></i>Nuevo Usuario
                    </button>
                </div>
            </div>

            <!-- Mensajes -->
            <?php if ($mensaje): ?>
                <div class="alert alert-<?php echo $tipoMensaje; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($mensaje); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Estadísticas -->
            <div class="row mb-4">
                <?php
                $totalUsuarios = count($usuarios);
                $usuariosActivos = count(array_filter($usuarios, fn($u) => $u['activo']));
                $administradores = count(array_filter($usuarios, fn($u) => $u['rol'] === 'admin'));
                ?>
                <div class="col-md-3">
                    <div class="card stats-card">
                        <div class="card-body text-center">
                            <i class="fas fa-users fa-2x mb-2"></i>
                            <h3><?php echo $totalUsuarios; ?></h3>
                            <p class="mb-0">Total Usuarios</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card income">
                        <div class="card-body text-center">
                            <i class="fas fa-user-check fa-2x mb-2"></i>
                            <h3><?php echo $usuariosActivos; ?></h3>
                            <p class="mb-0">Activos</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card expense">
                        <div class="card-body text-center">
                            <i class="fas fa-user-times fa-2x mb-2"></i>
                            <h3><?php echo $totalUsuarios - $usuariosActivos; ?></h3>
                            <p class="mb-0">Inactivos</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card savings">
                        <div class="card-body text-center">
                            <i class="fas fa-user-shield fa-2x mb-2"></i>
                            <h3><?php echo $administradores; ?></h3>
                            <p class="mb-0">Administradores</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de usuarios -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-list me-2"></i>Listado de Usuarios
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Usuario</th>
                                    <th>Email</th>
                                    <th>Rol</th>
                                    <th>Estado</th>
                                    <th>Transacciones</th>
                                    <th>Actividad Financiera</th>
                                    <th>Registro</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usuarios as $usuario): ?>
                                    <tr class="<?php echo !$usuario['activo'] ? 'table-secondary' : ''; ?>">
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar me-2">
                                                    <i class="fas fa-user-circle fa-2x text-<?php echo $usuario['rol'] === 'admin' ? 'primary' : 'secondary'; ?>"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($usuario['nombre']); ?></div>
                                                    <?php if ($usuario['id'] == $_SESSION['user_id']): ?>
                                                        <small class="text-primary"><i class="fas fa-star me-1"></i>Tú</small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $usuario['rol'] === 'admin' ? 'primary' : 'secondary'; ?>">
                                                <i class="fas fa-<?php echo $usuario['rol'] === 'admin' ? 'user-shield' : 'user'; ?> me-1"></i>
                                                <?php echo ucfirst($usuario['rol']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($usuario['activo']): ?>
                                                <span class="badge bg-success">Activo</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Inactivo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?php echo $usuario['total_transacciones'] ?? 0; ?></span>
                                        </td>
                                        <td>
                                            <?php if ($usuario['total_transacciones'] > 0): ?>
                                                <small class="text-success">
                                                    <i class="fas fa-arrow-up me-1"></i>
                                                    $<?php echo number_format($usuario['total_ingresos'] ?? 0, 0); ?>
                                                </small>
                                                <br>
                                                <small class="text-danger">
                                                    <i class="fas fa-arrow-down me-1"></i>
                                                    $<?php echo number_format($usuario['total_gastos'] ?? 0, 0); ?>
                                                </small>
                                            <?php else: ?>
                                                <small class="text-muted">Sin actividad</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo date('d/m/Y', strtotime($usuario['created_at'])); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary" onclick="editarUsuario(<?php echo htmlspecialchars(json_encode($usuario)); ?>)" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                
                                                <?php if ($usuario['id'] != $_SESSION['user_id']): ?>
                                                    <button class="btn btn-outline-<?php echo $usuario['activo'] ? 'warning' : 'success'; ?>" 
                                                            onclick="toggleActivo(<?php echo $usuario['id']; ?>)" title="<?php echo $usuario['activo'] ? 'Desactivar' : 'Activar'; ?>">
                                                        <i class="fas fa-<?php echo $usuario['activo'] ? 'pause' : 'play'; ?>"></i>
                                                    </button>
                                                    
                                                    <?php if ($usuario['total_transacciones'] == 0): ?>
                                                        <button class="btn btn-outline-danger" onclick="eliminarUsuario(<?php echo $usuario['id']; ?>)" title="Eliminar">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal Nuevo/Editar Usuario -->
<div class="modal fade" id="nuevoUsuarioModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">
                    <i class="fas fa-user-plus me-2"></i>Nuevo Usuario
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="usuarioForm" method="POST">
                <input type="hidden" name="accion" id="accion" value="crear">
                <input type="hidden" name="id" id="usuarioId">
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre Completo</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div class="form-text">Mínimo 6 caracteres</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="rol" class="form-label">Rol</label>
                        <select class="form-select" id="rol" name="rol" required>
                            <option value="">Seleccionar rol</option>
                            <option value="usuario">👤 Usuario</option>
                            <option value="admin">👑 Administrador</option>
                        </select>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">Crear Usuario</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Formularios ocultos -->
<form id="toggleForm" method="POST" style="display: none;">
    <input type="hidden" name="accion" value="toggle_activo">
    <input type="hidden" name="id" id="toggleId">
</form>

<form id="deleteForm" method="POST" style="display: none;">
    <input type="hidden" name="accion" value="eliminar">
    <input type="hidden" name="id" id="deleteId">
</form>

<script>
function editarUsuario(usuario) {
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-edit me-2"></i>Editar Usuario';
    document.getElementById('accion').value = 'editar';
    document.getElementById('usuarioId').value = usuario.id;
    document.getElementById('nombre').value = usuario.nombre;
    document.getElementById('email').value = usuario.email;
    document.getElementById('rol').value = usuario.rol;
    
    // Hacer contraseña opcional en edición
    const passwordField = document.getElementById('password');
    passwordField.required = false;
    passwordField.placeholder = 'Dejar vacío para mantener la actual';
    passwordField.nextElementSibling.textContent = 'Dejar vacío para mantener la contraseña actual';
    
    document.getElementById('submitBtn').textContent = 'Actualizar Usuario';
    
    new bootstrap.Modal(document.getElementById('nuevoUsuarioModal')).show();
}

function toggleActivo(id) {
    if (confirm('¿Estás seguro de que deseas cambiar el estado de este usuario?')) {
        document.getElementById('toggleId').value = id;
        document.getElementById('toggleForm').submit();
    }
}

function eliminarUsuario(id) {
    if (confirm('¿Estás seguro de que deseas eliminar este usuario? Esta acción no se puede deshacer.')) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}

// Resetear modal
document.getElementById('nuevoUsuarioModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-plus me-2"></i>Nuevo Usuario';
    document.getElementById('accion').value = 'crear';
    document.getElementById('usuarioForm').reset();
    document.getElementById('password').required = true;
    document.getElementById('password').placeholder = '';
    document.getElementById('password').nextElementSibling.textContent = 'Mínimo 6 caracteres';
    document.getElementById('submitBtn').textContent = 'Crear Usuario';
});
</script>

<?php include 'includes/footer.php'; ?>
