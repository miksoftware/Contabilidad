<?php
session_start();
require_once 'config/database.php';

echo "<h1>🔍 Diagnóstico Completo del Sistema</h1>";

$checks = [];
$errors = [];
$warnings = [];
$success = [];

// 1. Verificar conexión a la base de datos
try {
    $db->getConnection();
    $success[] = "Conexión a la base de datos exitosa";
} catch (Exception $e) {
    $errors[] = "Error de conexión a la base de datos: " . $e->getMessage();
}

// 2. Verificar tablas existentes
$tables = ['usuarios', 'categorias', 'cuentas', 'transacciones', 'metas_ahorro', 'presupuestos_items', 'presupuestos_pagos', 'transferencias'];
foreach ($tables as $table) {
    try {
        $result = $db->query("SHOW TABLES LIKE '$table'");
        if ($result->rowCount() > 0) {
            $success[] = "Tabla '$table' existe";
        } else {
            $warnings[] = "Tabla '$table' no existe - puede usar nombre diferente";
        }
    } catch (Exception $e) {
        $errors[] = "Error verificando tabla '$table': " . $e->getMessage();
    }
}

// Verificar tablas con nombres alternativos
$tablas_existentes = [];
try {
    $todas_tablas = $db->fetchAll("SHOW TABLES");
    foreach ($todas_tablas as $tabla) {
        $nombre = array_values($tabla)[0];
        $tablas_existentes[] = $nombre;
    }
    $success[] = "Total de tablas en la base de datos: " . count($tablas_existentes);
    
    // Verificar presencia de tablas principales con nombres flexibles
    $encontradas = [];
    foreach ($tablas_existentes as $tabla) {
        if (strpos($tabla, 'meta') !== false) $encontradas[] = "Tabla de metas: $tabla";
        if (strpos($tabla, 'presupuesto') !== false) $encontradas[] = "Tabla de presupuestos: $tabla";
        if (strpos($tabla, 'transferencia') !== false) $encontradas[] = "Tabla de transferencias: $tabla";
    }
    
    foreach ($encontradas as $encontrada) {
        $success[] = $encontrada;
    }
    
} catch (Exception $e) {
    $errors[] = "Error obteniendo lista de tablas: " . $e->getMessage();
}

// 3. Verificar datos esenciales
try {
    $categorias = $db->fetchAll("SELECT * FROM categorias WHERE activa = 1");
    if (count($categorias) > 0) {
        $success[] = "Categorías activas disponibles: " . count($categorias);
        
        $categoriasIngreso = array_filter($categorias, function($c) { return $c['tipo'] === 'ingreso'; });
        $categoriasGasto = array_filter($categorias, function($c) { return $c['tipo'] === 'gasto'; });
        
        if (count($categoriasIngreso) > 0) {
            $success[] = "Categorías de ingreso: " . count($categoriasIngreso);
        } else {
            $warnings[] = "No hay categorías de ingreso";
        }
        
        if (count($categoriasGasto) > 0) {
            $success[] = "Categorías de gasto: " . count($categoriasGasto);
        } else {
            $warnings[] = "No hay categorías de gasto";
        }
    } else {
        $errors[] = "No hay categorías activas - el formulario de transacciones no funcionará";
    }
} catch (Exception $e) {
    $errors[] = "Error verificando categorías: " . $e->getMessage();
}

try {
    $cuentas = $db->fetchAll("SELECT * FROM cuentas WHERE activa = 1");
    if (count($cuentas) > 0) {
        $success[] = "Cuentas activas disponibles: " . count($cuentas);
    } else {
        $errors[] = "No hay cuentas activas - el formulario de transacciones no funcionará";
    }
} catch (Exception $e) {
    $errors[] = "Error verificando cuentas: " . $e->getMessage();
}

try {
    $usuarios = $db->fetchAll("SELECT * FROM usuarios");
    if (count($usuarios) > 0) {
        $success[] = "Usuarios registrados: " . count($usuarios);
    } else {
        $warnings[] = "No hay usuarios registrados";
    }
} catch (Exception $e) {
    $errors[] = "Error verificando usuarios: " . $e->getMessage();
}

// 4. Verificar archivos principales
$archivos = [
    'index.php', 'login.php', 'dashboard.php', 'logout.php',
    'cuentas.php', 'categorias.php', 'transacciones.php', 'usuarios.php',
    'metas.php', 'presupuestos.php', 'transferencias.php', 'reportes.php',
    'formulario_transaccion.php', 'procesar_transaccion.php',
    'config/database.php', 'includes/header.php', 'includes/footer.php'
];

foreach ($archivos as $archivo) {
    if (file_exists($archivo)) {
        $success[] = "Archivo '$archivo' existe";
    } else {
        $errors[] = "Archivo '$archivo' no existe";
    }
}

// 5. Verificar permisos de sesión
if (isset($_SESSION['user_id'])) {
    $success[] = "Sesión de usuario activa (ID: " . $_SESSION['user_id'] . ")";
} else {
    $warnings[] = "No hay sesión activa - algunas funciones pueden no funcionar";
}

// Mostrar resultados
function mostrarLista($items, $tipo, $icono, $color) {
    if (!empty($items)) {
        echo "<div class='alert alert-$color'>";
        echo "<h4>$icono $tipo</h4>";
        echo "<ul>";
        foreach ($items as $item) {
            echo "<li>$item</li>";
        }
        echo "</ul>";
        echo "</div>";
    }
}

mostrarLista($success, 'Exitoso', '✅', 'success');
mostrarLista($warnings, 'Advertencias', '⚠️', 'warning');
mostrarLista($errors, 'Errores', '❌', 'danger');

// Resumen final
echo "<hr>";
echo "<h2>📊 Resumen del Estado</h2>";

$total_checks = count($success) + count($warnings) + count($errors);
$success_rate = round((count($success) / $total_checks) * 100, 1);

echo "<div class='card'>";
echo "<div class='card-body'>";
echo "<h5>Estado General del Sistema</h5>";
echo "<p><strong>Verificaciones exitosas:</strong> " . count($success) . "</p>";
echo "<p><strong>Advertencias:</strong> " . count($warnings) . "</p>";
echo "<p><strong>Errores:</strong> " . count($errors) . "</p>";
echo "<p><strong>Tasa de éxito:</strong> $success_rate%</p>";

if (count($errors) === 0) {
    if (count($warnings) === 0) {
        echo "<div class='alert alert-success'><strong>🎉 Sistema completamente funcional</strong></div>";
    } else {
        echo "<div class='alert alert-warning'><strong>⚠️ Sistema funcional con advertencias menores</strong></div>";
    }
} else {
    echo "<div class='alert alert-danger'><strong>❌ Sistema con errores que requieren atención</strong></div>";
}

echo "</div>";
echo "</div>";

// Guía de solución
if (!empty($errors) || !empty($warnings)) {
    echo "<hr>";
    echo "<h2>🔧 Guía de Solución</h2>";
    
    if (in_array("No hay categorías activas - el formulario de transacciones no funcionará", $errors) ||
        in_array("No hay cuentas activas - el formulario de transacciones no funcionará", $errors)) {
        echo "<div class='card mb-3'>";
        echo "<div class='card-header bg-primary text-white'>";
        echo "<h5>Problema Principal: Falta de Datos Básicos</h5>";
        echo "</div>";
        echo "<div class='card-body'>";
        echo "<p>El formulario de transacciones no puede funcionar sin categorías y cuentas.</p>";
        echo "<p><strong>Solución:</strong></p>";
        echo "<ol>";
        echo "<li><a href='crear_datos_ejemplo.php' class='btn btn-success btn-sm'>Crear datos de ejemplo</a></li>";
        echo "<li>O ve a <a href='categorias.php'>Gestión de Categorías</a> y <a href='cuentas.php'>Gestión de Cuentas</a> para crear manualmente</li>";
        echo "</ol>";
        echo "</div>";
        echo "</div>";
    }
}

// Enlaces de navegación
echo "<hr>";
echo "<h2>🔗 Enlaces Útiles</h2>";
echo "<div class='row'>";
echo "<div class='col-md-4'>";
echo "<div class='card'>";
echo "<div class='card-header'>Pruebas</div>";
echo "<div class='card-body'>";
echo "<a href='test_categorias.php' class='btn btn-outline-primary btn-sm d-block mb-2'>Test Categorías</a>";
echo "<a href='test_formulario.php' class='btn btn-outline-primary btn-sm d-block mb-2'>Test Formulario</a>";
echo "<a href='crear_datos_ejemplo.php' class='btn btn-outline-success btn-sm d-block'>Crear Datos</a>";
echo "</div>";
echo "</div>";
echo "</div>";

echo "<div class='col-md-4'>";
echo "<div class='card'>";
echo "<div class='card-header'>Gestión</div>";
echo "<div class='card-body'>";
echo "<a href='categorias.php' class='btn btn-outline-secondary btn-sm d-block mb-2'>Categorías</a>";
echo "<a href='cuentas.php' class='btn btn-outline-secondary btn-sm d-block mb-2'>Cuentas</a>";
echo "<a href='usuarios.php' class='btn btn-outline-secondary btn-sm d-block'>Usuarios</a>";
echo "</div>";
echo "</div>";
echo "</div>";

echo "<div class='col-md-4'>";
echo "<div class='card'>";
echo "<div class='card-header'>Sistema</div>";
echo "<div class='card-body'>";
echo "<a href='dashboard.php' class='btn btn-outline-primary btn-sm d-block mb-2'>Dashboard</a>";
echo "<a href='migracion.php' class='btn btn-outline-warning btn-sm d-block mb-2'>Migración</a>";
echo "<a href='diagnostico.php' class='btn btn-outline-info btn-sm d-block'>Diagnóstico</a>";
echo "</div>";
echo "</div>";
echo "</div>";
echo "</div>";

echo "<style>";
echo "body { font-family: Arial, sans-serif; margin: 20px; }";
echo ".alert { padding: 15px; margin: 10px 0; border-radius: 5px; }";
echo ".alert-success { background-color: #d4edda; border-color: #c3e6cb; color: #155724; }";
echo ".alert-warning { background-color: #fff3cd; border-color: #ffeaa7; color: #856404; }";
echo ".alert-danger { background-color: #f8d7da; border-color: #f5c6cb; color: #721c24; }";
echo ".alert-info { background-color: #d1ecf1; border-color: #bee5eb; color: #0c5460; }";
echo ".alert-primary { background-color: #cce7ff; border-color: #b3d7ff; color: #004085; }";
echo ".card { border: 1px solid #ddd; margin: 10px 0; }";
echo ".card-header { background-color: #f8f9fa; padding: 10px; font-weight: bold; }";
echo ".card-body { padding: 15px; }";
echo ".btn { padding: 8px 12px; text-decoration: none; border-radius: 4px; display: inline-block; margin: 2px; }";
echo ".btn-primary { background-color: #007bff; color: white; }";
echo ".btn-success { background-color: #28a745; color: white; }";
echo ".btn-outline-primary { border: 1px solid #007bff; color: #007bff; background: white; }";
echo ".btn-outline-secondary { border: 1px solid #6c757d; color: #6c757d; background: white; }";
echo ".btn-outline-success { border: 1px solid #28a745; color: #28a745; background: white; }";
echo ".btn-outline-warning { border: 1px solid #ffc107; color: #ffc107; background: white; }";
echo ".btn-outline-info { border: 1px solid #17a2b8; color: #17a2b8; background: white; }";
echo ".btn-sm { font-size: 12px; padding: 5px 8px; }";
echo ".d-block { display: block; }";
echo ".mb-2 { margin-bottom: 8px; }";
echo ".row { display: flex; flex-wrap: wrap; margin: -5px; }";
echo ".col-md-4 { flex: 0 0 33.333333%; padding: 5px; }";
echo "</style>";
?>
