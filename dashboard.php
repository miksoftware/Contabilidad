<?php
session_start();

// Configurar manejo de errores para producción
error_reporting(E_ALL);
ini_set('display_errors', 0); // Ocultar errores en producción
ini_set('log_errors', 1);

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

try {
    require_once 'config/database.php';
} catch (Exception $e) {
    error_log("Error al conectar BD en dashboard: " . $e->getMessage());
    die("Error de conexión. Contacte al administrador.");
}

// Obtener estadísticas del mes actual con manejo de errores
$mesActual = date('Y-m');
$añoActual = date('Y');

try {
    // Ingresos del mes
    $ingresosMes = $db->fetch(
        "SELECT COALESCE(SUM(cantidad), 0) as total FROM transacciones 
         WHERE tipo = 'ingreso' AND DATE_FORMAT(fecha, '%Y-%m') = ?",
        [$mesActual]
    );
    $ingresosMes = $ingresosMes ? $ingresosMes['total'] : 0;
} catch (Exception $e) {
    error_log("Error consultando ingresos: " . $e->getMessage());
    $ingresosMes = 0;
}

try {
    // Gastos del mes
    $gastosMes = $db->fetch(
        "SELECT COALESCE(SUM(cantidad), 0) as total FROM transacciones 
         WHERE tipo = 'gasto' AND DATE_FORMAT(fecha, '%Y-%m') = ?",
        [$mesActual]
    );
    $gastosMes = $gastosMes ? $gastosMes['total'] : 0;
} catch (Exception $e) {
    error_log("Error consultando gastos: " . $e->getMessage());
    $gastosMes = 0;
}

// Balance del mes
$balanceMes = $ingresosMes - $gastosMes;

try {
    // Saldo total de todas las cuentas
    $saldoTotal = $db->fetch(
        "SELECT COALESCE(SUM(saldo_actual), 0) as total FROM cuentas WHERE activa = 1"
    );
    $saldoTotal = $saldoTotal ? $saldoTotal['total'] : 0;
} catch (Exception $e) {
    error_log("Error consultando saldo total: " . $e->getMessage());
    $saldoTotal = 0;
}

try {
    // Últimas transacciones
    $ultimasTransacciones = $db->fetchAll(
        "SELECT t.*, c.nombre as categoria, cu.nombre as cuenta, u.nombre as usuario
         FROM transacciones t
         JOIN categorias c ON t.categoria_id = c.id
         JOIN cuentas cu ON t.cuenta_id = cu.id
         JOIN usuarios u ON t.usuario_id = u.id
         ORDER BY t.created_at DESC
         LIMIT 10"
    );
    if (!$ultimasTransacciones) {
        $ultimasTransacciones = [];
    }
} catch (Exception $e) {
    error_log("Error consultando últimas transacciones: " . $e->getMessage());
    $ultimasTransacciones = [];
}

try {
    // Gastos por categoría (mes actual)
    $gastosPorCategoria = $db->fetchAll(
        "SELECT c.nombre, c.color, COALESCE(SUM(t.cantidad), 0) as total
         FROM categorias c
         LEFT JOIN transacciones t ON c.id = t.categoria_id 
             AND t.tipo = 'gasto' 
             AND DATE_FORMAT(t.fecha, '%Y-%m') = ?
         WHERE c.tipo = 'gasto' AND c.activa = 1
         GROUP BY c.id, c.nombre, c.color
         HAVING total > 0
         ORDER BY total DESC",
        [$mesActual]
    );
    if (!$gastosPorCategoria) {
        $gastosPorCategoria = [];
    }
} catch (Exception $e) {
    error_log("Error consultando gastos por categoría: " . $e->getMessage());
    $gastosPorCategoria = [];
}

try {
    // Metas de ahorro
    $metasAhorro = $db->fetchAll(
        "SELECT *, 
         ROUND((cantidad_actual / cantidad_objetivo) * 100, 2) as progreso
         FROM metas_ahorro 
         WHERE completada = 0
         ORDER BY fecha_objetivo ASC
         LIMIT 5"
    );
    if (!$metasAhorro) {
        $metasAhorro = [];
    }
} catch (Exception $e) {
    error_log("Error consultando metas de ahorro: " . $e->getMessage());
    $metasAhorro = [];
}

$titulo = 'Dashboard - Contabilidad Familiar';
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
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
                <h1 class="h2">
                    <i class="fas fa-tachometer-alt me-2"></i>
                    Dashboard
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#nuevaTransaccionModal">
                            <i class="fas fa-plus me-1"></i>
                            Nueva Transacción
                        </button>
                    </div>
                </div>
            </div>

            <!-- Alert container -->
            <div id="alert-container"></div>

            <!-- Tarjetas de estadísticas -->
            <div class="row mb-4 animate-fade-in">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stats-card income">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-uppercase mb-1">
                                        Ingresos del Mes
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold">
                                        $<?php echo number_format($ingresosMes, 2); ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-arrow-up fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stats-card expense">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-uppercase mb-1">
                                        Gastos del Mes
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold">
                                        $<?php echo number_format($gastosMes, 2); ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-arrow-down fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stats-card <?php echo $balanceMes >= 0 ? 'income' : 'expense'; ?>">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-uppercase mb-1">
                                        Balance del Mes
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold">
                                        $<?php echo number_format($balanceMes, 2); ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-balance-scale fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stats-card savings">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-uppercase mb-1">
                                        Saldo Total
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold">
                                        $<?php echo number_format($saldoTotal, 2); ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-wallet fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Gráfico de gastos por categoría -->
                <div class="col-lg-6 mb-4">
                    <div class="card animate-fade-in">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-chart-pie me-2"></i>
                                Gastos por Categoría (<?php echo date('F Y'); ?>)
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($gastosPorCategoria)): ?>
                                <canvas id="gastosChart" width="400" height="300"></canvas>
                            <?php else: ?>
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-chart-pie fa-3x mb-3"></i>
                                    <p>No hay gastos registrados este mes</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Metas de ahorro -->
                <div class="col-lg-6 mb-4">
                    <div class="card animate-fade-in">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-bullseye me-2"></i>
                                Metas de Ahorro
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($metasAhorro)): ?>
                                <?php foreach ($metasAhorro as $meta): ?>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="fw-bold"><?php echo htmlspecialchars($meta['nombre']); ?></span>
                                            <small class="text-muted"><?php echo $meta['progreso']; ?>%</small>
                                        </div>
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar bg-success" 
                                                 style="width: <?php echo min($meta['progreso'], 100); ?>%"></div>
                                        </div>
                                        <small class="text-muted">
                                            $<?php echo number_format($meta['cantidad_actual'], 2); ?> de 
                                            $<?php echo number_format($meta['cantidad_objetivo'], 2); ?>
                                        </small>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-bullseye fa-3x mb-3"></i>
                                    <p>No tienes metas de ahorro activas</p>
                                    <a href="metas.php" class="btn btn-primary btn-sm">Crear Meta</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Últimas transacciones -->
            <div class="row">
                <div class="col-12">
                    <div class="card animate-fade-in">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-history me-2"></i>
                                Últimas Transacciones
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($ultimasTransacciones)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Descripción</th>
                                                <th>Categoría</th>
                                                <th>Cuenta</th>
                                                <th>Usuario</th>
                                                <th>Cantidad</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($ultimasTransacciones as $transaccion): ?>
                                                <tr>
                                                    <td><?php echo date('d/m/Y', strtotime($transaccion['fecha'])); ?></td>
                                                    <td><?php echo htmlspecialchars($transaccion['descripcion']); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $transaccion['tipo'] == 'ingreso' ? 'success' : 'danger'; ?>">
                                                            <?php echo htmlspecialchars($transaccion['categoria']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($transaccion['cuenta']); ?></td>
                                                    <td><?php echo htmlspecialchars($transaccion['usuario']); ?></td>
                                                    <td class="text-<?php echo $transaccion['tipo'] == 'ingreso' ? 'success' : 'danger'; ?>">
                                                        <?php echo $transaccion['tipo'] == 'ingreso' ? '+' : '-'; ?>$<?php echo number_format($transaccion['cantidad'], 2); ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-receipt fa-3x mb-3"></i>
                                    <p>No hay transacciones registradas</p>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#nuevaTransaccionModal">
                                        Agregar Primera Transacción
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<!-- Modal para nueva transacción -->
<div class="modal fade" id="nuevaTransaccionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus me-2"></i>
                    Nueva Transacción
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="nuevaTransaccionForm" method="POST" action="procesar_transaccion.php">
                <div class="modal-body">
                    <!-- El formulario se cargará aquí -->
                    <p class="text-center"><i class="fas fa-spinner fa-spin"></i> Cargando formulario...</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Transacción</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Gráfico de gastos por categoría
<?php if (!empty($gastosPorCategoria)): ?>
const gastosData = {
    labels: [<?php echo implode(',', array_map(function($item) { return '"' . addslashes($item['nombre']) . '"'; }, $gastosPorCategoria)); ?>],
    datasets: [{
        data: [<?php echo implode(',', array_column($gastosPorCategoria, 'total')); ?>],
        backgroundColor: [<?php echo implode(',', array_map(function($item) { return '"' . $item['color'] . '"'; }, $gastosPorCategoria)); ?>],
        borderWidth: 2,
        borderColor: '#fff'
    }]
};

const gastosChart = new Chart(document.getElementById('gastosChart'), {
    type: 'doughnut',
    data: gastosData,
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
<?php endif; ?>

// Cargar formulario de nueva transacción cuando se abre el modal
document.getElementById('nuevaTransaccionModal').addEventListener('show.bs.modal', function () {
    fetch('formulario_transaccion.php')
        .then(response => response.text())
        .then(html => {
            document.querySelector('#nuevaTransaccionModal .modal-body').innerHTML = html;
        })
        .catch(error => {
            console.error('Error:', error);
            document.querySelector('#nuevaTransaccionModal .modal-body').innerHTML = 
                '<div class="alert alert-danger">Error al cargar el formulario. Inténtalo de nuevo.</div>';
        });
});
</script>

<?php include 'includes/footer.php'; ?>
