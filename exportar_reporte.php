<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'config/database.php';

// Obtener filtros
$fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fechaFin = $_GET['fecha_fin'] ?? date('Y-m-t');
$categoria = $_GET['categoria'] ?? '';
$cuenta = $_GET['cuenta'] ?? '';
$usuario = $_GET['usuario'] ?? '';
$formato = $_GET['format'] ?? 'excel';
$isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

// Construir query con filtros
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

// Obtener datos
$transacciones = $db->fetchAll(
    "SELECT t.fecha, t.descripcion, t.tipo, t.cantidad, 
            c.nombre as categoria, cu.nombre as cuenta, u.nombre as usuario
     FROM transacciones t
     JOIN categorias c ON t.categoria_id = c.id
     JOIN cuentas cu ON t.cuenta_id = cu.id
     JOIN usuarios u ON t.usuario_id = u.id
     WHERE $whereClause
     ORDER BY t.fecha DESC",
    $params
);

// Obtener resumen
$resumen = $db->fetch(
    "SELECT 
        SUM(CASE WHEN tipo = 'ingreso' THEN cantidad ELSE 0 END) as total_ingresos,
        SUM(CASE WHEN tipo = 'gasto' THEN cantidad ELSE 0 END) as total_gastos,
        COUNT(*) as total_transacciones
     FROM transacciones t
     WHERE $whereClause",
    $params
);

if ($formato === 'excel') {
    // Exportar a Excel (CSV)
    $filename = 'reporte_transacciones_' . date('Y-m-d_H-i-s') . '.csv';
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $output = fopen('php://output', 'w');
    
    // Escribir BOM para UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Encabezados
    fputcsv($output, [
        'Fecha',
        'Descripción',
        'Tipo',
        'Cantidad',
        'Categoría',
        'Cuenta',
        'Usuario'
    ]);
    
    // Datos
    foreach ($transacciones as $transaccion) {
        fputcsv($output, [
            date('d/m/Y', strtotime($transaccion['fecha'])),
            $transaccion['descripcion'],
            ucfirst($transaccion['tipo']),
            number_format($transaccion['cantidad'], 2),
            $transaccion['categoria'],
            $transaccion['cuenta'],
            $transaccion['usuario']
        ]);
    }
    
    // Línea en blanco
    fputcsv($output, []);
    
    // Resumen
    fputcsv($output, ['RESUMEN']);
    fputcsv($output, ['Total Ingresos', '$' . number_format($resumen['total_ingresos'], 2)]);
    fputcsv($output, ['Total Gastos', '$' . number_format($resumen['total_gastos'], 2)]);
    fputcsv($output, ['Balance', '$' . number_format($resumen['total_ingresos'] - $resumen['total_gastos'], 2)]);
    fputcsv($output, ['Total Transacciones', $resumen['total_transacciones']]);
    
    fclose($output);
    exit();
    
} elseif ($formato === 'pdf') {
    // Para PDF necesitaríamos una librería como TCPDF o DOMPDF
    // Por ahora, redirigir a HTML para imprimir
    $formato = 'html';
}

if ($formato === 'html') {
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Transacciones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
            .table { font-size: 12px; }
        }
        .header-info {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="text-center mb-4">
            <h1>Reporte de Transacciones</h1>
            <p class="text-muted">Contabilidad Familiar</p>
        </div>
        
        <div class="header-info">
            <div class="row">
                <div class="col-md-6">
                    <strong>Período:</strong> <?php echo date('d/m/Y', strtotime($fechaInicio)); ?> - <?php echo date('d/m/Y', strtotime($fechaFin)); ?><br>
                    <strong>Generado:</strong> <?php echo date('d/m/Y H:i:s'); ?><br>
                    <strong>Usuario:</strong> <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                </div>
                <div class="col-md-6">
                    <strong>Total Ingresos:</strong> $<?php echo number_format($resumen['total_ingresos'], 2); ?><br>
                    <strong>Total Gastos:</strong> $<?php echo number_format($resumen['total_gastos'], 2); ?><br>
                    <strong>Balance:</strong> <span class="<?php echo ($resumen['total_ingresos'] - $resumen['total_gastos']) >= 0 ? 'text-success' : 'text-danger'; ?>">
                        $<?php echo number_format($resumen['total_ingresos'] - $resumen['total_gastos'], 2); ?>
                    </span>
                </div>
            </div>
        </div>
        
        <div class="mb-3 no-print">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print"></i> Imprimir
            </button>
            <button onclick="window.close()" class="btn btn-secondary">
                <i class="fas fa-times"></i> Cerrar
            </button>
        </div>
        
        <div class="table-responsive">
            <table class="table table-striped table-sm">
                <thead class="table-dark">
                    <tr>
                        <th>Fecha</th>
                        <th>Descripción</th>
                        <th>Tipo</th>
                        <th>Categoría</th>
                        <th>Cuenta</th>
                        <th>Usuario</th>
                        <th class="text-end">Cantidad</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transacciones as $transaccion): ?>
                    <tr>
                        <td><?php echo date('d/m/Y', strtotime($transaccion['fecha'])); ?></td>
                        <td><?php echo htmlspecialchars($transaccion['descripcion']); ?></td>
                        <td>
                            <span class="badge <?php echo $transaccion['tipo'] === 'ingreso' ? 'bg-success' : 'bg-danger'; ?>">
                                <?php echo ucfirst($transaccion['tipo']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($transaccion['categoria']); ?></td>
                        <td><?php echo htmlspecialchars($transaccion['cuenta']); ?></td>
                        <td><?php echo htmlspecialchars($transaccion['usuario']); ?></td>
                        <td class="text-end">
                            <span class="<?php echo $transaccion['tipo'] === 'ingreso' ? 'text-success' : 'text-danger'; ?>">
                                <?php echo $transaccion['tipo'] === 'ingreso' ? '+' : '-'; ?>$<?php echo number_format($transaccion['cantidad'], 2); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="table-dark">
                    <tr>
                        <th colspan="6">TOTALES</th>
                        <th class="text-end">
                            Ingresos: +$<?php echo number_format($resumen['total_ingresos'], 2); ?><br>
                            Gastos: -$<?php echo number_format($resumen['total_gastos'], 2); ?><br>
                            <hr class="my-1">
                            Balance: $<?php echo number_format($resumen['total_ingresos'] - $resumen['total_gastos'], 2); ?>
                        </th>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <div class="text-center mt-4 text-muted">
            <small>Reporte generado automáticamente por el Sistema de Contabilidad Familiar</small>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</body>
</html>
<?php
}
?>
