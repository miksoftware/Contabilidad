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
                $descripcion = trim($_POST['descripcion']);
                $cantidad_objetivo = floatval($_POST['cantidad_objetivo']);
                $fecha_objetivo = $_POST['fecha_objetivo'];
                
                if (empty($nombre) || $cantidad_objetivo <= 0) {
                    throw new Exception('Nombre y cantidad objetivo son obligatorios');
                }
                
                $db->query(
                    "INSERT INTO metas_ahorro (nombre, descripcion, cantidad_objetivo, fecha_objetivo) VALUES (?, ?, ?, ?)",
                    [$nombre, $descripcion, $cantidad_objetivo, $fecha_objetivo ?: null]
                );
                
                $mensaje = 'Meta de ahorro creada exitosamente';
                break;
                
            case 'editar':
                $id = intval($_POST['id']);
                $nombre = trim($_POST['nombre']);
                $descripcion = trim($_POST['descripcion']);
                $cantidad_objetivo = floatval($_POST['cantidad_objetivo']);
                $fecha_objetivo = $_POST['fecha_objetivo'];
                
                if (empty($nombre) || $cantidad_objetivo <= 0) {
                    throw new Exception('Nombre y cantidad objetivo son obligatorios');
                }
                
                $db->query(
                    "UPDATE metas_ahorro SET nombre = ?, descripcion = ?, cantidad_objetivo = ?, fecha_objetivo = ? WHERE id = ?",
                    [$nombre, $descripcion, $cantidad_objetivo, $fecha_objetivo ?: null, $id]
                );
                
                $mensaje = 'Meta de ahorro actualizada exitosamente';
                break;
                
            case 'agregar_dinero':
                $id = intval($_POST['id']);
                $cantidad = floatval($_POST['cantidad']);
                $cuenta_id = isset($_POST['cuenta_id']) ? intval($_POST['cuenta_id']) : 0;

                if ($cantidad <= 0) {
                    throw new Exception('La cantidad debe ser mayor a 0');
                }
                if ($cuenta_id <= 0) {
                    throw new Exception('Selecciona la cuenta de donde saldrá el dinero');
                }

                // Validar cuenta pertenece al usuario (o es compartida) y que tenga saldo suficiente
                $cuenta = $db->fetch(
                    "SELECT id, nombre, saldo_actual FROM cuentas WHERE id = ? AND activa = 1 AND (usuario_id = ? OR usuario_id IS NULL)",
                    [$cuenta_id, $_SESSION['user_id']]
                );
                if (!$cuenta) {
                    throw new Exception('Cuenta inválida o no autorizada');
                }
                if ($cuenta['saldo_actual'] < $cantidad) {
                    throw new Exception('Saldo insuficiente en la cuenta seleccionada');
                }

                // Obtener meta para descripción y validar existencia
                $meta = $db->fetch("SELECT * FROM metas_ahorro WHERE id = ?", [$id]);
                if (!$meta) {
                    throw new Exception('Meta no encontrada');
                }

                // Buscar categoría "Otros Gastos"; si no existe, tomar la primera de gasto
                $cat = $db->fetch("SELECT id FROM categorias WHERE tipo = 'gasto' AND nombre = 'Otros Gastos' LIMIT 1");
                if (!$cat) {
                    $cat = $db->fetch("SELECT id FROM categorias WHERE tipo = 'gasto' ORDER BY id LIMIT 1");
                }
                if (!$cat) {
                    throw new Exception('No hay categorías de gasto configuradas');
                }

                // Registrar todo en una transacción
                $db->getConnection()->beginTransaction();
                try {
                    // 1) Insertar transacción de gasto
                    $descripcion = 'Abono a meta de ahorro: ' . $meta['nombre'];
                    $db->query(
                        "INSERT INTO transacciones (usuario_id, categoria_id, cuenta_id, tipo, cantidad, descripcion, fecha, created_at) VALUES (?, ?, ?, 'gasto', ?, ?, CURDATE(), NOW())",
                        [$_SESSION['user_id'], $cat['id'], $cuenta_id, $cantidad, $descripcion]
                    );

                    // 2) Debitar cuenta
                    $db->query(
                        "UPDATE cuentas SET saldo_actual = saldo_actual - ? WHERE id = ?",
                        [$cantidad, $cuenta_id]
                    );

                    // 3) Aumentar ahorro en la meta
                    $db->query(
                        "UPDATE metas_ahorro SET cantidad_actual = cantidad_actual + ? WHERE id = ?",
                        [$cantidad, $id]
                    );

                    // 4) Verificar si se completó la meta
                    $metaAct = $db->fetch("SELECT cantidad_actual, cantidad_objetivo FROM metas_ahorro WHERE id = ?", [$id]);
                    if ($metaAct && $metaAct['cantidad_actual'] >= $metaAct['cantidad_objetivo']) {
                        $db->query("UPDATE metas_ahorro SET completada = 1 WHERE id = ?", [$id]);
                        $mensaje = '¡Felicidades! Meta completada exitosamente';
                    } else {
                        $mensaje = 'Dinero agregado a la meta exitosamente';
                    }

                    $db->getConnection()->commit();
                } catch (Exception $ex) {
                    if ($db->getConnection()->inTransaction()) {
                        $db->getConnection()->rollBack();
                    }
                    throw $ex;
                }
                break;
                
            case 'retirar_dinero':
                $id = intval($_POST['id']);
                $cantidad = floatval($_POST['cantidad']);
                
                if ($cantidad <= 0) {
                    throw new Exception('La cantidad debe ser mayor a 0');
                }
                
                $meta = $db->fetch("SELECT cantidad_actual FROM metas_ahorro WHERE id = ?", [$id]);
                if ($cantidad > $meta['cantidad_actual']) {
                    throw new Exception('No puedes retirar más dinero del que tienes ahorrado');
                }
                
                $db->query(
                    "UPDATE metas_ahorro SET cantidad_actual = cantidad_actual - ?, completada = 0 WHERE id = ?",
                    [$cantidad, $id]
                );
                
                $mensaje = 'Dinero retirado de la meta exitosamente';
                break;
                
            case 'toggle_completada':
                $id = intval($_POST['id']);
                $db->query("UPDATE metas_ahorro SET completada = NOT completada WHERE id = ?", [$id]);
                $mensaje = 'Estado de meta actualizado';
                break;
                
            case 'eliminar':
                $id = intval($_POST['id']);
                $db->query("DELETE FROM metas_ahorro WHERE id = ?", [$id]);
                $mensaje = 'Meta eliminada exitosamente';
                break;
        }
    } catch (Exception $e) {
        $mensaje = $e->getMessage();
        $tipoMensaje = 'danger';
    }
}

// Obtener todas las metas
$metas = $db->fetchAll(
    "SELECT *, 
     ROUND((cantidad_actual / cantidad_objetivo) * 100, 2) as progreso,
     DATEDIFF(fecha_objetivo, CURDATE()) as dias_restantes
     FROM metas_ahorro 
     ORDER BY completada ASC, fecha_objetivo ASC"
);

$metasActivas = array_filter($metas, fn($m) => !$m['completada']);
$metasCompletadas = array_filter($metas, fn($m) => $m['completada']);

// Cuentas del usuario (y compartidas) para fondear metas
$cuentas_usuario = $db->fetchAll(
    "SELECT id, nombre, saldo_actual FROM cuentas WHERE activa = 1 AND (usuario_id = ? OR usuario_id IS NULL) ORDER BY nombre",
    [$_SESSION['user_id']]
);

$titulo = 'Metas de Ahorro - Contabilidad Familiar';
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
                        <a class="nav-link active" href="metas.php">
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
                    <i class="fas fa-bullseye me-2"></i>Metas de Ahorro
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#nuevaMetaModal">
                        <i class="fas fa-plus me-1"></i>Nueva Meta
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
                $totalMetas = count($metas);
                $totalAhorrado = array_sum(array_column($metas, 'cantidad_actual'));
                $totalObjetivo = array_sum(array_column($metas, 'cantidad_objetivo'));
                $progresoGeneral = $totalObjetivo > 0 ? round(($totalAhorrado / $totalObjetivo) * 100, 2) : 0;
                ?>
                <div class="col-md-3">
                    <div class="card stats-card">
                        <div class="card-body text-center">
                            <i class="fas fa-bullseye fa-2x mb-2"></i>
                            <h3><?php echo $totalMetas; ?></h3>
                            <p class="mb-0">Total Metas</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card income">
                        <div class="card-body text-center">
                            <i class="fas fa-check-circle fa-2x mb-2"></i>
                            <h3><?php echo count($metasCompletadas); ?></h3>
                            <p class="mb-0">Completadas</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card savings">
                        <div class="card-body text-center">
                            <i class="fas fa-piggy-bank fa-2x mb-2"></i>
                            <h3>$<?php echo number_format($totalAhorrado, 2); ?></h3>
                            <p class="mb-0">Total Ahorrado</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <div class="card-body text-center">
                            <i class="fas fa-percentage fa-2x mb-2"></i>
                            <h3><?php echo $progresoGeneral; ?>%</h3>
                            <p class="mb-0">Progreso General</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs para Metas Activas y Completadas -->
            <ul class="nav nav-tabs" id="metaTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="activas-tab" data-bs-toggle="tab" data-bs-target="#activas" type="button" role="tab">
                        <i class="fas fa-play text-primary me-2"></i>Activas (<?php echo count($metasActivas); ?>)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="completadas-tab" data-bs-toggle="tab" data-bs-target="#completadas" type="button" role="tab">
                        <i class="fas fa-check text-success me-2"></i>Completadas (<?php echo count($metasCompletadas); ?>)
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="metaTabContent">
                <!-- Tab Metas Activas -->
                <div class="tab-pane fade show active" id="activas" role="tabpanel">
                    <?php if (!empty($metasActivas)): ?>
                        <div class="row">
                            <?php foreach ($metasActivas as $meta): ?>
                                <div class="col-lg-6 mb-4">
                                    <div class="card h-100">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0">
                                                <i class="fas fa-bullseye me-2"></i>
                                                <?php echo htmlspecialchars($meta['nombre']); ?>
                                            </h6>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-success" onclick="gestionarDinero(<?php echo $meta['id']; ?>, 'agregar')" title="Agregar dinero">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                                <button class="btn btn-outline-warning" onclick="gestionarDinero(<?php echo $meta['id']; ?>, 'retirar')" title="Retirar dinero">
                                                    <i class="fas fa-minus"></i>
                                                </button>
                                                <button class="btn btn-outline-primary" onclick="editarMeta(<?php echo htmlspecialchars(json_encode($meta)); ?>)" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-outline-danger" onclick="eliminarMeta(<?php echo $meta['id']; ?>)" title="Eliminar">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <?php if ($meta['descripcion']): ?>
                                                <p class="text-muted small"><?php echo htmlspecialchars($meta['descripcion']); ?></p>
                                            <?php endif; ?>
                                            
                                            <div class="mb-3">
                                                <div class="d-flex justify-content-between mb-1">
                                                    <span>Progreso</span>
                                                    <span><?php echo $meta['progreso']; ?>%</span>
                                                </div>
                                                <div class="progress" style="height: 12px;">
                                                    <div class="progress-bar bg-<?php echo $meta['progreso'] >= 100 ? 'success' : ($meta['progreso'] >= 50 ? 'warning' : 'info'); ?>" 
                                                         style="width: <?php echo min($meta['progreso'], 100); ?>%"></div>
                                                </div>
                                            </div>
                                            
                                            <div class="row text-center">
                                                <div class="col-6">
                                                    <small class="text-muted">Ahorrado</small>
                                                    <div class="fw-bold text-success">$<?php echo number_format($meta['cantidad_actual'], 2); ?></div>
                                                </div>
                                                <div class="col-6">
                                                    <small class="text-muted">Objetivo</small>
                                                    <div class="fw-bold">$<?php echo number_format($meta['cantidad_objetivo'], 2); ?></div>
                                                </div>
                                            </div>
                                            
                                            <?php if ($meta['fecha_objetivo']): ?>
                                                <div class="mt-2 text-center">
                                                    <small class="text-muted">
                                                        <?php if ($meta['dias_restantes'] > 0): ?>
                                                            <i class="fas fa-calendar me-1"></i>
                                                            <?php echo $meta['dias_restantes']; ?> días restantes
                                                        <?php elseif ($meta['dias_restantes'] == 0): ?>
                                                            <i class="fas fa-exclamation-triangle text-warning me-1"></i>
                                                            ¡Vence hoy!
                                                        <?php else: ?>
                                                            <i class="fas fa-times-circle text-danger me-1"></i>
                                                            Vencida hace <?php echo abs($meta['dias_restantes']); ?> días
                                                        <?php endif; ?>
                                                    </small>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="card">
                            <div class="card-body text-center py-5">
                                <i class="fas fa-bullseye fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No tienes metas de ahorro activas</h5>
                                <p class="text-muted">¡Crea tu primera meta y comienza a ahorrar!</p>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#nuevaMetaModal">
                                    <i class="fas fa-plus me-2"></i>Crear Primera Meta
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Tab Metas Completadas -->
                <div class="tab-pane fade" id="completadas" role="tabpanel">
                    <?php if (!empty($metasCompletadas)): ?>
                        <div class="row">
                            <?php foreach ($metasCompletadas as $meta): ?>
                                <div class="col-lg-6 mb-4">
                                    <div class="card h-100 border-success">
                                        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0">
                                                <i class="fas fa-check-circle me-2"></i>
                                                <?php echo htmlspecialchars($meta['nombre']); ?>
                                            </h6>
                                            <span class="badge bg-light text-success">COMPLETADA</span>
                                        </div>
                                        <div class="card-body">
                                            <?php if ($meta['descripcion']): ?>
                                                <p class="text-muted small"><?php echo htmlspecialchars($meta['descripcion']); ?></p>
                                            <?php endif; ?>
                                            
                                            <div class="row text-center">
                                                <div class="col-6">
                                                    <small class="text-muted">Ahorrado</small>
                                                    <div class="fw-bold text-success">$<?php echo number_format($meta['cantidad_actual'], 2); ?></div>
                                                </div>
                                                <div class="col-6">
                                                    <small class="text-muted">Objetivo</small>
                                                    <div class="fw-bold">$<?php echo number_format($meta['cantidad_objetivo'], 2); ?></div>
                                                </div>
                                            </div>
                                            
                                            <div class="mt-3 text-center">
                                                <button class="btn btn-sm btn-outline-primary" onclick="reactivarMeta(<?php echo $meta['id']; ?>)">
                                                    <i class="fas fa-redo me-1"></i>Reactivar
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" onclick="eliminarMeta(<?php echo $meta['id']; ?>)">
                                                    <i class="fas fa-trash me-1"></i>Eliminar
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="card">
                            <div class="card-body text-center py-5">
                                <i class="fas fa-medal fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No tienes metas completadas aún</h5>
                                <p class="text-muted">¡Sigue ahorrando para lograr tus primeras metas!</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal Nueva/Editar Meta -->
<div class="modal fade" id="nuevaMetaModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">
                    <i class="fas fa-plus me-2"></i>Nueva Meta de Ahorro
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="metaForm" method="POST">
                <input type="hidden" name="accion" id="accion" value="crear">
                <input type="hidden" name="id" id="metaId">
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre de la Meta</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required 
                               placeholder="Ej: Vacaciones 2025, Auto nuevo, etc.">
                    </div>
                    
                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción (Opcional)</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="2" 
                                  placeholder="Describe tu meta de ahorro..."></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <label for="cantidad_objetivo" class="form-label">Cantidad Objetivo</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="cantidad_objetivo" name="cantidad_objetivo" 
                                       step="0.01" min="0.01" required placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="fecha_objetivo" class="form-label">Fecha Objetivo (Opcional)</label>
                            <input type="date" class="form-control" id="fecha_objetivo" name="fecha_objetivo">
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">Crear Meta</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Gestionar Dinero -->
<div class="modal fade" id="gestionarDineroModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="dineroModalTitle">Gestionar Dinero</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="dineroForm" method="POST">
                <input type="hidden" name="accion" id="dineroAccion">
                <input type="hidden" name="id" id="dineroMetaId">
                
                <div class="modal-body">
                    <div class="mb-3" id="cuentaGroup">
                        <label for="cuenta_id" class="form-label">Cuenta de origen</label>
                        <select class="form-select" id="cuenta_id" name="cuenta_id">
                            <option value="">Seleccionar cuenta</option>
                            <?php foreach ($cuentas_usuario as $c): ?>
                                <option value="<?php echo $c['id']; ?>">
                                    <?php echo htmlspecialchars($c['nombre']); ?> (Saldo: $<?php echo number_format($c['saldo_actual'], 2); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($cuentas_usuario)): ?>
                            <div class="form-text text-danger">No tienes cuentas activas para realizar el abono.</div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label for="cantidad" class="form-label">Cantidad</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" id="dinero_cantidad" name="cantidad" 
                                   step="0.01" min="0.01" required placeholder="0.00">
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="dineroSubmitBtn">Confirmar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Formularios ocultos -->
<form id="deleteForm" method="POST" style="display: none;">
    <input type="hidden" name="accion" value="eliminar">
    <input type="hidden" name="id" id="deleteId">
</form>

<form id="reactivarForm" method="POST" style="display: none;">
    <input type="hidden" name="accion" value="toggle_completada">
    <input type="hidden" name="id" id="reactivarId">
</form>

<script>
function editarMeta(meta) {
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Editar Meta';
    document.getElementById('accion').value = 'editar';
    document.getElementById('metaId').value = meta.id;
    document.getElementById('nombre').value = meta.nombre;
    document.getElementById('descripcion').value = meta.descripcion || '';
    document.getElementById('cantidad_objetivo').value = meta.cantidad_objetivo;
    document.getElementById('fecha_objetivo').value = meta.fecha_objetivo || '';
    document.getElementById('submitBtn').textContent = 'Actualizar Meta';
    
    new bootstrap.Modal(document.getElementById('nuevaMetaModal')).show();
}

function gestionarDinero(id, accion) {
    document.getElementById('dineroMetaId').value = id;
    document.getElementById('dineroAccion').value = accion === 'agregar' ? 'agregar_dinero' : 'retirar_dinero';
    document.getElementById('dineroModalTitle').textContent = accion === 'agregar' ? 'Agregar Dinero' : 'Retirar Dinero';
    document.getElementById('dineroSubmitBtn').textContent = accion === 'agregar' ? 'Agregar' : 'Retirar';
    document.getElementById('dineroSubmitBtn').className = 'btn btn-' + (accion === 'agregar' ? 'success' : 'warning');
    // Mostrar selección de cuenta solo para agregar (abono)
    const cuentaGroup = document.getElementById('cuentaGroup');
    const cuentaSelect = document.getElementById('cuenta_id');
    if (accion === 'agregar') {
        cuentaGroup.style.display = '';
        cuentaSelect.required = true;
    } else {
        cuentaGroup.style.display = 'none';
        cuentaSelect.required = false;
        cuentaSelect.value = '';
    }
    
    new bootstrap.Modal(document.getElementById('gestionarDineroModal')).show();
}

function eliminarMeta(id) {
    if (confirm('¿Estás seguro de que deseas eliminar esta meta? Esta acción no se puede deshacer.')) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}

function reactivarMeta(id) {
    if (confirm('¿Deseas reactivar esta meta?')) {
        document.getElementById('reactivarId').value = id;
        document.getElementById('reactivarForm').submit();
    }
}

// Resetear modales
document.getElementById('nuevaMetaModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus me-2"></i>Nueva Meta de Ahorro';
    document.getElementById('accion').value = 'crear';
    document.getElementById('metaForm').reset();
    document.getElementById('submitBtn').textContent = 'Crear Meta';
});

document.getElementById('gestionarDineroModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('dineroForm').reset();
});
</script>

<?php include 'includes/footer.php'; ?>
