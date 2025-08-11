<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'config/database.php';

$titulo = 'Presupuestos';

// Procesar acciones CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    // Evitar comparaciones de strings en SQL para no mezclar collations
    $isAdminFlag = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin' ? 1 : 0;
    
    try {
        switch ($action) {
            case 'create':
                $db->query(
                    "INSERT INTO presupuestos_items (nombre, categoria_id, tipo, monto, fecha_vencimiento, es_recurrente, frecuencia, usuario_id, activo, created_at) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())",
                    [
                        $_POST['nombre'],
                        $_POST['categoria_id'],
                        $_POST['tipo'],
                        $_POST['monto'],
                        $_POST['fecha_vencimiento'] ?: null,
                        isset($_POST['es_recurrente']) ? 1 : 0,
                        $_POST['frecuencia'] ?? 'mensual',
                        $_SESSION['user_id']
                    ]
                );
                $success = "Item de presupuesto creado exitosamente.";
                break;
                
            case 'update':
                $db->query(
                    "UPDATE presupuestos_items SET nombre = ?, categoria_id = ?, tipo = ?, monto = ?, fecha_vencimiento = ?, es_recurrente = ?, frecuencia = ? 
                     WHERE id = ? AND (usuario_id = ? OR ? = 1)",
                    [
                        $_POST['nombre'],
                        $_POST['categoria_id'],
                        $_POST['tipo'],
                        $_POST['monto'],
                        $_POST['fecha_vencimiento'] ?: null,
                        isset($_POST['es_recurrente']) ? 1 : 0,
                        $_POST['frecuencia'] ?? 'mensual',
                        $_POST['id'],
                        $_SESSION['user_id'],
                        $isAdminFlag
                    ]
                );
                $success = "Item de presupuesto actualizado exitosamente.";
                break;
                
            case 'delete':
                $db->query(
                    "DELETE FROM presupuestos_items WHERE id = ? AND (usuario_id = ? OR ? = 1)",
                    [$_POST['id'], $_SESSION['user_id'], $isAdminFlag]
                );
                $success = "Item de presupuesto eliminado exitosamente.";
                break;
                
            case 'marcar_pagado':
                // Crear transacción automática y marcar como pagado
                $item = $db->fetch(
                    "SELECT * FROM presupuestos_items WHERE id = ? AND (usuario_id = ? OR ? = 1)",
                    [$_POST['id'], $_SESSION['user_id'], $isAdminFlag]
                );
                
                if ($item) {
                    // Crear transacción
                    $cuenta_id = $_POST['cuenta_id'];
                    $fecha = $_POST['fecha_pago'] ?: date('Y-m-d');
                    
                    $db->query(
                        "INSERT INTO transacciones (usuario_id, categoria_id, cuenta_id, tipo, cantidad, descripcion, fecha, created_at) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, NOW())",
                        [
                            $_SESSION['user_id'],
                            $item['categoria_id'],
                            $cuenta_id,
                            $item['tipo'],
                            $item['monto'],
                            'Pago automático: ' . $item['nombre'],
                            $fecha
                        ]
                    );
                    
                    // Actualizar saldo de cuenta
                    $multiplier = $item['tipo'] === 'ingreso' ? 1 : -1;
                    $db->query(
                        "UPDATE cuentas SET saldo_actual = saldo_actual + (? * ?) WHERE id = ?",
                        [$item['monto'], $multiplier, $cuenta_id]
                    );
                    
                    // Marcar como pagado este mes
                    $mes_actual = date('Y-m');
                    $db->query(
                        "INSERT INTO presupuestos_pagos (item_id, mes_año, fecha_pago, monto_pagado, usuario_id) 
                         VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE fecha_pago = ?, monto_pagado = ?",
                        [$item['id'], $mes_actual, $fecha, $item['monto'], $_SESSION['user_id'], $fecha, $item['monto']]
                    );
                    
                    $success = "Pago registrado exitosamente.";
                }
                break;
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Obtener items de presupuesto evitando concatenación con rol (previene problemas de collation)
$isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
if ($isAdmin) {
    $presupuestos = $db->fetchAll(
        "SELECT p.*, c.nombre as categoria, u.nombre as usuario, c.tipo as categoria_tipo,
                CASE WHEN pago.fecha_pago IS NOT NULL THEN 1 ELSE 0 END as pagado_este_mes,
                pago.fecha_pago, pago.monto_pagado
         FROM presupuestos_items p
         LEFT JOIN categorias c ON p.categoria_id = c.id
         LEFT JOIN usuarios u ON p.usuario_id = u.id
         LEFT JOIN presupuestos_pagos pago ON p.id = pago.item_id AND pago.mes_año = ?
         ORDER BY p.tipo, p.fecha_vencimiento, p.nombre",
        [date('Y-m')]
    );
} else {
    $presupuestos = $db->fetchAll(
        "SELECT p.*, c.nombre as categoria, u.nombre as usuario, c.tipo as categoria_tipo,
                CASE WHEN pago.fecha_pago IS NOT NULL THEN 1 ELSE 0 END as pagado_este_mes,
                pago.fecha_pago, pago.monto_pagado
         FROM presupuestos_items p
         LEFT JOIN categorias c ON p.categoria_id = c.id
         LEFT JOIN usuarios u ON p.usuario_id = u.id
         LEFT JOIN presupuestos_pagos pago ON p.id = pago.item_id AND pago.mes_año = ?
         WHERE p.usuario_id = ?
         ORDER BY p.tipo, p.fecha_vencimiento, p.nombre",
        [date('Y-m'), $_SESSION['user_id']]
    );
}

// Obtener categorías para el formulario
$categorias = $db->fetchAll("SELECT * FROM categorias ORDER BY tipo, nombre");

// Obtener cuentas para el usuario
$cuentas_usuario = $_SESSION['user_role'] === 'admin' 
    ? $db->fetchAll("SELECT * FROM cuentas WHERE activa = 1 ORDER BY nombre")
    : $db->fetchAll("SELECT * FROM cuentas WHERE activa = 1 AND (usuario_id = ? OR usuario_id IS NULL) ORDER BY nombre", [$_SESSION['user_id']]);

// Estadísticas del mes actual
$mes_actual = date('Y-m');
if ($isAdmin) {
    $stats = $db->fetch(
        "SELECT 
            SUM(CASE WHEN p.tipo = 'gasto' THEN p.monto ELSE 0 END) as gastos_presupuestados,
            SUM(CASE WHEN p.tipo = 'ingreso' THEN p.monto ELSE 0 END) as ingresos_presupuestados,
            COUNT(CASE WHEN p.tipo = 'gasto' AND pago.fecha_pago IS NOT NULL THEN 1 END) as gastos_pagados,
            COUNT(CASE WHEN p.tipo = 'gasto' THEN 1 END) as total_gastos,
            SUM(CASE WHEN p.tipo = 'gasto' AND pago.fecha_pago IS NOT NULL THEN pago.monto_pagado ELSE 0 END) as monto_gastado
         FROM presupuestos_items p
         LEFT JOIN presupuestos_pagos pago ON p.id = pago.item_id AND pago.mes_año = ?
         WHERE p.activo = 1",
        [$mes_actual]
    );
} else {
    $stats = $db->fetch(
        "SELECT 
            SUM(CASE WHEN p.tipo = 'gasto' THEN p.monto ELSE 0 END) as gastos_presupuestados,
            SUM(CASE WHEN p.tipo = 'ingreso' THEN p.monto ELSE 0 END) as ingresos_presupuestados,
            COUNT(CASE WHEN p.tipo = 'gasto' AND pago.fecha_pago IS NOT NULL THEN 1 END) as gastos_pagados,
            COUNT(CASE WHEN p.tipo = 'gasto' THEN 1 END) as total_gastos,
            SUM(CASE WHEN p.tipo = 'gasto' AND pago.fecha_pago IS NOT NULL THEN pago.monto_pagado ELSE 0 END) as monto_gastado
         FROM presupuestos_items p
         LEFT JOIN presupuestos_pagos pago ON p.id = pago.item_id AND pago.mes_año = ?
         WHERE p.activo = 1 AND p.usuario_id = ?",
        [$mes_actual, $_SESSION['user_id']]
    );
}

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
                        <a class="nav-link active" href="presupuestos.php">
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
                <h1 class="h2"><i class="fas fa-calculator me-2"></i>Presupuestos y Gastos Recurrentes</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#presupuestoModal">
                        <i class="fas fa-plus me-2"></i>Nuevo Item
                    </button>
                </div>
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

            <!-- Resumen del mes actual -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card stats-card">
                        <div class="card-body text-center">
                            <i class="fas fa-list fa-2x mb-2"></i>
                            <h4 class="mb-1"><?php echo $stats['total_gastos'] ?? 0; ?></h4>
                            <p class="mb-0">Items Programados</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card income">
                        <div class="card-body text-center">
                            <i class="fas fa-money-bill-wave fa-2x mb-2"></i>
                            <h4 class="mb-1">$<?php echo number_format($stats['gastos_presupuestados'] ?? 0, 2); ?></h4>
                            <p class="mb-0">Presupuestado</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card expense">
                        <div class="card-body text-center">
                            <i class="fas fa-shopping-cart fa-2x mb-2"></i>
                            <h4 class="mb-1">$<?php echo number_format($stats['monto_gastado'] ?? 0, 2); ?></h4>
                            <p class="mb-0">Gastado</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card <?php echo ($stats['gastos_pagados'] ?? 0) == ($stats['total_gastos'] ?? 0) ? 'savings' : 'expense'; ?>">
                        <div class="card-body text-center">
                            <i class="fas fa-check-circle fa-2x mb-2"></i>
                            <h4 class="mb-1"><?php echo $stats['gastos_pagados'] ?? 0; ?>/<?php echo $stats['total_gastos'] ?? 0; ?></h4>
                            <p class="mb-0">Pagados</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lista de items de presupuesto -->
            <div class="row">
                <!-- Gastos recurrentes -->
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-calendar-alt me-2"></i>Gastos Recurrentes
                                <small class="text-muted">(<?php echo date('F Y'); ?>)</small>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Item</th>
                                            <th>Categoría</th>
                                            <th>Vencimiento</th>
                                            <th>Monto</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($presupuestos as $item): ?>
                                        <?php if ($item['tipo'] === 'gasto'): ?>
                                        <tr class="<?php echo $item['pagado_este_mes'] ? 'table-success' : ($item['fecha_vencimiento'] && strtotime($item['fecha_vencimiento']) < time() ? 'table-danger' : ''); ?>">
                                            <td>
                                                <strong><?php echo htmlspecialchars($item['nombre']); ?></strong>
                                                <br>
                                                <small class="text-muted">
                                                    <?php echo ucfirst($item['frecuencia']); ?>
                                                    <?php if ($item['es_recurrente']): ?>
                                                        <i class="fas fa-repeat text-info"></i>
                                                    <?php endif; ?>
                                                </small>
                                            </td>
                                            <td><?php echo htmlspecialchars($item['categoria']); ?></td>
                                            <td>
                                                <?php if ($item['fecha_vencimiento']): ?>
                                                    <?php echo date('d/m', strtotime($item['fecha_vencimiento'])); ?>
                                                    <?php if (strtotime($item['fecha_vencimiento']) < time()): ?>
                                                        <i class="fas fa-exclamation-triangle text-danger"></i>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Sin fecha</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong>$<?php echo number_format($item['monto'], 2); ?></strong>
                                            </td>
                                            <td>
                                                <?php if ($item['pagado_este_mes']): ?>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check me-1"></i>Pagado
                                                    </span>
                                                    <br>
                                                    <small class="text-muted"><?php echo date('d/m', strtotime($item['fecha_pago'])); ?></small>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark">
                                                        <i class="fas fa-clock me-1"></i>Pendiente
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <?php if (!$item['pagado_este_mes']): ?>
                                                        <button class="btn btn-success" 
                                                                onclick="marcarPagado(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['nombre']); ?>')" 
                                                                title="Marcar como pagado">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <button class="btn btn-outline-primary" 
                                                            onclick="editarItem(<?php echo htmlspecialchars(json_encode($item)); ?>)" 
                                                            title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-outline-danger" 
                                                            onclick="eliminarItem(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['nombre']); ?>')" 
                                                            title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Panel lateral con próximos vencimientos -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-bell me-2"></i>Próximos Vencimientos
                            </h6>
                        </div>
                        <div class="card-body">
                            <?php 
                            $proximosVencimientos = array_filter($presupuestos, function($item) {
                                return $item['fecha_vencimiento'] && !$item['pagado_este_mes'] && strtotime($item['fecha_vencimiento']) >= time();
                            });
                            usort($proximosVencimientos, function($a, $b) {
                                return strtotime($a['fecha_vencimiento']) - strtotime($b['fecha_vencimiento']);
                            });
                            ?>
                            
                            <?php if (!empty($proximosVencimientos)): ?>
                                <?php foreach (array_slice($proximosVencimientos, 0, 5) as $item): ?>
                                <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                    <div>
                                        <strong><?php echo htmlspecialchars($item['nombre']); ?></strong>
                                        <br>
                                        <small class="text-muted">
                                            <?php echo date('d/m/Y', strtotime($item['fecha_vencimiento'])); ?>
                                        </small>
                                    </div>
                                    <div class="text-end">
                                        <strong>$<?php echo number_format($item['monto'], 2); ?></strong>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted text-center">No hay vencimientos próximos</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Resumen de ingresos recurrentes -->
                    <div class="card mt-3">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-plus-circle me-2"></i>Ingresos Recurrentes
                            </h6>
                        </div>
                        <div class="card-body">
                            <?php 
                            $ingresosRecurrentes = array_filter($presupuestos, function($item) {
                                return $item['tipo'] === 'ingreso';
                            });
                            ?>
                            
                            <?php if (!empty($ingresosRecurrentes)): ?>
                                <?php foreach ($ingresosRecurrentes as $item): ?>
                                <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                    <div>
                                        <strong><?php echo htmlspecialchars($item['nombre']); ?></strong>
                                        <br>
                                        <small class="text-muted"><?php echo ucfirst($item['frecuencia']); ?></small>
                                    </div>
                                    <div class="text-end">
                                        <strong class="text-success">+$<?php echo number_format($item['monto'], 2); ?></strong>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted text-center">No hay ingresos recurrentes configurados</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<!-- Modal para crear/editar item de presupuesto -->
<div class="modal fade" id="presupuestoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="presupuestoModalLabel">Nuevo Item de Presupuesto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" id="action" value="create">
                    <input type="hidden" name="id" id="item_id">
                    
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre del Item</label>
                        <input type="text" class="form-control" name="nombre" id="nombre" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <label for="tipo" class="form-label">Tipo</label>
                            <select class="form-select" name="tipo" id="tipo" required onchange="filtrarCategoriasPorTipo()">
                                <option value="">Seleccionar tipo</option>
                                <option value="ingreso">Ingreso</option>
                                <option value="gasto">Gasto</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="categoria_id" class="form-label">Categoría</label>
                            <select class="form-select" name="categoria_id" id="categoria_id" required>
                                <option value="">Seleccionar categoría</option>
                                <?php foreach ($categorias as $categoria): ?>
                                <option value="<?php echo $categoria['id']; ?>" data-tipo="<?php echo $categoria['tipo']; ?>">
                                    <?php echo htmlspecialchars($categoria['nombre']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label for="monto" class="form-label">Monto</label>
                            <input type="number" step="0.01" class="form-control" name="monto" id="monto" required>
                        </div>
                        <div class="col-md-6">
                            <label for="fecha_vencimiento" class="form-label">Fecha de Vencimiento</label>
                            <input type="date" class="form-control" name="fecha_vencimiento" id="fecha_vencimiento">
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="es_recurrente" id="es_recurrente" checked>
                                <label class="form-check-label" for="es_recurrente">
                                    Es recurrente
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="frecuencia" class="form-label">Frecuencia</label>
                            <select class="form-select" name="frecuencia" id="frecuencia">
                                <option value="semanal">Semanal</option>
                                <option value="quincenal">Quincenal</option>
                                <option value="mensual" selected>Mensual</option>
                                <option value="bimestral">Bimestral</option>
                                <option value="trimestral">Trimestral</option>
                                <option value="anual">Anual</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para marcar como pagado -->
<div class="modal fade" id="pagarModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Marcar como Pagado</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="marcar_pagado">
                    <input type="hidden" name="id" id="pagar_id">
                    
                    <p>¿Deseas marcar como pagado el item <strong id="pagar_nombre"></strong>?</p>
                    
                    <div class="mb-3">
                        <label for="cuenta_id" class="form-label">Cuenta de pago</label>
                        <select class="form-select" name="cuenta_id" required>
                            <option value="">Seleccionar cuenta</option>
                            <?php foreach ($cuentas_usuario as $cuenta): ?>
                            <option value="<?php echo $cuenta['id']; ?>">
                                <?php echo htmlspecialchars($cuenta['nombre']); ?> 
                                (Saldo: $<?php echo number_format($cuenta['saldo_actual'], 2); ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="fecha_pago" class="form-label">Fecha de pago</label>
                        <input type="date" class="form-control" name="fecha_pago" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check me-2"></i>Marcar como Pagado
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de confirmación para eliminar -->
<div class="modal fade" id="eliminarModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Eliminación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro de que desea eliminar el item <strong id="nombreEliminar"></strong>?</p>
                <p class="text-muted">Esta acción no se puede deshacer.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="idEliminar">
                    <button type="submit" class="btn btn-danger">Eliminar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function filtrarCategoriasPorTipo() {
    const tipo = document.getElementById('tipo').value;
    const categoriaSelect = document.getElementById('categoria_id');
    const opciones = categoriaSelect.querySelectorAll('option');
    
    categoriaSelect.value = '';
    
    opciones.forEach(opcion => {
        if (opcion.value === '') {
            opcion.style.display = 'block';
        } else {
            const tipoCategoria = opcion.getAttribute('data-tipo');
            opcion.style.display = (tipoCategoria === tipo) ? 'block' : 'none';
        }
    });
}

function editarItem(item) {
    document.getElementById('presupuestoModalLabel').textContent = 'Editar Item de Presupuesto';
    document.getElementById('action').value = 'update';
    document.getElementById('item_id').value = item.id;
    document.getElementById('nombre').value = item.nombre;
    document.getElementById('tipo').value = item.tipo;
    document.getElementById('categoria_id').value = item.categoria_id;
    document.getElementById('monto').value = item.monto;
    document.getElementById('fecha_vencimiento').value = item.fecha_vencimiento;
    document.getElementById('es_recurrente').checked = item.es_recurrente == 1;
    document.getElementById('frecuencia').value = item.frecuencia;
    
    // Filtrar categorías por tipo
    filtrarCategoriasPorTipo();
    document.getElementById('categoria_id').value = item.categoria_id;
    
    new bootstrap.Modal(document.getElementById('presupuestoModal')).show();
}

function eliminarItem(id, nombre) {
    document.getElementById('nombreEliminar').textContent = nombre;
    document.getElementById('idEliminar').value = id;
    new bootstrap.Modal(document.getElementById('eliminarModal')).show();
}

function marcarPagado(id, nombre) {
    document.getElementById('pagar_id').value = id;
    document.getElementById('pagar_nombre').textContent = nombre;
    new bootstrap.Modal(document.getElementById('pagarModal')).show();
}

// Limpiar modal al cerrarse
document.getElementById('presupuestoModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('presupuestoModalLabel').textContent = 'Nuevo Item de Presupuesto';
    document.getElementById('action').value = 'create';
    document.getElementById('item_id').value = '';
    document.querySelector('#presupuestoModal form').reset();
    
    // Mostrar todas las categorías
    const opciones = document.getElementById('categoria_id').querySelectorAll('option');
    opciones.forEach(opcion => {
        opcion.style.display = 'block';
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
