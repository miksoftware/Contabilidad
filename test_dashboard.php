<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Iniciando prueba del dashboard...<br>";

session_start();

// Simular usuario logueado para la prueba
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Test User';
$_SESSION['user_role'] = 'admin';

echo "Sesión iniciada correctamente<br>";

try {
    require_once 'config/database.php';
    echo "Conexión a base de datos exitosa<br>";
    
    // Probar consultas individuales
    
    // Test 1: Ingresos del mes
    echo "Probando consulta de ingresos...<br>";
    $mesActual = date('Y-m');
    $ingresosMes = $db->fetch(
        "SELECT COALESCE(SUM(cantidad), 0) as total FROM transacciones 
         WHERE tipo = 'ingreso' AND DATE_FORMAT(fecha, '%Y-%m') = ?",
        [$mesActual]
    );
    echo "Ingresos del mes: " . ($ingresosMes['total'] ?? 'Error') . "<br>";
    
    // Test 2: Gastos del mes
    echo "Probando consulta de gastos...<br>";
    $gastosMes = $db->fetch(
        "SELECT COALESCE(SUM(cantidad), 0) as total FROM transacciones 
         WHERE tipo = 'gasto' AND DATE_FORMAT(fecha, '%Y-%m') = ?",
        [$mesActual]
    );
    echo "Gastos del mes: " . ($gastosMes['total'] ?? 'Error') . "<br>";
    
    // Test 3: Saldo total
    echo "Probando consulta de saldo total...<br>";
    $saldoTotal = $db->fetch(
        "SELECT COALESCE(SUM(saldo_actual), 0) as total FROM cuentas WHERE activa = 1"
    );
    echo "Saldo total: " . ($saldoTotal['total'] ?? 'Error') . "<br>";
    
    // Test 4: Últimas transacciones
    echo "Probando consulta de últimas transacciones...<br>";
    $ultimasTransacciones = $db->fetchAll(
        "SELECT t.*, c.nombre as categoria, cu.nombre as cuenta, u.nombre as usuario
         FROM transacciones t
         JOIN categorias c ON t.categoria_id = c.id
         JOIN cuentas cu ON t.cuenta_id = cu.id
         JOIN usuarios u ON t.usuario_id = u.id
         ORDER BY t.created_at DESC
         LIMIT 10"
    );
    echo "Transacciones encontradas: " . count($ultimasTransacciones) . "<br>";
    
    // Test 5: Gastos por categoría
    echo "Probando consulta de gastos por categoría...<br>";
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
    echo "Categorías de gasto: " . count($gastosPorCategoria) . "<br>";
    
    // Test 6: Metas de ahorro
    echo "Probando consulta de metas...<br>";
    $metasAhorro = $db->fetchAll(
        "SELECT *, 
         ROUND((cantidad_actual / cantidad_objetivo) * 100, 2) as progreso
         FROM metas_ahorro 
         WHERE completada = 0
         ORDER BY fecha_objetivo ASC
         LIMIT 5"
    );
    echo "Metas de ahorro: " . count($metasAhorro) . "<br>";
    
    echo "<br><strong style='color: green;'>✅ Todas las consultas funcionan correctamente!</strong><br>";
    
} catch (Exception $e) {
    echo "<strong style='color: red;'>Error: " . $e->getMessage() . "</strong><br>";
    echo "Trace: " . $e->getTraceAsString();
}
?>
