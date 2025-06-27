<?php
session_start();
require_once 'config/database.php';

echo "<h3>Test de Categorías</h3>";

// Verificar conexión
try {
    $db->getConnection();
    echo "<p style='color: green;'>✓ Conexión a la base de datos exitosa</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error de conexión: " . $e->getMessage() . "</p>";
    exit();
}

// Verificar tabla categorias
try {
    $result = $db->query("SHOW TABLES LIKE 'categorias'");
    if ($result->rowCount() > 0) {
        echo "<p style='color: green;'>✓ Tabla 'categorias' existe</p>";
    } else {
        echo "<p style='color: red;'>✗ Tabla 'categorias' no existe</p>";
        exit();
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error verificando tabla: " . $e->getMessage() . "</p>";
    exit();
}

// Obtener todas las categorías
try {
    $categorias = $db->fetchAll("SELECT * FROM categorias ORDER BY tipo, nombre");
    echo "<p style='color: green;'>✓ Consulta de categorías exitosa</p>";
    echo "<p>Total de categorías encontradas: " . count($categorias) . "</p>";
    
    if (count($categorias) > 0) {
        echo "<h4>Lista de categorías:</h4>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Nombre</th><th>Tipo</th><th>Activa</th></tr>";
        foreach ($categorias as $categoria) {
            $color = $categoria['activa'] ? 'green' : 'red';
            echo "<tr style='color: $color;'>";
            echo "<td>" . $categoria['id'] . "</td>";
            echo "<td>" . htmlspecialchars($categoria['nombre']) . "</td>";
            echo "<td>" . $categoria['tipo'] . "</td>";
            echo "<td>" . ($categoria['activa'] ? 'Sí' : 'No') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠ No hay categorías en la base de datos</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error obteniendo categorías: " . $e->getMessage() . "</p>";
}

// Obtener solo categorías activas
try {
    $categoriasActivas = $db->fetchAll("SELECT * FROM categorias WHERE activa = 1 ORDER BY tipo, nombre");
    echo "<p style='color: green;'>✓ Consulta de categorías activas exitosa</p>";
    echo "<p>Total de categorías activas: " . count($categoriasActivas) . "</p>";
    
    if (count($categoriasActivas) > 0) {
        echo "<h4>Categorías activas (las que deberían aparecer en el formulario):</h4>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Nombre</th><th>Tipo</th></tr>";
        foreach ($categoriasActivas as $categoria) {
            echo "<tr>";
            echo "<td>" . $categoria['id'] . "</td>";
            echo "<td>" . htmlspecialchars($categoria['nombre']) . "</td>";
            echo "<td>" . $categoria['tipo'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>✗ No hay categorías activas. Esto explica por qué no aparecen en el formulario.</p>";
        echo "<p><strong>Solución:</strong> Ve a <a href='categorias.php'>Gestión de Categorías</a> y crea algunas categorías.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error obteniendo categorías activas: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='dashboard.php'>← Volver al Dashboard</a></p>";
echo "<p><a href='categorias.php'>→ Ir a Gestión de Categorías</a></p>";
?>
