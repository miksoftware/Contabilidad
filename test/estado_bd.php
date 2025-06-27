<?php
require_once 'config/database.php';

echo "<h1>🗄️ Estado Actualizado de la Base de Datos</h1>";

// Obtener todas las tablas
$todas_tablas = $db->fetchAll("SHOW TABLES");
$nombres_tablas = array_map(function($t) { return array_values($t)[0]; }, $todas_tablas);

echo "<h2>✅ Tablas Existentes</h2>";
echo "<div style='display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin: 20px 0;'>";

$tablas_info = [];
foreach ($nombres_tablas as $tabla) {
    try {
        $count = $db->fetch("SELECT COUNT(*) as total FROM `$tabla`");
        $registros = $count['total'];
        $tablas_info[$tabla] = $registros;
        
        $color = $registros > 0 ? 'green' : 'orange';
        echo "<div style='border: 1px solid #ddd; padding: 10px; border-radius: 5px; background: #f9f9f9;'>";
        echo "<h4 style='margin: 0; color: $color;'>$tabla</h4>";
        echo "<p style='margin: 5px 0; color: #666;'>$registros registros</p>";
        echo "</div>";
    } catch (Exception $e) {
        echo "<div style='border: 1px solid red; padding: 10px; border-radius: 5px; background: #fff0f0;'>";
        echo "<h4 style='margin: 0; color: red;'>$tabla</h4>";
        echo "<p style='margin: 5px 0; color: red;'>Error: " . $e->getMessage() . "</p>";
        echo "</div>";
    }
}

echo "</div>";

// Mapeo correcto de nombres
echo "<h2>📋 Mapeo de Tablas del Sistema</h2>";
$mapeo = [
    'usuarios' => 'usuarios',
    'categorias' => 'categorias', 
    'cuentas' => 'cuentas',
    'transacciones' => 'transacciones',
    'metas' => 'metas_ahorro',
    'presupuestos' => 'presupuestos_items + presupuestos_pagos',
    'transferencias' => 'transacciones (doble registro)'
];

echo "<table border='1' cellpadding='10' style='width: 100%; border-collapse: collapse;'>";
echo "<tr style='background: #f0f0f0;'><th>Módulo del Sistema</th><th>Tabla(s) en BD</th><th>Estado</th></tr>";

foreach ($mapeo as $modulo => $tabla_real) {
    $estado = "❌ No configurado";
    $color = "red";
    
    if ($modulo === 'usuarios' && in_array('usuarios', $nombres_tablas)) {
        $estado = "✅ Activo (" . $tablas_info['usuarios'] . " usuarios)";
        $color = "green";
    } elseif ($modulo === 'categorias' && in_array('categorias', $nombres_tablas)) {
        $estado = "✅ Activo (" . $tablas_info['categorias'] . " categorías)";
        $color = "green";
    } elseif ($modulo === 'cuentas' && in_array('cuentas', $nombres_tablas)) {
        $estado = "✅ Activo (" . $tablas_info['cuentas'] . " cuentas)";
        $color = "green";
    } elseif ($modulo === 'transacciones' && in_array('transacciones', $nombres_tablas)) {
        $estado = "✅ Activo (" . $tablas_info['transacciones'] . " transacciones)";
        $color = "green";
    } elseif ($modulo === 'metas' && in_array('metas_ahorro', $nombres_tablas)) {
        $estado = "✅ Activo (" . $tablas_info['metas_ahorro'] . " metas)";
        $color = "green";
    } elseif ($modulo === 'presupuestos' && in_array('presupuestos_items', $nombres_tablas)) {
        $items = $tablas_info['presupuestos_items'] ?? 0;
        $pagos = $tablas_info['presupuestos_pagos'] ?? 0;
        $estado = "✅ Activo ($items items, $pagos pagos)";
        $color = "green";
    } elseif ($modulo === 'transferencias' && in_array('transacciones', $nombres_tablas)) {
        $estado = "✅ Activo (usa tabla transacciones)";
        $color = "green";
    }
    
    echo "<tr>";
    echo "<td><strong>$modulo</strong></td>";
    echo "<td><code>$tabla_real</code></td>";
    echo "<td style='color: $color;'>$estado</td>";
    echo "</tr>";
}

echo "</table>";

// Verificar integridad de datos
echo "<h2>🔍 Verificación de Integridad</h2>";

$problemas = [];
$todo_bien = [];

// Verificar usuarios activos
try {
    $usuarios_activos = $db->fetchAll("SELECT * FROM usuarios WHERE activo = 1");
    if (count($usuarios_activos) > 0) {
        $todo_bien[] = "Hay " . count($usuarios_activos) . " usuarios activos";
    } else {
        $problemas[] = "No hay usuarios activos";
    }
} catch (Exception $e) {
    $problemas[] = "Error verificando usuarios: " . $e->getMessage();
}

// Verificar categorías activas
try {
    $categorias_activas = $db->fetchAll("SELECT * FROM categorias WHERE activa = 1");
    if (count($categorias_activas) > 0) {
        $ingreso = array_filter($categorias_activas, function($c) { return $c['tipo'] === 'ingreso'; });
        $gasto = array_filter($categorias_activas, function($c) { return $c['tipo'] === 'gasto'; });
        
        if (count($ingreso) > 0 && count($gasto) > 0) {
            $todo_bien[] = "Categorías balanceadas: " . count($ingreso) . " ingresos, " . count($gasto) . " gastos";
        } else {
            $problemas[] = "Categorías desbalanceadas: " . count($ingreso) . " ingresos, " . count($gasto) . " gastos";
        }
    } else {
        $problemas[] = "No hay categorías activas";
    }
} catch (Exception $e) {
    $problemas[] = "Error verificando categorías: " . $e->getMessage();
}

// Verificar cuentas activas
try {
    $cuentas_activas = $db->fetchAll("SELECT * FROM cuentas WHERE activa = 1");
    if (count($cuentas_activas) > 0) {
        $todo_bien[] = "Hay " . count($cuentas_activas) . " cuentas activas";
    } else {
        $problemas[] = "No hay cuentas activas";
    }
} catch (Exception $e) {
    $problemas[] = "Error verificando cuentas: " . $e->getMessage();
}

// Mostrar resultados
if (!empty($todo_bien)) {
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h4 style='color: #155724; margin-top: 0;'>✅ Todo Bien</h4>";
    foreach ($todo_bien as $item) {
        echo "<p style='margin: 5px 0; color: #155724;'>• $item</p>";
    }
    echo "</div>";
}

if (!empty($problemas)) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h4 style='color: #721c24; margin-top: 0;'>⚠️ Problemas Detectados</h4>";
    foreach ($problemas as $problema) {
        echo "<p style='margin: 5px 0; color: #721c24;'>• $problema</p>";
    }
    echo "</div>";
}

// Enlaces de acción
echo "<h2>🔧 Acciones Disponibles</h2>";
echo "<div style='display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin: 20px 0;'>";

$enlaces = [
    ['url' => 'crear_datos_ejemplo.php', 'texto' => '🏗️ Crear Datos', 'desc' => 'Categorías y cuentas'],
    ['url' => 'diagnostico_login.php', 'texto' => '🔐 Login', 'desc' => 'Diagnosticar login'],
    ['url' => 'dashboard.php', 'texto' => '🏠 Dashboard', 'desc' => 'Ir al sistema'],
    ['url' => 'diagnostico_completo.php', 'texto' => '🔍 Diagnóstico', 'desc' => 'Análisis completo']
];

foreach ($enlaces as $enlace) {
    echo "<div style='border: 1px solid #007bff; padding: 10px; border-radius: 5px; text-align: center;'>";
    echo "<a href='" . $enlace['url'] . "' style='text-decoration: none; color: #007bff; font-weight: bold;'>";
    echo $enlace['texto'] . "<br>";
    echo "<small style='color: #666;'>" . $enlace['desc'] . "</small>";
    echo "</a>";
    echo "</div>";
}

echo "</div>";

echo "<h2>📄 Resumen</h2>";
$total_tablas = count($nombres_tablas);
$tablas_con_datos = count(array_filter($tablas_info, function($count) { return $count > 0; }));

echo "<p><strong>Base de datos:</strong> contabilidad_familiar</p>";
echo "<p><strong>Tablas totales:</strong> $total_tablas</p>";
echo "<p><strong>Tablas con datos:</strong> $tablas_con_datos</p>";
echo "<p><strong>Estado general:</strong> " . (empty($problemas) ? "✅ Funcional" : "⚠️ Requiere atención") . "</p>";
?>
