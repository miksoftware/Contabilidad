<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'config/database.php';

$titulo = 'Mi Perfil';

// Procesar actualización de perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'update_profile') {
            // Actualizar información básica
            $db->query(
                "UPDATE usuarios SET nombre = ?, email = ? WHERE id = ?",
                [$_POST['nombre'], $_POST['email'], $_SESSION['user_id']]
            );
            
            // Actualizar sesión
            $_SESSION['user_name'] = $_POST['nombre'];
            
            $success = "Perfil actualizado exitosamente.";
            
        } elseif ($action === 'change_password') {
            // Verificar contraseña actual
            $user = $db->fetch(
                "SELECT password FROM usuarios WHERE id = ?",
                [$_SESSION['user_id']]
            );
            
            if (!password_verify($_POST['current_password'], $user['password'])) {
                throw new Exception("La contraseña actual es incorrecta.");
            }
            
            if ($_POST['new_password'] !== $_POST['confirm_password']) {
                throw new Exception("Las contraseñas nuevas no coinciden.");
            }
            
            if (strlen($_POST['new_password']) < 6) {
                throw new Exception("La nueva contraseña debe tener al menos 6 caracteres.");
            }
            
            // Actualizar contraseña
            $hashedPassword = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            $db->query(
                "UPDATE usuarios SET password = ? WHERE id = ?",
                [$hashedPassword, $_SESSION['user_id']]
            );
            
            $success = "Contraseña actualizada exitosamente.";
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Obtener datos del usuario
$usuario = $db->fetch(
    "SELECT * FROM usuarios WHERE id = ?",
    [$_SESSION['user_id']]
);

// Obtener estadísticas del usuario
$estadisticas = $db->fetch(
    "SELECT 
        COUNT(*) as total_transacciones,
        SUM(CASE WHEN tipo = 'ingreso' THEN cantidad ELSE 0 END) as total_ingresos,
        SUM(CASE WHEN tipo = 'gasto' THEN cantidad ELSE 0 END) as total_gastos
     FROM transacciones 
     WHERE usuario_id = ?",
    [$_SESSION['user_id']]
);

// Obtener cuentas del usuario
$cuentas_usuario = $db->fetchAll(
    "SELECT * FROM cuentas WHERE usuario_id = ? AND activa = 1 ORDER BY nombre",
    [$_SESSION['user_id']]
);

require_once 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i>
                            Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="transacciones.php">
                            <i class="fas fa-exchange-alt me-2"></i>
                            Transacciones
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="categorias.php">
                            <i class="fas fa-tags me-2"></i>
                            Categorías
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="cuentas.php">
                            <i class="fas fa-university me-2"></i>
                            Cuentas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="metas.php">
                            <i class="fas fa-bullseye me-2"></i>
                            Metas de Ahorro
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reportes.php">
                            <i class="fas fa-chart-bar me-2"></i>
                            Reportes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="presupuestos.php">
                            <i class="fas fa-calculator me-2"></i>
                            Presupuestos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="transferencias.php">
                            <i class="fas fa-arrows-alt-h me-2"></i>
                            Transferencias
                        </a>
                    </li>
                    <?php if ($_SESSION['user_role'] === 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="usuarios.php">
                            <i class="fas fa-users me-2"></i>
                            Usuarios
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </nav>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="fas fa-user-circle me-2"></i>Mi Perfil</h1>
            </div>

            <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Estadísticas del usuario -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card stats-card">
                        <div class="card-body text-center">
                            <i class="fas fa-list fa-2x mb-2"></i>
                            <h4 class="mb-1"><?php echo $estadisticas['total_transacciones']; ?></h4>
                            <p class="mb-0">Transacciones</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card income">
                        <div class="card-body text-center">
                            <i class="fas fa-arrow-up fa-2x mb-2"></i>
                            <h4 class="mb-1">$<?php echo number_format($estadisticas['total_ingresos'], 2); ?></h4>
                            <p class="mb-0">Total Ingresos</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card expense">
                        <div class="card-body text-center">
                            <i class="fas fa-arrow-down fa-2x mb-2"></i>
                            <h4 class="mb-1">$<?php echo number_format($estadisticas['total_gastos'], 2); ?></h4>
                            <p class="mb-0">Total Gastos</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card savings">
                        <div class="card-body text-center">
                            <i class="fas fa-university fa-2x mb-2"></i>
                            <h4 class="mb-1"><?php echo count($cuentas_usuario); ?></h4>
                            <p class="mb-0">Cuentas Activas</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Información del perfil -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-user me-2"></i>Información Personal</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="update_profile">
                                
                                <div class="mb-3">
                                    <label for="nombre" class="form-label">Nombre Completo</label>
                                    <input type="text" class="form-control" name="nombre" id="nombre" 
                                           value="<?php echo htmlspecialchars($usuario['nombre']); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Correo Electrónico</label>
                                    <input type="email" class="form-control" name="email" id="email" 
                                           value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Rol</label>
                                    <input type="text" class="form-control" value="<?php echo ucfirst($usuario['role']); ?>" readonly>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Fecha de Registro</label>
                                    <input type="text" class="form-control" 
                                           value="<?php echo date('d/m/Y H:i', strtotime($usuario['created_at'])); ?>" readonly>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Actualizar Información
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Cambiar contraseña -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-lock me-2"></i>Cambiar Contraseña</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="change_password">
                                
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Contraseña Actual</label>
                                    <input type="password" class="form-control" name="current_password" id="current_password" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">Nueva Contraseña</label>
                                    <input type="password" class="form-control" name="new_password" id="new_password" 
                                           minlength="6" required>
                                    <div class="form-text">Mínimo 6 caracteres</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirmar Nueva Contraseña</label>
                                    <input type="password" class="form-control" name="confirm_password" id="confirm_password" 
                                           minlength="6" required>
                                </div>
                                
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-key me-2"></i>Cambiar Contraseña
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cuentas del usuario -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-university me-2"></i>Mis Cuentas</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Nombre</th>
                                            <th>Tipo</th>
                                            <th>Banco</th>
                                            <th>Saldo Inicial</th>
                                            <th>Saldo Actual</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($cuentas_usuario as $cuenta): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($cuenta['nombre']); ?></td>
                                            <td><?php echo htmlspecialchars($cuenta['tipo']); ?></td>
                                            <td><?php echo htmlspecialchars($cuenta['banco'] ?: 'N/A'); ?></td>
                                            <td>$<?php echo number_format($cuenta['saldo_inicial'], 2); ?></td>
                                            <td>$<?php echo number_format($cuenta['saldo_actual'], 2); ?></td>
                                            <td>
                                                <span class="badge bg-success">Activa</span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<script>
// Validar que las contraseñas coincidan
document.getElementById('confirm_password').addEventListener('input', function() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = this.value;
    
    if (newPassword !== confirmPassword) {
        this.setCustomValidity('Las contraseñas no coinciden');
    } else {
        this.setCustomValidity('');
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
