<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'config/database.php';

$titulo = 'Reportes';

// Filtros
$fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fechaFin = $_GET['fecha_fin'] ?? date('Y-m-t');
$categoria = $_GET['categoria'] ?? '';
$cuenta = $_GET['cuenta'] ?? '';
$usuario = $_GET['usuario'] ?? '';
$isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

// Obtener listas para filtros
$categorias = $db->fetchAll("SELECT * FROM categorias ORDER BY nombre");
if ($isAdmin) {
    $cuentas = $db->fetchAll("SELECT * FROM cuentas WHERE activa = 1 ORDER BY nombre");
    $usuarios = $db->fetchAll("SELECT * FROM usuarios WHERE activo = 1 ORDER BY nombre");
} else {
    // Solo cuentas propias o compartidas para no-admins
    $cuentas = $db->fetchAll(
        "SELECT * FROM cuentas WHERE activa = 1 AND (usuario_id = ? OR usuario_id IS NULL) ORDER BY nombre",
        [$_SESSION['user_id']]
    );
    // Lista de usuarios no se muestra para no-admins, pero dejamos el propio por consistencia si se necesitara
    $usuarios = $db->fetchAll(
        "SELECT id, nombre FROM usuarios WHERE id = ?",
        [$_SESSION['user_id']]
    );
}

// Construir query de transacciones con filtros
$whereConditions = ["DATE(t.fecha) BETWEEN ? AND ?"];
$params = [$fechaInicio, $fechaFin];

if ($categoria) {
    $whereConditions[] = "t.categoria_id = ?";
    $params[] = $categoria;
}

if ($cuenta) {
    $whereConditions[] = "t.cuenta_id = ?";
    $params[] = $cuenta;
}

// Filtro de usuario solo permitido para administradores
if ($isAdmin && $usuario) {
    $whereConditions[] = "t.usuario_id = ?";
    $params[] = $usuario;
}

// Aislamiento por usuario para no-admins
if (!$isAdmin) {
    $whereConditions[] = "t.usuario_id = ?";
    $params[] = $_SESSION['user_id'];
}

$whereClause = implode(' AND ', $whereConditions);

// Obtener transacciones filtradas
$transacciones = $db->fetchAll(
    "SELECT t.*, c.nombre as categoria, cu.nombre as cuenta, u.nombre as usuario
     FROM transacciones t
     JOIN categorias c ON t.categoria_id = c.id
     JOIN cuentas cu ON t.cuenta_id = cu.id
     JOIN usuarios u ON t.usuario_id = u.id
     WHERE $whereClause
     ORDER BY t.fecha DESC",
    $params
);

// Estadísticas del período
$resumen = $db->fetch(
    "SELECT 
        SUM(CASE WHEN tipo = 'ingreso' THEN cantidad ELSE 0 END) as total_ingresos,
        SUM(CASE WHEN tipo = 'gasto' THEN cantidad ELSE 0 END) as total_gastos,
        COUNT(*) as total_transacciones
     FROM transacciones t
     WHERE $whereClause",
    $params
);

$balance = ($resumen['total_ingresos'] ?? 0) - ($resumen['total_gastos'] ?? 0);

// Gastos por categoría
$gastosPorCategoria = $db->fetchAll(
    "SELECT c.nombre, SUM(t.cantidad) as total, c.color
     FROM transacciones t
     JOIN categorias c ON t.categoria_id = c.id
     WHERE t.tipo = 'gasto' AND $whereClause
     GROUP BY t.categoria_id, c.nombre, c.color
     ORDER BY total DESC",
    $params
);

// Ingresos por categoría
$ingresosPorCategoria = $db->fetchAll(
    "SELECT c.nombre, SUM(t.cantidad) as total, c.color
     FROM transacciones t
     JOIN categorias c ON t.categoria_id = c.id
     WHERE t.tipo = 'ingreso' AND $whereClause
     GROUP BY t.categoria_id, c.nombre, c.color
     ORDER BY total DESC",
    $params
);

// Transacciones por mes
$transaccionesPorMes = $db->fetchAll(
    "SELECT 
        DATE_FORMAT(fecha, '%Y-%m') as mes,
        SUM(CASE WHEN tipo = 'ingreso' THEN cantidad ELSE 0 END) as ingresos,
        SUM(CASE WHEN tipo = 'gasto' THEN cantidad ELSE 0 END) as gastos
     FROM transacciones t
     WHERE $whereClause
     GROUP BY DATE_FORMAT(fecha, '%Y-%m')
     ORDER BY mes",
    $params
);

// Contexto del usuario seleccionado para análisis
$usuarioSeleccionadoId = null;
$usuarioSeleccionadoNombre = null;
$saldoCuentasUsuario = null;
if ($isAdmin && $usuario) {
    $usuarioSeleccionadoId = intval($usuario);
} elseif (!$isAdmin) {
    $usuarioSeleccionadoId = intval($_SESSION['user_id']);
}

if ($usuarioSeleccionadoId) {
    $usrRow = $db->fetch("SELECT id, nombre FROM usuarios WHERE id = ?", [$usuarioSeleccionadoId]);
    $usuarioSeleccionadoNombre = $usrRow ? $usrRow['nombre'] : null;
    // Saldo actual (cuentas activas del usuario y compartidas)
    $saldoRow = $db->fetch(
        "SELECT COALESCE(SUM(saldo_actual),0) AS total FROM cuentas WHERE activa = 1 AND (usuario_id = ? OR usuario_id IS NULL)",
        [$usuarioSeleccionadoId]
    );
    $saldoCuentasUsuario = $saldoRow ? $saldoRow['total'] : 0;
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
                        <a class="nav-link active" href="reportes.php">
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
                <h1 class="h2"><i class="fas fa-chart-bar me-2"></i>Reportes Financieros</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-success" onclick="exportarReporte()">
                        <i class="fas fa-download me-2"></i>Exportar
                    </button>
                </div>
            </div>

            <!-- Filtros -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filtros de Reporte</h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="fecha_inicio" class="form-label">Fecha Inicio</label>
                            <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" value="<?php echo $fechaInicio; ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="fecha_fin" class="form-label">Fecha Fin</label>
                            <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" value="<?php echo $fechaFin; ?>">
                        </div>
                        <div class="col-md-2">
                            <label for="categoria" class="form-label">Categoría</label>
                            <select class="form-select" id="categoria" name="categoria">
                                <option value="">Todas</option>
                                <?php foreach ($categorias as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo $categoria == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['nombre']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="cuenta" class="form-label">Cuenta</label>
                            <select class="form-select" id="cuenta" name="cuenta">
                                <option value="">Todas</option>
                                <?php foreach ($cuentas as $cta): ?>
                                <option value="<?php echo $cta['id']; ?>" <?php echo $cuenta == $cta['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cta['nombre']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php if ($isAdmin): ?>
                        <div class="col-md-2">
                            <label for="usuario" class="form-label">Usuario</label>
                            <select class="form-select" id="usuario" name="usuario">
                                <option value="">Todos</option>
                                <?php foreach ($usuarios as $usr): ?>
                                <option value="<?php echo $usr['id']; ?>" <?php echo $usuario == $usr['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($usr['nombre']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i>Filtrar
                            </button>
                            <a href="reportes.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>Limpiar
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Resumen del período -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card stats-card income">
                        <div class="card-body text-center">
                            <i class="fas fa-arrow-up fa-2x mb-2"></i>
                            <h4 class="mb-1">$<?php echo number_format($resumen['total_ingresos'] ?? 0, 2); ?></h4>
                            <p class="mb-0">Total Ingresos</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card expense">
                        <div class="card-body text-center">
                            <i class="fas fa-arrow-down fa-2x mb-2"></i>
                            <h4 class="mb-1">$<?php echo number_format($resumen['total_gastos'] ?? 0, 2); ?></h4>
                            <p class="mb-0">Total Gastos</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card <?php echo $balance >= 0 ? 'savings' : 'expense'; ?>">
                        <div class="card-body text-center">
                            <i class="fas <?php echo $balance >= 0 ? 'fa-plus' : 'fa-minus'; ?> fa-2x mb-2"></i>
                            <h4 class="mb-1">$<?php echo number_format($balance, 2); ?></h4>
                            <p class="mb-0">Balance</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card">
                        <div class="card-body text-center">
                            <i class="fas fa-list fa-2x mb-2"></i>
                            <h4 class="mb-1"><?php echo $resumen['total_transacciones']; ?></h4>
                            <p class="mb-0">Transacciones</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gráficos -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Gastos por Categoría</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="gastosChart" width="400" height="300"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Ingresos por Categoría</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="ingresosChart" width="400" height="300"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Evolución Mensual</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="evolucionChart" width="400" height="300"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($usuarioSeleccionadoId): ?>
            <!-- Análisis por Usuario -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-user me-2"></i>
                        Análisis por Usuario: <?php echo htmlspecialchars($usuarioSeleccionadoNombre ?? ''); ?>
                    </h5>
                    <span class="badge bg-secondary">
                        Período: <?php echo date('d/m/Y', strtotime($fechaInicio)); ?> - <?php echo date('d/m/Y', strtotime($fechaFin)); ?>
                    </span>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <div class="p-3 border rounded text-center">
                                <div class="text-muted small">Total Ingresos</div>
                                <div class="h4 text-success mb-0">$<?php echo number_format($resumen['total_ingresos'] ?? 0, 2); ?></div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 border rounded text-center">
                                <div class="text-muted small">Total Gastos</div>
                                <div class="h4 text-danger mb-0">$<?php echo number_format($resumen['total_gastos'] ?? 0, 2); ?></div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 border rounded text-center">
                                <div class="text-muted small">Balance</div>
                                <?php $bal = ($resumen['total_ingresos'] ?? 0) - ($resumen['total_gastos'] ?? 0); ?>
                                <div class="h4 <?php echo $bal >= 0 ? 'text-success' : 'text-danger'; ?> mb-0">$<?php echo number_format($bal, 2); ?></div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 border rounded text-center">
                                <div class="text-muted small">Saldo Cuentas (actual)</div>
                                <div class="h4 mb-0">$<?php echo number_format($saldoCuentasUsuario ?? 0, 2); ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="mb-2">Top Gastos por Categoría</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-striped">
                                    <thead>
                                        <tr>
                                            <th>Categoría</th>
                                            <th class="text-end">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($gastosPorCategoria as $row): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                                            <td class="text-end">$<?php echo number_format($row['total'], 2); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($gastosPorCategoria)): ?>
                                        <tr><td colspan="2" class="text-muted">Sin datos</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="mb-2">Top Ingresos por Categoría</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-striped">
                                    <thead>
                                        <tr>
                                            <th>Categoría</th>
                                            <th class="text-end">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($ingresosPorCategoria as $row): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                                            <td class="text-end">$<?php echo number_format($row['total'], 2); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($ingresosPorCategoria)): ?>
                                        <tr><td colspan="2" class="text-muted">Sin datos</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Tabla de transacciones -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Detalle de Transacciones</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Descripción</th>
                                    <th>Categoría</th>
                                    <th>Cuenta</th>
                                    <th>Usuario</th>
                                    <th>Tipo</th>
                                    <th>Cantidad</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transacciones as $transaccion): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($transaccion['fecha'])); ?></td>
                                    <td><?php echo htmlspecialchars($transaccion['descripcion']); ?></td>
                                    <td><?php echo htmlspecialchars($transaccion['categoria']); ?></td>
                                    <td><?php echo htmlspecialchars($transaccion['cuenta']); ?></td>
                                    <td><?php echo htmlspecialchars($transaccion['usuario']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $transaccion['tipo'] === 'ingreso' ? 'bg-success' : 'bg-danger'; ?>">
                                            <?php echo ucfirst($transaccion['tipo']); ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <span class="<?php echo $transaccion['tipo'] === 'ingreso' ? 'text-success' : 'text-danger'; ?>">
                                            <?php echo $transaccion['tipo'] === 'ingreso' ? '+' : '-'; ?>$<?php echo number_format($transaccion['cantidad'], 2); ?>
                                        </span>
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

<script>
// Gráfico de gastos por categoría
const gastosCtx = document.getElementById('gastosChart').getContext('2d');
const gastosChart = new Chart(gastosCtx, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode(array_column($gastosPorCategoria, 'nombre')); ?>,
        datasets: [{
            data: <?php echo json_encode(array_column($gastosPorCategoria, 'total')); ?>,
            backgroundColor: <?php echo json_encode(array_column($gastosPorCategoria, 'color')); ?>,
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Gráfico de ingresos por categoría
const ingresosCtx = document.getElementById('ingresosChart').getContext('2d');
const ingresosChart = new Chart(ingresosCtx, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode(array_column($ingresosPorCategoria, 'nombre')); ?>,
        datasets: [{
            data: <?php echo json_encode(array_column($ingresosPorCategoria, 'total')); ?>,
            backgroundColor: <?php echo json_encode(array_column($ingresosPorCategoria, 'color')); ?>,
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Gráfico de evolución mensual
const evolucionCtx = document.getElementById('evolucionChart').getContext('2d');
const evolucionChart = new Chart(evolucionCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_column($transaccionesPorMes, 'mes')); ?>,
        datasets: [{
            label: 'Ingresos',
            data: <?php echo json_encode(array_column($transaccionesPorMes, 'ingresos')); ?>,
            borderColor: '#28a745',
            backgroundColor: 'rgba(40, 167, 69, 0.1)',
            tension: 0.4
        }, {
            label: 'Gastos',
            data: <?php echo json_encode(array_column($transaccionesPorMes, 'gastos')); ?>,
            borderColor: '#dc3545',
            backgroundColor: 'rgba(220, 53, 69, 0.1)',
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '$' + value.toLocaleString();
                    }
                }
            }
        },
        plugins: {
            legend: {
                position: 'top'
            }
        }
    }
});

function exportarReporte() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', '1');
    window.open('exportar_reporte.php?' + params.toString(), '_blank');
}
</script>

<?php require_once 'includes/footer.php'; ?>
