<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'config/database.php';

// Procesar transferencia
$mensaje = '';
$tipoMensaje = 'success';

if ($_POST && isset($_POST['realizar_transferencia'])) {
    try {
    $usuario_origen = intval($_POST['usuario_origen']);
        $cuenta_origen = intval($_POST['cuenta_origen']);
        $usuario_destino = intval($_POST['usuario_destino']);
        $cuenta_destino = intval($_POST['cuenta_destino']);
        $cantidad = floatval($_POST['cantidad']);
        $descripcion = trim($_POST['descripcion']);
        $categoria_gasto = intval($_POST['categoria_gasto']);
        $categoria_ingreso = intval($_POST['categoria_ingreso']);
        
        // Validar que el usuario de origen sea el mismo que el de la sesión
        if ($usuario_origen !== intval($_SESSION['user_id'])) {
            throw new Exception('No autorizado: el usuario de origen debe ser el que inició sesión');
        }

        if ($cantidad <= 0) {
            throw new Exception('La cantidad debe ser mayor a 0');
        }
        
        if ($usuario_origen === $usuario_destino && $cuenta_origen === $cuenta_destino) {
            throw new Exception('No puedes transferir a la misma cuenta');
        }
        
        if (empty($descripcion)) {
            throw new Exception('La descripción es obligatoria');
        }
        
        // Verificar saldo suficiente en cuenta origen
        $cuentaOrigen = $db->fetch(
            "SELECT saldo_actual FROM cuentas WHERE id = ? AND usuario_id = ?",
            [$cuenta_origen, $usuario_origen]
        );
        
        if (!$cuentaOrigen || $cuentaOrigen['saldo_actual'] < $cantidad) {
            throw new Exception('Saldo insuficiente en la cuenta de origen');
        }
        
        // Iniciar transacción
        $db->getConnection()->beginTransaction();
        
        $fecha = date('Y-m-d');
        
        // Registrar gasto en cuenta origen
        $db->query(
            "INSERT INTO transacciones (usuario_id, categoria_id, cuenta_id, tipo, cantidad, descripcion, fecha) 
             VALUES (?, ?, ?, 'gasto', ?, ?, ?)",
            [$usuario_origen, $categoria_gasto, $cuenta_origen, $cantidad, $descripcion, $fecha]
        );
        
        // Registrar ingreso en cuenta destino
        $db->query(
            "INSERT INTO transacciones (usuario_id, categoria_id, cuenta_id, tipo, cantidad, descripcion, fecha) 
             VALUES (?, ?, ?, 'ingreso', ?, ?, ?)",
            [$usuario_destino, $categoria_ingreso, $cuenta_destino, $cantidad, $descripcion, $fecha]
        );
        
        // Actualizar saldos
        $db->query(
            "UPDATE cuentas SET saldo_actual = saldo_actual - ? WHERE id = ? AND usuario_id = ?",
            [$cantidad, $cuenta_origen, $usuario_origen]
        );
        
        $db->query(
            "UPDATE cuentas SET saldo_actual = saldo_actual + ? WHERE id = ?",
            [$cantidad, $cuenta_destino]
        );
        
        // Confirmar transacción
        $db->getConnection()->commit();
        
        $mensaje = 'Transferencia realizada exitosamente';
        
    } catch (Exception $e) {
        if ($db->getConnection()->inTransaction()) {
            $db->getConnection()->rollBack();
        }
        $mensaje = $e->getMessage();
        $tipoMensaje = 'danger';
    }
}

// Obtener usuarios activos
$usuarios = $db->fetchAll(
    "SELECT id, nombre, email FROM usuarios WHERE activo = 1 ORDER BY nombre"
);

// Obtener categorías
$categoriasGasto = $db->fetchAll(
    "SELECT id, nombre, color, icono FROM categorias WHERE tipo = 'gasto' AND activa = 1 ORDER BY nombre"
);

$categoriasIngreso = $db->fetchAll(
    "SELECT id, nombre, color, icono FROM categorias WHERE tipo = 'ingreso' AND activa = 1 ORDER BY nombre"
);

// Obtener transferencias recientes
$transferenciasRecientes = $db->fetchAll(
    "SELECT 
        t1.fecha, t1.cantidad, t1.descripcion, t1.created_at,
        u1.nombre as usuario_origen, c1.nombre as cuenta_origen,
        u2.nombre as usuario_destino, c2.nombre as cuenta_destino,
        cat1.nombre as categoria_gasto, cat1.color as color_gasto,
        cat2.nombre as categoria_ingreso, cat2.color as color_ingreso
     FROM transacciones t1
     JOIN transacciones t2 ON (
         t1.fecha = t2.fecha 
         AND t1.cantidad = t2.cantidad 
         AND t1.descripcion = t2.descripcion
         AND ABS(TIMESTAMPDIFF(SECOND, t1.created_at, t2.created_at)) <= 5
         AND t1.tipo = 'gasto' 
         AND t2.tipo = 'ingreso'
     )
     JOIN usuarios u1 ON t1.usuario_id = u1.id
     JOIN usuarios u2 ON t2.usuario_id = u2.id
     JOIN cuentas c1 ON t1.cuenta_id = c1.id
     JOIN cuentas c2 ON t2.cuenta_id = c2.id
     JOIN categorias cat1 ON t1.categoria_id = cat1.id
     JOIN categorias cat2 ON t2.categoria_id = cat2.id
     ORDER BY t1.created_at DESC
     LIMIT 20"
);

$titulo = 'Transferencias entre Usuarios - Contabilidad Familiar';
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
                        <a class="nav-link active" href="transferencias.php">
                            <i class="fas fa-arrows-alt-h me-2"></i>Transferencias
                        </a>
                    </li>
                    <?php if ($_SESSION['user_role'] === 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="usuarios.php">
                            <i class="fas fa-users me-2"></i>Usuarios
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="diagnostico.php">
                            <i class="fas fa-stethoscope me-2"></i>Diagnóstico
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
                    <i class="fas fa-exchange-alt me-2"></i>Transferencias entre Usuarios
                </h1>
            </div>

            <!-- Mensajes -->
            <?php if ($mensaje): ?>
                <div class="alert alert-<?php echo $tipoMensaje; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($mensaje); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <!-- Formulario de Transferencia -->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-paper-plane me-2"></i>Nueva Transferencia
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" id="transferenciaForm">
                                <input type="hidden" name="realizar_transferencia" value="1">
                                
                                <!-- Usuario y Cuenta Origen -->
                                <div class="mb-4">
                                    <h6 class="text-danger">
                                        <i class="fas fa-minus-circle me-2"></i>Origen (Quien envía)
                                    </h6>
                                    <div class="row">
                                        <?php 
                                        // Solo el usuario autenticado como origen
                                        $usuarioActual = $db->fetch("SELECT id, nombre FROM usuarios WHERE id = ?", [$_SESSION['user_id']]);
                                        ?>
                                        <div class="col-md-6">
                                            <label class="form-label">Usuario</label>
                                            <select name="usuario_origen" id="usuario_origen" class="form-select" required disabled>
                                                <option value="<?php echo $usuarioActual['id']; ?>" selected>
                                                    <?php echo htmlspecialchars($usuarioActual['nombre']); ?>
                                                </option>
                                            </select>
                                            <input type="hidden" name="usuario_origen" value="<?php echo $usuarioActual['id']; ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Cuenta</label>
                                            <select name="cuenta_origen" id="cuenta_origen" class="form-select" required>
                                                <option value="">Primero selecciona usuario</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Usuario y Cuenta Destino -->
                                <div class="mb-4">
                                    <h6 class="text-success">
                                        <i class="fas fa-plus-circle me-2"></i>Destino (Quien recibe)
                                    </h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label class="form-label">Usuario</label>
                                            <select name="usuario_destino" id="usuario_destino" class="form-select" required onchange="cargarCuentas('destino')">
                                                <option value="">Seleccionar usuario</option>
                                                <?php foreach ($usuarios as $usuario): ?>
                                                    <option value="<?php echo $usuario['id']; ?>">
                                                        <?php echo htmlspecialchars($usuario['nombre']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Cuenta</label>
                                            <select name="cuenta_destino" id="cuenta_destino" class="form-select" required>
                                                <option value="">Primero selecciona usuario</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Cantidad y Descripción -->
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Cantidad</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" name="cantidad" class="form-control" step="0.01" min="0.01" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Descripción</label>
                                        <input type="text" name="descripcion" class="form-control" required 
                                               placeholder="Ej: Pago de comida, Préstamo, etc.">
                                    </div>
                                </div>

                                <!-- Categorías -->
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label">Categoría del Gasto</label>
                                        <select name="categoria_gasto" class="form-select" required>
                                            <option value="">Seleccionar categoría</option>
                                            <?php foreach ($categoriasGasto as $categoria): ?>
                                                <option value="<?php echo $categoria['id']; ?>">
                                                    <?php echo htmlspecialchars($categoria['nombre']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Categoría del Ingreso</label>
                                        <select name="categoria_ingreso" class="form-select" required>
                                            <option value="">Seleccionar categoría</option>
                                            <?php foreach ($categoriasIngreso as $categoria): ?>
                                                <option value="<?php echo $categoria['id']; ?>">
                                                    <?php echo htmlspecialchars($categoria['nombre']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-paper-plane me-2"></i>Realizar Transferencia
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Transferencias Recientes -->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-history me-2"></i>Transferencias Recientes
                            </h5>
                        </div>
                        <div class="card-body" style="max-height: 600px; overflow-y: auto;">
                            <?php if (!empty($transferenciasRecientes)): ?>
                                <?php foreach ($transferenciasRecientes as $transferencia): ?>
                                    <div class="border rounded p-3 mb-3">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <span class="fw-bold text-primary">$<?php echo number_format($transferencia['cantidad'], 2); ?></span>
                                            <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($transferencia['created_at'])); ?></small>
                                        </div>
                                        
                                        <div class="mb-2">
                                            <strong><?php echo htmlspecialchars($transferencia['descripcion']); ?></strong>
                                        </div>
                                        
                                        <!-- Origen -->
                                        <div class="d-flex align-items-center mb-1">
                                            <i class="fas fa-minus-circle text-danger me-2"></i>
                                            <span class="fw-bold"><?php echo htmlspecialchars($transferencia['usuario_origen']); ?></span>
                                            <i class="fas fa-arrow-right mx-2 text-muted"></i>
                                            <span><?php echo htmlspecialchars($transferencia['cuenta_origen']); ?></span>
                                        </div>
                                        
                                        <!-- Destino -->
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-plus-circle text-success me-2"></i>
                                            <span class="fw-bold"><?php echo htmlspecialchars($transferencia['usuario_destino']); ?></span>
                                            <i class="fas fa-arrow-left mx-2 text-muted"></i>
                                            <span><?php echo htmlspecialchars($transferencia['cuenta_destino']); ?></span>
                                        </div>
                                        
                                        <!-- Categorías -->
                                        <div class="d-flex justify-content-between">
                                            <span class="badge" style="background-color: <?php echo $transferencia['color_gasto']; ?>; color: white;">
                                                <?php echo htmlspecialchars($transferencia['categoria_gasto']); ?>
                                            </span>
                                            <span class="badge" style="background-color: <?php echo $transferencia['color_ingreso']; ?>; color: white;">
                                                <?php echo htmlspecialchars($transferencia['categoria_ingreso']); ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-exchange-alt fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No hay transferencias registradas</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
async function cargarCuentas(tipo) {
    const usuarioSelect = document.getElementById(`usuario_${tipo}`);
    const cuentaSelect = document.getElementById(`cuenta_${tipo}`);
    const usuarioId = usuarioSelect.value;
    
    // Limpiar cuentas
    cuentaSelect.innerHTML = '<option value="">Cargando...</option>';
    
    if (!usuarioId) {
        cuentaSelect.innerHTML = '<option value="">Primero selecciona usuario</option>';
        return;
    }
    
    try {
        const response = await fetch(`obtener_cuentas_usuario.php?usuario_id=${usuarioId}`);
        const data = await response.json();

        cuentaSelect.innerHTML = '<option value="">Seleccionar cuenta</option>';

        if (!response.ok) {
            const msg = (data && data.error) ? data.error : 'Error al cargar cuentas';
            cuentaSelect.innerHTML = `<option value="">${msg}</option>`;
            return;
        }

        const cuentas = Array.isArray(data) ? data : [];
        if (cuentas.length === 0) {
            cuentaSelect.innerHTML = '<option value="">Sin cuentas disponibles</option>';
            return;
        }

        cuentas.forEach(cuenta => {
            const option = document.createElement('option');
            option.value = cuenta.id;
            const saldo = Number.parseFloat(cuenta.saldo_actual || 0).toLocaleString();
            option.textContent = `${cuenta.nombre} (Saldo: $${saldo})`;
            cuentaSelect.appendChild(option);
        });

    } catch (error) {
        console.error('Error al cargar cuentas:', error);
        cuentaSelect.innerHTML = '<option value="">Error al cargar cuentas</option>';
    }
}

// Validación del formulario
document.getElementById('transferenciaForm').addEventListener('submit', function(e) {
    const usuarioOrigen = document.getElementById('usuario_origen').value;
    const usuarioDestino = document.getElementById('usuario_destino').value;
    const cuentaOrigen = document.getElementById('cuenta_origen').value;
    const cuentaDestino = document.getElementById('cuenta_destino').value;
    
    if (usuarioOrigen === usuarioDestino && cuentaOrigen === cuentaDestino) {
        e.preventDefault();
        alert('No puedes transferir a la misma cuenta.');
        return false;
    }
});

// Cargar automáticamente las cuentas del usuario de origen al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    const usuarioOrigenSelect = document.getElementById('usuario_origen');
    if (usuarioOrigenSelect && usuarioOrigenSelect.value) {
        cargarCuentas('origen');
    }
});
</script>

<?php include 'includes/footer.php'; ?>
