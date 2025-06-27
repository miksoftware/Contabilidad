<?php
echo "<h2>🔍 Diagnóstico de Login</h2>";

// Test 1: Conexión a la base de datos
echo "<h3>1. Test de Conexión</h3>";
try {
    require_once 'config/database.php';
    echo "<p style='color: green;'>✅ Conexión a la base de datos exitosa</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error de conexión: " . $e->getMessage() . "</p>";
    exit();
}

// Test 2: Verificar tabla usuarios
echo "<h3>2. Test de Tabla Usuarios</h3>";
try {
    $usuarios = $db->fetchAll("SELECT id, nombre, email, rol, activo FROM usuarios");
    echo "<p style='color: green;'>✅ Tabla usuarios accesible</p>";
    echo "<p>Usuarios encontrados: " . count($usuarios) . "</p>";
    
    if (count($usuarios) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Nombre</th><th>Email</th><th>Rol</th><th>Activo</th></tr>";
        foreach ($usuarios as $user) {
            $color = $user['activo'] ? 'green' : 'red';
            echo "<tr style='color: $color;'>";
            echo "<td>" . $user['id'] . "</td>";
            echo "<td>" . htmlspecialchars($user['nombre']) . "</td>";
            echo "<td>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td>" . $user['rol'] . "</td>";
            echo "<td>" . ($user['activo'] ? 'Sí' : 'No') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠️ No hay usuarios en la base de datos</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error accediendo a usuarios: " . $e->getMessage() . "</p>";
}

// Test 3: Probar login con datos específicos
echo "<h3>3. Test de Login Simulado</h3>";

if (count($usuarios) > 0) {
    $usuario_test = $usuarios[0]; // Primer usuario
    echo "<p>Probando login con: " . $usuario_test['email'] . "</p>";
    
    try {
        $user = $db->fetch(
            "SELECT id, nombre, email, password, rol, activo FROM usuarios WHERE email = ? AND activo = 1",
            [$usuario_test['email']]
        );
        
        if ($user) {
            echo "<p style='color: green;'>✅ Usuario encontrado en consulta de login</p>";
            echo "<p>Hash de contraseña almacenado: " . substr($user['password'], 0, 20) . "...</p>";
            
            // Verificar si la contraseña es un hash válido
            if (strlen($user['password']) >= 60 && substr($user['password'], 0, 4) === '$2y$') {
                echo "<p style='color: green;'>✅ Hash de contraseña válido (bcrypt)</p>";
            } else {
                echo "<p style='color: red;'>❌ Hash de contraseña inválido</p>";
                echo "<p>Longitud: " . strlen($user['password']) . " (debería ser ≥60)</p>";
                echo "<p>Inicio: " . substr($user['password'], 0, 10) . " (debería empezar con \$2y\$)</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ Usuario no encontrado en consulta de login</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error en consulta de login: " . $e->getMessage() . "</p>";
    }
}

// Test 4: Probar hash de contraseña
echo "<h3>4. Test de Hash de Contraseña</h3>";
$password_test = "123456";
$hash_test = password_hash($password_test, PASSWORD_DEFAULT);
echo "<p>Contraseña de prueba: $password_test</p>";
echo "<p>Hash generado: $hash_test</p>";

$verificacion = password_verify($password_test, $hash_test);
echo "<p>Verificación: " . ($verificacion ? "✅ Correcto" : "❌ Falló") . "</p>";

// Test 5: Crear usuario de prueba si no existe
echo "<h3>5. Usuario de Prueba</h3>";

$email_test = "admin@test.com";
$user_existe = $db->fetch("SELECT id FROM usuarios WHERE email = ?", [$email_test]);

if (!$user_existe) {
    echo "<p>Creando usuario de prueba...</p>";
    try {
        $hash_admin = password_hash("123456", PASSWORD_DEFAULT);
        $db->query(
            "INSERT INTO usuarios (nombre, email, password, rol, activo) VALUES (?, ?, ?, ?, ?)",
            ["Admin Test", $email_test, $hash_admin, "admin", 1]
        );
        echo "<p style='color: green;'>✅ Usuario de prueba creado</p>";
        echo "<p><strong>Email:</strong> admin@test.com</p>";
        echo "<p><strong>Contraseña:</strong> 123456</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error creando usuario de prueba: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: blue;'>ℹ️ Usuario de prueba ya existe</p>";
    echo "<p><strong>Email:</strong> admin@test.com</p>";
    echo "<p><strong>Contraseña:</strong> 123456</p>";
}

// Test 6: Información de sesión
echo "<h3>6. Test de Sesión</h3>";
session_start();
echo "<p>Estado de sesión PHP: " . (session_status() === PHP_SESSION_ACTIVE ? "✅ Activa" : "❌ Inactiva") . "</p>";
echo "<p>ID de sesión: " . session_id() . "</p>";

if (isset($_SESSION['user_id'])) {
    echo "<p style='color: blue;'>ℹ️ Ya hay una sesión activa para usuario ID: " . $_SESSION['user_id'] . "</p>";
    echo "<p><a href='logout.php'>Cerrar sesión actual</a></p>";
} else {
    echo "<p>No hay sesión activa</p>";
}

echo "<hr>";
echo "<p><strong>Recomendaciones:</strong></p>";
echo "<ul>";
echo "<li>Usa las credenciales: admin@test.com / 123456</li>";
echo "<li>Si persiste el error, revisa los logs de PHP</li>";
echo "<li>Verifica que no haya espacios en las credenciales</li>";
echo "</ul>";

echo "<p><a href='login.php'>← Ir al Login</a> | <a href='dashboard.php'>Ir al Dashboard</a></p>";
?>
