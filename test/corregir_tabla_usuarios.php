<?php
require_once 'config/database.php';

echo "<h2>🔧 Corrección de Tabla Usuarios</h2>";

try {
    // Verificar estructura actual
    $estructura = $db->fetchAll("DESCRIBE usuarios");
    $campos_existentes = array_column($estructura, 'Field');
    
    echo "<h3>Campos actuales: " . implode(', ', $campos_existentes) . "</h3>";
    
    $correcciones = [];
    $errores = [];
    
    // Verificar y agregar columna 'rol' si no existe
    if (!in_array('rol', $campos_existentes)) {
        try {
            $db->query("ALTER TABLE usuarios ADD COLUMN rol ENUM('admin', 'usuario') DEFAULT 'usuario' AFTER email");
            $correcciones[] = "✅ Columna 'rol' agregada";
        } catch (Exception $e) {
            $errores[] = "❌ Error agregando columna 'rol': " . $e->getMessage();
        }
    } else {
        $correcciones[] = "✅ Columna 'rol' ya existe";
    }
    
    // Verificar y agregar columna 'activo' si no existe
    if (!in_array('activo', $campos_existentes)) {
        try {
            $db->query("ALTER TABLE usuarios ADD COLUMN activo TINYINT(1) DEFAULT 1 AFTER rol");
            $correcciones[] = "✅ Columna 'activo' agregada";
        } catch (Exception $e) {
            $errores[] = "❌ Error agregando columna 'activo': " . $e->getMessage();
        }
    } else {
        $correcciones[] = "✅ Columna 'activo' ya existe";
    }
    
    // Verificar y agregar columnas de timestamp si no existen
    if (!in_array('created_at', $campos_existentes)) {
        try {
            $db->query("ALTER TABLE usuarios ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
            $correcciones[] = "✅ Columna 'created_at' agregada";
        } catch (Exception $e) {
            $errores[] = "❌ Error agregando columna 'created_at': " . $e->getMessage();
        }
    } else {
        $correcciones[] = "✅ Columna 'created_at' ya existe";
    }
    
    if (!in_array('updated_at', $campos_existentes)) {
        try {
            $db->query("ALTER TABLE usuarios ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
            $correcciones[] = "✅ Columna 'updated_at' agregada";
        } catch (Exception $e) {
            $errores[] = "❌ Error agregando columna 'updated_at': " . $e->getMessage();
        }
    } else {
        $correcciones[] = "✅ Columna 'updated_at' ya existe";
    }
    
    // Mostrar resultados
    if (!empty($correcciones)) {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h4>Correcciones Aplicadas:</h4>";
        foreach ($correcciones as $correccion) {
            echo "<p>$correccion</p>";
        }
        echo "</div>";
    }
    
    if (!empty($errores)) {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h4>Errores Encontrados:</h4>";
        foreach ($errores as $error) {
            echo "<p>$error</p>";
        }
        echo "</div>";
    }
    
    // Verificar usuarios existentes y actualizar roles si es necesario
    echo "<h3>Verificando usuarios existentes:</h3>";
    $usuarios = $db->fetchAll("SELECT * FROM usuarios");
    
    if (count($usuarios) > 0) {
        foreach ($usuarios as $usuario) {
            // Si el usuario no tiene rol, asignar 'admin' al primer usuario, 'usuario' al resto
            if (empty($usuario['rol']) || $usuario['rol'] === null) {
                $nuevo_rol = ($usuario['id'] == 1) ? 'admin' : 'usuario';
                try {
                    $db->query("UPDATE usuarios SET rol = ? WHERE id = ?", [$nuevo_rol, $usuario['id']]);
                    echo "<p>✅ Usuario ID {$usuario['id']} ({$usuario['email']}) asignado rol: $nuevo_rol</p>";
                } catch (Exception $e) {
                    echo "<p>❌ Error actualizando rol para usuario ID {$usuario['id']}: " . $e->getMessage() . "</p>";
                }
            }
            
            // Si el usuario no tiene estado activo, activarlo
            if (!isset($usuario['activo']) || $usuario['activo'] === null) {
                try {
                    $db->query("UPDATE usuarios SET activo = 1 WHERE id = ?", [$usuario['id']]);
                    echo "<p>✅ Usuario ID {$usuario['id']} activado</p>";
                } catch (Exception $e) {
                    echo "<p>❌ Error activando usuario ID {$usuario['id']}: " . $e->getMessage() . "</p>";
                }
            }
        }
    } else {
        echo "<p>No hay usuarios en la tabla. Se creará un usuario admin...</p>";
        
        // Crear usuario admin por defecto
        try {
            $hash_admin = password_hash("admin123", PASSWORD_DEFAULT);
            $db->query(
                "INSERT INTO usuarios (nombre, email, password, rol, activo) VALUES (?, ?, ?, ?, ?)",
                ["Administrador", "admin@contabilidad.local", $hash_admin, "admin", 1]
            );
            echo "<p>✅ Usuario administrador creado:</p>";
            echo "<p><strong>Email:</strong> admin@contabilidad.local</p>";
            echo "<p><strong>Contraseña:</strong> admin123</p>";
        } catch (Exception $e) {
            echo "<p>❌ Error creando usuario admin: " . $e->getMessage() . "</p>";
        }
    }
    
    // Crear usuario de prueba adicional
    $email_test = "admin@test.com";
    $usuario_test = $db->fetch("SELECT id FROM usuarios WHERE email = ?", [$email_test]);
    
    if (!$usuario_test) {
        try {
            $hash_test = password_hash("123456", PASSWORD_DEFAULT);
            $db->query(
                "INSERT INTO usuarios (nombre, email, password, rol, activo) VALUES (?, ?, ?, ?, ?)",
                ["Admin Test", $email_test, $hash_test, "admin", 1]
            );
            echo "<p>✅ Usuario de prueba creado:</p>";
            echo "<p><strong>Email:</strong> admin@test.com</p>";
            echo "<p><strong>Contraseña:</strong> 123456</p>";
        } catch (Exception $e) {
            echo "<p>❌ Error creando usuario de prueba: " . $e->getMessage() . "</p>";
        }
    }
    
    // Mostrar estructura final
    echo "<h3>Estructura final de la tabla usuarios:</h3>";
    $estructura_final = $db->fetchAll("DESCRIBE usuarios");
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($estructura_final as $campo) {
        echo "<tr>";
        echo "<td><strong>" . $campo['Field'] . "</strong></td>";
        echo "<td>" . $campo['Type'] . "</td>";
        echo "<td>" . $campo['Null'] . "</td>";
        echo "<td>" . $campo['Key'] . "</td>";
        echo "<td>" . ($campo['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Mostrar usuarios finales
    echo "<h3>Usuarios en la tabla (actualizados):</h3>";
    $usuarios_finales = $db->fetchAll("SELECT id, nombre, email, rol, activo, created_at FROM usuarios");
    
    if (count($usuarios_finales) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Nombre</th><th>Email</th><th>Rol</th><th>Activo</th><th>Creado</th></tr>";
        foreach ($usuarios_finales as $usuario) {
            $color = $usuario['activo'] ? 'green' : 'red';
            echo "<tr style='color: $color;'>";
            echo "<td>" . $usuario['id'] . "</td>";
            echo "<td>" . htmlspecialchars($usuario['nombre']) . "</td>";
            echo "<td>" . htmlspecialchars($usuario['email']) . "</td>";
            echo "<td><strong>" . $usuario['rol'] . "</strong></td>";
            echo "<td>" . ($usuario['activo'] ? 'Sí' : 'No') . "</td>";
            echo "<td>" . ($usuario['created_at'] ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Error general:</strong> " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>🎉 ¡Corrección Completada!</h3>";
echo "<p>La tabla de usuarios ha sido corregida. Ahora puedes:</p>";
echo "<ul>";
echo "<li><a href='login.php'><strong>🔐 Ir al Login</strong></a> - Probar el login corregido</li>";
echo "<li><a href='verificar_usuarios.php'>📊 Verificar Usuarios</a> - Ver la estructura actualizada</li>";
echo "<li><a href='dashboard.php'>🏠 Ir al Dashboard</a> - Acceder al sistema</li>";
echo "</ul>";

echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<h4>📝 Credenciales Disponibles:</h4>";
echo "<p><strong>Opción 1:</strong> admin@test.com / 123456</p>";
echo "<p><strong>Opción 2:</strong> admin@contabilidad.local / admin123</p>";
echo "</div>";
?>
