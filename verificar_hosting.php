<?php
// Verificador de problemas comunes en hosting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Diagnóstico de Hosting</h1>";
echo "<style>body{font-family:Arial;margin:20px;} .ok{color:green;} .error{color:red;} .warning{color:orange;}</style>";

echo "<h2>📋 Información del Servidor</h2>";
echo "<ul>";
echo "<li><strong>PHP Version:</strong> " . phpversion() . "</li>";
echo "<li><strong>Server Software:</strong> " . ($_SERVER['SERVER_SOFTWARE'] ?? 'No disponible') . "</li>";
echo "<li><strong>Document Root:</strong> " . ($_SERVER['DOCUMENT_ROOT'] ?? 'No disponible') . "</li>";
echo "<li><strong>Script Path:</strong> " . __FILE__ . "</li>";
echo "</ul>";

echo "<h2>🔧 Verificaciones Básicas</h2>";

// 1. Verificar si las sesiones funcionan
echo "<h3>1. Sesiones</h3>";
if (session_start()) {
    echo "<span class='ok'>✅ Las sesiones funcionan correctamente</span><br>";
    $_SESSION['test'] = 'hosting_test';
    if (isset($_SESSION['test'])) {
        echo "<span class='ok'>✅ Se pueden escribir datos en sesión</span><br>";
    } else {
        echo "<span class='error'>❌ No se pueden escribir datos en sesión</span><br>";
    }
} else {
    echo "<span class='error'>❌ Error al iniciar sesiones</span><br>";
}

// 2. Verificar archivos principales
echo "<h3>2. Archivos Principales</h3>";
$archivos = [
    'config/database.php',
    'includes/header.php', 
    'includes/footer.php',
    'dashboard.php',
    'login.php'
];

foreach ($archivos as $archivo) {
    if (file_exists($archivo)) {
        echo "<span class='ok'>✅ $archivo existe</span><br>";
        if (is_readable($archivo)) {
            echo "<span class='ok'>✅ $archivo es legible</span><br>";
        } else {
            echo "<span class='error'>❌ $archivo no es legible</span><br>";
        }
    } else {
        echo "<span class='error'>❌ $archivo NO existe</span><br>";
    }
}

// 3. Verificar permisos de carpetas
echo "<h3>3. Permisos de Carpetas</h3>";
$carpetas = ['config', 'includes', 'test'];
foreach ($carpetas as $carpeta) {
    if (is_dir($carpeta)) {
        echo "<span class='ok'>✅ Carpeta $carpeta existe</span><br>";
        if (is_readable($carpeta)) {
            echo "<span class='ok'>✅ Carpeta $carpeta es legible</span><br>";
        } else {
            echo "<span class='error'>❌ Carpeta $carpeta no es legible</span><br>";
        }
    } else {
        echo "<span class='error'>❌ Carpeta $carpeta NO existe</span><br>";
    }
}

// 4. Probar conexión a base de datos
echo "<h3>4. Base de Datos</h3>";
if (file_exists('config/database.php')) {
    try {
        require_once 'config/database.php';
        if (isset($db)) {
            $connection = $db->getConnection();
            echo "<span class='ok'>✅ Conexión a base de datos exitosa</span><br>";
            
            // Probar una consulta simple
            try {
                $result = $db->query("SELECT 1 as test");
                echo "<span class='ok'>✅ Consultas SQL funcionan</span><br>";
            } catch (Exception $e) {
                echo "<span class='error'>❌ Error en consultas SQL: " . $e->getMessage() . "</span><br>";
            }
            
            // Verificar tablas principales
            $tablas = ['usuarios', 'categorias', 'cuentas', 'transacciones'];
            foreach ($tablas as $tabla) {
                try {
                    $result = $db->query("SELECT COUNT(*) as total FROM $tabla");
                    $row = $result->fetch();
                    $count = $row['total'];
                    echo "<span class='ok'>✅ Tabla $tabla existe ($count registros)</span><br>";
                } catch (Exception $e) {
                    echo "<span class='error'>❌ Error en tabla $tabla: " . $e->getMessage() . "</span><br>";
                }
            }
            
        } else {
            echo "<span class='error'>❌ Variable \$db no está definida</span><br>";
        }
    } catch (Exception $e) {
        echo "<span class='error'>❌ Error conectando a base de datos: " . $e->getMessage() . "</span><br>";
    }
} else {
    echo "<span class='error'>❌ Archivo config/database.php no existe</span><br>";
}

// 5. Verificar extensiones PHP necesarias
echo "<h3>5. Extensiones PHP</h3>";
$extensiones = ['pdo', 'pdo_mysql', 'mysqli', 'json', 'session'];
foreach ($extensiones as $ext) {
    if (extension_loaded($ext)) {
        echo "<span class='ok'>✅ Extensión $ext cargada</span><br>";
    } else {
        echo "<span class='error'>❌ Extensión $ext NO cargada</span><br>";
    }
}

// 6. Verificar variables de entorno y configuración
echo "<h3>6. Configuración PHP</h3>";
echo "<ul>";
echo "<li><strong>max_execution_time:</strong> " . ini_get('max_execution_time') . " segundos</li>";
echo "<li><strong>memory_limit:</strong> " . ini_get('memory_limit') . "</li>";
echo "<li><strong>upload_max_filesize:</strong> " . ini_get('upload_max_filesize') . "</li>";
echo "<li><strong>post_max_size:</strong> " . ini_get('post_max_size') . "</li>";
echo "<li><strong>session.save_path:</strong> " . ini_get('session.save_path') . "</li>";
echo "</ul>";

// 7. Probar include/require
echo "<h3>7. Sistema de Includes</h3>";
if (file_exists('includes/header.php')) {
    try {
        ob_start();
        $titulo = 'Test de Hosting';
        include 'includes/header.php';
        $header_content = ob_get_clean();
        echo "<span class='ok'>✅ Header se incluye correctamente</span><br>";
    } catch (Exception $e) {
        echo "<span class='error'>❌ Error incluyendo header: " . $e->getMessage() . "</span><br>";
    }
}

// 8. Mostrar errores recientes si existen
echo "<h3>8. Log de Errores</h3>";
$error_log = ini_get('error_log');
if ($error_log && file_exists($error_log)) {
    echo "<span class='warning'>⚠️ Archivo de errores: $error_log</span><br>";
    $errors = file_get_contents($error_log);
    $recent_errors = array_slice(explode("\n", $errors), -10);
    echo "<pre style='background:#f5f5f5;padding:10px;'>" . implode("\n", $recent_errors) . "</pre>";
} else {
    echo "<span class='ok'>✅ No se encontraron logs de errores</span><br>";
}

echo "<hr>";
echo "<h2>🚀 Recomendaciones</h2>";
echo "<ol>";
echo "<li>Si hay errores de base de datos, verifica las credenciales en config/database.php</li>";
echo "<li>Si hay errores de permisos, contacta al soporte de hosting</li>";
echo "<li>Si faltan extensiones PHP, solicita su activación al hosting</li>";
echo "<li>Verifica que todas las carpetas y archivos se hayan subido correctamente</li>";
echo "<li>Asegúrate de que la estructura de carpetas sea la misma que en local</li>";
echo "</ol>";

echo "<hr>";
echo "<p><strong>Diagnóstico completado:</strong> " . date('Y-m-d H:i:s') . "</p>";
?>
