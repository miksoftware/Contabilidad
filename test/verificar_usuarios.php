<?php
require_once 'config/database.php';

echo "<h2>🔍 Diagnóstico de Tabla Usuarios</h2>";

try {
    // Verificar estructura de la tabla usuarios
    echo "<h3>Estructura actual de la tabla usuarios:</h3>";
    $estructura = $db->fetchAll("DESCRIBE usuarios");
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($estructura as $campo) {
        echo "<tr>";
        echo "<td><strong>" . $campo['Field'] . "</strong></td>";
        echo "<td>" . $campo['Type'] . "</td>";
        echo "<td>" . $campo['Null'] . "</td>";
        echo "<td>" . $campo['Key'] . "</td>";
        echo "<td>" . ($campo['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . ($campo['Extra'] ?? '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Verificar si existe la columna 'rol'
    $campos = array_column($estructura, 'Field');
    if (in_array('rol', $campos)) {
        echo "<p style='color: green;'>✅ La columna 'rol' existe</p>";
    } else {
        echo "<p style='color: red;'>❌ La columna 'rol' NO existe</p>";
        echo "<p><strong>Campos disponibles:</strong> " . implode(', ', $campos) . "</p>";
    }
    
    // Mostrar usuarios existentes
    echo "<h3>Usuarios en la tabla:</h3>";
    $usuarios = $db->fetchAll("SELECT * FROM usuarios");
    
    if (count($usuarios) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr>";
        foreach ($campos as $campo) {
            echo "<th>$campo</th>";
        }
        echo "</tr>";
        
        foreach ($usuarios as $usuario) {
            echo "<tr>";
            foreach ($campos as $campo) {
                echo "<td>" . htmlspecialchars($usuario[$campo] ?? '') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No hay usuarios en la tabla</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='corregir_tabla_usuarios.php'>🔧 Corregir Tabla Usuarios</a></p>";
echo "<p><a href='diagnostico_login.php'>← Volver al Diagnóstico de Login</a></p>";
?>
