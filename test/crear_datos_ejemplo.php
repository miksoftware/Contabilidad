<?php
session_start();
require_once 'config/database.php';

echo "<h2>Verificar y Crear Datos de Ejemplo</h2>";

try {
    // Verificar categorías existentes
    $categorias = $db->fetchAll("SELECT * FROM categorias");
    echo "<p>Categorías existentes: " . count($categorias) . "</p>";
    
    if (count($categorias) == 0) {
        echo "<p style='color: orange;'>No hay categorías. Creando categorías de ejemplo...</p>";
        
        $categorias_ejemplo = [
            ['nombre' => 'Salario', 'tipo' => 'ingreso', 'activa' => 1],
            ['nombre' => 'Freelance', 'tipo' => 'ingreso', 'activa' => 1],
            ['nombre' => 'Inversiones', 'tipo' => 'ingreso', 'activa' => 1],
            ['nombre' => 'Alimentación', 'tipo' => 'gasto', 'activa' => 1],
            ['nombre' => 'Transporte', 'tipo' => 'gasto', 'activa' => 1],
            ['nombre' => 'Servicios', 'tipo' => 'gasto', 'activa' => 1],
            ['nombre' => 'Entretenimiento', 'tipo' => 'gasto', 'activa' => 1],
            ['nombre' => 'Salud', 'tipo' => 'gasto', 'activa' => 1],
        ];
        
        foreach ($categorias_ejemplo as $categoria) {
            $db->query(
                "INSERT INTO categorias (nombre, tipo, activa) VALUES (?, ?, ?)",
                [$categoria['nombre'], $categoria['tipo'], $categoria['activa']]
            );
        }
        
        echo "<p style='color: green;'>✓ Se crearon " . count($categorias_ejemplo) . " categorías de ejemplo</p>";
    } else {
        echo "<p style='color: green;'>✓ Ya existen categorías en la base de datos</p>";
    }
    
    // Verificar cuentas existentes
    $cuentas = $db->fetchAll("SELECT * FROM cuentas");
    echo "<p>Cuentas existentes: " . count($cuentas) . "</p>";
    
    if (count($cuentas) == 0) {
        echo "<p style='color: orange;'>No hay cuentas. Creando cuentas de ejemplo...</p>";
        
        $cuentas_ejemplo = [
            ['nombre' => 'Cuenta Corriente', 'tipo' => 'corriente', 'saldo_inicial' => 1000.00, 'saldo_actual' => 1000.00, 'activa' => 1],
            ['nombre' => 'Cuenta de Ahorros', 'tipo' => 'ahorro', 'saldo_inicial' => 5000.00, 'saldo_actual' => 5000.00, 'activa' => 1],
            ['nombre' => 'Efectivo', 'tipo' => 'efectivo', 'saldo_inicial' => 200.00, 'saldo_actual' => 200.00, 'activa' => 1],
        ];
        
        foreach ($cuentas_ejemplo as $cuenta) {
            $db->query(
                "INSERT INTO cuentas (nombre, tipo, saldo_inicial, saldo_actual, activa) VALUES (?, ?, ?, ?, ?)",
                [$cuenta['nombre'], $cuenta['tipo'], $cuenta['saldo_inicial'], $cuenta['saldo_actual'], $cuenta['activa']]
            );
        }
        
        echo "<p style='color: green;'>✓ Se crearon " . count($cuentas_ejemplo) . " cuentas de ejemplo</p>";
    } else {
        echo "<p style='color: green;'>✓ Ya existen cuentas en la base de datos</p>";
    }
    
    // Mostrar resumen final
    $categorias_activas = $db->fetchAll("SELECT * FROM categorias WHERE activa = 1");
    $cuentas_activas = $db->fetchAll("SELECT * FROM cuentas WHERE activa = 1");
    
    echo "<hr>";
    echo "<h3>Resumen Final</h3>";
    echo "<p>Categorías activas: " . count($categorias_activas) . "</p>";
    echo "<p>Cuentas activas: " . count($cuentas_activas) . "</p>";
    
    if (count($categorias_activas) > 0 && count($cuentas_activas) > 0) {
        echo "<p style='color: green; font-weight: bold;'>✓ El sistema está listo para registrar transacciones</p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>✗ Aún faltan datos para poder registrar transacciones</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='dashboard.php'>← Volver al Dashboard</a></p>";
echo "<p><a href='test_categorias.php'>→ Probar Categorías</a></p>";
?>
