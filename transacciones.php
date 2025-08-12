<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'config/database.php';

// Filtros
$filtroTipo = $_GET['tipo'] ?? '';
$filtroCategoria = $_GET['categoria'] ?? '';
$filtroCuenta = $_GET['cuenta'] ?? '';
$filtroFechaInicio = $_GET['fecha_inicio'] ?? '';
$filtroFechaFin = $_GET['fecha_fin'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// Construir consulta con filtros
$whereConditions = [];
$params = [];

if ($filtroTipo) {
    $whereConditions[] = "t.tipo = ?";
    $params[] = $filtroTipo;
}

if ($filtroCategoria) {
    $whereConditions[] = "t.categoria_id = ?";
    $params[] = $filtroCategoria;
}

if ($filtroCuenta) {
    $whereConditions[] = "t.cuenta_id = ?";
    $params[] = $filtroCuenta;
}

if ($filtroFechaInicio) {
    $whereConditions[] = "t.fecha >= ?";
    $params[] = $filtroFechaInicio;
}

if ($filtroFechaFin) {
    $whereConditions[] = "t.fecha <= ?";
    $params[] = $filtroFechaFin;
}

// Si no es admin, solo ver sus transacciones
if ($_SESSION['user_role'] !== 'admin') {
    $whereConditions[] = "t.usuario_id = ?";
    $params[] = $_SESSION['user_id'];
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

// Obtener transacciones
$transacciones = $db->fetchAll(
    "SELECT t.*, c.nombre as categoria, c.color as categoria_color, c.icono as categoria_icono,
     cu.nombre as cuenta, u.nombre as usuario
     FROM transacciones t
     JOIN categorias c ON t.categoria_id = c.id
     JOIN cuentas cu ON t.cuenta_id = cu.id
     JOIN usuarios u ON t.usuario_id = u.id
     {$whereClause}
     ORDER BY t.fecha DESC, t.created_at DESC
     LIMIT {$limit} OFFSET {$offset}",
    $params
);

// Contar total para paginación
$totalTransacciones = $db->fetch(
    "SELECT COUNT(*) as total FROM transacciones t
     JOIN categorias c ON t.categoria_id = c.id
     JOIN cuentas cu ON t.cuenta_id = cu.id
     JOIN usuarios u ON t.usuario_id = u.id
     {$whereClause}",
    $params
)['total'];

$totalPages = ceil($totalTransacciones / $limit);

// Obtener datos para filtros
$categorias = $db->fetchAll("SELECT * FROM categorias WHERE activa = 1 ORDER BY tipo, nombre");
if ($_SESSION['user_role'] === 'admin') {
    $cuentas = $db->fetchAll("SELECT * FROM cuentas WHERE activa = 1 ORDER BY nombre");
} else {
    $cuentas = $db->fetchAll(
        "SELECT * FROM cuentas WHERE activa = 1 AND (usuario_id = ? OR usuario_id IS NULL) ORDER BY nombre",
        [$_SESSION['user_id']]
    );
}

// Estadísticas del período filtrado
$stats = $db->fetch(
    "SELECT 
     SUM(CASE WHEN t.tipo = 'ingreso' THEN t.cantidad ELSE 0 END) as total_ingresos,
     SUM(CASE WHEN t.tipo = 'gasto' THEN t.cantidad ELSE 0 END) as total_gastos,
     COUNT(*) as total_transacciones
     FROM transacciones t
     JOIN categorias c ON t.categoria_id = c.id
     JOIN cuentas cu ON t.cuenta_id = cu.id
     JOIN usuarios u ON t.usuario_id = u.id
     {$whereClause}",
    $params
);

$titulo = 'Transacciones - Contabilidad Familiar';
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
                        <a class="nav-link active" href="transacciones.php">
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
                    <i class="fas fa-exchange-alt me-2"></i>Transacciones
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#nuevaTransaccionModal">
                            <i class="fas fa-plus me-1"></i>Nueva Transacción
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="exportarTransacciones()">
                            <i class="fas fa-download me-1"></i>Exportar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Estadísticas del período -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card stats-card income">
                        <div class="card-body text-center">
                            <i class="fas fa-arrow-up fa-2x mb-2"></i>
                            <h4>$<?php echo number_format($stats['total_ingresos'] ?? 0, 2); ?></h4>
                            <p class="mb-0">Ingresos</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card expense">
                        <div class="card-body text-center">
                            <i class="fas fa-arrow-down fa-2x mb-2"></i>
                            <h4>$<?php echo number_format($stats['total_gastos'] ?? 0, 2); ?></h4>
                            <p class="mb-0">Gastos</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card <?php echo ($stats['total_ingresos'] - $stats['total_gastos']) >= 0 ? 'income' : 'expense'; ?>">
                        <div class="card-body text-center">
                            <i class="fas fa-balance-scale fa-2x mb-2"></i>
                            <h4>$<?php echo number_format(($stats['total_ingresos'] ?? 0) - ($stats['total_gastos'] ?? 0), 2); ?></h4>
                            <p class="mb-0">Balance</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card">
                        <div class="card-body text-center">
                            <i class="fas fa-list fa-2x mb-2"></i>
                            <h4><?php echo $stats['total_transacciones'] ?? 0; ?></h4>
                            <p class="mb-0">Transacciones</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-filter me-2"></i>Filtros
                        <button class="btn btn-sm btn-outline-secondary float-end" onclick="limpiarFiltros()">
                            <i class="fas fa-times me-1"></i>Limpiar
                        </button>
                    </h6>
                </div>
                <div class="card-body">
                    <form method="GET" id="filtrosForm">
                        <div class="row">
                            <div class="col-md-2">
                                <label class="form-label">Tipo</label>
                                <select name="tipo" class="form-select form-select-sm">
                                    <option value="">Todos</option>
                                    <option value="ingreso" <?php echo $filtroTipo === 'ingreso' ? 'selected' : ''; ?>>Ingresos</option>
                                    <option value="gasto" <?php echo $filtroTipo === 'gasto' ? 'selected' : ''; ?>>Gastos</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Categoría</label>
                                <select name="categoria" class="form-select form-select-sm">
                                    <option value="">Todas</option>
                                    <?php foreach ($categorias as $categoria): ?>
                                        <option value="<?php echo $categoria['id']; ?>" 
                                                <?php echo $filtroCategoria == $categoria['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($categoria['nombre']); ?> (<?php echo ucfirst($categoria['tipo']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Cuenta</label>
                                <select name="cuenta" class="form-select form-select-sm">
                                    <option value="">Todas</option>
                                    <?php foreach ($cuentas as $cuenta): ?>
                                        <option value="<?php echo $cuenta['id']; ?>" 
                                                <?php echo $filtroCuenta == $cuenta['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cuenta['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Desde</label>
                                <input type="date" name="fecha_inicio" class="form-control form-control-sm" 
                                       value="<?php echo htmlspecialchars($filtroFechaInicio); ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Hasta</label>
                                <input type="date" name="fecha_fin" class="form-control form-control-sm" 
                                       value="<?php echo htmlspecialchars($filtroFechaFin); ?>">
                            </div>
                            <div class="col-md-1 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary btn-sm w-100">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabla de transacciones -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-list me-2"></i>
                        Listado de Transacciones 
                        <small class="text-muted">(<?php echo $totalTransacciones; ?> total)</small>
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($transacciones)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Descripción</th>
                                        <th>Categoría</th>
                                        <th>Cuenta</th>
                                        <?php if ($_SESSION['user_role'] === 'admin'): ?>
                                            <th>Usuario</th>
                                        <?php endif; ?>
                                        <th>Cantidad</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($transacciones as $transaccion): ?>
                                        <tr>
                                            <td>
                                                <span class="fw-bold"><?php echo date('d/m/Y', strtotime($transaccion['fecha'])); ?></span>
                                                <br>
                                                <small class="text-muted"><?php echo date('H:i', strtotime($transaccion['created_at'])); ?></small>
                                            </td>
                                            <td>
                                                <div class="fw-bold"><?php echo htmlspecialchars($transaccion['descripcion']); ?></div>
                                            </td>
                                            <td>
                                                <span class="badge" style="background-color: <?php echo $transaccion['categoria_color']; ?>; color: white;">
                                                    <i class="<?php echo $transaccion['categoria_icono']; ?> me-1"></i>
                                                    <?php echo htmlspecialchars($transaccion['categoria']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($transaccion['cuenta']); ?></td>
                                            <?php if ($_SESSION['user_role'] === 'admin'): ?>
                                                <td>
                                                    <i class="fas fa-user me-1"></i>
                                                    <?php echo htmlspecialchars($transaccion['usuario']); ?>
                                                </td>
                                            <?php endif; ?>
                                            <td>
                                                <span class="fw-bold text-<?php echo $transaccion['tipo'] === 'ingreso' ? 'success' : 'danger'; ?>">
                                                    <?php echo $transaccion['tipo'] === 'ingreso' ? '+' : '-'; ?>$<?php echo number_format($transaccion['cantidad'], 2); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary" onclick="editarTransaccion(<?php echo $transaccion['id']; ?>)" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-outline-danger" onclick="eliminarTransaccion(<?php echo $transaccion['id']; ?>)" title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginación -->
                        <?php if ($totalPages > 1): ?>
                            <nav aria-label="Paginación de transacciones">
                                <ul class="pagination justify-content-center">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>

                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No se encontraron transacciones</h5>
                            <p class="text-muted">Prueba ajustando los filtros o agrega una nueva transacción</p>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#nuevaTransaccionModal">
                                <i class="fas fa-plus me-2"></i>Agregar Transacción
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal Nueva Transacción -->
<div class="modal fade" id="nuevaTransaccionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus me-2"></i>Nueva Transacción
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div id="modalContent">
                <!-- El contenido se carga dinámicamente -->
            </div>
        </div>
    </div>
</div>

<script>
// Cargar formulario de transacción
document.getElementById('nuevaTransaccionModal').addEventListener('show.bs.modal', function (e) {
    const editId = e.target.getAttribute('data-edit-id') || '';
    const url = editId ? ('formulario_transaccion.php?id=' + encodeURIComponent(editId)) : 'formulario_transaccion.php';
    fetch(url)
        .then(response => response.text())
        .then(html => {
            document.getElementById('modalContent').innerHTML = `
                <form method="POST" action="procesar_transaccion.php" onsubmit="return validarFormulario()">
                    <div class="modal-body">${html}</div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Guardar
                        </button>
                    </div>
                </form>
            `;
        })
        .catch(error => {
            console.error('Error cargando formulario:', error);
            document.getElementById('modalContent').innerHTML = `
                <div class="modal-body">
                    <div class="alert alert-danger">
                        Error cargando el formulario. Por favor recarga la página.
                    </div>
                </div>
            `;
        });
});

function validarFormulario() {
    const tipo = document.getElementById('tipo').value;
    const cantidad = document.getElementById('cantidad').value;
    const categoria = document.getElementById('categoria_id').value;
    const cuenta = document.getElementById('cuenta_id').value;
    const descripcion = document.getElementById('descripcion').value;
    
    if (!tipo || !cantidad || !categoria || !cuenta || !descripcion) {
        alert('Por favor completa todos los campos obligatorios.');
        return false;
    }
    
    if (parseFloat(cantidad) <= 0) {
        alert('La cantidad debe ser mayor a 0.');
        return false;
    }
    
    return true;
}

function limpiarFiltros() {
    window.location.href = 'transacciones.php';
}

function editarTransaccion(id) {
    const modalEl = document.getElementById('nuevaTransaccionModal');
    modalEl.setAttribute('data-edit-id', id);
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
}

function eliminarTransaccion(id) {
    if (!confirm('¿Estás seguro de que deseas eliminar esta transacción?')) return;
    // Construir y enviar un formulario POST oculto
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'procesar_transaccion.php';
    const accion = document.createElement('input');
    accion.type = 'hidden';
    accion.name = 'accion';
    accion.value = 'eliminar';
    const idInput = document.createElement('input');
    idInput.type = 'hidden';
    idInput.name = 'id';
    idInput.value = String(id);
    form.appendChild(accion);
    form.appendChild(idInput);
    document.body.appendChild(form);
    form.submit();
}

function exportarTransacciones() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'csv');
    window.open('exportar_transacciones.php?' + params.toString());
}
</script>

<?php include 'includes/footer.php'; ?>
