<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'config/database.php';

// Obtener los mismos filtros que en transacciones.php
$filtroTipo = $_GET['tipo'] ?? '';
$filtroCategoria = $_GET['categoria'] ?? '';
$filtroCuenta = $_GET['cuenta'] ?? '';
$filtroFechaInicio = $_GET['fecha_inicio'] ?? '';
$filtroFechaFin = $_GET['fecha_fin'] ?? '';

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

// Obtener todas las transacciones (sin límite para exportar)
$transacciones = $db->fetchAll(
    "SELECT t.fecha, t.descripcion, t.tipo, t.cantidad,
     c.nombre as categoria, cu.nombre as cuenta, u.nombre as usuario
     FROM transacciones t
     JOIN categorias c ON t.categoria_id = c.id
     JOIN cuentas cu ON t.cuenta_id = cu.id
     JOIN usuarios u ON t.usuario_id = u.id
     {$whereClause}
     ORDER BY t.fecha DESC, t.created_at DESC",
    $params
);

// Configurar headers para descarga CSV
$filename = 'transacciones_' . date('Y-m-d_H-i-s') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// Escribir BOM para UTF-8
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Escribir encabezados
$headers = ['Fecha', 'Descripción', 'Tipo', 'Categoría', 'Cuenta', 'Cantidad'];
if ($_SESSION['user_role'] === 'admin') {
    $headers[] = 'Usuario';
}
fputcsv($output, $headers);

// Escribir datos
foreach ($transacciones as $transaccion) {
    $row = [
        date('d/m/Y', strtotime($transaccion['fecha'])),
        $transaccion['descripcion'],
        ucfirst($transaccion['tipo']),
        $transaccion['categoria'],
        $transaccion['cuenta'],
        number_format($transaccion['cantidad'], 2)
    ];
    
    if ($_SESSION['user_role'] === 'admin') {
        $row[] = $transaccion['usuario'];
    }
    
    fputcsv($output, $row);
}

fclose($output);
exit();
?>
