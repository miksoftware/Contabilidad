<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
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
                $tipo = $_POST['tipo'];
                $saldo_inicial = floatval($_POST['saldo_inicial']);
                $color = $_POST['color'];
                
                if (empty($nombre) || empty($tipo)) {
                    throw new Exception('Nombre y tipo son obligatorios');
                }
                
                $db->query(
                    "INSERT INTO cuentas (nombre, tipo, saldo_inicial, saldo_actual, color) VALUES (?, ?, ?, ?, ?)",
                    [$nombre, $tipo, $saldo_inicial, $saldo_inicial, $color]
                );
                
                $mensaje = 'Cuenta creada exitosamente';
                break;
                
            case 'editar':
                $id = intval($_POST['id']);
                $nombre = trim($_POST['nombre']);
                $tipo = $_POST['tipo'];
                $color = $_POST['color'];
                
                if (empty($nombre) || empty($tipo)) {
                    throw new Exception('Nombre y tipo son obligatorios');
                }
                
                $db->query(
                    "UPDATE cuentas SET nombre = ?, tipo = ?, color = ? WHERE id = ?",
                    [$nombre, $tipo, $color, $id]
                );
                
                $mensaje = 'Cuenta actualizada exitosamente';
                break;
                
            case 'eliminar':
                $id = intval($_POST['id']);
                
                // Verificar si tiene transacciones
                $transacciones = $db->fetch(
                    "SELECT COUNT(*) as total FROM transacciones WHERE cuenta_id = ?",
                    [$id]
                );
                
                if ($transacciones['total'] > 0) {
                    throw new Exception('No se puede eliminar una cuenta con transacciones. Desactívala en su lugar.');
                }
                
                $db->query("DELETE FROM cuentas WHERE id = ?", [$id]);
                $mensaje = 'Cuenta eliminada exitosamente';
                break;
                
            case 'toggle_activo':
                $id = intval($_POST['id']);
                $db->query("UPDATE cuentas SET activa = NOT activa WHERE id = ?", [$id]);
                $mensaje = 'Estado de cuenta actualizado';
                break;
        }
    } catch (Exception $e) {
        $mensaje = $e->getMessage();
        $tipoMensaje = 'danger';
    }
}

// Obtener todas las cuentas
$cuentas = $db->fetchAll(
    "SELECT c.*, 
     (SELECT COUNT(*) FROM transacciones WHERE cuenta_id = c.id) as total_transacciones
     FROM cuentas c 
     ORDER BY c.activa DESC, c.nombre"
);

$titulo = 'Gestión de Cuentas - Contabilidad Familiar';
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
                        <a class="nav-link active" href="cuentas.php">
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
                        <a class="nav-link" href="transferencias.php">
                            <i class="fas fa-arrows-alt-h me-2"></i>Transferencias
                        </a>
                    </li>
                    <?php if ($_SESSION['user_role'] === 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="usuarios.php">
                            <i class="fas fa-users me-2"></i>Usuarios
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </nav>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="fas fa-university me-2"></i>Gestión de Cuentas
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#nuevaCuentaModal">
                        <i class="fas fa-plus me-1"></i>Nueva Cuenta
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

            <!-- Tarjetas de resumen -->
            <div class="row mb-4">
                <?php
                $totalCuentas = count($cuentas);
                $cuentasActivas = count(array_filter($cuentas, fn($c) => $c['activa']));
                $saldoTotal = array_sum(array_column(array_filter($cuentas, fn($c) => $c['activa']), 'saldo_actual'));
                ?>
                <div class="col-md-4">
                    <div class="card stats-card">
                        <div class="card-body text-center">
                            <i class="fas fa-university fa-2x mb-2"></i>
                            <h3><?php echo $totalCuentas; ?></h3>
                            <p class="mb-0">Total Cuentas</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stats-card income">
                        <div class="card-body text-center">
                            <i class="fas fa-check-circle fa-2x mb-2"></i>
                            <h3><?php echo $cuentasActivas; ?></h3>
                            <p class="mb-0">Cuentas Activas</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stats-card savings">
                        <div class="card-body text-center">
                            <i class="fas fa-wallet fa-2x mb-2"></i>
                            <h3>$<?php echo number_format($saldoTotal, 2); ?></h3>
                            <p class="mb-0">Saldo Total</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de cuentas -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>Listado de Cuentas
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($cuentas)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Tipo</th>
                                        <th>Saldo Inicial</th>
                                        <th>Saldo Actual</th>
                                        <th>Transacciones</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cuentas as $cuenta): ?>
                                        <tr class="<?php echo !$cuenta['activa'] ? 'table-secondary' : ''; ?>">
                                            <td>
                                                <span class="badge" style="background-color: <?php echo $cuenta['color']; ?>; color: white;">
                                                    <i class="fas fa-circle me-1"></i>
                                                </span>
                                                <?php echo htmlspecialchars($cuenta['nombre']); ?>
                                            </td>
                                            <td>
                                                <?php
                                                $iconos = [
                                                    'efectivo' => 'fas fa-money-bill',
                                                    'banco' => 'fas fa-university',
                                                    'tarjeta' => 'fas fa-credit-card',
                                                    'ahorro' => 'fas fa-piggy-bank'
                                                ];
                                                ?>
                                                <i class="<?php echo $iconos[$cuenta['tipo']] ?? 'fas fa-wallet'; ?> me-1"></i>
                                                <?php echo ucfirst($cuenta['tipo']); ?>
                                            </td>
                                            <td>$<?php echo number_format($cuenta['saldo_inicial'], 2); ?></td>
                                            <td class="<?php echo $cuenta['saldo_actual'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                                                $<?php echo number_format($cuenta['saldo_actual'], 2); ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?php echo $cuenta['total_transacciones']; ?></span>
                                            </td>
                                            <td>
                                                <?php if ($cuenta['activa']): ?>
                                                    <span class="badge bg-success">Activa</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Inactiva</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                                            onclick="editarCuenta(<?php echo htmlspecialchars(json_encode($cuenta)); ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-<?php echo $cuenta['activa'] ? 'warning' : 'success'; ?>" 
                                                            onclick="toggleActivo(<?php echo $cuenta['id']; ?>)">
                                                        <i class="fas fa-<?php echo $cuenta['activa'] ? 'pause' : 'play'; ?>"></i>
                                                    </button>
                                                    <?php if ($cuenta['total_transacciones'] == 0): ?>
                                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                onclick="eliminarCuenta(<?php echo $cuenta['id']; ?>)">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-university fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No hay cuentas registradas</p>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#nuevaCuentaModal">
                                Crear Primera Cuenta
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal Nueva/Editar Cuenta -->
<div class="modal fade" id="nuevaCuentaModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">
                    <i class="fas fa-plus me-2"></i>Nueva Cuenta
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="cuentaForm" method="POST">
                <input type="hidden" name="accion" id="accion" value="crear">
                <input type="hidden" name="id" id="cuentaId">
                
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <label for="nombre" class="form-label">Nombre de la Cuenta</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required 
                                   placeholder="Ej: Cuenta Corriente Bancomer">
                        </div>
                        <div class="col-md-4">
                            <label for="color" class="form-label">Color</label>
                            <input type="color" class="form-control form-control-color" id="color" name="color" value="#007bff">
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label for="tipo" class="form-label">Tipo de Cuenta</label>
                            <select class="form-select" id="tipo" name="tipo" required>
                                <option value="">Seleccionar tipo</option>
                                <option value="efectivo">💵 Efectivo</option>
                                <option value="banco">🏦 Cuenta Bancaria</option>
                                <option value="tarjeta">💳 Tarjeta de Crédito</option>
                                <option value="ahorro">🐷 Cuenta de Ahorro</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="saldo_inicial" class="form-label">Saldo Inicial</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="saldo_inicial" name="saldo_inicial" 
                                       step="0.01" value="0.00" placeholder="0.00">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">Crear Cuenta</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Formularios ocultos para acciones -->
<form id="toggleForm" method="POST" style="display: none;">
    <input type="hidden" name="accion" value="toggle_activo">
    <input type="hidden" name="id" id="toggleId">
</form>

<form id="deleteForm" method="POST" style="display: none;">
    <input type="hidden" name="accion" value="eliminar">
    <input type="hidden" name="id" id="deleteId">
</form>

<script>
function editarCuenta(cuenta) {
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Editar Cuenta';
    document.getElementById('accion').value = 'editar';
    document.getElementById('cuentaId').value = cuenta.id;
    document.getElementById('nombre').value = cuenta.nombre;
    document.getElementById('tipo').value = cuenta.tipo;
    document.getElementById('color').value = cuenta.color;
    document.getElementById('saldo_inicial').style.display = 'none';
    document.getElementById('saldo_inicial').previousElementSibling.style.display = 'none';
    document.getElementById('submitBtn').textContent = 'Actualizar Cuenta';
    
    new bootstrap.Modal(document.getElementById('nuevaCuentaModal')).show();
}

function toggleActivo(id) {
    if (confirm('¿Estás seguro de que deseas cambiar el estado de esta cuenta?')) {
        document.getElementById('toggleId').value = id;
        document.getElementById('toggleForm').submit();
    }
}

function eliminarCuenta(id) {
    if (confirm('¿Estás seguro de que deseas eliminar esta cuenta? Esta acción no se puede deshacer.')) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}

// Resetear modal al cerrarse
document.getElementById('nuevaCuentaModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus me-2"></i>Nueva Cuenta';
    document.getElementById('accion').value = 'crear';
    document.getElementById('cuentaForm').reset();
    document.getElementById('saldo_inicial').style.display = 'block';
    document.getElementById('saldo_inicial').previousElementSibling.style.display = 'block';
    document.getElementById('submitBtn').textContent = 'Crear Cuenta';
});
</script>

<?php include 'includes/footer.php'; ?>
