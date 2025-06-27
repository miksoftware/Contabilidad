<?php
require_once 'config/database.php';

echo "<h2>📊 Estructura Real de la Base de Datos</h2>";

try {
    // Obtener todas las tablas
    $tablas = $db->fetchAll("SHOW TABLES");
    
    echo "<h3>Tablas existentes:</h3>";
    echo "<ul>";
    foreach ($tablas as $tabla) {
        $nombreTabla = array_values($tabla)[0];
        echo "<li><strong>$nombreTabla</strong></li>";
    }
    echo "</ul>";
    
    // Verificar estructura de cada tabla importante
    $tablasImportantes = ['usuarios', 'categorias', 'cuentas', 'transacciones'];
    
    // Buscar variaciones de nombres
    $todasTablas = array_map(function($t) { return array_values($t)[0]; }, $tablas);
    
    echo "<h3>Análisis de tablas:</h3>";
    
    foreach ($todasTablas as $tabla) {
        echo "<h4>Tabla: $tabla</h4>";
        try {
            $estructura = $db->fetchAll("DESCRIBE `$tabla`");
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
            foreach ($estructura as $campo) {
                echo "<tr>";
                echo "<td>" . $campo['Field'] . "</td>";
                echo "<td>" . $campo['Type'] . "</td>";
                echo "<td>" . $campo['Null'] . "</td>";
                echo "<td>" . $campo['Key'] . "</td>";
                echo "<td>" . $campo['Default'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Contar registros
            $count = $db->fetch("SELECT COUNT(*) as total FROM `$tabla`");
            echo "<p><strong>Registros:</strong> " . $count['total'] . "</p>";
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
        }
        echo "<hr>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error general: " . $e->getMessage() . "</p>";
}

echo "<p><a href='diagnostico_completo.php'>← Volver al Diagnóstico</a></p>";
?>
