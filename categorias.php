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
                $color = $_POST['color'];
                $icono = $_POST['icono'];
                
                if (empty($nombre) || empty($tipo)) {
                    throw new Exception('Nombre y tipo son obligatorios');
                }
                
                $db->query(
                    "INSERT INTO categorias (nombre, tipo, color, icono) VALUES (?, ?, ?, ?)",
                    [$nombre, $tipo, $color, $icono]
                );
                
                $mensaje = 'Categoría creada exitosamente';
                break;
                
            case 'editar':
                $id = intval($_POST['id']);
                $nombre = trim($_POST['nombre']);
                $tipo = $_POST['tipo'];
                $color = $_POST['color'];
                $icono = $_POST['icono'];
                
                if (empty($nombre) || empty($tipo)) {
                    throw new Exception('Nombre y tipo son obligatorios');
                }
                
                $db->query(
                    "UPDATE categorias SET nombre = ?, tipo = ?, color = ?, icono = ? WHERE id = ?",
                    [$nombre, $tipo, $color, $icono, $id]
                );
                
                $mensaje = 'Categoría actualizada exitosamente';
                break;
                
            case 'eliminar':
                $id = intval($_POST['id']);
                
                // Verificar si tiene transacciones
                $transacciones = $db->fetch(
                    "SELECT COUNT(*) as total FROM transacciones WHERE categoria_id = ?",
                    [$id]
                );
                
                if ($transacciones['total'] > 0) {
                    throw new Exception('No se puede eliminar una categoría con transacciones. Desactívala en su lugar.');
                }
                
                $db->query("DELETE FROM categorias WHERE id = ?", [$id]);
                $mensaje = 'Categoría eliminada exitosamente';
                break;
                
            case 'toggle_activo':
                $id = intval($_POST['id']);
                $db->query("UPDATE categorias SET activa = NOT activa WHERE id = ?", [$id]);
                $mensaje = 'Estado de categoría actualizado';
                break;
        }
    } catch (Exception $e) {
        $mensaje = $e->getMessage();
        $tipoMensaje = 'danger';
    }
}

// Obtener todas las categorías
$categorias = $db->fetchAll(
    "SELECT c.*, 
     (SELECT COUNT(*) FROM transacciones WHERE categoria_id = c.id) as total_transacciones
     FROM categorias c 
     ORDER BY c.tipo, c.activa DESC, c.nombre"
);

// Separar por tipo
$ingresos = array_filter($categorias, fn($c) => $c['tipo'] === 'ingreso');
$gastos = array_filter($categorias, fn($c) => $c['tipo'] === 'gasto');

$titulo = 'Gestión de Categorías - Contabilidad Familiar';
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
                        <a class="nav-link active" href="categorias.php">
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
                    <?php if ($_SESSION['user_role'] === 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="usuarios.php">
                            <i class="fas fa-users me-2"></i>Usuarios
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="transferencias.php">
                            <i class="fas fa-exchange-alt me-2"></i>Transferencias
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
                    <i class="fas fa-tags me-2"></i>Gestión de Categorías
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#nuevaCategoriaModal">
                        <i class="fas fa-plus me-1"></i>Nueva Categoría
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
                <div class="col-md-4">
                    <div class="card stats-card income">
                        <div class="card-body text-center">
                            <i class="fas fa-arrow-up fa-2x mb-2"></i>
                            <h3><?php echo count($ingresos); ?></h3>
                            <p class="mb-0">Categorías de Ingresos</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stats-card expense">
                        <div class="card-body text-center">
                            <i class="fas fa-arrow-down fa-2x mb-2"></i>
                            <h3><?php echo count($gastos); ?></h3>
                            <p class="mb-0">Categorías de Gastos</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stats-card">
                        <div class="card-body text-center">
                            <i class="fas fa-tags fa-2x mb-2"></i>
                            <h3><?php echo count($categorias); ?></h3>
                            <p class="mb-0">Total Categorías</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs para Ingresos y Gastos -->
            <ul class="nav nav-tabs" id="categoriaTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="ingresos-tab" data-bs-toggle="tab" data-bs-target="#ingresos" type="button" role="tab">
                        <i class="fas fa-arrow-up text-success me-2"></i>Ingresos (<?php echo count($ingresos); ?>)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="gastos-tab" data-bs-toggle="tab" data-bs-target="#gastos" type="button" role="tab">
                        <i class="fas fa-arrow-down text-danger me-2"></i>Gastos (<?php echo count($gastos); ?>)
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="categoriaTabContent">
                <!-- Tab Ingresos -->
                <div class="tab-pane fade show active" id="ingresos" role="tabpanel">
                    <div class="card">
                        <div class="card-body">
                            <?php echo renderTablaCategoria($ingresos, 'ingresos'); ?>
                        </div>
                    </div>
                </div>

                <!-- Tab Gastos -->
                <div class="tab-pane fade" id="gastos" role="tabpanel">
                    <div class="card">
                        <div class="card-body">
                            <?php echo renderTablaCategoria($gastos, 'gastos'); ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal Nueva/Editar Categoría -->
<div class="modal fade" id="nuevaCategoriaModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">
                    <i class="fas fa-plus me-2"></i>Nueva Categoría
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="categoriaForm" method="POST">
                <input type="hidden" name="accion" id="accion" value="crear">
                <input type="hidden" name="id" id="categoriaId">
                
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <label for="nombre" class="form-label">Nombre de la Categoría</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required 
                                   placeholder="Ej: Alimentación, Salario, etc.">
                        </div>
                        <div class="col-md-4">
                            <label for="color" class="form-label">Color</label>
                            <input type="color" class="form-control form-control-color" id="color" name="color" value="#007bff">
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label for="tipo" class="form-label">Tipo</label>
                            <select class="form-select" id="tipo" name="tipo" required>
                                <option value="">Seleccionar tipo</option>
                                <option value="ingreso">💰 Ingreso</option>
                                <option value="gasto">💸 Gasto</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="icono" class="form-label">Icono</label>
                            <select class="form-select" id="icono" name="icono" required>
                                <option value="">Seleccionar icono</option>
                                <optgroup label="Ingresos">
                                    <option value="fas fa-wallet">💼 Salario</option>
                                    <option value="fas fa-laptop">💻 Freelance</option>
                                    <option value="fas fa-chart-line">📈 Inversiones</option>
                                    <option value="fas fa-gift">🎁 Bonus</option>
                                    <option value="fas fa-plus-circle">➕ Otros</option>
                                </optgroup>
                                <optgroup label="Gastos">
                                    <option value="fas fa-utensils">🍽️ Alimentación</option>
                                    <option value="fas fa-car">🚗 Transporte</option>
                                    <option value="fas fa-home">🏠 Servicios</option>
                                    <option value="fas fa-gamepad">🎮 Entretenimiento</option>
                                    <option value="fas fa-heartbeat">❤️ Salud</option>
                                    <option value="fas fa-graduation-cap">🎓 Educación</option>
                                    <option value="fas fa-tshirt">👕 Ropa</option>
                                    <option value="fas fa-shopping-cart">🛒 Compras</option>
                                    <option value="fas fa-plane">✈️ Viajes</option>
                                    <option value="fas fa-minus-circle">➖ Otros</option>
                                </optgroup>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">Crear Categoría</button>
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

<?php
function renderTablaCategoria($categorias, $tipo) {
    if (empty($categorias)) {
        return '<div class="text-center py-4">
            <i class="fas fa-tags fa-3x text-muted mb-3"></i>
            <p class="text-muted">No hay categorías de ' . $tipo . ' registradas</p>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#nuevaCategoriaModal">
                Crear Primera Categoría
            </button>
        </div>';
    }
    
    $html = '<div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Categoría</th>
                    <th>Icono</th>
                    <th>Transacciones</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>';
    
    foreach ($categorias as $categoria) {
        $html .= '<tr class="' . (!$categoria['activa'] ? 'table-secondary' : '') . '">
            <td>
                <span class="badge" style="background-color: ' . $categoria['color'] . '; color: white;">
                    <i class="' . $categoria['icono'] . ' me-1"></i>
                </span>
                ' . htmlspecialchars($categoria['nombre']) . '
            </td>
            <td><i class="' . $categoria['icono'] . ' fa-lg" style="color: ' . $categoria['color'] . ';"></i></td>
            <td><span class="badge bg-info">' . $categoria['total_transacciones'] . '</span></td>
            <td>';
        
        if ($categoria['activa']) {
            $html .= '<span class="badge bg-success">Activa</span>';
        } else {
            $html .= '<span class="badge bg-secondary">Inactiva</span>';
        }
        
        $html .= '</td>
            <td>
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-sm btn-outline-primary" 
                            onclick="editarCategoria(' . htmlspecialchars(json_encode($categoria)) . ')">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-' . ($categoria['activa'] ? 'warning' : 'success') . '" 
                            onclick="toggleActivo(' . $categoria['id'] . ')">
                        <i class="fas fa-' . ($categoria['activa'] ? 'pause' : 'play') . '"></i>
                    </button>';
        
        if ($categoria['total_transacciones'] == 0) {
            $html .= '<button type="button" class="btn btn-sm btn-outline-danger" 
                            onclick="eliminarCategoria(' . $categoria['id'] . ')">
                        <i class="fas fa-trash"></i>
                    </button>';
        }
        
        $html .= '</div>
            </td>
        </tr>';
    }
    
    $html .= '</tbody></table></div>';
    return $html;
}
?>

<script>
function editarCategoria(categoria) {
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Editar Categoría';
    document.getElementById('accion').value = 'editar';
    document.getElementById('categoriaId').value = categoria.id;
    document.getElementById('nombre').value = categoria.nombre;
    document.getElementById('tipo').value = categoria.tipo;
    document.getElementById('color').value = categoria.color;
    document.getElementById('icono').value = categoria.icono;
    document.getElementById('submitBtn').textContent = 'Actualizar Categoría';
    
    new bootstrap.Modal(document.getElementById('nuevaCategoriaModal')).show();
}

function toggleActivo(id) {
    if (confirm('¿Estás seguro de que deseas cambiar el estado de esta categoría?')) {
        document.getElementById('toggleId').value = id;
        document.getElementById('toggleForm').submit();
    }
}

function eliminarCategoria(id) {
    if (confirm('¿Estás seguro de que deseas eliminar esta categoría? Esta acción no se puede deshacer.')) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}

// Resetear modal al cerrarse
document.getElementById('nuevaCategoriaModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus me-2"></i>Nueva Categoría';
    document.getElementById('accion').value = 'crear';
    document.getElementById('categoriaForm').reset();
    document.getElementById('submitBtn').textContent = 'Crear Categoría';
});
</script>

<?php include 'includes/footer.php'; ?>
