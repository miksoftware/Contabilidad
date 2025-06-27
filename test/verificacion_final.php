<?php
require_once 'config/database.php';

echo "<h1>✅ Verificación Final del Sistema</h1>";

$todo_bien = true;
$problemas = [];
$exitos = [];

try {
    // 1. Verificar conexión a BD
    $db->getConnection();
    $exitos[] = "✅ Conexión a base de datos exitosa";
} catch (Exception $e) {
    $problemas[] = "❌ Error de conexión: " . $e->getMessage();
    $todo_bien = false;
}

try {
    // 2. Verificar tabla usuarios y sus columnas
    $estructura_usuarios = $db->fetchAll("DESCRIBE usuarios");
    $campos_usuarios = array_column($estructura_usuarios, 'Field');
    
    $campos_requeridos = ['id', 'nombre', 'email', 'password', 'rol', 'activo'];
    $campos_faltantes = array_diff($campos_requeridos, $campos_usuarios);
    
    if (empty($campos_faltantes)) {
        $exitos[] = "✅ Tabla usuarios tiene todas las columnas necesarias";
    } else {
        $problemas[] = "❌ Faltan columnas en usuarios: " . implode(', ', $campos_faltantes);
        $todo_bien = false;
    }
    
    // 3. Verificar usuarios activos
    $usuarios_activos = $db->fetchAll("SELECT id, nombre, email, rol FROM usuarios WHERE activo = 1");
    if (count($usuarios_activos) > 0) {
        $exitos[] = "✅ Hay " . count($usuarios_activos) . " usuarios activos";
        
        // Mostrar usuarios disponibles
        echo "<h3>👥 Usuarios Disponibles para Login:</h3>";
        echo "<table border='1' cellpadding='8' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'><th>Email</th><th>Nombre</th><th>Rol</th><th>Contraseña Sugerida</th></tr>";
        
        foreach ($usuarios_activos as $usuario) {
            echo "<tr>";
            echo "<td><strong>" . htmlspecialchars($usuario['email']) . "</strong></td>";
            echo "<td>" . htmlspecialchars($usuario['nombre']) . "</td>";
            echo "<td><span style='background: " . ($usuario['rol'] === 'admin' ? '#ff6b6b' : '#4ecdc4') . "; color: white; padding: 2px 8px; border-radius: 3px;'>" . $usuario['rol'] . "</span></td>";
            
            // Sugerir contraseñas basadas en el email
            if ($usuario['email'] === 'admin@test.com') {
                echo "<td><code>123456</code></td>";
            } elseif ($usuario['email'] === 'admin@contabilidad.local') {
                echo "<td><code>admin123</code></td>";
            } elseif ($usuario['email'] === 'usuario@contabilidad.local') {
                echo "<td><code>usuario123</code></td>";
            } else {
                echo "<td><em>Verifica con administrador</em></td>";
            }
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        $problemas[] = "❌ No hay usuarios activos";
        $todo_bien = false;
    }
    
} catch (Exception $e) {
    $problemas[] = "❌ Error verificando usuarios: " . $e->getMessage();
    $todo_bien = false;
}

try {
    // 4. Verificar categorías
    $categorias = $db->fetchAll("SELECT COUNT(*) as total FROM categorias WHERE activa = 1");
    $total_categorias = $categorias[0]['total'];
    
    if ($total_categorias > 0) {
        $exitos[] = "✅ Hay $total_categorias categorías activas";
    } else {
        $problemas[] = "⚠️ No hay categorías activas (formulario de transacciones no funcionará)";
    }
} catch (Exception $e) {
    $problemas[] = "❌ Error verificando categorías: " . $e->getMessage();
}

try {
    // 5. Verificar cuentas
    $cuentas = $db->fetchAll("SELECT COUNT(*) as total FROM cuentas WHERE activa = 1");
    $total_cuentas = $cuentas[0]['total'];
    
    if ($total_cuentas > 0) {
        $exitos[] = "✅ Hay $total_cuentas cuentas activas";
    } else {
        $problemas[] = "⚠️ No hay cuentas activas (formulario de transacciones no funcionará)";
    }
} catch (Exception $e) {
    $problemas[] = "❌ Error verificando cuentas: " . $e->getMessage();
}

// 6. Test de login simulado
try {
    echo "<h3>🔐 Test de Login</h3>";
    
    // Intentar con usuario de prueba
    $usuario_test = $db->fetch("SELECT id, email, password FROM usuarios WHERE email = 'admin@test.com' AND activo = 1");
    
    if ($usuario_test) {
        // Verificar que el hash sea válido
        if (strlen($usuario_test['password']) >= 60 && substr($usuario_test['password'], 0, 4) === '$2y$') {
            $exitos[] = "✅ Usuario de prueba listo: admin@test.com";
            
            // Test de verificación de contraseña
            if (password_verify('123456', $usuario_test['password'])) {
                $exitos[] = "✅ Contraseña de prueba verificada correctamente";
            } else {
                $problemas[] = "❌ Contraseña de prueba no verifica correctamente";
            }
        } else {
            $problemas[] = "❌ Hash de contraseña inválido para usuario de prueba";
        }
    } else {
        $problemas[] = "❌ Usuario de prueba admin@test.com no encontrado o inactivo";
    }
    
} catch (Exception $e) {
    $problemas[] = "❌ Error en test de login: " . $e->getMessage();
}

// Mostrar resultados
if (!empty($exitos)) {
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3 style='color: #155724; margin-top: 0;'>✅ Verificaciones Exitosas</h3>";
    foreach ($exitos as $exito) {
        echo "<p style='margin: 8px 0; color: #155724;'>$exito</p>";
    }
    echo "</div>";
}

if (!empty($problemas)) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3 style='color: #721c24; margin-top: 0;'>⚠️ Problemas Detectados</h3>";
    foreach ($problemas as $problema) {
        echo "<p style='margin: 8px 0; color: #721c24;'>$problema</p>";
    }
    echo "</div>";
}

// Estado general
echo "<div style='background: " . ($todo_bien ? "#e7f3ff" : "#fff3cd") . "; border: 1px solid " . ($todo_bien ? "#bee5eb" : "#ffeaa7") . "; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h2 style='margin-top: 0;'>" . ($todo_bien ? "🎉 Sistema Completamente Funcional" : "⚠️ Sistema Requiere Atención") . "</h2>";

if ($todo_bien) {
    echo "<p><strong>¡Excelente!</strong> Todos los componentes están funcionando correctamente.</p>";
    echo "<p>Puedes proceder a usar el sistema sin problemas.</p>";
} else {
    echo "<p><strong>Hay algunos problemas que necesitan atención.</strong></p>";
    echo "<p>Usa los enlaces de abajo para corregir los problemas detectados.</p>";
}
echo "</div>";

// Enlaces de acción
echo "<h2>🔗 Acciones Disponibles</h2>";
echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;'>";

$acciones = [
    ['url' => 'login.php', 'texto' => '🔐 Iniciar Sesión', 'desc' => 'Probar el login', 'color' => '#007bff'],
    ['url' => 'corregir_tabla_usuarios.php', 'texto' => '🔧 Corregir Usuarios', 'desc' => 'Si hay problemas de usuarios', 'color' => '#28a745'],
    ['url' => 'crear_datos_ejemplo.php', 'texto' => '📊 Crear Datos', 'desc' => 'Si faltan categorías/cuentas', 'color' => '#17a2b8'],
    ['url' => 'dashboard.php', 'texto' => '🏠 Dashboard', 'desc' => 'Ir al sistema principal', 'color' => '#6f42c1']
];

foreach ($acciones as $accion) {
    echo "<div style='border: 2px solid {$accion['color']}; padding: 15px; border-radius: 8px; text-align: center; background: white;'>";
    echo "<a href='{$accion['url']}' style='text-decoration: none; color: {$accion['color']}; font-weight: bold; display: block;'>";
    echo "<div style='font-size: 1.2em; margin-bottom: 5px;'>{$accion['texto']}</div>";
    echo "<small style='color: #666;'>{$accion['desc']}</small>";
    echo "</a>";
    echo "</div>";
}

echo "</div>";

// Información de credenciales
if (!empty($usuarios_activos)) {
    echo "<div style='background: #f8f9fa; border: 1px solid #dee2e6; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>📝 Credenciales para Probar</h3>";
    echo "<p><strong>Recomendado:</strong></p>";
    echo "<ul>";
    echo "<li><code>admin@test.com</code> / <code>123456</code></li>";
    
    foreach ($usuarios_activos as $usuario) {
        if ($usuario['email'] !== 'admin@test.com') {
            $pass_sugerida = '';
            if ($usuario['email'] === 'admin@contabilidad.local') $pass_sugerida = 'admin123';
            elseif ($usuario['email'] === 'usuario@contabilidad.local') $pass_sugerida = 'usuario123';
            
            if ($pass_sugerida) {
                echo "<li><code>{$usuario['email']}</code> / <code>$pass_sugerida</code></li>";
            }
        }
    }
    echo "</ul>";
    echo "</div>";
}

echo "<hr>";
echo "<p style='text-align: center; color: #666; margin: 20px 0;'>";
echo "Verificación completada el " . date('d/m/Y H:i:s');
echo "</p>";
?>
